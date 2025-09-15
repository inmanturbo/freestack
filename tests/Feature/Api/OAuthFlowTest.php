<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('oauth authorization flow works end to end', function () {
    // Create a user
    $user = User::factory()->create();

    // Create an OAuth client using the API (which handles the structure correctly)
    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'Test Client',
            'redirect_uris' => ['http://localhost:3000/callback']
        ]);

    $clientData = $response->json('data');
    $client = (object) $clientData;

    // Step 1: Visit the authorization URL (need to authenticate as web user)
    $authUrl = '/oauth/authorize?' . http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost:3000/callback',
        'response_type' => 'code',
        'scope' => 'read write',
        'state' => 'xyz123',
        'prompt' => 'consent',
    ]);

    $response = $this->actingAs($user, 'web')
        ->get($authUrl);

    $response->assertOk();
    $response->assertSee('Test Client');
    $response->assertSee('Authorize Application');

    // Get auth token from session after the GET request
    $sessionData = session()->all();

    // Step 2: Submit the authorization form (approve)
    $response = $this->actingAs($user, 'web')
        ->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'auth_token' => $sessionData['authToken'],
            'client_id' => $client->id,
            'redirect_uri' => 'http://localhost:3000/callback',
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz123',
            'approve' => 'true',
        ]);

    // Should redirect to the callback URL with an authorization code
    $response->assertStatus(302);

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('http://localhost:3000/callback');
    expect($location)->toContain('code=');
    expect($location)->toContain('state=xyz123');

});

test('oauth authorization flow handles denial', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/oauth-applications', [
            'name' => 'Test Client',
            'redirect_uris' => ['http://localhost:3000/callback']
        ]);

    $client = (object) $response->json('data');

    // First visit the authorization URL with consent prompt
    $authUrl = '/oauth/authorize?' . http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost:3000/callback',
        'response_type' => 'code',
        'scope' => 'read write',
        'state' => 'xyz123',
        'prompt' => 'consent',
    ]);

    $getResponse = $this->actingAs($user, 'web')
        ->get($authUrl);

    $getResponse->assertOk();

    // Get auth token from session
    $sessionData = session()->all();

    // Submit denial (using DELETE method)
    $response = $this->actingAs($user, 'web')
        ->delete('/oauth/authorize', [
            '_token' => csrf_token(),
            'auth_token' => $sessionData['authToken'],
            'client_id' => $client->id,
            'redirect_uri' => 'http://localhost:3000/callback',
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz123',
        ]);

    $response->assertStatus(302);

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('http://localhost:3000/callback');
    expect($location)->toContain('error=access_denied');
});
