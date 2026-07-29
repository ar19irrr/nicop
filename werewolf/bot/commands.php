<?php
// commands.php - نسخه نهایی

function processUpdate($update) {
    if (!isset($update['message'])) {
        return;
    }
    
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $first_name = $message['from']['first_name'] ?? 'کاربر';
    $chat_type = $message['chat']['type'] ?? 'private';
    
    if (empty($text)) {
        return;
    }
    
    $parts = explode(' ', $text);
    $command = strtolower($parts[0]);
    $param = $parts[1] ?? '';
    
    switch ($command) {
        case '/start':
            $msg = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعاله!\n\n📱 دستورات:\n/game - ساخت بازی\n/help - راهنما";
            sendMessage($chat_id, $msg);
            break;
            
        case '/ping':
            sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
            break;
            
        case '/game':
            if ($chat_type == 'private') {
                sendMessage($chat_id, "❌ ساخت بازی فقط در گروه!");
            } else {
                $code = generateGameCode();
                sendMessage($chat_id, "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>");
            }
            break;
            
        case '/help':
            sendMessage($chat_id, "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/ping - تست");
            break;
            
        default:
            sendMessage($chat_id, "❌ دستور نامشخص!\n/help - راهنما");
            break;
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
    
    return json_decode($result, true);
}
