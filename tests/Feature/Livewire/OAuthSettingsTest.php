<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('oauth settings component can regenerate secret and display it', function () {
    // Create a user
    $user = User::factory()->create();

    // Create an OAuth application
    $client = $user->oauthApps()->create([
        'name' => 'Test App',
        'secret' => 'original-secret',
        'redirect_uris' => ['https://example.com/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    // Mock the API response for regenerate secret
    Http::fake([
        '*/api/oauth-applications' => Http::response([
            'data' => [
                [
                    'id' => $client->id,
                    'name' => 'Test App',
                    'redirect_uris' => ['https://example.com/callback'],
                    'revoked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]
        ]),
        "*/api/oauth-applications/{$client->id}/regenerate-secret" => Http::response([
            'data' => [
                'id' => $client->id,
                'name' => 'Test App',
                'secret' => 'new-regenerated-secret-1234567890123456',
                'redirect_uris' => ['https://example.com/callback'],
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]),
    ]);

    // Act as the user and test the component
    $component = Livewire::actingAs($user)
        ->test('settings.oauth')
        ->assertSet('applications', function ($applications) use ($client) {
            return count($applications) === 1 && $applications[0]['id'] === $client->id;
        });

    // Call regenerate secret
    $component->call('regenerateSecret', $client->id);


    // Assert the newApplication is set
    $component->assertSet('newApplication', function ($newApplication) {
        return $newApplication !== null &&
               isset($newApplication['secret']) &&
               $newApplication['secret'] === 'new-regenerated-secret-1234567890123456';
    });

    // Assert the secret is visible in the rendered component
    $component->assertSee('new-regenerated-secret-1234567890123456');
    $component->assertSee('Client Secret');
    $component->assertSee('Copy');

    // Test the close functionality
    $component->call('closeNewApplicationAlert');
    $component->assertSet('newApplication', null);
    $component->assertDontSee('new-regenerated-secret-1234567890123456');
});

test('oauth settings component handles api token correctly', function () {
    $user = User::factory()->create();

    // Mock API response
    Http::fake([
        '*/api/oauth-applications' => Http::response(['data' => []], 200),
    ]);

    // Test that the component can make API calls with the session token
    Livewire::actingAs($user)
        ->test('settings.oauth')
        ->call('loadApplications');

    // Verify HTTP request was made (this will fail if no token is set)
    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization') &&
               str_contains($request->header('Authorization')[0], 'Bearer');
    });
});

test('oauth settings component shows error when api fails', function () {
    $user = User::factory()->create();

    // Mock API failure
    Http::fake([
        '*/api/oauth-applications/*' => Http::response(['message' => 'API Error'], 500),
    ]);

    $component = Livewire::actingAs($user)
        ->test('settings.oauth');

    $component->call('regenerateSecret', 'fake-id');

    // Check that error was flashed to session
    $flashData = session()->get('_flash.new', []);
    expect(in_array('error', $flashData))->toBeTrue();
});

