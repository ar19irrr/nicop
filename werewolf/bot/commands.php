<?php
// commands.php - ساده برای تست
function processUpdate($update) {
    if (!isset($update['message'])) {
        return;
    }
    
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $first_name = $message['from']['first_name'] ?? 'کاربر';
    
    // لاگ برای دیباگ
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Command: $text\n", FILE_APPEND);
    
    if ($text === '/start') {
        sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه روی Render فعاله!");
    } elseif ($text === '/ping') {
        sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
    } else {
        sendMessage($chat_id, "❌ دستور نامشخص!\n/start - منو\n/ping - تست");
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
    curl_close($ch);
    
    return $result;
}
