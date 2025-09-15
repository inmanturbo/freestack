<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('oauth settings component can regenerate secret and display it', function () {
    // Create a user first
    $user = User::factory()->create();

    $clientId = '01994982-bb4b-726f-8585-bf1fd779f7f4';

    // Mock all HTTP calls before any component instantiation
    Http::fake([
        '*/api/oauth-applications' => Http::response([
            'data' => [
                [
                    'id' => $clientId,
                    'name' => 'Test App',
                    'redirect_uris' => ['https://example.com/callback'],
                    'revoked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]
        ]),
        "*/api/oauth-applications/*/regenerate-secret" => Http::response([
            'data' => [
                'id' => $clientId,
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
        ->assertSet('applications', function ($applications) use ($clientId) {
            return count($applications) === 1 && $applications[0]['id'] === $clientId;
        });

    // Call regenerate secret
    $component->call('regenerateSecret', $clientId);


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
    // Mock API response before creating user
    Http::fake([
        '*/api/oauth-applications' => Http::response(['data' => []], 200),
    ]);

    $user = User::factory()->create();

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
    // Mock API failure before creating user
    Http::fake([
        '*/api/oauth-applications' => Http::response(['data' => []], 200),
        '*/api/oauth-applications/*' => Http::response(['message' => 'API Error'], 500),
    ]);

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('settings.oauth');

    $component->call('regenerateSecret', 'fake-id');

    // Check that error was flashed to session
    $flashData = session()->get('_flash.new', []);
    expect(in_array('error', $flashData))->toBeTrue();
});

