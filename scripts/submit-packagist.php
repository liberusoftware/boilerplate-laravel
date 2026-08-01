#!/usr/bin/env php
<?php

declare(strict_types=1);

$arguments = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $arguments, true);
$configArgument = array_search('--config', $arguments, true);
$root = getcwd() ?: dirname(__DIR__);
$configFile = $configArgument === false ? ($root.'/.liberu-meta.json') : ($arguments[$configArgument + 1] ?? '');

if (! is_file($configFile)) {
    fwrite(STDERR, "Configuration not found: {$configFile}\n");
    exit(1);
}

$config = json_decode((string) file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR);
$organization = getenv('META_ORGANIZATION') ?: (string) $config['organization'];
$username = getenv('PACKAGIST_USERNAME') ?: '';
$token = getenv('PACKAGIST_API_TOKEN') ?: '';

if (! $dryRun && ($username === '' || $token === '')) {
    fwrite(STDERR, "Set PACKAGIST_USERNAME and PACKAGIST_API_TOKEN before submitting.\n");
    exit(1);
}

$packages = [];
foreach ($config['components'] ?? [] as $component) {
    $path = $root.'/'.$component['path'];
    $files = isset($component['repository'])
        ? [$path.'/composer.json']
        : (glob($path.'/*/composer.json') ?: []);
    foreach ($files as $file) {
        if (! is_file($file)) {
            continue;
        }
        $metadata = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $repository = $component['repository'] ?? (($component['repositoryPrefix'] ?? '').basename(dirname($file)));
        $packages[(string) $metadata['name']] = (string) $repository;
    }
}

$rootComposer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$packages[(string) $rootComposer['name']] = (string) $config['repository'];
$packages += $config['additionalPackages'] ?? [];
ksort($packages);

$failures = [];
foreach ($packages as $package => $repository) {
    $repositoryUrl = "https://github.com/{$organization}/{$repository}";
    printf("%-52s %s\n", $package, $repositoryUrl);
    if ($dryRun) {
        continue;
    }

    $existing = request("https://repo.packagist.org/p2/{$package}.json");
    $endpoint = $existing['status'] === 200 ? 'update-package' : 'create-package';
    $payload = $endpoint === 'update-package' ? ['repository' => ['url' => $repositoryUrl]] : ['repository' => $repositoryUrl];
    $response = request(
        "https://packagist.org/api/{$endpoint}",
        json_encode($payload, JSON_THROW_ON_ERROR),
        "Authorization: Bearer {$username}:{$token}",
    );
    if ($response['status'] < 200 || $response['status'] >= 300) {
        $message = json_decode($response['body'], true);
        $failures[$package] = is_array($message) ? ($message['status'] ?? $response['body']) : $response['body'];
        fwrite(STDERR, "  {$endpoint} failed ({$response['status']})\n");
    } else {
        fwrite(STDOUT, '  '.($endpoint === 'update-package' ? 'updated' : 'submitted')."\n");
    }
}

if ($failures !== []) {
    foreach ($failures as $package => $message) {
        fwrite(STDERR, "- {$package}: {$message}\n");
    }
    exit(1);
}

/** @return array{status:int,body:string} */
function request(string $url, ?string $body = null, ?string $authorization = null): array
{
    $handle = curl_init($url);
    $headers = ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Liberu-Package-Publisher/2.0'];
    if ($authorization !== null) {
        $headers[] = $authorization;
    }
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $headers]);
    if ($body !== null) {
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body]);
    }
    $response = curl_exec($handle);
    if ($response === false) {
        throw new RuntimeException(curl_error($handle));
    }

    return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => (string) $response];
}
