<?php
// index.php - مرحله 5 و 6: فاز شب + پنل شب

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';

// ============================================================
// دیتابیس
// ============================================================

function loadGames() {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'games.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveGames($games) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'games.json';
    file_put_contents($file, json_encode($games, JSON_PRETTY_PRINT));
}

// ============================================================
// توابع بازی
// ============================================================

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function createGame($group_id, $creator_id, $creator_name) {
    $games = loadGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return ['success' => false, 'message' => '⏳ یک بازی فعال در این گروه وجود دارد!'];
        }
    }
    
    $code = generateGameCode();
    $games[$code] = [
        'code' => $code,
        'group_id' => $group_id,
        'creator_id' => $creator_id,
        'creator_name' => $creator_name,
        'players' => [
            ['id' => $creator_id, 'name' => $creator_name, 'alive' => true, 'role' => null]
        ],
        'status' => 'waiting',
        'created' => time(),
        'phase' => null,
        'night_count' => 0,
        'day_count' => 0,
        'night_actions' => []
    ];
    saveGames($games);
    
    return ['success' => true, 'message' => "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>\n👤 سازنده: $creator_name"];
}

function joinGame($code, $user_id, $user_name) {
    $games = loadGames();
    if (!isset($games[$code])) {
        return ['success' => false, 'message' => '❌ بازی با این کد پیدا نشد!'];
    }
    $game = $games[$code];
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ این بازی قبلاً شروع شده!'];
    }
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
        }
    }
    $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
    $games[$code] = $game;
    saveGames($games);
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر"];
}

function getGameInfo($group_id) {
    $games = loadGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
}

