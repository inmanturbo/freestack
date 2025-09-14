<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokens = $user->tokens()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => $token->scopes,
                    'revoked' => $token->revoked,
                    'created_at' => $token->created_at,
                    'updated_at' => $token->updated_at,
                    'expires_at' => $token->expires_at,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'string',
        ]);

        $user = $request->user();
        $scopes = $request->input('scopes', []);

        $token = $user->createToken($request->name, $scopes);

        return response()->json([
            'data' => [
                'id' => $token->token->id,
                'name' => $token->token->name,
                'scopes' => $token->token->scopes,
                'access_token' => $token->accessToken,
                'created_at' => $token->token->created_at,
                'expires_at' => $token->token->expires_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->where('id', $id)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'scopes' => $token->scopes,
                'revoked' => $token->revoked,
                'created_at' => $token->created_at,
                'updated_at' => $token->updated_at,
                'expires_at' => $token->expires_at,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'scopes' => 'sometimes|array',
            'scopes.*' => 'string',
        ]);

        $user = $request->user();
        $token = $user->tokens()->where('id', $id)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        if ($request->has('name')) {
            $token->name = $request->name;
        }

        if ($request->has('scopes')) {
            $token->scopes = $request->scopes;
        }

        $token->save();

        return response()->json([
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'scopes' => $token->scopes,
                'revoked' => $token->revoked,
                'created_at' => $token->created_at,
                'updated_at' => $token->updated_at,
                'expires_at' => $token->expires_at,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->where('id', $id)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token deleted successfully']);
    }

    public function revoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->where('id', $id)->first();

        if (! $token) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        $token->revoke();

        return response()->json(['message' => 'Token revoked successfully']);
    }
}
