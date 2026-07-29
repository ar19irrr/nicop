<?php
// index.php - نسخه با دستورات ساده

$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . "\n" . $json . "\n---\n", FILE_APPEND);

$update = json_decode($json, true);
if (!$update || !isset($update['message'])) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$message = $update['message'];
$chat_id = $message['chat']['id'];
$text = $message['text'] ?? '';
$first_name = $message['from']['first_name'] ?? 'کاربر';

// پردازش دستورات ساده
$response = "";
switch ($text) {
    case '/start':
        $response = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعال است!\n\n📱 دستورات:\n/game - ساخت بازی\n/help - راهنما";
        break;
        
    case '/ping':
        $response = "🏓 Pong! زمان: " . date('H:i:s');
        break;
        
    case '/game':
        $code = generateGameCode();
        $response = "🐺 بازی جدید ساخته شد!\n🎲 کد: <code>$code</code>\n👤 سازنده: $first_name\n\n📌 برای پیوستن: /join $code";
        break;
        
    case '/help':
        $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/ping - تست اتصال";
        break;
        
    default:
        if (strpos($text, '/join') === 0) {
            $code = trim(str_replace('/join', '', $text));
            if (empty($code)) {
                $response = "❌ کد بازی را وارد کنید!\nمثال: /join AB12CD";
            } else {
                $response = "✅ به بازی با کد <code>" . strtoupper(trim($code)) . "</code> پیوستید!";
            }
        } else {
            $response = "❌ دستور نامشخص!\nبرای راهنما /help را بزنید.";
        }
        break;
}

// ارسال پاسخ
sendMessage($chat_id, $response);

http_response_code(200);
echo '{"ok":true}';

// ===== توابع =====

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
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
    
    file_put_contents('telegram_log.txt', "Response: " . substr($result, 0, 200) . "\n", FILE_APPEND);
    return $result;
}