function startGame($group_id, $user_id) {
    $games = loadGames();
    $game = null;
    $game_code = null;
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $group_id && $g['status'] == 'waiting') {
            $game = $g;
            $game_code = $code;
            break;
        }
    }
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی برای شروع وجود ندارد!'];
    }
    if (count($game['players']) < 4) {
        return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
    }
    
    $roles = ['villager', 'villager', 'werewolf', 'seer'];
    while (count($roles) < count($game['players'])) {
        $roles[] = 'villager';
    }
    shuffle($roles);
    
    foreach ($game['players'] as $i => &$p) {
        $p['role'] = $roles[$i];
    }
    
    $game['status'] = 'started';
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    $game['night_actions'] = [];
    $games[$game_code] = $game;
    saveGames($games);
    
    foreach ($game['players'] as $p) {
        sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . getRoleDisplayName($p['role']) . "</b>\n\n🌙 شب اول شروع شد...");
        sendNightPanel($p, $game);
    }
    
    sendMessage($group_id, "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ ۶۰ ثانیه تا صبح");
    
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>\n\n👥 " . count($game['players']) . " نفر\n🌙 شب اول..."];
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $nightRoles = ['werewolf', 'seer'];
    
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n💤 تو می‌تونی بخوابی...");
        return;
    }
    
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
    });
    
    foreach ($alivePlayers as $p) {
        if ($role == 'werewolf' && $p['role'] == 'werewolf') continue;
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
        return;
    }
    
    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: " . getRoleDisplayName($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => $role . '_' . $target['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function getRoleDisplayName($role) {
    $names = [
        'villager' => '👨‍🌾 روستایی ساده',
        'seer' => '👳🏻‍♂️ پیشگو',
        'werewolf' => '🐺 گرگینه',
        'guardian_angel' => '👼🏻 فرشته نگهبان',
        'hunter' => '👮🏻‍♂️ کلانتر'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getRules() {
    return "📜 <b>قوانین بازی گرگینه</b>\n\n" .
           "🌙 <b>شب:</b> گرگ‌ها یک نفر را می‌خورند، پیشگو نقش یک نفر را می‌بیند.\n" .
           "☀️ <b>روز:</b> همه بحث می‌کنند و به یک نفر رأی می‌دهند.\n" .
           "🏆 <b>برد:</b> گرگ‌ها باید همه روستایی‌ها را بخورند، روستایی‌ها باید همه گرگ‌ها را پیدا کنند.";
}

function getRolesList() {
    return "🎭 <b>نقش‌های بازی</b>\n\n" .
           "🐺 گرگینه - هر شب یک نفر را می‌خورد\n" .
           "👳🏻‍♂️ پیشگو - هر شب نقش یک نفر را می‌بیند\n" .
           "👨‍🌾 روستایی - در روز رأی می‌دهد\n" .
           "👼🏻 فرشته نگهبان - هر شب از یک نفر محافظت می‌کند\n" .
           "👮🏻‍♂️ کلانتر - اگر بمیرد، می‌تواند به یک نفر شلیک کند";
}

function sendPrivateMessage($user_id, $text, $keyboard = null) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $user_id, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    
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

// ============================================================
// پردازش اصلی
// ============================================================

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - START\n", FILE_APPEND);

$json = file_get_contents('php://input');
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

// ===== پردازش دکمه‌های شیشه‌ای =====
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    // پردازش اکشن شب
    $parts = explode('_', $data);
    if (count($parts) == 2 && in_array($parts[0], ['werewolf', 'seer'])) {
        $role = $parts[0];
        $target_id = (int)$parts[1];
        
        $games = loadGames();
        $game = null;
        $game_code = null;
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && in_array($g['status'], ['started']) && $g['phase'] == 'night') {
                    $game = $g;
                    $game_code = $code;
                    break 2;
                }
            }
        }
        
        if ($game) {
            $game['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => $target_id];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "✅ انتخاب شما ثبت شد!");
            sendPrivateMessage($user_id, "✅ انتخاب شما برای شب " . $game['night_count'] . " ثبت شد.");
        } else {
            answerCallbackQuery($callback_id, "❌ خطا: بازی پیدا نشد!", true);
        }
        
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    // دکمه‌های منو
    $response = "";
    switch ($data) {
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و دستور /game را بزنید."; break;
        case 'join_menu': $response = "🔗 کد بازی را وارد کنید:\nمثال: /join AB12CD"; break;
        case 'rules': $response = getRules(); break;
        case 'roles': $response = getRolesList(); break;
        case 'help': $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join [کد] - پیوستن\n/players - لیست بازیکنان\n/startgame - شروع بازی\n/ping - تست"; break;
        case 'stats':
            $games = loadGames();
            $total = count($games);
            $waiting = count(array_filter($games, fn($g) => $g['status'] == 'waiting'));
            $started = count(array_filter($games, fn($g) => $g['status'] == 'started'));
            $response = "📊 <b>آمار ربات</b>\n\n" . "🎮 کل بازی‌ها: $total\n" . "⏳ در انتظار: $waiting\n" . "▶️ در حال اجرا: $started";
            break;
        default: $response = "✅ دکمه $data فشار داده شد!"; break;
    }
    
    answerCallbackQuery($callback_id, $response);
    sendMessage($chat_id, $response);
    
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

if (!isset($update['message'])) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$text = $message['text'] ?? '';
$first_name = $message['from']['first_name'] ?? 'کاربر';
$chat_type = $message['chat']['type'] ?? 'private';

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

switch ($command) {
    case '/start':
        $msg = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعاله!\n\n📱 یکی رو انتخاب کن:";
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'],
                    ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']
                ],
                [
                    ['text' => '📜 قوانین', 'callback_data' => 'rules'],
                    ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
                ],
                [
                    ['text' => '❓ راهنما', 'callback_data' => 'help'],
                    ['text' => '📊 آمار', 'callback_data' => 'stats']
                ]
            ]
        ];
        sendMessage($chat_id, $msg, $keyboard);
        break;
        
    case '/game':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/join':
        if (empty($param)) {
            sendMessage($chat_id, "❌ کد بازی را وارد کنید!\nمثال: /join AB12CD");
        } else {
            $code = strtoupper(trim($param));
            $result = joinGame($code, $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/players':
        $game = getGameInfo($chat_id);
        if (!$game) {
            sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        } else {
            $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
            $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
            foreach ($game['players'] as $p) {
                $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
                $you = ($p['id'] == $user_id) ? ' (شما)' : '';
                $msg .= "• {$p['name']} $creator$you\n";
            }
            sendMessage($chat_id, $msg);
        }
        break;
        
    case '/startgame':
        $result = startGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/ping':
        sendMessage($chat_id, "🏓 Pong! زمان: " . date('H:i:s'));
        break;
        
    default:
        sendMessage($chat_id, "❌ دستور نامشخص!\nبرای راهنما /help را بزنید.");
        break;
}

http_response_code(200);
echo '{"ok":true}';

// ============================================================
// توابع کمکی
// ============================================================

function sendMessage($chat_id, $text, $keyboard = null) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);
    
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

function answerCallbackQuery($callback_id, $text, $show_alert = false) {
    global $token;
    $url = "https://api.telegram.org/bot$token/answerCallbackQuery";
    $data = ['callback_query_id' => $callback_id, 'text' => $text, 'show_alert' => $show_alert];
    
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
