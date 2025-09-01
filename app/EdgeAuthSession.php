<?php

namespace App;

use Illuminate\Contracts\Session\Session as SessionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades;
use Illuminate\Support\Uri;
use Laravel\Passport\Contracts\OAuthenticatable as PassportUser;
use Laravel\Passport\PersonalAccessTokenResult;
use RuntimeException;

class EdgeAuthSession
{
    public function __construct(private SessionContract $session)
    {
        if (Facades\Config::get('session.driver') !== 'database') {
            throw new RuntimeException('EdgeAuthSession requires SESSION_DRIVER=database.');
        }
    }

    public static function redirectRequest(Request $request)
    {
        return static::makeForCurrentSession()->redirect($request);
    }

    public static function redirectRequestWithMetaData(Request $request, array $overrides)
    {
        return static::makeForCurrentSession()->redirectWithMetaData($request, $overrides);
    }

    /** Bind to a provided session */
    public static function make(SessionContract $session): self
    {
        return new self($session);
    }

    /** Bind to the current request’s session */
    public static function makeForCurrentSession(): self
    {
        return new self(Facades\Session::driver());
    }

    /** Bind to a different session by its ID (operate with the same no-arg API) */
    public static function makeForSessionId(string $sessionId): self
    {
        $handler = Facades\Session::getHandler();
        $store = new Store('edge-target', $handler, $sessionId);
        $store->start(); // hydrate from storage

        return new self($store);
    }

    /** Issue a PAT aligned to session lifetime; stash token id in THIS session */
    public function issueToken(): PersonalAccessTokenResult
    {
        $user = $this->resolveUser();
        $result = $user->createToken('edge-ticket', ['edge']);

        $ttl = (int) Facades\Config::get('session.lifetime', 120);
        $token = $result->token;
        $token->expires_at = now()->addMinutes($ttl);
        $token->save();

        $this->session->put('current_passport_token_id', $token->id);

        return $result;
    }

    /** Issue token, build ?ticket= redirect, store default metadata, and return RedirectResponse */
    public function redirect(Request $request): RedirectResponse
    {
        $result = $this->issueToken();
        $baseUrl = $this->buildReturnUrlFromRequest($request);

        // Store defaults (no overrides)
        $this->session->put('edge_session', $this->defaultMeta($baseUrl, $result));

        $to = $this->appendQuery($baseUrl, ['ticket' => $result->accessToken]);

        return Facades\Redirect::to($to);
    }

    /** Same as redirect(), but also stores recognizable session metadata (overridable) */
    public function redirectWithMetaData(Request $request, array $overrides = []): RedirectResponse
    {
        $result = $this->issueToken();
        $baseUrl = $this->buildReturnUrlFromRequest($request);

        $meta = array_replace($this->defaultMeta($baseUrl, $result), $overrides);
        $this->session->put('edge_session', $meta);

        $to = $this->appendQuery($baseUrl, ['ticket' => $result->accessToken]);

        return Facades\Redirect::to($to);
    }

    /** Revoke THIS session’s PAT (if present), then destroy THIS session */
    public function destroyThisSessionAndToken(): bool
    {
        if ($tokenId = $this->session->get('current_passport_token_id')) {
            $this->resolveUser()->tokens()->where('id', $tokenId)->delete();
        }

        Facades\Session::getHandler()->destroy($this->session->getId());

        return true;
    }

    /** Revoke PATs for all OTHER sessions of THIS session’s user and destroy them */
    public function destroyAllOtherSessionsAndTokens(): int
    {
        $userId = $this->currentUserId();
        $currentId = $this->session->getId();

        $otherIds = Facades\DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentId)
            ->pluck('id')
            ->all();

        $handler = Facades\Session::getHandler();
        $tokenIds = [];

        foreach ($otherIds as $sid) {
            $store = new Store('edge-inspect', $handler, $sid);
            $store->start();
            if ($tid = $store->get('current_passport_token_id')) {
                $tokenIds[] = $tid;
            }
        }

        if ($tokenIds) {
            $this->resolveUser()->tokens()->whereIn('id', $tokenIds)->delete();
        }

        foreach ($otherIds as $sid) {
            $handler->destroy($sid);
        }

