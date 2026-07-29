<?php
// index.php - ساده برای تست

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Started\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "🐺 Werewolf Bot is running!";
    exit;
}

$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Input: " . substr($json, 0, 200) . "\n", FILE_APPEND);

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

// لود کردن فایل‌ها
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/game.php';
require_once __DIR__ . '/commands.php';

if (function_exists('processUpdate')) {
    processUpdate($update);
}

http_response_code(200);
echo '{"ok":true}';
