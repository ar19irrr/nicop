<?php
// index.php - همه چیز در یک فایل

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';

// ===== لاگ =====
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - START\n", FILE_APPEND);

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

// ===== پردازش =====
if (!isset($update['message'])) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$message = $update['message'];
$chat_id = $message['chat']['id'];
$text = $message['text'] ?? '';
$first_name = $message['from']['first_name'] ?? 'کاربر';
$chat_type = $message['chat']['type'] ?? 'private';

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Command: $text\n", FILE_APPEND);

// ===== پردازش دستورات =====
$text = preg_replace('/@' . $bot_username . '$/i', '', $text);
$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

$response = "";

switch ($command) {
    case '/start':
        $response = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعاله!\n\n📱 دستورات:\n/game - ساخت بازی\n/help - راهنما\n/ping - تست";
        break;
        
    case '/ping':
        $response = "🏓 Pong! زمان: " . date('H:i:s');
        break;
        
    case '/game':
        if ($chat_type == 'private') {
            $response = "❌ ساخت بازی فقط در گروه ممکن است!";
        } else {
            $code = generateGameCode();
            $response = "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>\n👤 سازنده: $first_name";
        }
        break;
        
    case '/help':
        $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/ping - تست اتصال";
        break;
        
    default:
        $response = "❌ دستور نامشخص!\nبرای راهنما /help را بزنید.";
        break;
}

// ===== ارسال پاسخ =====
if (!empty($response)) {
    sendMessage($chat_id, $response);
}

http_response_code(200);
echo '{"ok":true}';
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - END\n", FILE_APPEND);

// ============================================================
// توابع کمکی
// ============================================================

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function sendMessage($chat_id, $text) {
    global $token;
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
    
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Response sent\n", FILE_APPEND);
    return json_decode($result, true);
}
