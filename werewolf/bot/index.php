<?php
// index.php - نسخه خطایابی کامل

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

// ===== تست بارگذاری فایل‌ها =====
logMessage("Loading config.php...");
require_once __DIR__ . '/config.php';
logMessage("config.php loaded");

logMessage("Loading functions.php...");
require_once __DIR__ . '/functions.php';
logMessage("functions.php loaded");

logMessage("Loading database.php...");
require_once __DIR__ . '/database.php';
logMessage("database.php loaded");

logMessage("Loading game.php...");
require_once __DIR__ . '/game.php';
logMessage("game.php loaded");

logMessage("Loading commands.php...");
require_once __DIR__ . '/commands.php';
logMessage("commands.php loaded");

// ===== پردازش =====
if (function_exists('processUpdate')) {
    logMessage("processUpdate exists, calling it...");
    processUpdate($update);
    logMessage("processUpdate done");
} else {
    logMessage("ERROR: processUpdate NOT FOUND!");
}

http_response_code(200);
echo '{"ok":true}';
logMessage("========== END ==========");
