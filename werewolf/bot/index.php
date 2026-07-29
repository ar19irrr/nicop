<?php
// index.php - نسخه نهایی با تایمر ۵ دقیقه، دکمه‌های شیشه‌ای و extend نامحدود برای ادمین

// ============================================================
// 1. تنظیمات اولیه
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

// ============================================================
// 2. دیتابیس
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

function getGame($code) {
    $games = loadGames();
    return $games[$code] ?? null;
}

function getGroupActiveGame($group_id) {
    $games = loadGames();
    foreach ($games as $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
}

function getPlayerActiveGame($user_id) {
    $games = loadGames();
    foreach ($games as $game) {
        if (!in_array($game['status'], ['waiting', 'started'])) continue;
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) return $game;
        }
    }
    return null;
}

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// ============================================================
// 3. سیستم سکه
// ============================================================

function getCoin($user_id) {
    $coins = loadCoins();
    return $coins[$user_id] ?? 0;
}

function loadCoins() {
    global $data_path;
    $file = $data_path . 'coins.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveCoins($coins) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'coins.json';
    file_put_contents($file, json_encode($coins, JSON_PRETTY_PRINT));
}

function addCoin($user_id, $amount) {
    $coins = loadCoins();
    $coins[$user_id] = ($coins[$user_id] ?? 0) + $amount;
    saveCoins($coins);
    return $coins[$user_id];
}

// ============================================================
// 4. سیستم درجه‌بندی
// ============================================================

