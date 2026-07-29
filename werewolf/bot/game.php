<?php
// game.php - نسخه کامل با بالانس پیشرفته

// ============================================================
// 1. تنظیمات و ثابت‌ها
// ============================================================

define('MIN_PLAYERS', 4);
define('MAX_PLAYERS', 60);
define('WAITING_TIME', 300);
define('EXTEND_TIME', 30);
define('MAX_EXTEND_COUNT', 3);
define('AFK_THRESHOLD', 2);

// ============================================================
// 2. بالانس نقش‌ها (نسخه کامل)
// ============================================================

function selectBalancedRoles($count) {
    // ===== ۴-۶ نفر =====
    if ($count <= 4) {
        $roles = ['villager', 'villager', 'villager', 'werewolf'];
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 6) {
        $roles = array_merge(
            array_fill(0, $count - 2, 'villager'),
            ['werewolf', 'werewolf']
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۷-۱۰ نفر =====
    if ($count <= 10) {
        $special = ['seer'];
        $wolfCount = 2;
        $villagerCount = $count - $wolfCount - count($special);
        $roles = array_merge(
            array_fill(0, $villagerCount, 'villager'),
            array_fill(0, $wolfCount, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۱۱-۱۵ نفر =====
    if ($count <= 15) {
        $special = ['seer', 'guardian_angel'];
        $wolfCount = 3;
        $villagerCount = $count - $wolfCount - count($special);
        $roles = array_merge(
            array_fill(0, $villagerCount, 'villager'),
            array_fill(0, $wolfCount, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۱۶-۲۰ نفر =====
    if ($count <= 20) {
        $special = ['seer', 'guardian_angel', 'hunter'];
        $wolfCount = 3;
        $villagerCount = $count - $wolfCount - count($special);
        $roles = array_merge(
            array_fill(0, $villagerCount, 'villager'),
            array_fill(0, $wolfCount, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۲۱-۳۰ نفر =====
    if ($count <= 30) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight'];
        $wolfCount = 4;
        $villagerCount = $count - $wolfCount - count($special);
        $roles = array_merge(
            array_fill(0, $villagerCount, 'villager'),
            array_fill(0, $wolfCount, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۳۱-۴۵ نفر =====
    if ($count <= 45) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 
                    'cupid', 'beholder'];
        $wolfCount = 5;
        $villagerCount = $count - $wolfCount - count($special);
        $roles = array_merge(
            array_fill(0, $villagerCount, 'villager'),
            array_fill(0, $wolfCount, 'werewolf'),
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ===== ۴۶-۶۰ نفر =====
    $wolfCount = max(6, round($count * 0.2));
    $specialCount = max(5, round($count * 0.15));
    
    $availableSpecial = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 
                         'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble',
                         'chemist', 'fool', 'wise_elder', 'sandman', 'ruler'];
    shuffle($availableSpecial);
    $specialRoles = array_slice($availableSpecial, 0, $specialCount);
    
    $villagerCount = $count - $wolfCount - count($specialRoles);
    
    $roles = array_merge(
        array_fill(0, $villagerCount, 'villager'),
        array_fill(0, $wolfCount, 'werewolf'),
        $specialRoles
    );
    
    shuffle($roles);
    return $roles;
}

// ============================================================
// 3. توابع اصلی بازی
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
    $existing = getGroupActiveGame($group_id);
    if ($existing) {
        return [
            'success' => false,
            'message' => '⏳ یک بازی فعال در این گروه وجود دارد!',
            'code' => $existing['code']
        ];
    }

    $code = generateGameCode();
    
    $game = [
        'code' => $code,
        'group_id' => $group_id,
        'creator_id' => $creator_id,
        'creator_name' => $creator_name,
        'players' => [
            [
                'id' => $creator_id,
                'name' => $creator_name,
                'role' => null,
                'alive' => true,
                'role_data' => [],
                'joined_at' => time()
            ]
        ],
        'status' => 'waiting',
        'created' => time(),
        'wait_until' => time() + WAITING_TIME,
        'extend_count' => 0,
        'started' => null,
        'ended' => null,
        'phase' => null,
        'night_count' => 0,
        'day_count' => 0,
        'roles_assigned' => false,
        'night_actions' => [],
        'votes' => [],
        'lovers' => [],
        'winners' => null,
        'time_set' => false,
        'settings' => [
            'day_duration' => 60,
            'vote_duration' => 60,
            'night_duration' => 60
        ]
    ];
    
    saveGame($game);
    
    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🐺 <b>بازی جدید ساخته شد!</b>\n\n";
    $msg .= "🎲 <b>کد بازی:</b> <code>" . $code . "</code>\n";
    $msg .= "👤 سازنده: " . $creator_name . "\n";
    $msg .= "👥 بازیکنان فعلی: ۱ نفر\n\n";
    $msg .= "⏱ <b>زمان باقیمانده جوین:</b> " . $minutes . ":" . sprintf("%02d", $seconds) . "\n\n";
    $msg .= "📌 <b>دوستانت رو دعوت کن:</b>\n";
    $msg .= "🔗 لینک دعوت: https://t.me/" . BOT_USERNAME . "?start=join_" . $code . "\n\n";
    $msg .= "👇 برای شروع بازی حداقل ۴ نفر نیازه!";
    
    return [
        'success' => true,
        'message' => $msg,
        'code' => $code,
        'game' => $game
    ];
}

function joinGame($code, $user_id, $user_name) {
    $game = getGame($code);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی با این کد پیدا نشد!'];
    }
    
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ این بازی قبلاً شروع شده!'];
    }
    
    if (time() > ($game['wait_until'] ?? 0)) {
        return ['success' => false, 'message' => '⏰ زمان انتظار تمام شده!'];
    }
    
    foreach ($game['players'] as $player) {
        if ($player['id'] == $user_id) {
            return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
        }
    }
    
    $game['players'][] = [
        'id' => $user_id,
        'name' => $user_name,
        'role' => null,
        'alive' => true,
        'role_data' => [],
        'joined_at' => time()
    ];
    
    saveGame($game);
    
    return [
        'success' => true,
        'message' => "✅ <b>" . $user_name . "</b> به بازی پیوست!\n👥 تعداد بازیکنان: " . count($game['players']) . " نفر",
        'player_count' => count($game['players']),
        'game' => $game
    ];
}

function leaveGame($user_id, $chat_id) {
    $game = getPlayerActiveGame($user_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ شما در هیچ بازی فعالی نیستید!'];
    }
    
    if ($game['status'] == 'started') {
        return ['success' => false, 'message' => '❌ بازی شروع شده! نمی‌توانید خارج شوید.'];
    }
    
    foreach ($game['players'] as $key => $player) {
        if ($player['id'] == $user_id) {
            unset($game['players'][$key]);
            $game['players'] = array_values($game['players']);
            break;
        }
    }
    
    if (empty($game['players'])) {
        deleteGame($game['code']);
        return ['success' => true, 'message' => '✅ بازی لغو شد.'];
    }
    
    if ($user_id == $game['creator_id'] && !empty($game['players'])) {
        $game['creator_id'] = $game['players'][0]['id'];
        $game['creator_name'] = $game['players'][0]['name'];
    }
    
    saveGame($game);
    
    return ['success' => true, 'message' => '✅ از بازی خارج شدید!'];
}

function startGame($group_id, $user_id = null) {
    $game = getGroupActiveGame($group_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ بازی قبلاً شروع شده!'];
    }
    
    $playerCount = count($game['players']);
    if ($playerCount < MIN_PLAYERS) {
        return [
            'success' => false,
            'message' => '❌ تعداد بازیکنان کافی نیست! (' . $playerCount . '/' . MIN_PLAYERS . ')'
        ];
    }
    
    if (!$game['time_set']) {
        $game['settings']['day_duration'] = 60;
        $game['settings']['vote_duration'] = 60;
        $game['settings']['night_duration'] = 60;
        $game['time_set'] = true;
    }
    
    // تخصیص نقش‌ها با بالانس
    $roles = selectBalancedRoles($playerCount);
    shuffle($roles);
    
    foreach ($game['players'] as $i => &$player) {
        $player['role'] = $roles[$i];
        $player['original_role'] = $roles[$i];
    }
    
    $game['status'] = 'started';
    $game['started'] = time();
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    $game['roles_assigned'] = true;
    unset($game['wait_until'], $game['extend_count']);
    
    saveGame($game);
    
    // ارسال نقش به هر بازیکن
    foreach ($game['players'] as $player) {
        $roleName = getRoleDisplayName($player['role']);
        sendPrivateMessage($player['id'], 
            "🎭 <b>نقش شما: " . $roleName . "</b>\n\n" .
            getRoleDescription($player['role']) . "\n\n" .
            "🌙 شب اول شروع شد..."
        );
    }
    
    // شروع فاز شب
    startNightPhase($game);
    
    return [
        'success' => true,
        'message' => "🎮 <b>بازی شروع شد!</b>\n\n👥 " . $playerCount . " نفر\n🌙 شب اول...",
        'game' => $game
    ];
}

function cancelGame($group_id, $user_id) {
    $game = getGroupActiveGame($group_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    if ($user_id != $game['creator_id'] && $user_id != ADMIN_ID) {
        return ['success' => false, 'message' => '❌ فقط سازنده بازی می‌تونه لغو کنه!'];
    }
    
    deleteGame($game['code']);
    
    return ['success' => true, 'message' => '❌ بازی لغو شد!'];
}

// ============================================================
// 4. فازهای بازی (شب و روز)
// ============================================================

function startNightPhase($game) {
    $game['phase'] = 'night';
    $game['night_actions'] = [];
    $game['night_end'] = time() + ($game['settings']['night_duration'] ?? 60);
    saveGame($game);
    
    // ارسال پنل شب به هر بازیکن زنده
    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;
        sendNightPanel($player, $game);
    }
    
    sendMessage($game['group_id'], 
        "🌙 <b>شب " . $game['night_count'] . "!</b>\n\n" .
        "همه بخوابید...\n⏱ " . ($game['settings']['night_duration'] ?? 60) . " ثانیه تا صبح"
    );
}

function startDayPhase($game) {
    $game['phase'] = 'day';
    $game['day_count'] = ($game['day_count'] ?? 0) + 1;
    
    // پردازش اکشن‌های شب
    $results = processNightActions($game);
    $game = $results['game'];
    
    saveGame($game);
    
    // اعلام نتایج
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    
    if (!empty($results['deaths'])) {
        $msg .= "💀 <b>کشته شدگان:</b>\n";
        foreach ($results['deaths'] as $death) {
            $msg .= "• <b>" . $death['name'] . "</b>\n";
        }
        $msg .= "\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    
    sendMessage($game['group_id'], $msg);
    
    // بررسی شرایط برد
    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }
    
    // شروع رأی‌گیری
    startVoting($game);
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    
    // لیست نقش‌هایی که اکشن شب دارن
    $nightRoles = ['werewolf', 'seer', 'guardian_angel', 'hunter', 'serial_killer', 
                   'vampire', 'cultist', 'joker', 'detective', 'knight'];
    
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], 
            "🌙 <b>شب " . $game['night_count'] . "</b>\n\n💤 تو می‌تونی بخوابی..."
        );
        return;
    }
    
    $targets = getValidNightTargets($role, $game, $player['id']);
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], 
            "🌙 <b>شب " . $game['night_count'] . "</b>\n\n⏳ هیچ هدف معتبری وجود نداره!"
        );
        return;
    }
    
    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: " . getRoleDisplayName($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => $target['callback']];
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

function getValidNightTargets($role, $game, $playerId) {
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($playerId) {
        return ($p['alive'] ?? false) && $p['id'] != $playerId;
    });
    
    $wolfRoles = ['werewolf'];
    $vampireRoles = ['vampire'];
    $cultRoles = ['cultist'];
    
    foreach ($alivePlayers as $p) {
        $skip = false;
        
        // گرگ‌ها نمی‌تونن به گرگ دیگه حمله کنن
        if (in_array($role, $wolfRoles) && in_array($p['role'], $wolfRoles)) {
            $skip = true;
        }
        
        // ومپایرها نمی‌تونن به ومپایر دیگه حمله کنن
        if (in_array($role, $vampireRoles) && in_array($p['role'], $vampireRoles)) {
            $skip = true;
        }
        
        // فرقه‌گراها نمی‌تونن فرقه‌های دیگه رو دعوت کنن
        if (in_array($role, $cultRoles) && in_array($p['role'], $cultRoles)) {
            $skip = true;
        }
        
        if (!$skip) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => $role . '_' . $p['id']
            ];
        }
    }
    
    return $targets;
}

function processNightActions($game) {
    $deaths = [];
    $messages = [];
    $actions = $game['night_actions'] ?? [];
    $protected = [];
    
    foreach ($actions as $action) {
        if ($action['action'] == 'guard') {
            $protected[] = $action['target'];
        }
    }
    
    foreach ($actions as $action) {
        if (in_array($action['action'], ['kill', 'eat', 'shoot']) && !in_array($action['target'], $protected)) {
            $target = getPlayerById($game, $action['target']);
            if ($target && ($target['alive'] ?? false)) {
                $game = killPlayer($game, $action['target'], $action['action']);
                $deaths[] = ['id' => $action['target'], 'name' => $target['name']];
            }
        }
    }
    
    saveGame($game);
    
    return ['game' => $game, 'deaths' => $deaths, 'messages' => $messages];
}

function startVoting($game) {
    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_end'] = time() + ($game['settings']['vote_duration'] ?? 60);
    saveGame($game);
    
    $alive = getAlivePlayers($game);
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "!</b>\n\n";
    $msg .= "👥 بازیکنان زنده: " . count($alive) . "\n";
    $msg .= "👇 به صورت خصوصی رأی دهید:";
    
    sendMessage($game['group_id'], $msg);
    
    foreach ($alive as $player) {
        sendVotePanel($player, $game);
    }
}

function sendVotePanel($player, $game) {
    $alive = getAlivePlayers($game);
    $targets = array_filter($alive, function($p) use ($player) {
        return $p['id'] != $player['id'];
    });
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "❌ هیچ بازیکن زنده دیگری برای رأی دادن وجود ندارد!");
        return;
    }
    
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    $msg .= "👇 یک نفر رو برای اعدام انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => 'vote_' . $target['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    
    // دکمه رأی سفید
    $keyboard[] = [['text' => '⚪ رأی سفید', 'callback_data' => 'vote_skip']];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processVoteResults($game) {
    $votes = $game['votes'] ?? [];
    $counts = [];
    
    foreach ($votes as $voterId => $targetId) {
        if ($targetId == 'skip') continue;
        $counts[$targetId] = ($counts[$targetId] ?? 0) + 1;
    }
    
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    
    if ($max > 0 && count($targets) == 1) {
        $targetId = $targets[0];
        $target = getPlayerById($game, $targetId);
        if ($target && ($target['alive'] ?? false)) {
            $game = killPlayer($game, $targetId, 'lynch');
            saveGame($game);
            sendMessage($game['group_id'], 
                "💀 <b>" . $target['name'] . "</b> اعدام شد!\n🎭 نقش: " . getRoleDisplayName($target['role'])
            );
        }
    } else {
        sendMessage($game['group_id'], "⚖️ <b>رأی‌ها مساوی شد! کسی اعدام نشد.</b>");
    }
    
    return $game;
}

