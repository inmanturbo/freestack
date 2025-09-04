<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;

class AdminerClient
{
    public static function config()
    {
        $default = config('database.default');

        return (object) config('database.connections.'.$default);
    }

    public static function uri()
    {
        $config = static::config();

        $token = Str::random(40);

        Cache::put('adminer:'.$token, $config->password, config('session.lifetime'));

        return Uri::of('http://localhost:8080')
            ->withQuery([
                $config->driver => '',
                'server' => $config->host,
                'username' => $config->username,
                'db' => $config->database,
                'key' => $token,
        ]);
    }

    public static function redirect()
    {
        $config = static::config();

        return redirect(static::uri()->value(), headers: [
            'X_ADMINER_SERVER' => '172.18.0.1',
            'X_ADMINER_USER' => $config->username,
            'X_ADMINER_PASSWORD' => $config->password,
        ]);
    }
}
