<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('unauthenticated access', function () {
    it('rejects unauthenticated users search', function () {
        $this->getJson('/api/search/users')->assertStatus(401);
    });

    it('rejects unauthenticated all search', function () {
        $this->getJson('/api/search/all')->assertStatus(401);
    });
});

describe('user results carry no PII', function () {
    it('projects away the email and verification timestamp', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/search/users');

        $response->assertStatus(200)
            ->assertJsonMissing(['email' => $this->user->email]);

        expect($response->json('data.0'))->toHaveKeys(['id', 'name', 'profile_photo_url'])
            ->and($response->json('data.0'))->not->toHaveKey('email')
            ->and($response->json('data.0'))->not->toHaveKey('email_verified_at');
    });

    it('projects the aggregate search the same way', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/search/all');

        $response->assertStatus(200);

        expect($response->json('users.data.0'))->not->toHaveKey('email');
    });
});
