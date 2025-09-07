<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Client;

class OAuth2ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $clients = $user->oauthApps()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'secret' => $client->secret,
                    'redirect_uris' => $client->redirect_uris,
                    'revoked' => $client->revoked,
                    'created_at' => $client->created_at,
                    'updated_at' => $client->updated_at,
                ];
            })
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'redirect_uris' => 'required|array',
            'redirect_uris.*' => 'required|url'
        ]);

        $user = $request->user();
        
        $client = $user->oauthApps()->create([
            'name' => $request->name,
            'secret' => \Illuminate\Support\Str::random(40),
            'redirect_uris' => $request->redirect_uris,
            'grant_types' => ['authorization_code'],
            'revoked' => false,
        ]);

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect_uris' => $client->redirect_uris,
                'revoked' => $client->revoked,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = $user->oauthApps()->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect_uris' => $client->redirect_uris,
                'revoked' => $client->revoked,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'redirect_uris' => 'sometimes|required|array',
            'redirect_uris.*' => 'required|url'
        ]);

        $user = $request->user();
        $client = $user->oauthApps()->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        if ($request->has('name')) {
            $client->name = $request->name;
        }

        if ($request->has('redirect_uris')) {
            $client->redirect_uris = $request->redirect_uris;
        }

        $client->save();

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect_uris' => $client->redirect_uris,
                'revoked' => $client->revoked,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = $user->oauthApps()->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $client->delete();

        return response()->json(['message' => 'Application deleted successfully']);
    }

    public function revoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = $user->oauthApps()->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $client->revoked = true;
        $client->save();

        return response()->json(['message' => 'Application revoked successfully']);
    }

    public function regenerateSecret(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = $user->oauthApps()->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $client->secret = \Illuminate\Support\Str::random(40);
        $client->save();

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect_uris' => $client->redirect_uris,
                'revoked' => $client->revoked,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]
        ]);
    }
}