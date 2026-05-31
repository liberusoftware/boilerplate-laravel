<?php

it('socialstream config has expected social media providers', function () {
    $providers = config('socialstream.providers', []);

    $providerNames = array_map(fn ($p) => $p['id'] ?? $p, $providers);

    expect($providers)->not->toBeEmpty();

    foreach (['bitbucket', 'facebook', 'github', 'gitlab', 'google', 'linkedin', 'linkedin-openid', 'slack', 'twitter-oauth-2'] as $expectedProvider) {
        $found = collect($providers)->contains(function ($provider) use ($expectedProvider) {
            $id = is_array($provider) ? ($provider['id'] ?? '') : $provider;

            return str_contains(strtolower($id), str_replace('-', '', $expectedProvider))
                || str_contains(strtolower($id), $expectedProvider);
        });
        expect($found)->toBeTrue("Expected provider '{$expectedProvider}' not found in socialstream config");
    }
});

it('socialstream config does not include twitter oauth1', function () {
    $providers = config('socialstream.providers', []);

    $hasOAuth1 = collect($providers)->contains(function ($provider) {
        $id = is_array($provider) ? ($provider['id'] ?? '') : $provider;

        return str_contains(strtolower($id), 'twitter-oauth-1')
            || str_contains(strtolower($id), 'twitteroauth1');
    });

    expect($hasOAuth1)->toBeFalse('twitterOAuth1 should not be in the providers list');
});

it('socialstream config has required features', function () {
    $features = config('socialstream.features', []);
    expect($features)->not->toBeEmpty();
});

it('socialstream config has valid redirect paths', function () {
    $redirects = config('socialstream.redirects', []);

    expect($redirects)->toHaveKey('login');
    expect($redirects)->toHaveKey('register');
    expect($redirects)->toHaveKey('login-failed');
});
