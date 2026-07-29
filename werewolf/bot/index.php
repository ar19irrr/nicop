<?php
// index.php - نسخه نهایی

// ===== لاگ =====
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - ========== START ==========\n", FILE_APPEND);

// ===== دریافت داده =====
$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Input received\n", FILE_APPEND);

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - JSON decoded\n", FILE_APPEND);

// ===== لود کردن فایل‌ها =====
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/game.php';
    require_once __DIR__ . '/commands.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - All files loaded\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo '{"ok":false}';
    exit;
}

// ===== پردازش =====
if (function_exists('processUpdate')) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Calling processUpdate\n", FILE_APPEND);
    processUpdate($update);
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Done\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - processUpdate NOT FOUND\n", FILE_APPEND);
}

http_response_code(200);
echo '{"ok":true}';
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - ========== END ==========\n", FILE_APPEND);