// ============================================================
// 5. تنظیمات تایم
// ============================================================

function setGameTiming($group_id, $user_id, $timing_option) {
    $game = getGroupActiveGame($group_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ بازی قبلاً شروع شده!'];
    }
    
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین گروه!'];
    }
    
    $times = ['fast' => 60, 'normal' => 90, 'slow' => 120];
    if (!isset($times[$timing_option])) {
        return ['success' => false, 'message' => '❌ گزینه نامعتبر!'];
    }
    
    $game['settings']['day_duration'] = $times[$timing_option];
    $game['settings']['vote_duration'] = $times[$timing_option];
    $game['settings']['night_duration'] = $times[$timing_option];
    $game['time_set'] = true;
    saveGame($game);
    
    return ['success' => true, 'message' => "⚙️ تایم بازی تنظیم شد! (" . $times[$timing_option] . " ثانیه)"];
}

function extendWaitingTime($group_id, $user_id) {
    $game = getGroupActiveGame($group_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ بازی قبلاً شروع شده!'];
    }
    
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین گروه!'];
    }
    
    if (($game['extend_count'] ?? 0) >= MAX_EXTEND_COUNT) {
        return ['success' => false, 'message' => '❌ حداکثر ۳ بار تمدید!'];
    }
    
    $game['wait_until'] = ($game['wait_until'] ?? time()) + EXTEND_TIME;
    $game['extend_count'] = ($game['extend_count'] ?? 0) + 1;
    saveGame($game);
    
    $remaining = max(0, $game['wait_until'] - time());
    return [
        'success' => true,
        'message' => "⏱ زمان ۳۰ ثانیه تمدید شد!\n⏳ باقیمانده: " . floor($remaining / 60) . ":" . sprintf("%02d", $remaining % 60)
    ];
}

