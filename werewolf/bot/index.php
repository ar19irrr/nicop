<?php
// ==================== لاگ ====================
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - START\n", FILE_APPEND);

// ==================== تست ساده ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "🐺 Werewolf Bot is running!";
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - GET request\n", FILE_APPEND);
    exit;
}

// ==================== دریافت داده از تلگرام ====================
$json = file_get_contents('php://input');
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Input: " . substr($json, 0, 200) . "\n", FILE_APPEND);

if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

// ==================== لود کردن فایل‌ها ====================
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/game.php';
    require_once __DIR__ . '/commands.php';
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - All files loaded\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo '{"ok":false}';
    exit;
}

// ==================== پردازش ====================
if (function_exists('processUpdate')) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Calling processUpdate\n", FILE_APPEND);
    processUpdate($update);
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Done\n", FILE_APPEND);
} else {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - processUpdate NOT FOUND\n", FILE_APPEND);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, "⚠️ خطا: تابع processUpdate پیدا نشد!");
    }
}

http_response_code(200);
echo '{"ok":true}';
