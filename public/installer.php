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
        header("Location: {
$uri}?key={
$key}");
        exit;
    }
    echo '<!doctype html><html><body style="font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif">';
    echo '<h2>Installer - Authentication</h2>';
    echo '<form method="POST"><label>Installer Key: <input name="key" type="password" /></label> <button type="submit">Unlock</button></form>';
    echo '</body></html>';
    exit;
}

// Helper to run a shell command and return output and exit code
function run_cmd($cmd, &$output = null) {
    // Use proc_open so we can capture stdout/stderr. Avoid shell injection by leaving caller to escape.
    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = @proc_open($cmd, $descriptorspec, $pipes, getcwd());
    if (!is_resource($process)) {
        $output = "Failed to start process for command: {$cmd}";
        return 255;
    }
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $status = proc_close($process);
    $output = trim($out . PHP_EOL . $err);
    return $status;
}

$action = $_REQUEST['action'] ?? null;

if ($action) {
    header('Content-Type: application/json');

    // Simple allowlist for actions
    $allowed = [
        'composer_install',
        'php_key_generate',
        'migrate_seed',
        'npm_install',
        'npm_build',
        'save_settings',
        'create_users',
        'test_db',
        'status',
        'list_modules',
        'enable_module',
        'install_module',
    ];
    if (!in_array($action, $allowed, true)) {
        echo json_encode(['ok' => false, 'message' => 'Invalid action']);
        exit;
    }

    // Make sure path calculations are consistent
    $projectRoot = realpath(__DIR__ . '/..');
    chdir($projectRoot);

    try {
        if ($action === 'status') {
            $composerInstalled = file_exists($projectRoot . '/vendor/autoload.php');
            echo json_encode(['ok' => true, 'composer_installed' => $composerInstalled]);
            exit;
        }

        if ($action === 'composer_install') {
            $composer = getenv('COMPOSER_BINARY') ?: 'composer';
            $cmd = escapeshellcmd($composer) . ' install --no-interaction --no-progress';
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'php_key_generate') {
            // php artisan key:generate requires vendor; fail gracefully if vendor missing
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' artisan key:generate --force';
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'migrate_seed') {
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' artisan migrate --force --seed';
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'npm_install') {
            $npm = getenv('NPM_BINARY') ?: 'npm';
            $cmd = escapeshellcmd($npm) . ' install --no-audit --no-fund';
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'npm_build') {
            $npm = getenv('NPM_BINARY') ?: 'npm';
            $cmd = escapeshellcmd($npm) . ' run build';
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'save_settings') {
            // Accept JSON body
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $appName = $body['app_name'] ?? null;
            $appUrl = $body['app_url'] ?? null;
            $adminEmail = $body['admin_email'] ?? null;

            // DB fields (optional)
            $dbConnection = $body['db_connection'] ?? null;
            $dbHost = $body['db_host'] ?? null;
            $dbPort = $body['db_port'] ?? null;
            $dbDatabase = $body['db_database'] ?? null;
            $dbUsername = $body['db_username'] ?? null;
            $dbPassword = $body['db_password'] ?? null;

            $envPath = $projectRoot . '/.env';
            if (!file_exists($envPath)) {
                // create
                file_put_contents($envPath, '');
            }
            $env = file_get_contents($envPath);
            $replacements = [
                'APP_NAME' => $appName,
                'APP_URL' => $appUrl,
                'ADMIN_EMAIL' => $adminEmail,

                // Persist DB values (new)
                'DB_CONNECTION' => $dbConnection,
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbDatabase,
                'DB_USERNAME' => $dbUsername,
                'DB_PASSWORD' => $dbPassword,
            ];
            foreach ($replacements as $k => $v) {
                if ($v === null || $v === '') continue;
                $escaped = (strpos($v, ' ') !== false) ? '"' . addcslashes($v, "\"") . '"' : $v;
                if (preg_match("/^{$k}=.*/m", $env)) {
                    $env = preg_replace("/^{$k}=.*/m", "{$k}={$escaped}", $env);
                } else {
                    $env .= PHP_EOL . "{$k}={$escaped}";
                }
            }
            file_put_contents($envPath, $env);
            // try clearing config caches (works only if vendor installed)
            if (file_exists($projectRoot . '/vendor/autoload.php')) {
                $php = getenv('PHP_BINARY') ?: 'php';
                run_cmd(escapeshellcmd($php) . ' artisan config:clear', $o1);
            }
            echo json_encode(['ok' => true, 'message' => '.env updated']);
            exit;
        }

        if ($action === 'test_db') {
            // Accept JSON body with DB params (do not persist), or fall back to .env values
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $dbConnection = $body['db_connection'] ?? dotenv_get('DB_CONNECTION') ?: 'mysql';
            $dbHost = $body['db_host'] ?? dotenv_get('DB_HOST') ?: '127.0.0.1';
            $dbPort = $body['db_port'] ?? dotenv_get('DB_PORT') ?: ($dbConnection === 'pgsql' ? '5432' : '3306');
            $dbDatabase = $body['db_database'] ?? dotenv_get('DB_DATABASE') ?: '';
            $dbUsername = $body['db_username'] ?? dotenv_get('DB_USERNAME') ?: '';
            $dbPassword = $body['db_password'] ?? dotenv_get('DB_PASSWORD') ?? '';

            try {
                $dsn = '';
                if (strpos($dbConnection, 'mysql') !== false) {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbDatabase};charset=utf8mb4";
                } elseif (strpos($dbConnection, 'pgsql') !== false || strpos($dbConnection, 'postgres') !== false) {
                    $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbDatabase}";
                } else {
                    // Try generic PDO DSN, but most likely unsupported
                    echo json_encode(['ok' => false, 'message' => 'Unsupported DB_CONNECTION: ' . $dbConnection]);
                    exit;
                }
                // Try connecting with PDO
                $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
                if (defined('PDO::ATTR_TIMEOUT')) $opts[PDO::ATTR_TIMEOUT] = 5;
                $pdo = new PDO($dsn, $dbUsername, $dbPassword, $opts);
                // simple query to ensure connection usable
                if (strpos($dbConnection, 'mysql') !== false) {
                    $pdo->exec('SELECT 1');
                } else {
                    $pdo->query('SELECT 1');
                }
                echo json_encode(['ok' => true, 'message' => 'Connection successful']);
                exit;
            } catch (Throwable $th) {
                echo json_encode(['ok' => false, 'message' => 'Connection failed: ' . $th->getMessage()]);
                exit;
            }
        }

        if ($action === 'create_users') {
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $users = $body['users'] ?? null;
            if (!$users) {
                echo json_encode(['ok' => false, 'message' => 'No users provided']);
                exit;
            }
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            // Ensure storage/installer exists
            $scriptDir = $projectRoot . '/storage/installer';
            if (!is_dir($scriptDir)) {
                @mkdir($scriptDir, 0755, true);
            }
            $scriptPath = $scriptDir . '/create_users.php';
            // Write helper script (idempotent)
            $helper = <<< 'PHP'
<?php
// storage/installer/create_users.php
// Usage: php create_users.php <base64_json_of_users>
// JSON: { "users": [ { "name":"", "email":"", "password":"", "role":"" }, ... ] }

$arg = $argv[1] ?? null;
if (!$arg) {
    echo "No data provided\n";
    exit(1);
}
$data = json_decode(base64_decode($arg), true);
if (!$data || !isset($data['users'])) {
    echo "Invalid payload\n";
    exit(1);
}

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';

$app = require $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

foreach ($data['users'] as $u) {
    if (!isset($u['name'],$u['email'],$u['password'])) {
        echo "Skipping incomplete user\n";
        continue;
    }
    try {
        // Create user using Eloquent (App\Models\User)
        $userClass = '\\App\\Models\\User';
        if (!class_exists($userClass)) {
            echo "User model not found\n";
            continue;
        }
        if (call_user_func([$userClass, 'where'], 'email', $u['email'])->exists()) {
            echo "User {$u['email']} already exists, skipping\n";
            continue;
        }
        $user = $userClass::create([
            'name' => $u['name'],
            'email' => $u['email'],
            'password' => Hash::make($u['password']),
        ]);
        // Assign role if Spatie exists and user has assignRole
        if (class_exists('\\Spatie\\Permission\\Models\\Role') && method_exists($user, 'assignRole') && !empty($u['role'])) {
            if (!\\Spatie\\Permission\\Models\\Role::where('name', $u['role'])->exists()) {
                \\Spatie\\Permission\\Models\\Role::create(['name' => $u['role']]);
            }
            $user->assignRole($u['role']);
            echo "Created user {$u['email']} with role {$u['role']}\n";
        } else {
            // fallback attempt to set role attribute
            if (array_key_exists('role', $user->getAttributes()) && isset($u['role'])) {
                $user->role = $u['role'];
                $user->save();
                echo "Created user {$u['email']} (role attribute set)\n";
            } else {
                echo "Created user {$u['email']} (no role assigned)\n";
            }
        }
    } catch (Exception $e) {
        echo "Failed to create {$u['email']}: " . $e->getMessage() . "\n";
    }
}
PHP;
            file_put_contents($scriptPath, $helper);

            // Encode user payload to base64 to safely pass via CLI
            $payload = ['users' => $users];
            $b64 = base64_encode(json_encode($payload));
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($b64);
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'list_modules') {
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' artisan module list --format=json 2>&1';
            $out = null;
            $code = run_cmd($cmd, $out);
            
            // Try to parse JSON output
            $jsonData = json_decode($out, true);
            if ($jsonData !== null && isset($jsonData['modules'])) {
                echo json_encode(['ok' => true, 'modules' => $jsonData['modules']]);
            } else {
                // Fallback to raw output
                echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            }
            exit;
        }

        if ($action === 'enable_module') {
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $moduleName = $body['module_name'] ?? null;
            if (!$moduleName) {
                echo json_encode(['ok' => false, 'message' => 'Module name is required']);
                exit;
            }
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' artisan module enable ' . escapeshellarg($moduleName);
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        if ($action === 'install_module') {
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $moduleName = $body['module_name'] ?? null;
            if (!$moduleName) {
                echo json_encode(['ok' => false, 'message' => 'Module name is required']);
                exit;
            }
            if (!file_exists($projectRoot . '/vendor/autoload.php')) {
                echo json_encode(['ok' => false, 'message' => 'vendor not installed. Run composer first.']);
                exit;
            }
            $php = getenv('PHP_BINARY') ?: 'php';
            $cmd = escapeshellcmd($php) . ' artisan module install ' . escapeshellarg($moduleName);
            $out = null;
            $code = run_cmd($cmd, $out);
            echo json_encode(['ok' => $code === 0, 'exit' => $code, 'output' => $out]);
            exit;
        }

        echo json_encode(['ok' => false, 'message' => 'Unhandled action']);
        exit;

    } catch (Throwable $th) {
        echo json_encode(['ok' => false, 'message' => $th->getMessage()]);
        exit;
    }
}