function loadRanks() {
    global $data_path;
    $file = $data_path . 'ranks.json';
    if (!file_exists($file)) {
        if (!is_dir($data_path)) mkdir($data_path, 0777, true);
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveRanks($ranks) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'ranks.json';
    file_put_contents($file, json_encode($ranks, JSON_PRETTY_PRINT));
}

function getRankName($rank) {
    $names = [
        1 => 'نوب پلیر 😣',
        2 => 'پلیر 😕',
        3 => 'روستایی 👨🏻',
        4 => 'روستایی پررو 😌',
        5 => 'دلبر روستا 👰🏻',
        6 => 'کلانتر روستا 👮🏻‍♂️',
        7 => 'پیشگو روستا 👳🏻‍♂️',
        8 => 'فرشته روستا 👼🏻',
        9 => 'شکارچی روستا 💂🏻‍♂️',
        10 => 'پررو پلیر روستا 👽'
    ];
    return $names[$rank] ?? 'افسانه‌ای 🏆';
}

function getXPForNextRank($rank) {
    return $rank * 100;
}

function addXP($user_id, $amount) {
    $ranks = loadRanks();
    if (!isset($ranks[$user_id])) {
        $ranks[$user_id] = ['xp' => 0, 'rank' => 1];
    }
    $old_rank = $ranks[$user_id]['rank'];
    $ranks[$user_id]['xp'] += $amount;
    $new_rank = $old_rank;
    while ($ranks[$user_id]['xp'] >= getXPForNextRank($new_rank)) {
        $ranks[$user_id]['xp'] -= getXPForNextRank($new_rank);
        $new_rank++;
    }
    $ranks[$user_id]['rank'] = $new_rank;
    saveRanks($ranks);
    return ['old_rank' => $old_rank, 'new_rank' => $new_rank, 'ranked_up' => $new_rank > $old_rank];
}

function getRankInfo($user_id) {
    $ranks = loadRanks();
    $data = $ranks[$user_id] ?? ['xp' => 0, 'rank' => 1];
    $rank = $data['rank'];
    $xp = $data['xp'];
    $next_xp = getXPForNextRank($rank);
    return ['rank' => $rank, 'xp' => $xp, 'next_xp' => $next_xp, 'rank_name' => getRankName($rank)];
}

function sendRankUpMessage($user_id, $old_rank, $new_rank, $user_name = 'کاربر عزیز') {
    $old_name = getRankName($old_rank);
    $new_name = getRankName($new_rank);
    $msg = "☀️☀️بهت کلی تبریک میگم میدونی چرا؟\n";
    $msg .= "چون ارتقا درجه پیدا کردی 🎖\n";
    $msg .= "به دستور <b>{$user_name}</b> 👑 تو از درجه <b>{$old_name}</b> به درجه <b>{$new_name}</b> ارتقا پیدا کردی.🏅\n";
    $msg .= "حالا برو جلوی دوستات پز بده 👬";
    sendMessage($user_id, $msg);
}

function addXPAfterGame($game) {
    $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    $dead = array_filter($game['players'], fn($p) => !($p['alive'] ?? false));
    foreach ($alive as $p) {
        $result = addXP($p['id'], 50);
        if ($result['ranked_up']) {
            sendRankUpMessage($p['id'], $result['old_rank'], $result['new_rank'], $p['name']);
        }
    }
    foreach ($dead as $p) {
        $result = addXP($p['id'], 20);
        if ($result['ranked_up']) {
            sendRankUpMessage($p['id'], $result['old_rank'], $result['new_rank'], $p['name']);
        }
    }
}

// ============================================================
// 5. توابع اصلی بازی
// ============================================================

function createGame($group_id, $creator_id, $creator_name, $mode = 'normal') {
    $games = loadGames();
    
    foreach ($games as $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return ['success' => false, 'message' => '⏳ یک بازی فعال در این گروه وجود دارد!'];
        }
    }
    
    $code = generateGameCode();
    $modes = [
        'normal' => 'عادی',
        'easy' => 'آسان 🎈',
        'mafia' => 'مافیا 🐺',
        'vampire' => 'ومپایری 🧛‍♂️',
        'werewolf' => 'ورولف 🐺',
        'bomber' => 'بمب‌گذار 💣',
        'foolish' => 'احمقانه 🃏',
        'mighty' => 'قدرتی ♨️',
        'romantic' => 'عاشقانه 👨‍❤️‍👨',
        'coin' => 'سکه‌ای 💰'
    ];
    $mode_name = $modes[$mode] ?? 'عادی';
    
    $games[$code] = [
        'code' => $code,
        'group_id' => $group_id,
        'creator_id' => $creator_id,
        'creator_name' => $creator_name,
        'mode' => $mode,
        'mode_name' => $mode_name,
        'players' => [
            ['id' => $creator_id, 'name' => $creator_name, 'alive' => true, 'role' => null]
        ],
        'status' => 'waiting',
        'created' => time(),
        'wait_until' => time() + 300,
        'extend_count' => 0,
        'phase' => null,
        'night_count' => 0,
        'day_count' => 0,
        'night_actions' => [],
        'votes' => [],
        'night_end_time' => 0,
        'day_end_time' => 0,
        'vote_end_time' => 0,
        'day_duration' => 60,
        'night_duration' => 60,
        'vote_duration' => 60
    ];
    
    saveGames($games);
    
    $remaining = 300;
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🎮 <b>بازی {$mode_name} ساخته شد!</b>\n\n";
    $msg .= "🎲 کد: <code>$code</code>\n";
    $msg .= "👤 سازنده: $creator_name\n";
    $msg .= "👥 تعداد: ۱ نفر\n\n";
    $msg .= "⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds) . "\n\n";
    $msg .= "👇 برای ورود به بازی روی دکمه زیر کلیک کن:";
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $code]]
        ]
    ];
    
    sendMessage($group_id, $msg, $keyboard);
    
    return ['success' => true, 'message' => $msg, 'code' => $code];
}

function startGame($group_id, $user_id) {
    $games = loadGames();
    $game = null;
    $game_code = null;
    
    foreach ($games as $code => $g) {
        if (isset($g['group_id']) && $g['group_id'] == $group_id && $g['status'] == 'waiting') {
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
    $game['votes'] = [];
    $game['night_end_time'] = time() + $game['night_duration'];
    $games[$game_code] = $game;
    saveGames($games);
    
    foreach ($game['players'] as $p) {
        $role_name = getRoleDisplayName($p['role']);
        sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n" .
            getRoleDescription($p['role']) . "\n\n🌙 شب اول شروع شد...");
    }
    
    sendMessage($group_id, "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>"];
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
    if (time() > $game['wait_until']) {
        return ['success' => false, 'message' => '⏰ زمان انتظار تمام شده!'];
    }
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
        }
    }
    $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
    $games[$code] = $game;
    saveGames($games);
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!", 'game' => $game];
}

