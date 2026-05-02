<?php
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {
    case 'install':      runInstall($input);      break;
    case 'create_admin': createAdmin($input);     break;
    default: echo json_encode(['success' => false, 'error' => 'Unknown action']); exit;
}

function runInstall($input) {
    $steps = [];

    // Test connection
    try {
        $dsn = "mysql:host={$input['host']};port={$input['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $input['user'], $input['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $steps[] = ['type' => 'ok', 'msg' => 'Database connection successful.'];
    } catch (Exception $e) {
        $steps[] = ['type' => 'err', 'msg' => 'Connection failed: ' . $e->getMessage()];
        echo json_encode(['success' => false, 'steps' => $steps]); exit;
    }

    // Create database
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$input['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$input['name']}`");
        $steps[] = ['type' => 'ok', 'msg' => "Database '{$input['name']}' ready."];
    } catch (Exception $e) {
        $steps[] = ['type' => 'err', 'msg' => 'Failed to create database: ' . $e->getMessage()];
        echo json_encode(['success' => false, 'steps' => $steps]); exit;
    }

    // Create tables
    $sql = file_get_contents('../setup.sql');
    // Strip USE and CREATE DATABASE lines since we already handled them
    $sql = preg_replace('/^(CREATE DATABASE|USE).+;/im', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try { $pdo->exec($stmt); }
        catch (Exception $e) { /* ignore duplicate/already-exists */ }
    }
    $steps[] = ['type' => 'ok', 'msg' => 'Tables and seed data installed successfully.'];

    // Write config
    $config = "<?php\ndefine('DB_HOST', '{$input['host']}');\ndefine('DB_USER', '{$input['user']}');\ndefine('DB_PASS', '{$input['pass']}');\ndefine('DB_NAME', '{$input['name']}');\n";
    file_put_contents(__DIR__ . '/../includes/db_config.php', $config);
    $steps[] = ['type' => 'ok', 'msg' => 'Configuration saved.'];

    echo json_encode(['success' => true, 'steps' => $steps]);
}

function createAdmin($input) {
    $steps = [];
    require_once '../includes/db.php';

    $firstName = trim($input['first_name'] ?? '');
    $lastName  = trim($input['last_name'] ?? '');
    $email     = trim($input['email'] ?? '');
    $password  = $input['password'] ?? '';

    if (!$firstName || !$email || strlen($password) < 8) {
        $steps[] = ['type' => 'err', 'msg' => 'All fields required and password must be 8+ characters.'];
        echo json_encode(['success' => false, 'steps' => $steps]); exit;
    }

    try {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $initials = strtoupper(substr($firstName,0,1) . substr($lastName,0,1));
        $stmt = $db->prepare("INSERT INTO users (first_name,last_name,email,password_hash,role,avatar_initials) VALUES (?,?,?,?,'admin',?) ON DUPLICATE KEY UPDATE password_hash=?, role='admin'");
        $stmt->execute([$firstName, $lastName, $email, $hash, $initials, $hash]);
        $steps[] = ['type' => 'ok', 'msg' => "Admin account created for {$email}."];
        echo json_encode(['success' => true, 'steps' => $steps]);
    } catch (Exception $e) {
        $steps[] = ['type' => 'err', 'msg' => 'Failed: ' . $e->getMessage()];
        echo json_encode(['success' => false, 'steps' => $steps]);
    }
}
