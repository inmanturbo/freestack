<?php

use App\Models\User;
use Illuminate\Support\Str;

test('user can list their oauth applications', function () {
    $user = User::factory()->create();

    // Create an OAuth application for the user
    $client = $user->oauthApps()->create([
        'name' => 'Test App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://example.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/oauth-applications');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    // secret not returned in listing
                    'redirect_uris',
                    'revoked',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'revoked' => false,
        ]);
});

test('user can create a new oauth application', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'My OAuth App',
            'redirect_uris' => ['https://myapp.com/callback', 'https://myapp.com/auth'],
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'secret',
                'redirect_uris',
                'revoked',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'My OAuth App',
            'redirect_uris' => ['https://myapp.com/callback', 'https://myapp.com/auth'],
            'revoked' => false,
        ]);

    $responseData = $response->json('data');
    expect($responseData['secret'])->toHaveLength(40); // Plain text secret

    $this->assertDatabaseHas('oauth_clients', [
        'name' => 'My OAuth App',
        'owner_id' => $user->id,
    ]);
});

test('oauth application creation requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'redirect_uris' => ['https://example.com/callback'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('oauth application creation requires redirect uris', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'Test App',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['redirect_uris']);
});

test('oauth application creation requires valid redirect uris', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'Test App',
            'redirect_uris' => ['not-a-url', 'https://valid.com/callback'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['redirect_uris.0']);
});

test('user can view a specific oauth application', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'View App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://view.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/oauth-applications/'.$client->id);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                // secret not returned in show
                'redirect_uris',
                'revoked',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'View App',
            'redirect_uris' => ['https://view.com/callback'],
        ]);
});

test('user cannot view another users oauth application', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $client = $user1->oauthApps()->create([
        'name' => 'Private App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://private.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user2, 'api')
        ->getJson('/api/oauth-applications/'.$client->id);

    $response->assertNotFound();
});

test('user can update oauth application name and redirect uris', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'Old Name',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://old.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/oauth-applications/'.$client->id, [
            'name' => 'New Name',
            'redirect_uris' => ['https://new.com/callback', 'https://new.com/auth'],
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'New Name',
            'redirect_uris' => ['https://new.com/callback', 'https://new.com/auth'],
        ]);

    $this->assertDatabaseHas('oauth_clients', [
        'id' => $client->id,
        'name' => 'New Name',
    ]);
});

test('user can update only oauth application name', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'Old Name',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://keep.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/oauth-applications/'.$client->id, [
            'name' => 'Updated Name',
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Updated Name',
            'redirect_uris' => ['https://keep.com/callback'],
        ]);
});

test('user can update only oauth application redirect uris', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'Keep Name',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://old.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->putJson('/api/oauth-applications/'.$client->id, [
            'redirect_uris' => ['https://updated.com/callback'],
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Keep Name',
            'redirect_uris' => ['https://updated.com/callback'],
        ]);
});

test('user cannot update another users oauth application', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $client = $user1->oauthApps()->create([
        'name' => 'Private App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://private.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user2, 'api')
        ->putJson('/api/oauth-applications/'.$client->id, [
            'name' => 'Hacked Name',
        ]);

    $response->assertNotFound();
});

test('user can delete oauth application', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'Delete Me',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://delete.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->deleteJson('/api/oauth-applications/'.$client->id);

    $response->assertOk()
        ->assertJson(['message' => 'Application deleted successfully']);

    $this->assertDatabaseMissing('oauth_clients', [
        'id' => $client->id,
    ]);
});

