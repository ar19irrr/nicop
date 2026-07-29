<?php
// commands.php - ساده‌ترین نسخه ممکن

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - COMMANDS.PHP LOADED!\n", FILE_APPEND);

function processUpdate($update) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - processUpdate called\n", FILE_APPEND);
    
    if (!isset($update['message'])) {
        file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - No message\n", FILE_APPEND);
        return;
    }
    
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $first_name = $message['from']['first_name'] ?? 'کاربر';
    
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Command: $text\n", FILE_APPEND);
    
    if ($text === '/start') {
        sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه روی Render فعاله!");
    } elseif ($text === '/ping') {
        sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
    } elseif ($text === '/game') {
        $code = generateGameCode();
        sendMessage($chat_id, "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>");
    } else {
        sendMessage($chat_id, "❌ دستور نامشخص!\n/start - منو\n/ping - تست\n/game - ساخت بازی");
    }
}
