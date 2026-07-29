<?php
// index.php - نسخه نهایی با لاگ‌گیری

error_reporting(E_ALL);
ini_set('display_errors', 1);

function logMessage($msg) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

logMessage("========== START ==========");

$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

logMessage("Input received");

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

logMessage("JSON decoded");

// ===== لود کردن فایل‌ها =====
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/game.php';
    require_once __DIR__ . '/commands.php';
    logMessage("All files loaded");
} catch (Exception $e) {
    logMessage("ERROR loading files: " . $e->getMessage());
    http_response_code(500);
    echo '{"ok":false}';
    exit;
}

// ===== پردازش =====
if (function_exists('processUpdate')) {
    logMessage("Calling processUpdate");
    try {
        processUpdate($update);
        logMessage("processUpdate done");
    } catch (Exception $e) {
        logMessage("processUpdate ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
} else {
    logMessage("processUpdate NOT FOUND");
}

http_response_code(200);
echo '{"ok":true}';
logMessage("========== END ==========");