        return count($otherIds);
    }

    public function buildReturnUrlFromRequest(Request $request): string
    {
        $scheme = $request->query('scheme', 'https');
        $scheme = in_array($scheme, ['http', 'https'], true) ? $scheme : 'https';

        $host = strtolower((string) $request->query('host', ''));
        if ($host === '' || str_contains($host, '/') || str_contains($host, '?')) {
            throw new RuntimeException('Invalid host.');
        }

        $allowed = array_map('strtolower', Facades\Config::get('edge.allowed_hosts', []));
        if (! empty($allowed) && ! in_array($host, $allowed, true)) {
            throw new RuntimeException('Host not allowed.');
        }

        $ret = (string) $request->query('return', '/');
        $retUi = Uri::of($ret);

        $path = '/'.ltrim($retUi->path() ?? '/', '/');
        $qs = $retUi->query()->all();

        $uri = Uri::of('')
            ->withHost($host)
            ->withScheme($scheme)
            ->withPath($path);

        if (! empty($qs)) {
            $uri = $uri->withQuery($qs);
        }

        return (string) $uri;
    }

    /**
     * Get a value from the 'edge_session' payload without hydrating a Store.
     *
     * - If $key is null, returns the whole 'edge_session' array (or []).
     * - If $sessionId is null, uses THIS instance's session id; otherwise reads that session's row.
     * - $default is returned when the key (or payload) is missing/corrupt.
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        $sessionId = $this->session->getId();

        $payload = Facades\DB::table('sessions')
            ->where('id', $sessionId)
            ->value('payload');

        if (!is_string($payload) || $payload === '') {
            return $key === null ? [] : $default;
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return $key === null ? [] : $default;
        }

        try {
            $session = unserialize($decoded, ['allowed_classes' => false]);
        } catch (\Throwable $e) {
            return $key === null ? [] : $default;
        }

        if (!is_array($session)) {
            return $key === null ? [] : $default;
        }

        $edgeSession = $session['edge_session'] ?? ($session['data']['edge_session'] ?? null);
        if (!is_array($edgeSession)) {
            return $key === null ? [] : $default;
        }

        if ($key === null) {
            return $edgeSession;
        }

        return data_get($edgeSession, $key, $default);
    }

    /** Append/merge query params to a URL. */
    protected function appendQuery(string $url, array $params): string
    {
        return (string) Uri::of($url)->withQuery($params, merge: true);
    }

    /** Build the default, human-friendly session metadata (shared by both redirect methods) */
    protected function defaultMeta(string $baseUrl, PersonalAccessTokenResult $result): array
    {
        $u = Uri::of($baseUrl);

        return [
            'token_id' => $result->token->id,
            'issued_at' => now()->toIso8601String(),
            'expires_at' => optional($result->token->expires_at)->toIso8601String(),
            'redirect_url' => (string) $baseUrl, // without ?ticket
            'app_host' => $u->host(),
            'app_path' => $u->path() ?? '/',
            'scheme' => $u->scheme() ?? 'https',
            'ip' => request()?->ip(),
            'user_agent' => (string) (request()?->userAgent() ?? ''),
            'label' => trim(($u->host() ?? '').($u->path() ? ' /'.ltrim($u->path(), '/') : '')),
            'scopes' => $result->token->scopes ?? [],
            'ticket_name' => 'edge-ticket',
        ];
    }

    protected function webGuard(): string
    {
        return (string) Facades\Config::get('auth.defaults.guard', 'web');
    }

    /** Read user_id for THIS session directly from the sessions table */
    protected function currentUserId(): int|string
    {
        $id = Facades\DB::table('sessions')
            ->where('id', $this->session->getId())
            ->value('user_id');

        if ($id === null) {
            throw new RuntimeException('No user associated with this session.');
        }

        return $id;
    }

    /** Resolve the user model for THIS session (must implement Passport OAuthenticatable) */
    protected function resolveUser(): PassportUser
    {
        $provider = Facades\Auth::guard($this->webGuard())->getProvider();
        $user = $provider->retrieveById($this->currentUserId());

        if (! $user instanceof PassportUser) {
            throw new RuntimeException('Resolved user does not implement Passport OAuthenticatable.');
        }

        return $user;
    }
}
