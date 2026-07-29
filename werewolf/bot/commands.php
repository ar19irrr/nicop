<?php
// commands.php - پردازش کامل دستورات

function processUpdate($update) {
    logMessage("Entered processUpdate");
    
    if (!isset($update['message'])) {
        logMessage("No message");
        return;
    }
    
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $text = $message['text'] ?? '';
    $first_name = $message['from']['first_name'] ?? 'کاربر';
    $chat_type = $message['chat']['type'] ?? 'private';
    
    logMessage("Command: $text | Type: $chat_type | User: $user_id");
    
    if (empty($text)) {
        return;
    }
    
    // حذف @username
    $text = preg_replace('/@' . BOT_USERNAME . '$/i', '', $text);
    $parts = explode(' ', $text);
    $command = strtolower($parts[0]);
    $param = $parts[1] ?? '';
    
    logMessage("Command: $command | Param: $param");
    
    switch ($command) {
        case '/start':
            cmdStart($chat_id, $first_name);
            break;
            
        case '/ping':
            sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
            break;
            
        case '/game':
            cmdGame($chat_id, $first_name, $chat_type, $user_id);
            break;
            
        case '/join':
            cmdJoin($chat_id, $param, $user_id, $first_name);
            break;
            
        case '/players':
            cmdPlayers($chat_id, $user_id);
            break;
            
        case '/startgame':
            cmdStartGame($chat_id, $user_id);
            break;
            
        case '/stop':
        case '/cancel':
            cmdCancel($chat_id, $user_id);
            break;
            
        case '/leave':
            cmdLeave($chat_id, $user_id);
            break;
            
        case '/help':
            cmdHelp($chat_id);
            break;
            
        default:
            sendMessage($chat_id, "❌ دستور نامشخص!\nبرای راهنما /help را بزنید.");
            break;
    }
}

// ===== توابع دستورات =====

function cmdStart($chat_id, $first_name) {
    $msg = "👋 سلام <b>" . htmlspecialchars($first_name) . "</b>!\n";
    $msg .= "🐺 به ربات گرگینه خوش آمدید!\n\n";
    $msg .= "📱 دستورات:\n";
    $msg .= "/game - ساخت بازی (گروه)\n";
    $msg .= "/join [کد] - پیوستن به بازی\n";
    $msg .= "/players - لیست بازیکنان\n";
    $msg .= "/startgame - شروع بازی\n";
    $msg .= "/stop - لغو بازی\n";
    $msg .= "/leave - خروج از بازی\n";
    $msg .= "/help - راهنما\n";
    $msg .= "/ping - تست اتصال";
    sendMessage($chat_id, $msg);
}

function cmdGame($chat_id, $first_name, $chat_type, $user_id) {
    if ($chat_type == 'private') {
        sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        return;
    }
    
    $result = createGame($chat_id, $user_id, $first_name);
    sendMessage($chat_id, $result['message']);
}

function cmdJoin($chat_id, $code, $user_id, $first_name) {
    if (empty($code)) {
        sendMessage($chat_id, "❌ کد بازی را وارد کنید!\nمثال: /join AB12CD");
        return;
    }
    
    $code = strtoupper(trim($code));
    $result = joinGame($code, $user_id, $first_name);
    sendMessage($chat_id, $result['message']);
}

function cmdPlayers($chat_id, $user_id) {
    $game = getGroupActiveGame($chat_id);
    if (!$game) {
        sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        return;
    }
    
    $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    
    foreach ($game['players'] as $p) {
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $you = ($p['id'] == $user_id) ? ' (شما)' : '';
        $msg .= "• {$p['name']} $creator$you\n";
    }
    
    sendMessage($chat_id, $msg);
}

function cmdStartGame($chat_id, $user_id) {
    $result = startGame($chat_id, $user_id);
    sendMessage($chat_id, $result['message']);
}

function cmdCancel($chat_id, $user_id) {
    $result = cancelGame($chat_id, $user_id);
    sendMessage($chat_id, $result['message']);
}

function cmdLeave($chat_id, $user_id) {
    $result = leaveGame($user_id, $chat_id);
    sendMessage($chat_id, $result['message']);
}

function cmdHelp($chat_id) {
    $msg = "📚 <b>راهنمای ربات گرگینه</b>\n\n";
    $msg .= "/start - منوی اصلی\n";
    $msg .= "/game - ساخت بازی جدید (فقط در گروه)\n";
    $msg .= "/join [کد] - پیوستن به بازی با کد\n";
    $msg .= "/players - لیست بازیکنان\n";
    $msg .= "/startgame - شروع بازی (حداقل ۴ نفر)\n";
    $msg .= "/stop - لغو بازی\n";
    $msg .= "/leave - خروج از بازی\n";
    $msg .= "/ping - تست اتصال\n";
    $msg .= "/help - این راهنما";
    sendMessage($chat_id, $msg);
}

// ===== توابع کمکی =====

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
    
    logMessage("sendMessage result: " . substr($result, 0, 100));
    return $result;
}

function logMessage($msg) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}