function cancelGame($chat_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            unset($games[$code]);
            saveGames($games);
            return ['success' => true, 'message' => '❌ بازی لغو شد!'];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای لغو وجود ندارد!'];
}

function leaveGame($chat_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            foreach ($game['players'] as $key => $p) {
                if ($p['id'] == $user_id) {
                    unset($game['players'][$key]);
                    $game['players'] = array_values($game['players']);
                    if (empty($game['players'])) {
                        unset($games[$code]);
                    } else {
                        $games[$code] = $game;
                    }
                    saveGames($games);
                    return ['success' => true, 'message' => '✅ از بازی خارج شدید!'];
                }
            }
        }
    }
    return ['success' => false, 'message' => '❌ شما در هیچ بازی فعالی نیستید!'];
}

// ============================================================
// 6. تابع تمدید زمان (extend) - بدون محدودیت، فقط ادمین
// ============================================================

function isAdmin($user_id, $group_id) {
    global $token, $admin_id;
    
    // ادمین اصلی
    if ($user_id == $admin_id) {
        return true;
    }
    
    // چک کردن با API تلگرام
    $url = "https://api.telegram.org/bot" . $token . "/getChatMember";
    $data = ['chat_id' => $group_id, 'user_id' => $user_id];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($response && isset($response['ok']) && $response['ok']) {
        $status = $response['result']['status'] ?? 'member';
        return in_array($status, ['creator', 'administrator']);
    }
    
    return false;
}

function extendWaitingTime($group_id, $user_id) {
    $games = loadGames();
    
    // چک کن که کاربر ادمین هست
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین‌های گروه می‌توانند زمان را تمدید کنند!'];
    }
    
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && $game['status'] == 'waiting') {
            // تمدید ۳۰ ثانیه (بدون محدودیت)
            $game['wait_until'] += 30;
            $games[$code] = $game;
            saveGames($games);
            
            $remaining = $game['wait_until'] - time();
            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;
            
            return [
                'success' => true, 
                'message' => "⏱ زمان ۳۰ ثانیه تمدید شد!\n⏳ باقیمانده: $minutes:" . sprintf("%02d", $seconds)
            ];
        }
    }
    
    return ['success' => false, 'message' => '❌ بازی فعالی برای تمدید وجود ندارد!'];
}

// ============================================================
// 7. توابع نقش‌ها و کمکی
// ============================================================

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

