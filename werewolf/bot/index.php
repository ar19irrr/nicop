<?php
// index.php - ورودی اصلی برای Render

error_reporting(E_ALL);
ini_set('display_errors', 1);

// دریافت داده از تلگرام
$json = file_get_contents('php://input');

// اگه درخواست از مرورگر بود (برای تست)
if (empty($json)) {
    echo "🐺 Werewolf Bot is running!";
    exit;
}

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

// پردازش
if (function_exists('processUpdate')) {
    processUpdate($update);
} else {
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, "⚠️ خطا: تابع processUpdate پیدا نشد!");
    }
}

http_response_code(200);
echo '{"ok":true}';
