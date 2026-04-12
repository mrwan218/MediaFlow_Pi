<?php
require_once 'config.php';
if (!is_admin()) {
    http_response_code(403);
    die('Forbidden: You do not have permission to access this page.');
}

// --- Fix: Dynamic Node.js path detection ---
$os = strtoupper(substr(PHP_OS, 0, 3));
if ($os === 'WIN') {
    define('NODE_EXECUTABLE_PATH', 'node'); 
} else {
    define('NODE_EXECUTABLE_PATH', 'node');
}

define('SCANNER_SCRIPT_PATH', 'scanner.js');
define('LOG_FILE_PATH', __DIR__ . '/backend/scan.log');
$errors = [];

// Check if scanner script exists
if (!file_exists(__DIR__ . '/backend/' . SCANNER_SCRIPT_PATH)) { 
    $errors[] = 'Scanner script not found at: ' . SCANNER_SCRIPT_PATH; 
}

if (!empty($errors)) {
    $redirect_url = 'admin_dashboard.php?error=' . urlencode($errors[0]);
    header('location: ' . $redirect_url);
    exit();
}

if ($os === 'WIN') {
    $command = 'start /B ' . NODE_EXECUTABLE_PATH . ' ' . SCANNER_SCRIPT_PATH . ' > ' . LOG_FILE_PATH . ' 2>&1';
} else {
    $command = NODE_EXECUTABLE_PATH . ' ' . SCANNER_SCRIPT_PATH . ' > ' . LOG_FILE_PATH . ' 2>&1 &';
}

$original_dir = getcwd();
chdir(__DIR__ . '/backend');
$log_header = "\n\n=== SCAN STARTED: " . date('Y-m-d H:i:s') . " ===\n";
file_put_contents(LOG_FILE_PATH, $log_header, FILE_APPEND);
shell_exec($command);
chdir($original_dir);
header('location: admin_dashboard.php?msg=scan_started');
exit();
?>
