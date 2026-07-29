<?php
// commands.php - نسخه خطایابی شده

function processUpdate($update) {
    // لاگ برای اطمینان از ورود به تابع
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Entered processUpdate\n", FILE_APPEND);

    if (!isset($update['message'])) {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - No message\n", FILE_APPEND);
        return;
    }
    
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $first_name = $message['from']['first_name'] ?? 'کاربر';
    
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Command: $text\n", FILE_APPEND);
    
    try {
        if ($text === '/start') {
            sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه روی Render فعاله!");
        } elseif ($text === '/ping') {
            sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
        } else {
            sendMessage($chat_id, "❌ دستور نامشخص!\n/start - منو\n/ping - تست");
        }
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Message sent\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

function sendMessage($chat_id, $text) {
    $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - sendMessage result: " . substr($result, 0, 200) . "\n", FILE_APPEND);
    if ($error) {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - cURL Error: $error\n", FILE_APPEND);
    }
    
    return json_decode($result, true);
}
