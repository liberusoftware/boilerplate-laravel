<?php
// Standalone installer UI (no Laravel bootstrap required for the pre-composer steps).
// Place this file at public/installer.php
//
// Security:
// - Set INSTALLER_ENABLED=true in .env to enable this UI.
// - Optionally set INSTALLER_KEY in .env and provide ?key=... or send key in POST to authenticate.
// - Remove/disable after use.

function dotenv_get($key) {
    // Try getenv first
    $v = getenv($key);
    if ($v !== false) {
        return $v;
    }
    // Fallback: parse .env if present
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return false;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$k, $rest] = explode('=', $line, 2);
        $k = trim($k);
        if ($k === $key) {
            $val = trim($rest);
            $val = trim($val, "\"'");
            return $val;
        }
    }
    return false;
}

function enabled() {
    $val = dotenv_get('INSTALLER_ENABLED');
    if ($val === false) return false;
    $val = strtolower((string)$val);
    return in_array($val, ['1','true','on','yes'], true);
}

function check_key() {
    $required = dotenv_get('INSTALLER_KEY');
    if ($required === false || $required === '') return true; // not configured
    $provided = $_REQUEST['key'] ?? null;
    return is_string($provided) && hash_equals((string)$required, (string)$provided);
}

if (!enabled()) {
    http_response_code(403);
    echo "<!doctype html><html><body><h2>Installer disabled</h2><p>Set INSTALLER_ENABLED=true in your .env to enable.</p></body></html>";
    exit;
}

if (!check_key()) {
    // Simple prompt page for key
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key'])) {
        // redirect with key in query for convenient requests (short-lived)
        $key = urlencode($_POST['key']);
        $uri = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: {$uri}?key={$key}");
        exit;
    }
    echo '<!doctype html><html