test('user cannot delete another users oauth application', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $client = $user1->oauthApps()->create([
        'name' => 'Protected App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://protected.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user2, 'api')
        ->deleteJson('/api/oauth-applications/'.$client->id);

    $response->assertNotFound();
});

test('user can revoke oauth application', function () {
    $user = User::factory()->create();

    $client = $user->oauthApps()->create([
        'name' => 'Revoke Me',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://revoke.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications/'.$client->id.'/revoke');

    $response->assertOk()
        ->assertJson(['message' => 'Application revoked successfully']);

    $this->assertDatabaseHas('oauth_clients', [
        'id' => $client->id,
        'revoked' => true,
    ]);
});

test('user cannot revoke another users oauth application', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $client = $user1->oauthApps()->create([
        'name' => 'Protected App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://protected.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user2, 'api')
        ->postJson('/api/oauth-applications/'.$client->id.'/revoke');

    $response->assertNotFound();
});

test('user can regenerate oauth application secret', function () {
    $user = User::factory()->create();

    $originalSecret = Str::random(40);
    $client = $user->oauthApps()->create([
        'name' => 'Regenerate App',
        'secret' => $originalSecret,
        'redirect_uris' => ['https://regenerate.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications/'.$client->id.'/regenerate-secret');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'secret',
                'redirect_uris',
                'revoked',
                'created_at',
                'updated_at',
            ],
        ]);

    $responseData = $response->json('data');
    expect($responseData['secret'])->toHaveLength(40); // Plain text secret
    expect($responseData['secret'])->not->toBe($originalSecret);

    // Check that the secret in the database has changed (it will be hashed)
    $this->assertDatabaseMissing('oauth_clients', [
        'id' => $client->id,
        'secret' => $originalSecret,
    ]);
});

test('user cannot regenerate another users oauth application secret', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $client = $user1->oauthApps()->create([
        'name' => 'Protected App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://protected.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user2, 'api')
        ->postJson('/api/oauth-applications/'.$client->id.'/regenerate-secret');

    $response->assertNotFound();
});

test('unauthenticated user cannot access oauth application endpoints', function () {
    $endpoints = [
        ['method' => 'get', 'url' => '/api/oauth-applications'],
        ['method' => 'post', 'url' => '/api/oauth-applications', 'data' => [
            'name' => 'Test',
            'redirect_uris' => ['https://example.com/callback'],
        ]],
        ['method' => 'get', 'url' => '/api/oauth-applications/1'],
        ['method' => 'put', 'url' => '/api/oauth-applications/1', 'data' => ['name' => 'Test']],
        ['method' => 'delete', 'url' => '/api/oauth-applications/1'],
        ['method' => 'post', 'url' => '/api/oauth-applications/1/revoke'],
        ['method' => 'post', 'url' => '/api/oauth-applications/1/regenerate-secret'],
    ];

    foreach ($endpoints as $endpoint) {
        $method = $endpoint['method'];
        $url = $endpoint['url'];
        $data = $endpoint['data'] ?? [];

        $response = $this->{$method.'Json'}($url, $data);
        $response->assertStatus(401);
    }
});

test('returns 404 for non-existent oauth application operations', function () {
    $user = User::factory()->create();
    $nonExistentId = '99999999-9999-9999-9999-999999999999';

    $endpoints = [
        ['method' => 'get', 'url' => '/api/oauth-applications/'.$nonExistentId],
        ['method' => 'put', 'url' => '/api/oauth-applications/'.$nonExistentId, 'data' => ['name' => 'Test']],
        ['method' => 'delete', 'url' => '/api/oauth-applications/'.$nonExistentId],
        ['method' => 'post', 'url' => '/api/oauth-applications/'.$nonExistentId.'/revoke'],
        ['method' => 'post', 'url' => '/api/oauth-applications/'.$nonExistentId.'/regenerate-secret'],
    ];

    foreach ($endpoints as $endpoint) {
        $method = $endpoint['method'];
        $url = $endpoint['url'];
        $data = $endpoint['data'] ?? [];

        $response = $this->actingAs($user, 'api')->{$method.'Json'}($url, $data);
        $response->assertNotFound();
    }
});

test('oauth application secret is generated with correct length', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'Secret Test App',
            'redirect_uris' => ['https://secret.com/callback'],
        ]);

    $response->assertCreated();

    $responseData = $response->json('data');
    expect($responseData['secret'])->toHaveLength(40); // Plain text secret  // Update to actual length
    expect($responseData['secret'])->toMatch('/^[a-zA-Z0-9]{40}$/');  // Plain text secret (40 random chars)
});

test('oauth application list is ordered by created_at descending', function () {
    $user = User::factory()->create();

    // Create apps with slight delay to ensure different timestamps
    $app1 = $user->oauthApps()->create([
        'name' => 'First App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://first.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
        'created_at' => now()->subMinutes(2),
    ]);

    $app2 = $user->oauthApps()->create([
        'name' => 'Second App',
        'secret' => Str::random(40),
        'redirect_uris' => ['https://second.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
        'created_at' => now()->subMinutes(1),
    ]);

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/oauth-applications');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['name'])->toBe('Second App');
    expect($data[1]['name'])->toBe('First App');
});
