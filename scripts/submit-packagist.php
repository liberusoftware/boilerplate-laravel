#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);
$username = getenv('PACKAGIST_USERNAME') ?: '';
$token = getenv('PACKAGIST_API_TOKEN') ?: '';

if (! $dryRun && ($username === '' || $token === '')) {
    fwrite(STDERR, "Set PACKAGIST_USERNAME and the Packagist MAIN token as PACKAGIST_API_TOKEN before submitting.\n");
    exit(1);
}

$files = array_merge(
    glob($root.'/modules/*/composer.json') ?: [],
    glob($root.'/themes/*/composer.json') ?: [],
    [$root.'/scripts/composer.json'],
    [$root.'/composer.json'],
);

$failures = [];

foreach ($files as $file) {
    $metadata = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $package = (string) ($metadata['name'] ?? '');
    $relative = ltrim(str_replace($root, '', dirname($file)), '/');

    if ($relative === '') {
        $repository = 'boilerplate-laravel';
    } elseif ($relative === 'scripts') {
        $repository = 'boilerplate-scripts';
    } elseif (str_starts_with($relative, 'modules/')) {
        $repository = 'module-'.basename($relative);
    } else {
        $repository = 'theme-'.basename($relative);
    }

    $repositoryUrl = "https://github.com/liberusoftware/{$repository}";
    printf("%-48s %s\n", $package, $repositoryUrl);

    if ($dryRun) {
        continue;
    }

    $existing = request("https://repo.packagist.org/p2/{$package}.json");
    if ($existing['status'] === 200) {
        fwrite(STDOUT, "  already registered\n");

        continue;
    }

    $response = request(
        'https://packagist.org/api/create-package',
        json_encode(['repository' => $repositoryUrl], JSON_THROW_ON_ERROR),
        "Authorization: Bearer {$username}:{$token}",
    );

    if ($response['status'] < 200 || $response['status'] >= 300) {
        $message = json_decode($response['body'], true);
        $failures[$package] = is_array($message) ? ($message['status'] ?? $response['body']) : $response['body'];
        fwrite(STDERR, "  submission failed ({$response['status']})\n");
    } else {
        fwrite(STDOUT, "  submitted\n");
    }
}

if ($failures !== []) {
    fwrite(STDERR, "\nFailed packages:\n");
    foreach ($failures as $package => $message) {
        fwrite(STDERR, "- {$package}: {$message}\n");
    }

    exit(1);
}

/** @return array{status:int,body:string} */
function request(string $url, ?string $body = null, ?string $authorization = null): array
{
    $handle = curl_init($url);
    $headers = ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Liberu-Package-Publisher/1.0 mailto:info@liberusoftware.com'];
    if ($authorization !== null) {
        $headers[] = $authorization;
    }
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body]);
    }

    $response = curl_exec($handle);
    if ($response === false) {
        throw new RuntimeException(curl_error($handle));
    }

    return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $response];
}