// ============================================================
// 6. توابع دیتابیس
// ============================================================

function getAllGames() {
    $file = __DIR__ . '/data/games.json';
    if (!file_exists($file)) {
        if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
        file_put_contents($file, '{}');
        return [];
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveAllGames($games) {
    $file = __DIR__ . '/data/games.json';
    if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
    file_put_contents($file, json_encode($games, JSON_PRETTY_PRINT));
}

function getGame($code) {
    $games = getAllGames();
    return $games[$code] ?? null;
}

function saveGame($game) {
    if (!isset($game['code'])) return false;
    $games = getAllGames();
    $games[$game['code']] = $game;
    saveAllGames($games);
    return true;
}

function deleteGame($code) {
    $games = getAllGames();
    if (isset($games[$code])) {
        unset($games[$code]);
        saveAllGames($games);
        return true;
    }
    return false;
}

function getGroupActiveGame($group_id) {
    $games = getAllGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'] ?? '', ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
}

function getPlayerActiveGame($user_id) {
    $games = getAllGames();
    foreach ($games as $game) {
        if (!in_array($game['status'] ?? '', ['waiting', 'started'])) continue;
        foreach ($game['players'] as $player) {
            if ($player['id'] == $user_id) return $game;
        }
    }
    return null;
}

function getAlivePlayers($game) {
    return array_filter($game['players'] ?? [], fn($p) => $p['alive'] ?? false);
}

function getPlayerById($game, $id) {
    foreach ($game['players'] ?? [] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

function killPlayer($game, $playerId, $cause) {
    foreach ($game['players'] as &$p) {
        if ($p['id'] == $playerId) {
            $p['alive'] = false;
            $p['death_cause'] = $cause;
            $p['death_time'] = time();
            break;
        }
    }
    return $game;
}

function checkWinCondition($game) {
    $alive = getAlivePlayers($game);
    $totalAlive = count($alive);
    
    if ($totalAlive == 0) {
        return ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردند!'];
    }
    
    $wolves = array_filter($alive, fn($p) => in_array($p['role'] ?? '', ['werewolf']));
    $villagers = array_filter($alive, fn($p) => $p['role'] == 'villager');
    
    if (count($wolves) == 0) {
        return ['ended' => true, 'winner' => 'villager', 'message' => '👨‍🌾 روستایی‌ها برنده شدند!'];
    }
    if (count($wolves) >= count($villagers)) {
        return ['ended' => true, 'winner' => 'werewolf', 'message' => '🐺 گرگ‌ها برنده شدند!'];
    }
    
    return ['ended' => false];
}

function endGame($game, $winCheck) {
    $game['status'] = 'ended';
    $game['ended'] = time();
    $game['winners'] = $winCheck['winner'] ?? null;
    saveGame($game);
    
    $msg = "🏁 <b>بازی تمام شد!</b>\n\n";
    $msg .= $winCheck['message'] . "\n\n";
    $msg .= "📊 <b>نقش‌ها:</b>\n";
    
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? false) ? '🟢' : '💀';
        $role = getRoleDisplayName($p['role']);
        $msg .= "$status {$p['name']} - $role\n";
    }
    
    sendMessage($game['group_id'], $msg);
    
    // ارسال پیام خصوصی به بازیکنان مرده با نقش‌های زنده‌ها
    foreach ($game['players'] as $p) {
        if (!($p['alive'] ?? false)) {
            $roleMsg = "👻 <b>بازی تمام شد!</b>\n\n";
            $roleMsg .= "🏆 برنده: " . $winCheck['message'] . "\n\n";
            $roleMsg .= "📊 <b>نقش‌ها:</b>\n";
            foreach ($game['players'] as $pp) {
                $status = ($pp['alive'] ?? false) ? '🟢' : '💀';
                $role = getRoleDisplayName($pp['role']);
                $roleMsg .= "$status {$pp['name']} - $role\n";
            }
            sendPrivateMessage($p['id'], $roleMsg);
        }
    }
}

function getGameInfo($group_id) {
    $game = getGroupActiveGame($group_id);
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    $msg = "🎮 <b>وضعیت بازی</b>\n\n";
    $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
    $msg .= "👤 سازنده: " . $game['creator_name'] . "\n";
    $msg .= "📊 وضعیت: " . ($game['status'] == 'waiting' ? '⏳ در انتظار' : '▶️ در حال اجرا') . "\n";
    $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر\n";
    
    if ($game['status'] == 'waiting') {
        $remaining = max(0, ($game['wait_until'] ?? time()) - time());
        $msg .= "⏱ زمان باقیمانده: " . floor($remaining / 60) . ":" . sprintf("%02d", $remaining % 60);
    }
    
    return ['success' => true, 'message' => $msg];
}

function getGameStats() {
    $games = getAllGames();
    return [
        'total' => count($games),
        'waiting' => count(array_filter($games, fn($g) => $g['status'] == 'waiting')),
        'started' => count(array_filter($games, fn($g) => $g['status'] == 'started')),
        'ended' => count(array_filter($games, fn($g) => $g['status'] == 'ended'))
    ];
}

function getGroupLinks() {
    $file = __DIR__ . '/data/group_links.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveGroupLinks($links) {
    $file = __DIR__ . '/data/group_links.json';
    if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
    file_put_contents($file, json_encode($links, JSON_PRETTY_PRINT));
}

function getDatabaseSize() {
    $file = __DIR__ . '/data/games.json';
    if (!file_exists($file)) return '0 KB';
    $size = filesize($file);
    if ($size < 1024) return $size . ' B';
    if ($size < 1024 * 1024) return round($size / 1024, 2) . ' KB';
    return round($size / (1024 * 1024), 2) . ' MB';
}

function cleanupOldGames() {
    $games = getAllGames();
    $now = time();
    foreach ($games as $code => $game) {
        if ($game['status'] == 'waiting' && ($now - $game['created']) > 600) {
            unset($games[$code]);
        }
        if ($game['status'] == 'ended' && isset($game['ended']) && ($now - $game['ended']) > 86400) {
            unset($games[$code]);
        }
    }
    saveAllGames($games);
}

function handleTeamChat($user_id, $message, $gameCode) {
    return ['success' => true, 'message' => "✅ پیام به تیم ارسال شد!"];
}

function isAdmin($user_id, $group_id) {
    return true;
}

function getRoleDisplayName($role) {
    $names = [
        'villager' => '👨‍🌾 روستایی',
        'seer' => '👳🏻‍♂️ پیشگو',
        'werewolf' => '🐺 گرگینه',
        'guardian_angel' => '👼🏻 فرشته نگهبان',
        'hunter' => '👮🏻‍♂️ کلانتر',
        'serial_killer' => '🔪 قاتل',
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'cultist' => '👤 فرقه‌گرا',
        'joker' => '🤡 جوکر',
        'tanner' => '👺 منافق',
        'detective' => '🕵🏻‍♂️ کاراگاه',
        'knight' => '🗡 شوالیه',
        'cupid' => '💘 الهه عشق',
        'beholder' => '👁 شاهد',
        'phoenix' => '🪶 ققنوس',
        'huntsman' => '🪓 هانتسمن',
        'trouble' => '👩🏻‍🌾 دختر دردسرساز',
        'chemist' => '👨‍🔬 شیمیدان',
        'fool' => '🃏 احمق',
        'wise_elder' => '📚 ریش سفید',
        'sandman' => '💤 خوابگذار',
        'ruler' => '👑 حاکم'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getRoleDescription($role) {
    $desc = [
        'villager' => "👨‍🌾 شما یک روستایی ساده هستید.\nدر روز رأی می‌دهید تا گرگ‌ها را پیدا کنید.",
        'seer' => "👳🏻‍♂️ شما پیشگو هستید!\nهر شب می‌توانید نقش یک نفر را ببینید.",
        'werewolf' => "🐺 شما گرگینه هستید!\nهر شب با گرگ‌های دیگر هماهنگ شوید و یک نفر را بخورید.",
        'guardian_angel' => "👼🏻 شما فرشته نگهبان هستید!\nهر شب می‌توانید از یک نفر محافظت کنید.",
        'hunter' => "👮🏻‍♂️ شما کلانتر هستید!\nاگر بمیرید، می‌توانید به یک نفر شلیک کنید.",
        'serial_killer' => "🔪 شما قاتل زنجیره‌ای هستید!\nهر شب یک نفر را بکشید.",
        'vampire' => "🧛🏻‍♂️ شما ومپایر هستید!\nهر شب به یک نفر حمله کنید و خونش را بخورید.",
        'cultist' => "👤 شما فرقه‌گرا هستید!\nهر شب یک نفر را به فرقه دعوت کنید.",
        'joker' => "🤡 شما جوکر هستید!\nسعی کنید اعدام شوید تا برنده شوید!",
        'tanner' => "👺 شما منافق هستید!\nباید اعدام شوید تا برنده شوید!",
        'detective' => "🕵🏻‍♂️ شما کاراگاه هستید!\nهر روز می‌توانید یک نفر را تحقیق کنید.",
        'knight' => "🗡 شما شوالیه هستید!\nهر شب می‌توانید به خانه یک نفر بروید و اگر منفی بود، بکشیدش.",
        'cupid' => "💘 شما الهه عشق هستید!\nدو نفر را عاشق هم کنید.",
        'phoenix' => "🪶 شما ققنوس هستید!\nمی‌توانید یک نفر را از مرگ نجات دهید.",
        'fool' => "🃏 شما احمق هستید!\nفکر می‌کنید پیشگو هستید ولی نتیجه‌هاتان اشتباه است.",
        'wise_elder' => "📚 شما ریش سفید هستید!\nیک بار می‌توانید از مرگ نجات پیدا کنید."
    ];
    return $desc[$role] ?? "🎭 شما " . getRoleName($role) . " هستید!";
}

function getRoleName($role) {
    $names = [
        'villager' => 'روستایی ساده',
        'seer' => 'پیشگو',
        'werewolf' => 'گرگینه',
        'guardian_angel' => 'فرشته نگهبان',
        'hunter' => 'کلانتر',
        'serial_killer' => 'قاتل زنجیره‌ای',
        'vampire' => 'ومپایر',
        'cultist' => 'فرقه‌گرا',
        'joker' => 'جوکر',
        'tanner' => 'منافق',
        'detective' => 'کاراگاه',
        'knight' => 'شوالیه',
        'cupid' => 'الهه عشق',
        'beholder' => 'شاهد',
        'phoenix' => 'ققنوس',
        'huntsman' => 'هانتسمن',
        'trouble' => 'دختر دردسرساز',
        'chemist' => 'شیمیدان',
        'fool' => 'احمق',
        'wise_elder' => 'ریش سفید',
        'sandman' => 'خوابگذار',
        'ruler' => 'حاکم'
    ];
    return $names[$role] ?? $role;
}

function getRoleIcon($role) {
    $icons = [
        'villager' => '👨‍🌾',
        'seer' => '👳🏻‍♂️',
        'werewolf' => '🐺',
        'guardian_angel' => '👼🏻',
        'hunter' => '👮🏻‍♂️',
        'serial_killer' => '🔪',
        'vampire' => '🧛🏻‍♂️',
        'cultist' => '👤',
        'joker' => '🤡',
        'tanner' => '👺',
        'detective' => '🕵🏻‍♂️',
        'knight' => '🗡',
        'cupid' => '💘',
        'beholder' => '👁',
        'phoenix' => '🪶',
        'huntsman' => '🪓',
        'trouble' => '👩🏻‍🌾',
        'chemist' => '👨‍🔬',
        'fool' => '🃏',
        'wise_elder' => '📚',
        'sandman' => '💤',
        'ruler' => '👑'
    ];
    return $icons[$role] ?? '❓';
}