// No action: render UI
$app_name = htmlspecialchars(dotenv_get('APP_NAME') ?: 'Laravel App', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$installer_key_configured = (bool)dotenv_get('INSTALLER_KEY');
$example_key = substr(bin2hex(random_bytes(8)),0,16);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Installer - <?= $app_name ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:#f3f4f6; color:#111827; }
    .wrap { max-width:1000px;margin:32px auto;background:#fff;padding:20px;border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    button { cursor:pointer; padding:8px 12px; border-radius:6px; border:1px solid #e5e7eb; background:#111827;color:#fff; }
    .btn-ghost{ background:#fff;color:#111827;border:1px solid #e5e7eb; }
    pre { background:#0b1220; color:#9ae6b4; padding:12px; border-radius:6px; overflow:auto; height:240px; }
    input, select { padding:8px;border:1px solid #e5e7eb;border-radius:6px;width:100%; }
    .grid { display:grid; grid-template-columns: 1fr 320px; gap:16px; }
    .steps { display:flex; flex-direction:column; gap:8px; }
    .row { display:flex; gap:8px; }
    label { font-size:13px; color:#374151; }
    .muted{ color:#6b7280; font-size:13px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Standalone Installer</h1>
    <p class="muted">This installer runs common setup tasks. Make sure this is disabled (INSTALLER_ENABLED=false or remove file) after use.</p>

    <?php if ($installer_key_configured): ?>
      <div style="margin:8px 0;padding:10px;background:#fff4e6;border-left:4px solid #f59e0b;">
        <strong>Installer Key is configured.</strong> Requests must include ?key=YOUR_KEY or send key in POST/JSON.
      </div>
    <?php else: ?>
      <div style="margin:8px 0;padding:10px;background:#eef2ff;border-left:4px solid #6366f1;">
        <strong>No INSTALLER_KEY set.</strong> For safety set INSTALLER_KEY in your .env. Example: <code>INSTALLER_KEY=<?= $example_key ?></code>
      </div>
    <?php endif; ?>

    <div class="grid" style="align-items:start">
      <div>
        <div class="steps">
          <div>
            <strong>Steps</strong>
            <div class="row" style="margin-top:8px;">
              <button onclick="run('composer_install')" id="b-composer">1. Composer install</button>
              <button onclick="run('php_key_generate')" id="b-key" class="btn-ghost">2. Key</button>
              <button onclick="run('migrate_seed')" id="b-migrate" class="btn-ghost">3. Migrate & Seed</button>
            </div>
            <div class="row" style="margin-top:8px;">
              <button onclick="run('npm_install')" id="b-npm" class="btn-ghost">4. NPM Install</button>
              <button onclick="run('npm_build')" id="b-npmbuild" class="btn-ghost">5. NPM Build</button>
            </div>
          </div>

          <div>
            <strong>Settings</strong>
            <div style="margin-top:8px;">
              <label>App Name</label>
              <input id="app_name" placeholder="<?= $app_name ?>">
              <label style="margin-top:8px">App URL</label>
              <input id="app_url" placeholder="https://example.com">
              <label style="margin-top:8px">Admin Email</label>
              <input id="admin_email" placeholder="admin@example.com">

              <!-- Database settings (new) -->
              <hr style="margin:12px 0">
              <strong>Database</strong>
              <label style="margin-top:8px">DB Connection</label>
              <input id="db_connection" placeholder="mysql">
              <label style="margin-top:8px">DB Host</label>
              <input id="db_host" placeholder="127.0.0.1">
              <label style="margin-top:8px">DB Port</label>
              <input id="db_port" placeholder="3306">
              <label style="margin-top:8px">DB Database</label>
              <input id="db_database" placeholder="database_name">
              <label style="margin-top:8px">DB Username</label>
              <input id="db_username" placeholder="db_user">
              <label style="margin-top:8px">DB Password</label>
              <input id="db_password" type="password" placeholder="password">
              <!-- end DB settings -->

              <div class="row" style="margin-top:8px;">
                <button onclick="saveSettings()" class="btn-ghost">Save Settings</button>
                <button onclick="testDb()" class="btn-ghost">Test DB connection</button>
              </div>
            </div>
          </div>

          <div>
            <strong>Create Users</strong>
            <div id="users-area" style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">
              <div class="user-row">
                <input placeholder="Name" class="u-name">
                <input placeholder="Email" class="u-email" style="margin-top:6px;">
                <input placeholder="Password" class="u-password" type="password" style="margin-top:6px;">
                <input placeholder="Role (optional)" class="u-role" style="margin-top:6px;">
              </div>
            </div>
            <div class="row" style="margin-top:8px;">
              <button onclick="addUserRow()" class="btn-ghost">Add user row</button>
              <button onclick="createUsers()" class="btn-ghost">Create Users</button>
            </div>
          </div>

          <div>
            <strong>Modules</strong>
            <div style="margin-top:8px;">
              <div class="row">
                <button onclick="listModules()" class="btn-ghost">List Modules</button>
              </div>
              <div id="modules-list" style="margin-top:8px;max-height:150px;overflow-y:auto;font-size:12px;"></div>
            </div>
          </div>

          <div>
            <strong>Console Output</strong>
            <pre id="out"></pre>
          </div>
        </div>
      </div>

      <div>
        <div style="background:#f8fafc;padding:12px;border-radius:6px">
          <h3>Quick Status</h3>
          <div id="status">Checking...</div>
          <hr>
          <div class="muted">
            - Commands are executed on the server using CLI binaries found in PATH or as set in .env (COMPOSER_BINARY, PHP_BINARY, NPM_BINARY).<br>
            - Composer must be run first. Artisan commands require vendor installed. <br>
            - After finishing, disable the installer by setting INSTALLER_ENABLED=false.
          </div>
        </div>
      </div>
    </div>

    <div style="margin-top:12px;text-align:right">
      <button onclick="clearOutput()" class="btn-ghost">Clear</button>
      <button onclick="window.location.reload()" class="btn-ghost">Reload</button>
    </div>
  </div>

<script>
const outEl = document.getElementById('out');
function append(s){ outEl.textContent += s + "\n"; outEl.scrollTop = outEl.scrollHeight; }
function clearOutput(){ outEl.textContent = ''; }

async function checkStatus(){
  try {
    const res = await fetch(window.location.pathname + '?action=status&key=' + encodeURIComponent(getKey()));
    const j = await res.json();
    document.getElementById('status').textContent = 'Composer installed: ' + (j.composer_installed ? 'yes' : 'no');
  } catch (e) {
    document.getElementById('status').textContent = 'Status unavailable';
  }
}
function getKey() {
  const qp = new URLSearchParams(window.location.search);
  return qp.get('key') || '';
}

async function run(action){
  append("Starting " + action + "...");
  try {
    const res = await fetch(window.location.pathname + '?action=' + encodeURIComponent(action) + '&key=' + encodeURIComponent(getKey()), { method: 'POST' });
    const j = await res.json();
    if (j.output) append(j.output);
    if (j.message) append(j.message);
    append("Exit: " + (j.exit ?? '') + " OK: " + (j.ok ? 'true' : 'false'));
  } catch (e) {
    append("Error: " + e.message);
  }
  checkStatus();
}

function saveSettings(){
  const payload = {
    app_name: document.getElementById('app_name').value,
    app_url: document.getElementById('app_url').value,
    admin_email: document.getElementById('admin_email').value,

    // DB fields (new)
    db_connection: document.getElementById('db_connection').value,
    db_host: document.getElementById('db_host').value,
    db_port: document.getElementById('db_port').value,
    db_database: document.getElementById('db_database').value,
    db_username: document.getElementById('db_username').value,
    db_password: document.getElementById('db_password').value,
  };
  append("Saving settings...");
  fetch(window.location.pathname + '?action=save_settings&key=' + encodeURIComponent(getKey()), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  }).then(r=>r.json()).then(j=>{
    append(JSON.stringify(j));
    checkStatus();
  }).catch(e=>{
    append("Save failed: " + e.message);
  });
}

function testDb(){
  const payload = {
    db_connection: document.getElementById('db_connection').value,
    db_host: document.getElementById('db_host').value,
    db_port: document.getElementById('db_port').value,
    db_database: document.getElementById('db_database').value,
    db_username: document.getElementById('db_username').value,
    db_password: document.getElementById('db_password').value,
  };
  append("Testing DB connection...");
  fetch(window.location.pathname + '?action=test_db&key=' + encodeURIComponent(getKey()), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  }).then(r=>r.json()).then(j=>{
    append(JSON.stringify(j));
    checkStatus();
  }).catch(e=>append("Error: "+e.message));
}

function addUserRow(){
  const area = document.getElementById('users-area');
  const div = document.createElement('div');
  div.className = 'user-row';
  div.style.display = 'flex';
  div.style.flexDirection = 'column';
  div.style.gap = '6px';
  div.innerHTML = '<input placeholder="Name" class="u-name"><input placeholder="Email" class="u-email"><input placeholder="Password" class="u-password" type="password"><input placeholder="Role (optional)" class="u-role">';
  area.appendChild(div);
}

function createUsers(){
  const rows = Array.from(document.querySelectorAll('.user-row'));
  const users = rows.map(r => {
    return {
      name: r.querySelector('.u-name')?.value || '',
      email: r.querySelector('.u-email')?.value || '',
      password: r.querySelector('.u-password')?.value || '',
      role: r.querySelector('.u-role')?.value || '',
    };
  });
  append("Creating users...");
  fetch(window.location.pathname + '?action=create_users&key=' + encodeURIComponent(getKey()), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ users })
  }).then(r=>r.json()).then(j=>{
    append(j.output || j.message || JSON.stringify(j));
    checkStatus();
  }).catch(e=>append("Error: "+e));
}

async function listModules(){
  append("Listing modules...");
  try {
    const res = await fetch(window.location.pathname + '?action=list_modules&key=' + encodeURIComponent(getKey()), { method: 'POST' });
    const j = await res.json();
    
    if (j.modules) {
      // We have structured JSON data
      displayModulesFromJson(j.modules);
      append(`Found ${j.modules.length} module(s)`);
    } else if (j.output) {
      // Fallback to parsing text output
      append(j.output);
      displayModulesTable(j.output);
    }
    
    if (j.message) append(j.message);
  } catch (e) {
    append("Error: " + e.message);
  }
}

function displayModulesFromJson(modules) {
  const listEl = document.getElementById('modules-list');
  
  if (!modules || modules.length === 0) {
    listEl.innerHTML = '<div style="padding:8px;color:#6b7280;">No modules found</div>';
    return;
  }
  
  let html = '<table style="width:100%;font-size:11px;border-collapse:collapse;">';
  html += '<tr style="background:#f3f4f6;font-weight:bold;">';
  html += '<th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Name</th>';
  html += '<th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Version</th>';
  html += '<th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Status</th>';
  html += '<th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Action</th>';
  html += '</tr>';
  
  modules.forEach(module => {
    const isEnabled = module.enabled;
    const statusColor = isEnabled ? '#10b981' : '#ef4444';
    const statusText = isEnabled ? 'Enabled' : 'Disabled';
    
    html += '<tr>';
    html += `<td style="padding:4px;border:1px solid #e5e7eb;">${module.name}</td>`;
    html += `<td style="padding:4px;border:1px solid #e5e7eb;">${module.version}</td>`;
    html += `<td style="padding:4px;border:1px solid #e5e7eb;color:${statusColor};">${statusText}</td>`;
    html += '<td style="padding:4px;border:1px solid #e5e7eb;">';
    
    if (!isEnabled) {
      html += `<button onclick="enableModule('${module.name}')" style="padding:2px 6px;font-size:10px;margin-right:4px;" class="btn-ghost">Enable</button>`;
      html += `<button onclick="installModule('${module.name}')" style="padding:2px 6px;font-size:10px;" class="btn-ghost">Install</button>`;
    } else {
      html += '<span style="color:#6b7280;font-size:10px;">Active</span>';
    }
    
    html += '</td></tr>';
  });
  
  html += '</table>';
  listEl.innerHTML = html;
}

function displayModulesTable(output) {
  const listEl = document.getElementById('modules-list');
  listEl.innerHTML = '';
  
  // Parse the table output from artisan command
  const lines = output.split('\n');
  let html = '<table style="width:100%;font-size:11px;border-collapse:collapse;">';
  
  for (let line of lines) {
    if (line.includes('Name') && line.includes('Status')) {
      // Header row
      html += '<tr style="background:#f3f4f6;font-weight:bold;"><th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Name</th><th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Status</th><th style="padding:4px;text-align:left;border:1px solid #e5e7eb;">Action</th></tr>';
    } else if (line.includes('│') || line.includes('|')) {
      // Data row
      const parts = line.split(/[│|]/).map(p => p.trim()).filter(p => p);
      if (parts.length >= 2) {
        const name = parts[0];
        const status = parts[2] || 'Unknown';
        const isEnabled = status.toLowerCase().includes('enabled');
        const color = isEnabled ? '#10b981' : '#ef4444';
        html += `<tr><td style="padding:4px;border:1px solid #e5e7eb;">${name}</td><td style="padding:4px;border:1px solid #e5e7eb;color:${color};">${status}</td><td style="padding:4px;border:1px solid #e5e7eb;">`;
        if (!isEnabled) {
          html += `<button onclick="enableModule('${name}')" style="padding:2px 6px;font-size:10px;" class="btn-ghost">Enable</button> `;
          html += `<button onclick="installModule('${name}')" style="padding:2px 6px;font-size:10px;" class="btn-ghost">Install</button>`;
        }
        html += '</td></tr>';
      }
    }
  }
  html += '</table>';
  listEl.innerHTML = html;
}

async function enableModule(moduleName){
  append(`Enabling module: ${moduleName}...`);
  try {
    const res = await fetch(window.location.pathname + '?action=enable_module&key=' + encodeURIComponent(getKey()), {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ module_name: moduleName })
    });
    const j = await res.json();
    if (j.output) append(j.output);
    if (j.message) append(j.message);
    append("Result: " + (j.ok ? 'Success' : 'Failed'));
    listModules(); // Refresh the list
  } catch (e) {
    append("Error: " + e.message);
  }
}

async function installModule(moduleName){
  append(`Installing module: ${moduleName}...`);
  try {
    const res = await fetch(window.location.pathname + '?action=install_module&key=' + encodeURIComponent(getKey()), {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ module_name: moduleName })
    });
    const j = await res.json();
    if (j.output) append(j.output);
    if (j.message) append(j.message);
    append("Result: " + (j.ok ? 'Success' : 'Failed'));
    listModules(); // Refresh the list
  } catch (e) {
    append("Error: " + e.message);
  }
}

checkStatus();
</script>
</body>
</html>
