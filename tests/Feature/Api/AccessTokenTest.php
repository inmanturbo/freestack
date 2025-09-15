<?php

use App\Models\User;

test('user can list their access tokens', function () {
    $user = User::factory()->create();

    // Create a token for the user
    $token = $user->createToken('Test Token', ['read']);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/access-tokens');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'scopes',
                    'revoked',
                    'created_at',
                    'updated_at',
                    'expires_at',
                ],
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Test Token',
            'scopes' => ['read'],
        ]);
});

test('user can create a new access token', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/access-tokens', [
            'name' => 'My API Token',
            'scopes' => ['read', 'write'],
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'scopes',
                'access_token',
                'created_at',
                'expires_at',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'My API Token',
            'scopes' => ['read', 'write'],
        ]);

    $this->assertDatabaseHas('oauth_access_tokens', [
        'name' => 'My API Token',
    ]);
});

test('user can create access token without scopes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/access-tokens', [
            'name' => 'Simple Token',
        ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'name' => 'Simple Token',
            'scopes' => ['edge'],  // Default scope when none provided
        ]);
});

test('access token creation requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/access-tokens', [
            'scopes' => ['read'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('user can view a specific access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('View Token', ['read']);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/access-tokens/'.$token->token->id);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'scopes',
                'revoked',
                'created_at',
                'updated_at',
                'expires_at',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'View Token',
            'scopes' => ['read'],
        ]);
});

test('user cannot view another users access token', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $token = $user1->createToken('Private Token');

    $response = $this->actingAs($user2, 'api')
        ->getJson('/api/access-tokens/'.$token->token->id);

    $response->assertNotFound();
});

test('user can update access token name and scopes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Old Name', ['read']);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/access-tokens/'.$token->token->id, [
            'name' => 'New Name',
            'scopes' => ['read', 'write'],
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'New Name',
            'scopes' => ['read', 'write'],
        ]);

    $this->assertDatabaseHas('oauth_access_tokens', [
        'id' => $token->token->id,
        'name' => 'New Name',
    ]);
});

test('user can update only token name', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Old Name', ['read']);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/access-tokens/'.$token->token->id, [
            'name' => 'Updated Name',
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Updated Name',
            'scopes' => ['read'],
        ]);
});

test('user can update only token scopes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Token Name', ['read']);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/access-tokens/'.$token->token->id, [
            'scopes' => ['read', 'write', 'admin'],
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Token Name',
            'scopes' => ['read', 'write', 'admin'],
        ]);
});

test('user cannot update another users access token', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $token = $user1->createToken('Private Token');

    $response = $this->actingAs($user2, 'api')
        ->putJson('/api/access-tokens/'.$token->token->id, [
            'name' => 'Hacked Name',
        ]);

    $response->assertNotFound();
});

test('user can delete access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Delete Me');

    $response = $this->actingAs($user, 'api')
        ->deleteJson('/api/access-tokens/'.$token->token->id);

    $response->assertOk()
        ->assertJson(['message' => 'Token deleted successfully']);

    $this->assertDatabaseMissing('oauth_access_tokens', [
        'id' => $token->token->id,
    ]);
});

test('user cannot delete another users access token', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $token = $user1->createToken('Protected Token');

    $response = $this->actingAs($user2, 'api')
        ->deleteJson('/api/access-tokens/'.$token->token->id);

    $response->assertNotFound();
});

test('user can revoke access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Revoke Me');

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/access-tokens/'.$token->token->id.'/revoke');

    $response->assertOk()
        ->assertJson(['message' => 'Token revoked successfully']);

    $this->assertDatabaseHas('oauth_access_tokens', [
        'id' => $token->token->id,
        'revoked' => true,
    ]);
});

test('user cannot revoke another users access token', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $token = $user1->createToken('Protected Token');

    $response = $this->actingAs($user2, 'api')
        ->postJson('/api/access-tokens/'.$token->token->id.'/revoke');

    $response->assertNotFound();
});

test('unauthenticated user cannot access token endpoints', function () {
    $endpoints = [
        ['method' => 'get', 'url' => '/api/access-tokens'],
        ['method' => 'post', 'url' => '/api/access-tokens', 'data' => ['name' => 'Test']],
        ['method' => 'get', 'url' => '/api/access-tokens/1'],
        ['method' => 'put', 'url' => '/api/access-tokens/1', 'data' => ['name' => 'Test']],
        ['method' => 'delete', 'url' => '/api/access-tokens/1'],
        ['method' => 'post', 'url' => '/api/access-tokens/1/revoke'],
    ];

    foreach ($endpoints as $endpoint) {
        $method = $endpoint['method'];
        $url = $endpoint['url'];
        $data = $endpoint['data'] ?? [];

        $response = $this->{$method.'Json'}($url, $data);
        $response->assertStatus(401);
    }
});

test('returns 404 for non-existent access token operations', function () {
    $user = User::factory()->create();
    $nonExistentId = '99999999-9999-9999-9999-999999999999';

    $endpoints = [
        ['method' => 'get', 'url' => '/api/access-tokens/'.$nonExistentId],
        ['method' => 'put', 'url' => '/api/access-tokens/'.$nonExistentId, 'data' => ['name' => 'Test']],
        ['method' => 'delete', 'url' => '/api/access-tokens/'.$nonExistentId],
        ['method' => 'post', 'url' => '/api/access-tokens/'.$nonExistentId.'/revoke'],
    ];

    foreach ($endpoints as $endpoint) {
        $method = $endpoint['method'];
        $url = $endpoint['url'];
        $data = $endpoint['data'] ?? [];

        $response = $this->actingAs($user, 'api')->{$method.'Json'}($url, $data);
        $response->assertNotFound();
    }
});
