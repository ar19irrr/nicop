<?php
// index.php - نسخه نهایی با پشتیبانی از ROLES_PATCH

// ============================================================
// 1. تنظیمات اولیه
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

// ============================================================
// 2. لود کردن سیستم نقش‌ها از ROLES_PATCH
// ============================================================

$roles_path = __DIR__ . '/ROLES_PATCH/';
if (is_dir($roles_path) && file_exists($roles_path . 'factory.php')) {
    require_once $roles_path . 'factory.php';
    require_once $roles_path . 'base.php';
}

// ============================================================
// 3. دیتابیس
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

function deleteGame($code) {
    $games = loadGames();
    if (isset($games[$code])) {
        unset($games[$code]);
        saveGames($games);
        return true;
    }
    return false;
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
// 4. سیستم سکه
// ============================================================

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

function getCoin($user_id) {
    $coins = loadCoins();
    return $coins[$user_id] ?? 0;
}

function addCoin($user_id, $amount) {
    $coins = loadCoins();
    $coins[$user_id] = ($coins[$user_id] ?? 0) + $amount;
    saveCoins($coins);
    return $coins[$user_id];
}

// ============================================================
// 5. سیستم درجه‌بندی
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

function sendRankUpMessage($user_id, $old_rank, $new_rank, $user_name = 'کاربر عزیز') {
    $old_name = getRankName($old_rank);
    $new_name = getRankName($new_rank);
    $msg = "☀️☀️بهت کلی تبریک میگم میدونی چرا؟\n";
    $msg .= "چون ارتقا درجه پیدا کردی 🎖\n";
    $msg .= "به دستور <b>{$user_name}</b> 👑 تو از درجه <b>{$old_name}</b> به درجه <b>{$new_name}</b> ارتقا پیدا کردی.🏅\n";
    $msg .= "حالا برو جلوی دوستات پز بده 👬";
    sendMessage($user_id, $msg);
}

// ============================================================
// 6. تابع بالانس با استفاده از RoleFactory
// ============================================================

function selectBalancedRoles($count) {
    if (class_exists('RoleFactory')) {
        $all_roles = RoleFactory::getAllRoles();
    } else {
        $all_roles = ['villager', 'seer', 'werewolf', 'guardian_angel', 'hunter'];
    }
    
    $villager_roles = [];
    $wolf_roles = [];
    $vampire_roles = [];
    $cult_roles = [];
    $killer_roles = [];
    $fire_ice_roles = [];
    $black_knight_roles = [];
    $joker_roles = [];
    $independent_roles = [];
    
    foreach ($all_roles as $role) {
        if (class_exists('RoleFactory')) {
            $team = RoleFactory::getRoleTeam($role);
        } else {
            $team = 'villager';
        }
        switch ($team) {
            case 'villager': $villager_roles[] = $role; break;
            case 'werewolf': $wolf_roles[] = $role; break;
            case 'vampire': $vampire_roles[] = $role; break;
            case 'cult': $cult_roles[] = $role; break;
            case 'killer': $killer_roles[] = $role; break;
            case 'fire_ice': $fire_ice_roles[] = $role; break;
            case 'black_knight': $black_knight_roles[] = $role; break;
            case 'joker': $joker_roles[] = $role; break;
            case 'independent': $independent_roles[] = $role; break;
        }
    }
    
    $roles = [];
    
    if ($count <= 4) {
        $roles = ['villager', 'villager', 'werewolf', 'seer'];
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 6) {
        $wolf_count = 1;
        $special_count = 1;
        $villager_count = $count - $wolf_count - $special_count;
        $roles = array_merge(
            array_fill(0, $villager_count, 'villager'),
            ['werewolf'],
            ['seer']
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 10) {
        $wolf_count = 2;
        $special_count = 2;
        $villager_count = $count - $wolf_count - $special_count;
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, $special_count);
        $roles = array_merge(
            array_fill(0, $villager_count, 'villager'),
            array_fill(0, $wolf_count, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 15) {
        $wolf_count = 2;
        $special_count = 4;
        $villager_count = $count - $wolf_count - $special_count;
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, $special_count);
        if ($count >= 10 && !empty($killer_roles)) {
            $special[] = 'serial_killer';
            $villager_count--;
        }
        if (rand(1, 100) <= 40 && !empty($cult_roles) && $count >= 8) {
            $special[] = 'cultist';
            $special[] = 'cult_hunter';
            $villager_count -= 2;
        }
        $roles = array_merge(
            array_fill(0, $villager_count, 'villager'),
            array_fill(0, $wolf_count, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    $wolf_count = 3;
    $special_count = 6;
    $villager_count = $count - $wolf_count - $special_count;
    
    $wolf_roles_selected = ['werewolf'];
    if (count($wolf_roles) >= 2) $wolf_roles_selected[] = 'alpha_wolf';
    $remaining_wolves = $wolf_count - count($wolf_roles_selected);
    for ($i = 0; $i < $remaining_wolves; $i++) $wolf_roles_selected[] = 'werewolf';
    
    shuffle($villager_roles);
    $special = array_slice($villager_roles, 0, $special_count);
    
    if (!empty($killer_roles) && $count >= 10) {
        $special[] = 'serial_killer';
        $villager_count--;
        if ($count >= 18) { $special[] = 'archer'; $villager_count--; }
    }
    
    if (!empty($vampire_roles) && $count >= 18) {
        $special[] = 'bloodthirsty';
        $special[] = 'vampire';
        $special[] = 'hunter';
        $villager_count -= 3;
    }
    
    if (!empty($cult_roles) && $count >= 15 && rand(1, 100) <= 50) {
        $special[] = 'cultist';
        $special[] = 'cult_hunter';
        $villager_count -= 2;
    }
    
    $roles = array_merge(
        array_fill(0, $villager_count, 'villager'),
        $wolf_roles_selected,
        $special
    );
    shuffle($roles);
    return $roles;
}

// ============================================================
// 7. توابع اصلی بازی
// ============================================================

function getRoleDisplayName($role) {
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getEmoji() . ' ' . $role_obj->getName();
    }
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
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getDescription();
    }
    $desc = [
        'villager' => '👨‍🌾 شما یک روستایی ساده هستید.',
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
           "👮🏻‍♂️ کلانتر - اگر بمیرد، می‌تواند به یک نفر شلیک کند\n" .
           "🕵🏻‍♂️ کاراگاه - هر شب یک نفر را تحقیق می‌کند\n" .
           "🗡 شوالیه - هر شب از یک نفر محافظت می‌کند\n" .
           "🔪 قاتل زنجیره‌ای - هر شب یک نفر را می‌کشد\n" .
           "🧛🏻‍♂️ ومپایر - هر شب به یک نفر حمله می‌کند\n" .
           "👤 فرقه‌گرا - هر شب یک نفر را به فرقه دعوت می‌کند\n" .
           "🤡 جوکر - کتیبه‌ها را جمع می‌کند\n" .
           "👺 منافق - باید اعدام شود تا برنده شود";
}

function sendPlayerList($chat_id, $game) {
    $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    foreach ($game['players'] as $p) {
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "• {$p['name']} $creator\n";
    }
    if ($game['status'] == 'waiting' && isset($game['wait_until'])) {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $msg .= "\n⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds);
        $msg .= "\n\n⏰ برای تمدید ۳۰ ثانیه: /extend (فقط ادمین)";
    }
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
// 8. توابع اصلی بازی
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
    
    $roles = selectBalancedRoles(count($game['players']));
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

function extendWaitingTime($group_id, $user_id) {
    $games = loadGames();
    
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین‌های گروه می‌توانند زمان را تمدید کنند!'];
    }
    
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && $game['status'] == 'waiting') {
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

function isAdmin($user_id, $group_id) {
    global $token, $admin_id;
    
    if ($user_id == $admin_id) return true;
    
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

// ============================================================
// 9. توابع ارسال پیام
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
// 10. پردازش اصلی
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

// ===== چک کردن تایمرها =====
function checkGameTimers() {
    $games = loadGames();
    $now = time();
    
    foreach ($games as $code => $game) {
        if ($game['status'] == 'waiting' && isset($game['wait_until']) && $now >= $game['wait_until']) {
            if (count($game['players']) >= 4) {
                $result = startGame($game['group_id'], $game['creator_id']);
                if ($result['success']) {
                    sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! بازی با " . count($game['players']) . " نفر شروع می‌شود...");
                }
            } else {
                sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! تعداد بازیکنان کافی نیست (" . count($game['players']) . "/4)");
                deleteGame($code);
                sendMessage($game['group_id'], "❌ بازی لغو شد!");
            }
        }
    }
}

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
    
    // دکمه‌های شروع بازی
    $start_modes = [
        'start_normal' => 'normal',
        'start_easy' => 'easy',
        'start_mafia' => 'mafia',
        'start_vampire' => 'vampire',
        'start_werewolf' => 'werewolf',
        'start_bomber' => 'bomber',
        'start_foolish' => 'foolish',
        'start_mighty' => 'mighty',
        'start_romantic' => 'romantic',
        'start_coin' => 'coin'
    ];
    
    if (isset($start_modes[$data])) {
        $mode = $start_modes[$data];
        $modes = getGameModes();
        $mode_name = $modes[$mode]['name'] ?? $mode;
        
        if ($chat_id > 0) {
            answerCallbackQuery($callback_id, "❌ ساخت بازی فقط در گروه ممکن است!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        $result = createGame($chat_id, $user_id, $callback['from']['first_name'] ?? 'کاربر', $mode);
        if ($result['success']) {
            answerCallbackQuery($callback_id, "✅ بازی {$mode_name} ساخته شد!", false);
        } else {
            answerCallbackQuery($callback_id, $result['message'], true);
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
// 11. پردازش دستورات
// ============================================================

switch ($command) {
    case '/start':
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
    
    case '/game':
    case '/startgame':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'normal');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/starteasy':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'easy');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startmafia':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mafia');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startvampire':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'vampire');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startwerewolf':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'werewolf');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startbomber':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'bomber');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startfoolish':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'foolish');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startmighty':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mighty');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startromantic':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'romantic');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/startcoin':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'coin');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
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
                if ($game) sendPlayerList($chat_id, $game);
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
