<?php
// game.php - نسخه پایدار

// ============================================================
// 1. توابع اصلی
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
        'wait_until' => time() + 300,
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
    if ($playerCount < 4) {
        return [
            'success' => false,
            'message' => '❌ تعداد بازیکنان کافی نیست! (' . $playerCount . '/4)'
        ];
    }
    
    if (!$game['time_set']) {
        $game['settings']['day_duration'] = 60;
        $game['settings']['vote_duration'] = 60;
        $game['settings']['night_duration'] = 60;
        $game['time_set'] = true;
    }
    
    // تخصیص نقش‌ها (ساده و پایدار)
    $roles = ['villager', 'villager', 'werewolf', 'seer'];
    while (count($roles) < $playerCount) {
        $roles[] = 'villager';
    }
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
    
    foreach ($game['players'] as $player) {
        $roleName = getRoleDisplayName($player['role']);
        sendPrivateMessage($player['id'], 
            "🎭 <b>نقش شما: " . $roleName . "</b>\n\n" .
            getRoleDescription($player['role']) . "\n\n" .
            "🌙 شب اول شروع شد..."
        );
    }
    
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
// 2. فازهای بازی
// ============================================================

function startNightPhase($game) {
    $game['phase'] = 'night';
    $game['night_actions'] = [];
    $game['night_end'] = time() + ($game['settings']['night_duration'] ?? 60);
    saveGame($game);
    
    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;
        sendNightPanel($player, $game);
    }
    
    sendMessage($game['group_id'], 
        "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ " . ($game['settings']['night_duration'] ?? 60) . " ثانیه تا صبح"
    );
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $nightRoles = ['werewolf', 'seer', 'guardian_angel', 'hunter', 'serial_killer', 'vampire'];
    
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
    
    foreach ($alivePlayers as $p) {
        if (in_array($role, $wolfRoles) && in_array($p['role'], $wolfRoles)) {
            continue;
        }
        $targets[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'callback' => $role . '_' . $p['id']
        ];
    }
    
    return $targets;
}

// ============================================================
// 3. تنظیمات تایم
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
    
    if (($game['extend_count'] ?? 0) >= 3) {
        return ['success' => false, 'message' => '❌ حداکثر ۳ بار تمدید!'];
    }
    
    $game['wait_until'] = ($game['wait_until'] ?? time()) + 30;
    $game['extend_count'] = ($game['extend_count'] ?? 0) + 1;
    saveGame($game);
    
    $remaining = max(0, $game['wait_until'] - time());
    return [
        'success' => true,
        'message' => "⏱ زمان ۳۰ ثانیه تمدید شد!\n⏳ باقیمانده: " . floor($remaining / 60) . ":" . sprintf("%02d", $remaining % 60)
    ];
}

// ============================================================
// 4. توابع دیتابیس
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
    sendMessage($game['group_id'], "🏁 <b>بازی تمام شد!</b>\n\n" . $winCheck['message']);
    return true;
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
        'vampire' => '🧛🏻‍♂️ ومپایر'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getRoleDescription($role) {
    $desc = [
        'villager' => "👨‍🌾 شما یک روستایی ساده هستید.",
        'seer' => "👳🏻‍♂️ شما پیشگو هستید! هر شب نقش یک نفر را می‌بینید.",
        'werewolf' => "🐺 شما گرگینه هستید! هر شب یک نفر را می‌خورید.",
        'guardian_angel' => "👼🏻 شما فرشته نگهبان هستید! هر شب از یک نفر محافظت می‌کنید.",
        'hunter' => "👮🏻‍♂️ شما کلانتر هستید! اگر بمیرید، می‌توانید به یک نفر شلیک کنید.",
        'serial_killer' => "🔪 شما قاتل زنجیره‌ای هستید! هر شب یک نفر را می‌کشید.",
        'vampire' => "🧛🏻‍♂️ شما ومپایر هستید! هر شب به یک نفر حمله می‌کنید."
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
        'vampire' => 'ومپایر'
    ];
    return $names[$role] ?? $role;
}