function getRoleDescription($role) {
    $desc = [
        'villager' => '👨‍🌾 شما یک روستایی ساده هستید. در روز رأی می‌دهید.',
        'seer' => '👳🏻‍♂️ شما پیشگو هستید! هر شب نقش یک نفر را می‌بینید.',
        'werewolf' => '🐺 شما گرگینه هستید! هر شب یک نفر را می‌خورید.',
        'guardian_angel' => '👼🏻 شما فرشته نگهبان هستید! هر شب از یک نفر محافظت می‌کنید.',
        'hunter' => '👮🏻‍♂️ شما کلانتر هستید! اگر بمیرید، می‌توانید به یک نفر شلیک کنید.'
    ];
    return $desc[$role] ?? '🎭 شما ' . getRoleDisplayName($role) . ' هستید!';
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

function sendPlayerList($chat_id, $game) {
    $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    foreach ($game['players'] as $p) {
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "• {$p['name']} $creator\n";
    }
    $remaining = max(0, $game['wait_until'] - time());
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    $msg .= "\n⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds);
    $msg .= "\n\n⏰ برای تمدید ۳۰ ثانیه: /extend (فقط ادمین)";
    sendMessage($chat_id, $msg);
}

function getGameModes() {
    return [
        'normal' => ['name' => 'عادی', 'cmd' => '/startgame'],
        'easy' => ['name' => 'آسان 🎈', 'cmd' => '/starteasy'],
        'mafia' => ['name' => 'مافیا 🐺', 'cmd' => '/startmafia'],
        'vampire' => ['name' => 'ومپایری 🧛‍♂️', 'cmd' => '/startvampire'],
        'werewolf' => ['name' => 'ورولف 🐺', 'cmd' => '/startwerewolf'],
        'bomber' => ['name' => 'بمب‌گذار 💣', 'cmd' => '/startbomber'],
        'foolish' => ['name' => 'احمقانه 🃏', 'cmd' => '/startfoolish'],
        'mighty' => ['name' => 'قدرتی ♨️', 'cmd' => '/startmighty'],
        'romantic' => ['name' => 'عاشقانه 👨‍❤️‍👨', 'cmd' => '/startromantic'],
        'coin' => ['name' => 'سکه‌ای 💰', 'cmd' => '/startcoin']
    ];
}

// ============================================================
// 8. توابع ارسال پیام
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

// ============================================================
// 9. پردازش اصلی
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

// ===== چک کردن تایمرهای خودکار =====
function checkGameTimers() {
    $games = loadGames();
    $now = time();
    
    foreach ($games as $code => $game) {
        // بازی در حالت انتظار - چک کردن پایان زمان
        if ($game['status'] == 'waiting' && isset($game['wait_until']) && $now >= $game['wait_until']) {
            // زمان تمام شده - بررسی تعداد بازیکنان
            if (count($game['players']) >= 4) {
                // شروع خودکار بازی
                $result = startGame($game['group_id'], $game['creator_id']);
                if ($result['success']) {
                    sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! بازی با " . count($game['players']) . " نفر شروع می‌شود...");
                }
            } else {
                // لغو بازی به دلیل کمبود بازیکن
                sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! تعداد بازیکنان کافی نیست (" . count($game['players']) . "/4)");
                deleteGame($code);
                sendMessage($game['group_id'], "❌ بازی لغو شد!");
            }
        }
        
        // بازی در حال اجرا - چک کردن فازها
        if ($game['status'] == 'started') {
            if ($game['phase'] == 'night' && isset($game['night_end_time']) && $now >= $game['night_end_time']) {
                // پایان شب - شروع روز
                // اینجا باید processNight صدا زده بشه
                processNight($code, $game);
                sendMessage($game['group_id'], "⏰ شب به پایان رسید! صبح شد...");
            }
            elseif ($game['phase'] == 'day' && isset($game['day_end_time']) && $now >= $game['day_end_time']) {
                // پایان روز - شروع رأی‌گیری
                startVoting($game);
                sendMessage($game['group_id'], "⏰ زمان بحث تمام شد! رأی‌گیری شروع می‌شود...");
            }
            elseif ($game['phase'] == 'vote' && isset($game['vote_end_time']) && $now >= $game['vote_end_time']) {
                // پایان رأی‌گیری
                processVotes($code, $game);
            }
        }
    }
}

// اجرای چک تایمرها
checkGameTimers();

// ===== پردازش دکمه‌های شیشه‌ای =====
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    // دکمه ورود به بازی (join_)
    if (strpos($data, 'join_') === 0) {
        $code = substr($data, 5);
        
        $games = loadGames();
        if (!isset($games[$code])) {
            answerCallbackQuery($callback_id, "❌ بازی با این کد پیدا نشد!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        $game = $games[$code];
        
        if ($game['status'] != 'waiting') {
            answerCallbackQuery($callback_id, "⏳ این بازی قبلاً شروع شده!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        if (time() > $game['wait_until']) {
            answerCallbackQuery($callback_id, "⏰ زمان انتظار تمام شده!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                answerCallbackQuery($callback_id, "❌ شما قبلاً در این بازی هستید!", true);
                http_response_code(200);
                echo '{"ok":true}';
                exit;
            }
        }
        
        $user_name = $callback['from']['first_name'] ?? 'کاربر';
        if (isset($callback['from']['last_name']) && !empty($callback['from']['last_name'])) {
            $user_name .= ' ' . $callback['from']['last_name'];
        }
        
        $game['players'][] = [
            'id' => $user_id,
            'name' => $user_name,
            'alive' => true,
            'role' => null
        ];
        
        $games[$code] = $game;
        saveGames($games);
        
        answerCallbackQuery($callback_id, "✅ به بازی پیوستید!", false);
        sendPrivateMessage($user_id, "✅ شما به بازی با کد <code>$code</code> پیوستید!\n👥 تعداد: " . count($game['players']) . " نفر");
        sendMessage($chat_id, "✅ <b>$user_name</b> به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر");
        sendPlayerList($chat_id, $game);
        
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    // پردازش اکشن شب
    $parts = explode('_', $data);
    if (count($parts) == 2) {
        $role = $parts[0];
        $target_id = $parts[1] == 'skip' ? 'skip' : (int)$parts[1];
        
        $nightRoles = ['werewolf', 'seer', 'guardian_angel', 'hunter'];
        
        if (in_array($role, $nightRoles)) {
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
                if ($target_id != 'skip') {
                    $game['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => $target_id];
                    $games[$game_code] = $game;
                    saveGames($games);
                    answerCallbackQuery($callback_id, "✅ انتخاب شما ثبت شد!");
                    sendPrivateMessage($user_id, "✅ انتخاب شما برای شب " . $game['night_count'] . " ثبت شد.");
                } else {
                    answerCallbackQuery($callback_id, "⏭️ شما این شب رو رد کردید.");
                    sendPrivateMessage($user_id, "⏭️ شما این شب رو رد کردید.");
                }
            } else {
                answerCallbackQuery($callback_id, "❌ خطا: بازی پیدا نشد!", true);
            }
            
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
    }
    
    // پردازش رأی
    $parts = explode('_', $data);
    if (count($parts) == 2 && $parts[0] == 'vote') {
        $target_id = $parts[1] == 'skip' ? 'skip' : (int)$parts[1];
        
        $games = loadGames();
        $game = null;
        $game_code = null;
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && in_array($g['status'], ['started']) && $g['phase'] == 'vote') {
                    $game = $g;
                    $game_code = $code;
                    break 2;
                }
            }
        }
        
        if ($game) {
            $game['votes'][$user_id] = $target_id;
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "✅ رأی شما ثبت شد!");
            
            $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
            if (count($game['votes']) >= count($alive)) {
                processVotes($game_code, $game);
            }
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
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و یکی از دستورات start را بزنید."; break;
        case 'join_menu': $response = "🔗 کد بازی را وارد کنید:\nمثال: /join AB12CD"; break;
        case 'rules': $response = getRules(); break;
        case 'roles': $response = getRolesList(); break;
        case 'help': $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join - پیوستن\n/players - لیست\n/startgame - شروع\n/stop - لغو\n/leave - خروج\n/extend - تمدید (ادمین)\n/ping - تست"; break;
        case 'stats':
            $games = loadGames();
            $total = count($games);
            $waiting = count(array_filter($games, fn($g) => $g['status'] == 'waiting'));
            $started = count(array_filter($games, fn($g) => $g['status'] == 'started'));
            $response = "📊 <b>آمار ربات</b>\n\n🎮 کل: $total\n⏳ در انتظار: $waiting\n▶️ در حال اجرا: $started";
            break;
        default: $response = "✅ انجام شد!"; break;
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

// اگه پیام با / شروع نشه، نادیده بگیر
if (substr($text, 0, 1) !== '/') {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

// ============================================================
// 10. پردازش دستورات
// ============================================================

switch ($command) {
    case '/start':
        $modes = getGameModes();
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'], ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']],
                [['text' => '📜 قوانین', 'callback_data' => 'rules'], ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']],
                [['text' => '❓ راهنما', 'callback_data' => 'help'], ['text' => '📊 آمار', 'callback_data' => 'stats']],
                [['text' => '▶️ شروع عادی', 'callback_data' => 'start_normal'], ['text' => '💪 شروع قدرتی', 'callback_data' => 'start_mighty']],
                [['text' => '🧛 شروع ومپایری', 'callback_data' => 'start_vampire'], ['text' => '🐺 شروع ورولفی', 'callback_data' => 'start_werewolf']]
            ]
        ];
        sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 به ربات گرگینه خوش اومدی!", $keyboard);
        break;
    
    // ===== شروع بازی با حالت‌های مختلف (دکمه شیشه‌ای) =====
    case '/startgame':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'normal');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/starteasy':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'easy');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startmafia':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mafia');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startvampire':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'vampire');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startwerewolf':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'werewolf');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startbomber':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'bomber');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startfoolish':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'foolish');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startmighty':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mighty');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startromantic':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'romantic');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/startcoin':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'coin');
            if (!$result['success']) {
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
    
    case '/join':
        if (empty($param)) {
            sendMessage($chat_id, "❌ کد بازی را وارد کنید!\nمثال: /join AB12CD");
        } else {
            $code = strtoupper(trim($param));
            $result = joinGame($code, $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
            if ($result['success']) {
                $game = getGame($code);
                if ($game) {
                    sendPlayerList($chat_id, $game);
                }
            }
        }
        break;
        
    case '/players':
        $game = getGroupActiveGame($chat_id);
        if (!$game) {
            sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        } else {
            sendPlayerList($chat_id, $game);
        }
        break;
        
    case '/startgame_force':
        $result = startGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/stop':
    case '/cancel':
        $result = cancelGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/leave':
        $result = leaveGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/extend':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ این دستور فقط در گروه قابل استفاده است!");
        } else {
            $result = extendWaitingTime($chat_id, $user_id);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/help':
        $msg = "📚 <b>راهنمای ربات</b>\n\n" .
               "/start - منوی اصلی\n" .
               "/startgame - شروع بازی عادی\n" .
               "/startmighty - شروع بازی قدرتی\n" .
               "/startvampire - شروع بازی ومپایری\n" .
               "/startwerewolf - شروع بازی ورولفی\n" .
               "/starteasy - شروع بازی آسان\n" .
               "/startmafia - شروع بازی مافیا\n" .
               "/startbomber - شروع بازی بمب‌گذار\n" .
               "/startfoolish - شروع بازی احمقانه\n" .
               "/startromantic - شروع بازی عاشقانه\n" .
               "/startcoin - شروع بازی سکه‌ای\n" .
               "/join [کد] - پیوستن\n" .
               "/players - لیست بازیکنان\n" .
               "/stop - لغو بازی\n" .
               "/leave - خروج از بازی\n" .
               "/extend - تمدید زمان (فقط ادمین)\n" .
               "/rules - قوانین\n" .
               "/roles - نقش‌ها\n" .
               "/ping - تست اتصال";
        sendMessage($chat_id, $msg);
        break;
        
    case '/rules':
        sendMessage($chat_id, getRules());
        break;
        
    case '/roles':
        sendMessage($chat_id, getRolesList());
        break;
        
    case '/myrank':
        $info = getRankInfo($user_id);
        $msg = "📊 <b>وضعیت درجه شما</b>\n\n";
        $msg .= "🎖️ درجه: <b>{$info['rank']}</b> - {$info['rank_name']}\n";
        $msg .= "📈 XP: <b>{$info['xp']}</b> / <b>{$info['next_xp']}</b>\n";
        $msg .= "📊 پیشرفت: " . round(($info['xp'] / $info['next_xp']) * 100, 1) . "%\n\n";
        $msg .= "💡 هر بازی زنده موندن: +50 XP\n";
        $msg .= "💀 هر بازی مردن: +20 XP";
        sendMessage($chat_id, $msg);
        break;
        
    case '/coin':
        $coins = getCoin($user_id);
        sendMessage($chat_id, "🪙 <b>سکه شما:</b> $coins");
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
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - END\n", FILE_APPEND);
?>
