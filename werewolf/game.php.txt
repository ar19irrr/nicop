
# ذخیره فایل کامل game.php

game_php_content = '''<?php
/**
 * 🎮 منطق بازی گرگینه - نسخه 60 نفره
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';
require_once 'ROLES_PATCH/factory.php';

// FALLBACK CONSTANTS
if (!defined('DAY_DURATION_DEFAULT')) define('DAY_DURATION_DEFAULT', 60);
if (!defined('VOTE_DURATION_DEFAULT')) define('VOTE_DURATION_DEFAULT', 60);
if (!defined('NIGHT_DURATION_DEFAULT')) define('NIGHT_DURATION_DEFAULT', 60);
if (!defined('WAITING_TIME')) define('WAITING_TIME', 300);
if (!defined('EXTEND_TIME')) define('EXTEND_TIME', 30);
if (!defined('MAX_EXTEND_COUNT')) define('MAX_EXTEND_COUNT', 3);
if (!defined('AFK_THRESHOLD')) define('AFK_THRESHOLD', 2);

// ==================== توابع اصلی بازی ====================

/**
 * 🆕 ساخت بازی جدید
 */
function createGame($group_id, $creator_id, $creator_name) {
    $existing = getGroupActiveGame($group_id);
    if ($existing) {
        return [
            'success' => false,
            'message' => '⏳ یه بازی فعال در این گروه وجود داره!',
            'code' => $existing['code']
        ];
    }

    do {
        $code = generateGameCode();
    } while (getGame($code) !== null);

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
                'afk_count' => 0,
                'afk_votes' => 0,
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
        'day_actions' => [],
        'votes' => [],
        'lovers' => [],
        'winners' => null,
        'time_set' => false,
        'settings' => [
            'day_duration' => DAY_DURATION_DEFAULT,
            'vote_duration' => VOTE_DURATION_DEFAULT,
            'night_duration' => NIGHT_DURATION_DEFAULT
        ]
    ];

    saveGame($game);

    $remaining = WAITING_TIME;
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🐺 <b>بازی جدید ساخته شد!</b>\\n\\n";
    $msg .= "🎲 <b>کد بازی:</b> <code>" . $code . "</code>\\n";
    $msg .= "👤 سازنده: " . $creator_name . "\\n";
    $msg .= "👥 بازیکنان فعلی: ۱ نفر\\n\\n";
    $msg .= "⏱ <b>زمان باقیمانده جوین:</b> " . $minutes . ":" . sprintf("%02d", $seconds) . "\\n\\n";
    $msg .= "⚙️ <b>تنظیم تایم بازی:</b>\\n";
    $msg .= "ادمین گروه باید تایم هر فاز رو انتخاب کنه:\\n\\n";
    $msg .= "• 🌙 شب: ۶۰ ثانیه\\n";
    $msg .= "• ☀️ روز (بحث): ۶۰ ثانیه\\n";
    $msg .= "• 🗳️ رأی‌گیری: ۶۰ ثانیه\\n\\n";
    $msg .= "👇 یکی رو انتخاب کن:";

    return [
        'success' => true,
        'message' => $msg,
        'code' => $code,
        'game' => $game,
        'need_time_setup' => true
    ];
}

/**
 * ⚙️ تنظیم تایم بازی
 */
function setGameTiming($group_id, $user_id, $timing_option) {
    $game = getGroupActiveGame($group_id);
    
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }
    
    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ بازی قبلاً شروع شده!'];
    }
    
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین گروه می‌تونه تایم رو تنظیم کنه!'];
    }
    
    if ($game['time_set']) {
        return ['success' => false, 'message' => '❌ تایم قبلاً تنظیم شده!'];
    }
    
    switch ($timing_option) {
        case 'fast':
            $game['settings']['day_duration'] = 60;
            $game['settings']['vote_duration'] = 60;
            $game['settings']['night_duration'] = 60;
            $timing_name = 'سریع (۶۰ ثانیه)';
            break;
        case 'normal':
            $game['settings']['day_duration'] = 90;
            $game['settings']['vote_duration'] = 90;
            $game['settings']['night_duration'] = 90;
            $timing_name = 'عادی (۹۰ ثانیه)';
            break;
        case 'slow':
            $game['settings']['day_duration'] = 120;
            $game['settings']['vote_duration'] = 120;
            $game['settings']['night_duration'] = 120;
            $timing_name = 'آرام (۱۲۰ ثانیه)';
            break;
        default:
            return ['success' => false, 'message' => '❌ گزینه نامعتبر!'];
    }
    
    $game['time_set'] = true;
    saveGame($game);
    
    $msg = "⚙️ <b>تایم بازی تنظیم شد!</b>\\n\\n";
    $msg .= "🎮 حالت: <b>" . $timing_name . "</b>\\n\\n";
    $msg .= "⏱ تایم‌ها:\\n";
    $msg .= "• 🌙 شب: " . $game['settings']['night_duration'] . " ثانیه\\n";
    $msg .= "• ☀️ روز: " . $game['settings']['day_duration'] . " ثانیه\\n";
    $msg .= "• 🗳️ رأی‌گیری: " . $game['settings']['vote_duration'] . " ثانیه\\n\\n";
    $msg .= "📌 برای تغییر، ادمین می‌تونه /timing رو بزنه.";
    
    return [
        'success' => true,
        'message' => $msg,
        'game' => $game
    ];
}

/**
 * ⚙️ تغییر تایم
 */
function changeGameTiming($group_id, $user_id, $timing_option) {
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
    
    switch ($timing_option) {
        case 'fast':
            $day = 60; $vote = 60; $night = 60;
            $timing_name = 'سریع (۶۰ ثانیه)';
            break;
        case 'normal':
            $day = 90; $vote = 60; $night = 90;
            $timing_name = 'عادی (۹۰ ثانیه)';
            break;
        case 'slow':
            $day = 120; $vote = 60; $night = 120;
            $timing_name = 'آرام (۱۲۰ ثانیه)';
            break;
        default:
            return ['success' => false, 'message' => '❌ گزینه نامعتبر!'];
    }
    
    $game['settings']['day_duration'] = $day;
    $game['settings']['vote_duration'] = $vote;
    $game['settings']['night_duration'] = $night;
    saveGame($game);
    
    $msg = "⚙️ <b>تایم بازی تغییر کرد!</b>\\n\\n";
    $msg .= "🎮 حالت: <b>" . $timing_name . "</b>\\n\\n";
    $msg .= "⏱ تایم‌ها:\\n";
    $msg .= "• 🌙 شب: " . $night . " ثانیه\\n";
    $msg .= "• ☀️ روز: " . $day . " ثانیه\\n";
    $msg .= "• 🗳️ رأی‌گیری: " . $vote . " ثانیه";
    
    return ['success' => true, 'message' => $msg];
}

/**
 * ➕ پیوستن به بازی
 */
function joinGame($code, $user_id, $user_name) {
    $game = getGame($code);

    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی با این کد پیدا نشد!'];
    }

    if ($game['status'] != 'waiting') {
        return ['success' => false, 'message' => '⏳ این بازی قبلاً شروع شده!'];
    }

    if (time() > $game['wait_until']) {
        return ['success' => false, 'message' => '⏰ زمان انتظار تمام شده!'];
    }

    foreach ($game['players'] as $player) {
        if ($player['id'] == $user_id) {
            return ['success' => false, 'message' => '❌ تو قبلاً تو این بازی هستی!'];
        }
    }

    if (count($game['players']) >= MAX_PLAYERS) {
        return ['success' => false, 'message' => '❌ ظرفیت بازی پر شده! (حداکثر ' . MAX_PLAYERS . ' نفر)'];
    }

    $game['players'][] = [
        'id' => $user_id,
        'name' => $user_name,
        'role' => null,
        'alive' => true,
        'role_data' => [],
        'afk_count' => 0,
        'afk_votes' => 0,
        'joined_at' => time()
    ];

    saveGame($game);

    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;

    return [
        'success' => true,
        'message' => '✅ ' . $user_name . ' به بازی پیوست!',
        'player_count' => count($game['players']),
        'time_remaining' => $minutes . ':' . sprintf("%02d", $seconds),
        'game' => $game
    ];
}

/**
 * ⏱ تمدید زمان
 */
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
    
    if ($game['extend_count'] >= MAX_EXTEND_COUNT) {
        return ['success' => false, 'message' => '❌ حداکثر ۳ بار!'];
    }
    
    $game['wait_until'] += EXTEND_TIME;
    $game['extend_count']++;
    saveGame($game);
    
    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "⏱ <b>زمان انتظار تمدید شد!</b>\\n\\n";
    $msg .= "➕ ۳۰ ثانیه\\n";
    $msg .= "📊 تمدیدها: " . $game['extend_count'] . "/3\\n";
    $msg .= "⏳ باقیمانده: " . $minutes . ":" . sprintf("%02d", $seconds);
    
    return [
        'success' => true,
        'message' => $msg,
        'game' => $game
    ];
}

/**
 * 🚪 خروج
 */
function leaveGame($user_id, $chat_id) {
    $game = getPlayerActiveGame($user_id);

    if (!$game) {
        return ['success' => false, 'message' => '❌ تو تو هیچ بازی فعالی نیستی!'];
    }

    if ($game['status'] == 'started') {
        return ['success' => false, 'message' => '❌ بازی شروع شده!'];
    }

    foreach ($game['players'] as $key => $player) {
        if ($player['id'] == $user_id) {
            unset($game['players'][$key]);
            $game['players'] = array_values($game['players']);
            break;
        }
    }

    if ($user_id == $game['creator_id'] && !empty($game['players'])) {
        $game['creator_id'] = $game['players'][0]['id'];
        $game['creator_name'] = $game['players'][0]['name'];
    }

    if (empty($game['players'])) {
        deleteGame($game['code']);
        return ['success' => true, 'message' => '✅ بازی لغو شد.'];
    }

    saveGame($game);

    return [
        'success' => true, 
        'message' => '✅ از بازی خارج شدی!',
        'game' => $game
    ];
}

// ==================== سیستم نقش‌دهی جدید برای ۶۰ نفر ====================

/**
 * 🎲 تخصیص نقش‌ها - الگوریتم نهایی
 */
function selectBalancedRoles($count) {
    $roles = [];
    
    // ✅ نقش‌های منحصربفرد روستا (فقط یکی)
    $uniqueVillageRoles = [
        'seer', 'apprentice_seer', 'guardian_angel', 'knight', 'hunter', 'harlot',
        'blacksmith', 'gunner', 'mayor', 'prince', 'detective', 'cupid', 'beholder',
        'phoenix', 'huntsman', 'trouble', 'chemist', 'fool', 'clumsy', 'cursed',
        'traitor', 'wild_child', 'wise_elder', 'sandman', 'sweetheart', 'ruler',
        'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong', 'princess', 'wolf_man', 'drunk'
    ];
    
    // ✅ نقش‌های تیم گرگ (خاص)
    $uniqueWolfRoles = [
        'alpha_wolf', 'wolf_cub', 'lycan', 'sorcerer', 'enchanter',
        'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'honey'
    ];
    
    // ✅ نقش‌های تیم فرقه (خاص)
    $uniqueCultRoles = ['royce', 'frankenstein', 'monk_black'];
    
    // ✅ نقش‌های تیم ومپایر (خاص)
    $uniqueVampireRoles = ['bloodthirsty', 'kent_vampire', 'chiang'];
    
    // ✅ نقش‌های مستقل (خالص - بدون تیم)
    $uniqueNeutralRoles = ['tanner', 'dian', 'dinamit', 'bomber', 'tso', 'doppelganger', 'lucifer', 'magento'];
    
    // ✅ محاسبه تعداد نقش‌ها
    $wolfCount = max(1, min(12, floor($count * 0.20)));                                              // 20% گرگ
    $cultCount = ($count >= 15) ? max(1, min(5, floor($count * 0.08))) : 0;             // 8% فرقه
    $vampireCount = ($count >= 20) ? max(1, min(4, floor($count * 0.06))) : 0;        // 6% ومپایر
    $killerCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0;      // 4% قاتل
    $fireIceCount = ($count >= 25) ? max(2, min(3, floor($count * 0.06))) : 0;     // 6% آتش و یخ
    $blackKnightCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0; // 4% شوالیه تاریکی
    $jokerCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0;       // 4% جوکر
    $neutralCount = ($count >= 25) ? max(1, min(2, floor($count * 0.04))) : 0;     // 4% مستقل

    // ==================== اضافه کردن نقش‌ها ====================
    
    // ✅ تیم روستا - نقش‌های خاص
    shuffle($uniqueVillageRoles);
    $villageUniqueCount = min(count($uniqueVillageRoles), floor($count * 0.35));
    for ($i = 0; $i < $villageUniqueCount && count($roles) < $count - $wolfCount - $cultCount - $vampireCount - $killerCount - $fireIceCount - $blackKnightCount - $jokerCount - $neutralCount - 5; $i++) {
        $roles[] = $uniqueVillageRoles[$i];
    }
    
    // ✅ تیم گرگ - حتماً آلفا گرگ + خاص‌ها + ساده
    shuffle($uniqueWolfRoles);
    $roles[] = 'alpha_wolf'; // لیدر
    $specialWolfCount = min(count($uniqueWolfRoles) - 1, max(0, floor($wolfCount / 2)));
    for ($i = 0; $i < $specialWolfCount; $i++) {
        if ($uniqueWolfRoles[$i] != 'alpha_wolf') {
            $roles[] = $uniqueWolfRoles[$i];
        }
    }
    // بقیه گرگ ساده
    $simpleWolfCount = $wolfCount - 1 - $specialWolfCount;
    for ($i = 0; $i < $simpleWolfCount; $i++) {
        $roles[] = 'werewolf';
    }
    
    // ✅ تیم فرقه - حتماً فرانکشتاین + خاص‌ها + ساده
    shuffle($uniqueCultRoles);
    $roles[] = 'frankenstein'; // لیدر جدید
    $specialCultCount = min(count($uniqueCultRoles) - 1, max(0, $cultCount - 1));
    for ($i = 0; $i < $specialCultCount; $i++) {
        if ($uniqueCultRoles[$i] != 'frankenstein') {
            $roles[] = $uniqueCultRoles[$i];
        }
    }
    // بقیه فرقه‌گرا ساده
    $simpleCultCount = $cultCount - 1 - $specialCultCount;
    for ($i = 0; $i < $simpleCultCount; $i++) {
        $roles[] = 'cultist';
    }
    
    // ✅ تیم ومپایر - حتماً bloodthirsty + خاص‌ها + ساده
    shuffle($uniqueVampireRoles);
    $roles[] = 'bloodthirsty'; // لیدر
    $specialVampCount = min(count($uniqueVampireRoles) - 1, max(0, $vampireCount - 1));
    for ($i = 0; $i < $specialVampCount; $i++) {
        if ($uniqueVampireRoles[$i] != 'bloodthirsty') {
            $roles[] = $uniqueVampireRoles[$i];
        }
    }
    // بقیه ومپایر ساده
    $simpleVampCount = $vampireCount - 1 - $specialVampCount;
    for ($i = 0; $i < $simpleVampCount; $i++) {
        $roles[] = 'vampire';
    }
    
    // ✅ تیم قاتل - حتماً serial_killer + احتمال archer یا davina
    $roles[] = 'serial_killer';
    if ($killerCount > 1) {
        $killerBuddy = (rand(0, 1) == 0) ? 'archer' : 'davina';
        $roles[] = $killerBuddy;
    }
    
    // ✅ تیم آتش و یخ - حتماً fire_king + ice_queen + احتمال lilith
    $roles[] = 'fire_king';
    $roles[] = 'ice_queen';
    if ($fireIceCount > 2) {
        $roles[] = 'lilith';
    }
    
    // ✅ تیم شوالیه تاریکی - حتماً black_knight + احتمال bride_dead
    $roles[] = 'black_knight';
    if ($blackKnightCount > 1) {
        $roles[] = 'bride_dead';
    }
    
    // ✅ تیم جوکر - حتماً joker + احتمال harly
    $roles[] = 'joker';
    if ($jokerCount > 1) {
        $roles[] = 'harly';
    }
    
    // ✅ نقش‌های مستقل خالص
    shuffle($uniqueNeutralRoles);
    for ($i = 0; $i < $neutralCount; $i++) {
        $roles[] = $uniqueNeutralRoles[$i];
    }
    
    // ✅ اضافه کردن بناها (2-4 تا)
    $masonCount = min(4, max(2, floor($count / 15)));
    for ($i = 0; $i < $masonCount && count($roles) < $count - 2; $i++) {
        $roles[] = 'builder';
    }
    
    // ✅ بقیه روستایی ساده
    while (count($roles) < $count) {
        $roles[] = 'villager';
    }
    
    return $roles;
}

/**
 * 🎲 تخصیص نقش‌ها
 */
function assignRoles($game) {
    $player_count = count($game['players']);

    if ($player_count < MIN_PLAYERS) {
        return ['success' => false, 'message' => '❌ حداقل ' . MIN_PLAYERS . ' نفر!'];
    }
    
    if ($player_count > MAX_PLAYERS) {
        return ['success' => false, 'message' => '❌ حداکثر ' . MAX_PLAYERS . ' نفر!'];
    }

    $selectedRoles = selectBalancedRoles($player_count);
    shuffle($selectedRoles);

    foreach ($game['players'] as $i => &$player) {
        $player['role'] = $selectedRoles[$i];
        $player['original_role'] = $selectedRoles[$i];
    }

    $game['roles_assigned'] = true;
    $game['status'] = 'started';
    $game['started'] = time();
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    unset($game['wait_until'], $game['extend_count']);

    if (!$game['time_set']) {
        $game['settings']['day_duration'] = 60;
        $game['settings']['vote_duration'] = 60;
        $game['settings']['night_duration'] = 60;
        $game['time_set'] = true;
    }

    saveGame($game);

    return [
        'success' => true,
        'message' => '🎭 نقش‌ها تخصیص داده شد!',
        'game' => $game
    ];
}

/**
 * ▶️ شروع بازی
 */
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
        deleteGame($game['code']);
        return [
            'success' => false, 
            'message' => '❌ تعداد بازیکنان کافی نبود! (' . $playerCount . '/' . MIN_PLAYERS . ')\\nبازی لغو شد.'
        ];
    }

    if (!$game['time_set']) {
        $game['settings']['day_duration'] = 60;
        $game['settings']['vote_duration'] = 60;
        $game['settings']['night_duration'] = 60;
        $game['time_set'] = true;
    }

    $result = assignRoles($game);
    if (!$result['success']) {
        return $result;
    }

    $game = $result['game'];

    foreach ($game['players'] as $player) {
        sendRoleAssignment($player, $game);
    }

    $msg = "🎮 <b>بازی شروع شد!</b>\\n\\n";
    $msg .= "👥 بازیکنان: " . $playerCount . "\\n";
    $msg .= "🐺 گرگ‌ها: ~" . floor($playerCount / 5) . " نفر\\n";
    $msg .= "⏱ تایم: " . $game['settings']['day_duration'] . "s / " . $game['settings']['vote_duration'] . "s\\n";
    $msg .= "🎭 نقش‌ها در پیام خصوصی ارسال شد\\n";
    $msg .= "🌙 شب اول شروع می‌شود...";

    sendMessage($game['group_id'], $msg);

    startNightPhase($game);

    return [
        'success' => true,
        'message' => $msg,
        'game' => $game
    ];
}

/**
 * 📨 ارسال نقش
 */
function sendRoleAssignment($player, $game) {
    $role = RoleFactory::create($player['role'], $player, $game);

    $msg = "🎭 <b>نقش شما: " . $role->getEmoji() . " " . $role->getName() . "</b>\\n\\n";
    $msg .= $role->getDescription();

    $team = $role->getTeam();
    if ($team == 'werewolf') {
        $teamMates = getWolfTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🐺 <b>بقیه گرگ‌ها:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'cult') {
        $teamMates = getCultTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n👤 <b>بقیه فرقه‌گراها:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'vampire') {
        $teamMates = getVampireTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🧛 <b>بقیه ومپایرها:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'killer') {
        $teamMates = getKillerTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🔪 <b>بقیه قاتل‌ها:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'fire_ice') {
        $teamMates = getFireIceTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🔥❄️ <b>بقیه تیم آتش و یخ:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'black_knight') {
        $teamMates = getBlackKnightTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🥷 <b>بقیه شوالیه‌های تاریکی:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    } elseif ($team == 'joker') {
        $teamMates = getJokerTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "\\n\\n🤡 <b>بقیه تیم جوکر:</b>\\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\\n";
            }
        }
    }

    $msg .= "\\n\\n🤫 <b>رازت رو نگه دار!</b>";

    sendPrivateMessage($player['id'], $msg);
}

function getVampireTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getKillerTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['serial_killer', 'archer', 'davina'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getFireIceTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['fire_king', 'ice_queen', 'lilith'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getBlackKnightTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['black_knight', 'bride_dead'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getJokerTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['joker', 'harly'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

/**
 * 🌙 شروع شب
 */
function startNightPhase($game) {
    $game['phase'] = 'night';
    $game['night_actions'] = [];
    $game['vote_skipped'] = [];

    foreach ($game['players'] as &$player) {
        if (isset($player['role_data'])) {
            unset($player['role_data']['vote_target']);
            unset($player['role_data']['tonight_target']);
        }
        if (isset($player['imprisoned'])) {
            unset($player['imprisoned']);
        }
    }

    saveGame($game);

    $nightDuration = $game['settings']['night_duration'] ?? 60;

    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;

        $role = RoleFactory::create($player['role'], $player, $game);

        if ($role->hasNightAction()) {
            sendNightPanel($player, $role, $game);
        } else {
            sendPrivateMessage($player['id'], 
                "🌙 <b>شب " . $game['night_count'] . "</b>\\n\\n" .
                "تو می‌تونی بخوابی... فردا صبح بیدارت می‌کنیم!"
            );
        }
    }

    $groupMsg = "🌙 <b>شب " . $game['night_count'] . "!</b>\\n\\n";
    $groupMsg .= "همه بخوابید...\\n";
    $groupMsg .= "⏱ " . $nightDuration . " ثانیه تا صبح";

    sendMessage($game['group_id'], $groupMsg);

    $game['night_end'] = time() + $nightDuration;
    saveGame($game);
}

/**
 * 📨 پنل شب
 */
function sendNightPanel($player, $role, $game) {
    $targets = $role->getValidTargets('night');

    if (empty($targets)) {
        return;
    }

    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\\n\\n";
    $msg .= "تو " . $role->getEmoji() . " <b>" . $role->getName() . "</b> هستی.\\n\\n";
    $msg .= $role->getDescription() . "\\n\\n";
    $msg .= "👇 <b>یک نفر رو انتخاب کن:</b>";

    $buttons = [];
    foreach ($targets as $target) {
        $buttons[] = [
            'text' => $target['name'],
            'callback_data' => $target['callback']
        ];
    }

    $keyboard = array_chunk($buttons, 2);

    // ✅ تیم‌هایی که می‌تونن skip کنن:
    $skipRoles = ['werewolf', 'cultist', 'serial_killer', 'vampire', 
                  'fire_king', 'ice_queen', 'lilith', 
                  'black_knight', 'bride_dead',
                  'joker', 'harly', 'archer', 'davina'];
    
   $evilRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 
              'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
              'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
              'serial_killer', 'archer', 'davina',
              'cultist', 'royce', 'frankenstein', 'monk_black',
              'fire_king', 'ice_queen', 'lilith',
              'black_knight', 'bride_dead',
              'joker', 'harly'];

if (in_array($player['role'], $evilRoles)) {
    $keyboard[] = [['text' => '💬 چت با تیم', 'callback_data' => 'team_chat']];
}

if (in_array($player['role'], ['werewolf', 'cultist', 'serial_killer', 'vampire'])) {
    $keyboard[] = [['text' => '⏭️ رد کردن', 'callback_data' => $player['role'] . '_skip']];
}
    }

    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

/**
 * ☀️ شروع روز
 */
function startDayPhase($game) {
    $game['phase'] = 'day';
    $game['day_count']++;

    $results = processNightActions($game);
    $game = $results['game'];

    saveGame($game);

    $msg = "☀️ <b>صبح روز " . $game['day_count'] . "!</b>\\n\\n";

    if (!empty($results['messages'])) {
        $msg .= implode("\\n", $results['messages']) . "\\n\\n";
    }

    if (!empty($results['deaths'])) {
        $msg .= "💀 <b>کشته شدگان:</b>\\n";
        foreach ($results['deaths'] as $death) {
            $msg .= "• <b>" . $death['name'] . "</b> - " . $death['role'] . "\\n";
        }
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>";
    }

    sendMessage($game['group_id'], $msg);

    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }

    $aliveList = getAlivePlayersList($game);
    sendMessage($game['group_id'], $aliveList);

    $dayDuration = $game['settings']['day_duration'] ?? 60;
    
    $dayMsg = "🗣 <b>زمان بحث!</b>\\n\\n";
    $dayMsg .= "شما " . $dayDuration . " ثانیه وقت دارید.\\n";
    $dayMsg .= "بعدش رأی‌گیری خودکار شروع می‌شه!";

    sendMessage($game['group_id'], $dayMsg);

    $game['discussion_end'] = time() + $dayDuration;
    saveGame($game);
}

/**
 * ⚙️ پردازش اکشن‌های شب
 */
function processNightActions($game) {
    $deaths = [];
    $messages = [];

    $actions = $game['night_actions'] ?? [];

    $actionOrder = ['save', 'guard', 'convert', 'bite', 'kill', 'vote_eat', 'hunt'];
    usort($actions, function($a, $b) use ($actionOrder) {
        $aPriority = array_search($a['action'], $actionOrder);
        $bPriority = array_search($b['action'], $actionOrder);
        return $aPriority - $bPriority;
    });

    $protected = [];
    foreach ($actions as $action) {
        if ($action['action'] == 'save') {
            $protected[] = $action['target'];
        }
        if ($action['action'] == 'guard') {
            $protected[] = $action['target'];
        }
    }

    $attacks = [];
    foreach ($actions as $action) {
        if (in_array($action['action'], ['vote_eat', 'kill', 'bite', 'hunt'])) {
            $targetId = $action['target'];

            if (in_array($targetId, $protected)) {
                $target = getPlayerById($game, $targetId);
                $messages[] = "🛡️ {$target['name']} نجات پیدا کرد!";
                continue;
            }

            foreach ($game['players'] as $p) {
                if ($p['id'] == $targetId && !empty($p['role_data']['phoenix_tear'])) {
                    $target = getPlayerById($game, $targetId);
                    $messages[] = "💧 {$target['name']} با اشک ققنوس زنده موند!";
                    unset($p['role_data']['phoenix_tear']);
                    continue 2;
                }
            }

            $attacks[] = $action;
        }
    }

    foreach ($attacks as $attack) {
        $targetId = $attack['target'];
        $target = getPlayerById($game, $targetId);

        if (!$target || !($target['alive'] ?? false)) continue;

        $game = killPlayer($game, $targetId, $attack['action']);
        $deaths[] = [
            'id' => $targetId,
            'name' => $target['name'],
            'role' => getRoleDisplayName($target['role'])
        ];
    }

    saveGame($game);

    return [
        'game' => $game,
        'deaths' => $deaths,
        'messages' => $messages
    ];
}

/**
 * 💀 کشتن بازیکن
 */
function killPlayer($game, $playerId, $cause) {
    $player = null;
    foreach ($game['players'] as &$p) {
        if ($p['id'] == $playerId) {
            $p['alive'] = false;
            $p['death_cause'] = $cause;
            $p['death_time'] = time();
            $p['death_night'] = $game['night_count'] ?? 0;
            $player = $p;
            break;
        }
    }

    if (!empty($game['lovers'])) {
        foreach ($game['lovers'] as $pair) {
            if ($pair[0] == $playerId && !isPlayerDead($game, $pair[1])) {
                $game = killPlayer($game, $pair[1], 'love');
            } elseif ($pair[1] == $playerId && !isPlayerDead($game, $pair[0])) {
                $game = killPlayer($game, $pair[0], 'love');
            }
        }
    }

    saveGame($game);
    return $game;
}

function isPlayerDead($game, $playerId) {
    foreach ($game['players'] as $p) {
        if ($p['id'] == $playerId) {
            return !($p['alive'] ?? false);
        }
    }
    return false;
}

// ==================== سیستم رأی‌گیری ====================

/**
 * 🗳️ شروع خودکار رأی‌گیری
 */
function autoStartVoting($gameCode) {
    $game = getGame($gameCode);
    
    if (!$game || $game['status'] != 'started' || $game['phase'] != 'day') {
        return false;
    }

    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_start'] = time();
    unset($game['discussion_end']);
    saveGame($game);

    $alive = getAlivePlayers($game);
    $aliveCount = count($alive);
    $voteDuration = $game['settings']['vote_duration'] ?? 60;

    $groupMsg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "!</b>\\n\\n";
    $groupMsg .= "⏱ <b>" . $voteDuration . " ثانیه</b>\\n";
    $groupMsg .= "👥 زنده‌ها: " . $aliveCount . "\\n\\n";
    $groupMsg .= "📩 <b>به صورت خصوصی رأی بدید!</b>";

    sendMessage($game['group_id'], $groupMsg);

    foreach ($alive as $player) {
        sendPrivateVotePanelPaginated($player, $game, 1);
    }

    $game['vote_end'] = time() + $voteDuration;
    saveGame($game);

    return true;
}

/**
 * 📨 پنل رأی با صفحه‌بندی
 */
function sendPrivateVotePanelPaginated($player, $game, $page = 1) {
    $playersPerPage = 6;
    
    $alive = getAlivePlayers($game);
    $alive = array_values(array_filter($alive, fn($p) => $p['id'] != $player['id']));
    
    $totalPlayers = count($alive);
    $totalPages = ceil($totalPlayers / $playersPerPage);
    
    $start = ($page - 1) * $playersPerPage;
    $pagePlayers = array_slice($alive, $start, $playersPerPage);
    
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\\n\\n";
    $msg .= "📄 صفحه <b>" . $page . "</b> از <b>" . $totalPages . "</b>\\n";
    $msg .= "👇 یک نفر رو انتخاب کن:";
    
    $buttons = [];
    
    foreach ($pagePlayers as $p) {
        $buttons[] = [
            'text' => "💀 " . mb_substr($p['name'], 0, 10),
            'callback_data' => 'vote_' . $p['id'] . '_' . $game['code']
        ];
    }
    
    $keyboard = array_chunk($buttons, 2);
    
    $navButtons = [];
    
    if ($page > 1) {
        $navButtons[] = ['text' => '◀️', 'callback_data' => 'votepage_' . ($page-1) . '_' . $game['code']];
    }
    
    $navButtons[] = ['text' => '⚪ سفید', 'callback_data' => 'vote_skip_' . $game['code']];
    
    if ($page < $totalPages) {
        $navButtons[] = ['text' => '▶️', 'callback_data' => 'votepage_' . ($page+1) . '_' . $game['code']];
    }
    
    $keyboard[] = $navButtons;
    
    if ($totalPages > 1) {
        $pageButtons = [];
        $startPage = max(1, $page - 1);
        $endPage = min($totalPages, $page + 1);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $text = ($i == $page) ? "·$i·" : "$i";
            $pageButtons[] = ['text' => $text, 'callback_data' => 'votepage_' . $i . '_' . $game['code']];
        }
        $keyboard[] = $pageButtons;
    }
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

/**
 * 🗳️ ثبت رأی
 */
function castVote($voterId, $targetId, $gameCode) {
    $game = getGame($gameCode);
    if (!$game || $game['phase'] != 'vote') {
        return ['success' => false, 'message' => '⏳ الان زمان رأی نیست!'];
    }

    $voter = getPlayerById($game, $voterId);
    if (!$voter || !($voter['alive'] ?? false)) {
        return ['success' => false, 'message' => '💀 شما مرده‌اید!'];
    }

    if (isset($game['votes'][$voterId])) {
        return ['success' => false, 'message' => '❌ قبلاً رأی دادید!'];
    }

    if ($targetId != 'skip') {
        $target = getPlayerById($game, $targetId);
        if (!$target || !($target['alive'] ?? false)) {
            return ['success' => false, 'message' => '❌ این بازیکن مرده!'];
        }
    }

    $game['votes'][$voterId] = $targetId;
    
    foreach ($game['players'] as &$p) {
        if ($p['id'] == $voterId) {
            $p['afk_votes'] = 0;
            break;
        }
    }
    
    saveGame($game);

    $voteCount = count($game['votes']);
    $aliveCount = count(getAlivePlayers($game));
    
    if ($targetId == 'skip') {
        $groupMsg = "🗳️ <b>" . $voter['name'] . "</b> رأی <b>سفید</b> داد!\\n";
    } else {
        $target = getPlayerById($game, $targetId);
        $groupMsg = "🗳️ <b>" . $voter['name'] . "</b> رأی داد!\\n";
    }
    $groupMsg .= "📊 <b>" . $voteCount . " / " . $aliveCount . "</b>";
    
    sendMessage($game['group_id'], $groupMsg);

    if ($targetId == 'skip') {
        $confirmMsg = "✅ رأی سفید ثبت شد.";
    } else {
        $target = getPlayerById($game, $targetId);
        $confirmMsg = "✅ رأی شما ثبت شد.";
    }
    sendPrivateMessage($voterId, $confirmMsg);

    if ($voteCount >= $aliveCount) {
        autoEndVoting($gameCode);
    }

    return ['success' => true, 'message' => 'رأی ثبت شد'];
}

/**
 * ⚖️ پایان رأی‌گیری
 */
function autoEndVoting($gameCode) {
    $game = getGame($gameCode);
    if (!$game || $game['phase'] != 'vote') return;

    $alive = getAlivePlayers($game);
    $afkPlayers = [];
    
    foreach ($alive as $player) {
        if (!isset($game['votes'][$player['id']])) {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player['id']) {
                    $p['afk_votes'] = ($p['afk_votes'] ?? 0) + 1;
                    
                    if ($p['afk_votes'] >= AFK_THRESHOLD) {
                        $afkPlayers[] = $p;
                    }
                    break;
                }
            }
        }
    }
    saveGame($game);

    foreach ($afkPlayers as $afkPlayer) {
        $game = killPlayer($game, $afkPlayer['id'], 'afk');
        
        $afkMsg = "😴 <b>" . $afkPlayer['name'] . "</b> به خاطر غیرفعالی حذف شد!";
        sendMessage($game['group_id'], $afkMsg);
    }

    $counts = [];
    $skipCount = 0;
    
    foreach ($game['votes'] as $voterId => $targetId) {
        if ($targetId == 'skip') {
            $skipCount++;
        } else {
            $counts[$targetId] = ($counts[$targetId] ?? 0) + 1;
        }
    }

    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);

    $msg = "🗳️ <b>نتیجه رأی‌گیری روز " . $game['day_count'] . "</b>\\n\\n";
    
    $msg .= "📊 آمار:\\n";
    $msg .= "• رأی‌ها: " . count($game['votes']) . "\\n";
    $msg .= "• سفید: " . $skipCount . "\\n";
    if (!empty($afkPlayers)) {
        $msg .= "• حذف شده: " . count($afkPlayers) . "\\n";
    }
    $msg .= "\\n";

    if (count($targets) == 1 && $max > 0) {
        $targetId = $targets[0];
        $targetPlayer = getPlayerById($game, $targetId);
        
        if ($targetPlayer && ($targetPlayer['alive'] ?? false)) {
            $msg .= "💀 <b>" . $targetPlayer['name'] . "</b> اعدام شد!\\n";
            $msg .= "🎭 نقش: " . getRoleDisplayName($targetPlayer['role']) . "\\n\\n";

            if ($targetPlayer['role'] == 'tanner') {
                $msg .= "🎉 <b>منافق برنده شد!</b>";
                sendMessage($game['group_id'], $msg);
                endGame($game, ['ended' => true, 'winner' => 'tanner', 'message' => $msg]);
                return;
            }

            $game = killPlayer($game, $targetId, 'lynch');
        } else {
            $msg .= "⚖️ <b>هدف قبلاً حذف شده بود!</b>";
        }

    } else {
        $msg .= "⚖️ <b>مساوی شد! کسی اعدام نمی‌شه.</b>";
    }

    sendMessage($game['group_id'], $msg);

    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }

    $game['night_count']++;
    $game['phase'] = 'night';
    $game['votes'] = [];
    unset($game['vote_start'], $game['vote_end']);
    saveGame($game);

    startNightPhase($game);
}

// ==================== توابع کمکی ====================

function getAlivePlayers($game) {
    return array_filter($game['players'], function($p) {
        return $p['alive'] ?? false;
    });
}

function getPlayerById($game, $id) {
    foreach ($game['players'] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

function getWolfTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'beta_wolf', 'ice_wolf', 'white_wolf', 'lycan', 'honey', 'forest_queen', 'enchanter', 'sorcerer'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getCultTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['cultist', 'royce', 'frankenstein', 'monk_black'])) {
            if ($excludeId && $p['id'] == $excludeId) continue;
            if (!($p['alive'] ?? false)) continue;
            $team[] = $p;
        }
    }
    return $team;
}

function getAlivePlayersList($game) {
    $alive = getAlivePlayers($game);
    $msg = "👥 <b>زنده‌ها (" . count($alive) . "):</b>\\n";
    foreach ($alive as $p) {
        $msg .= "• " . $p['name'] . "\\n";
    }
    return $msg;
}

function getRoleDisplayName($role) {
    $names = [
        'villager' => '👨‍🌾 روستایی ساده',
        'seer' => '👳🏻‍♂️ پیشگو',
         'cult_hunter' => '💂🏻‍♂️ شکارچی',
        'guardianAngel' => '👼🏻 فرشته نگهبان',
        'detective' => '🕵🏻‍♂️ کاراگاه',
         'gunner' => '🔫 تفنگدار',
        'mayor' => '🎖️ کدخدا',
         'prince' => '🤴🏻 شاهزاده',
        'builder' => '👷🏻‍♂️ بنا',
        'apprentice_seer' => '🙇🏻‍♂️ شاگرد پیشگو',
        'beholder' => '👁 شاهد',
        'cupid' => '💘 الهه عشق',
        'harlot' => '💋 ناتاشا',
        'cursed' => '😾 نفرین شده',
        'drunk' => '🍻 مست',
        'traitor' => '🖕🏿 خائن',
        'wise_elder' => '📚 ریش سفید',
        'blacksmith' => '⚒ آهنگر',
        'sandman' => '💤 خوابگزار',
        'marouf' => '🛡️🌿 معروف',
        'hunter' => '👮🏻‍♂️ کلانتر',
         'lycan' => '🌝🐺 گرگ ایکس',
         'wolf_man' => '🌚👨🏻 گرگنما',
         'clumsy' => '🤕 پسر گیج',
         'werewolf' => '🐺 گرگینه',
         'alpha_wolf' => '⚡️🐺 گرگ آلفا',
         'wolf_cub' => '🐶 توله گرگ',
        'sorcerer' => '🔮 جادوگر',
        'enchanter' => '🧙🏻‍♂️ افسونگر',
        'forest_queen' => '🧝🏻‍♀️🐺 ملکه جنگل',
        'white_wolf' => '🐺🌩 گرگ سفید',
         'honey' => '🧙🏻‍♀️ عجوزه',
        'beta_wolf' => '💤🐺 گرگ خوابالو',
         'ice_wolf' => '🐺☃️ گرگ برفی',
         'cultist' => '👤 فرقه‌گرا',
        'royce' => '🎩 رئیس فرقه',
        'serial_killer' => '🔪 قاتل',
        'archer' => '🏹 کماندار',
         'fool' => '🃏 احمق',
        'tanner' => '👺 منافق',
        'joker' => '🤡 جوکر',
        'harly' => '👩🏻‍🎤 هارلی کویین',
        'bomber' => '💣 بمب گذار',
        'fireKing' => '🔥🤴🏻 پادشاه آتش',
         'ice_queen' => '❄️👸🏻 ملکه یخی',
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'kent_vampire' => '💍🧛🏻 کنت ومپایر',
         'lucifer' => '👹 لوسیفر',
        'lilith' => '🐍👩🏻‍🦳 لیلیث',
        'dian' => '🧞‍♂️ دیان',
        'black_knight' => '🥷🗡 شوالیه تاریکی',
        'bride_dead' => '👰‍♀☠️ عروس مردگان',
        'magento' => '🧲 مگنیتو',
        'princess' => '👸🏻 پرنسس',
         'phoenix' => '🪶 ققنوس',
        'wild_child' => '👶🏻 بچه وحشی',
        'hamal' => '🛒 حمال',
       'jumong' => '🏹⚔️ جومونگ',
        'trouble' => '👩🏻‍🌾 دختر دردسرساز',
        'frankenstein' => '🧟‍♂️🪖 فرانکشتاین',
        'huntsman' => '🪓 هانتسمن',
        'ruler' => '👑 حاکم',
        'sweetheart' => '👰🏻 دلبر',
        'monk_black' => '🦇 راهب سیاه',
        'chemist' => '👨‍🔬 شیمیدان',
        'chiang' => '👩‍🦳 چیانگ',
         'davina' => '🍾 داوینا',
        'knight' => '🗡 شوالیه',
        'spy' => '🦹🏻‍♂️ جاسوس',
        'dinamit' => '🧨 دینامیت',
        'tso' => '⚔️ تسو',
        'doppelganger' => '👯 همزاد',
       'fire_king' => '🔥🤴🏻 پادشاه آتش',
         'ice_queen' => '❄️👸🏻 ملکه یخی',
        'lilith' => '🐍👩🏻‍🦳 لیلیث',
        'lucifer' => '😈 لوسیفر',
        'black_knight' => '🥷🗡 شوالیه تاریکی',
        'bride_dead' => '👰‍♀☠️ عروس مردگان',
        'joker' => '🤡 جوکر',
        'harly' => '👩🏻‍🎤 هارلی کویین',
        'serial_killer' => '🔪 قاتل زنجیره‌ای',
        'archer' => '🏹 کماندار',
         'davina' => '🍾 داوینا',
        'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'kent_vampire' => '💍🧛🏻 کنت ومپایر',
         'chiang' => '👩‍🦳 چیانگ',
         'vampire' => '🧛🏻‍♂️ ومپایر',
        'frankenstein' => '🧟‍♂️🪖 فرانکشتاین',
        'monk_black' => '🦇 راهب سیاه',
        'royce' => '🎩 رئیس فرقه',
        'cultist' => '👤 فرقه‌گرا',
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function checkWinCondition($game) {
    $alive = getAlivePlayers($game);
    $totalAlive = count($alive);

    if ($totalAlive == 0) {
        return ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردن!'];
    }

    $teams = [];
    foreach ($alive as $p) {
        $role = RoleFactory::create($p['role'], $p, $game);
        $team = $role->getTeam();
        $teams[$team] = ($teams[$team] ?? 0) + 1;
    }

    $wolves = $teams['werewolf'] ?? 0;
    $villagers = ($teams['villager'] ?? 0) + ($teams['village'] ?? 0);
    $cult = $teams['cult'] ?? 0;
    $killers = $teams['killer'] ?? 0;
    $vampires = $teams['vampire'] ?? 0;
    $jokers = $teams['joker'] ?? 0;
    $fireIce = $teams['fire_ice'] ?? 0;           //  
    $blackKnights = $teams['black_knight'] ?? 0;  // 

    // جوکر با 3 طومار برنده میشه
    if ($jokers > 0) {
        foreach ($alive as $p) {
            if ($p['role'] == 'joker' && !empty($p['role_data']['scrolls']) && $p['role_data']['scrolls'] >= 3) {
                return [
                    'ended' => true,
                    'winner' => 'joker',
                    'message' => '🤡 <b>جوکر برنده شد!</b>'
                ];
            }
        }
    }

    // گرگ‌ها برنده میشن
    if ($wolves > 0 && $wolves >= $villagers && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0) {
        return [
            'ended' => true,
            'winner' => 'werewolf',
            'message' => '🐺 <b>گرگ‌ها برنده شدند!</b>'
        ];
    }

    // روستایی‌ها برنده میشن
    if ($wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0) {
        return [
            'ended' => true,
            'winner' => 'villager',
            'message' => '👨‍🌾 <b>روستایی‌ها برنده شدند!</b>'
        ];
    }

    // فرقه برنده میشه
    if ($cult > $totalAlive / 2) {
        return [
            'ended' => true,
            'winner' => 'cult',
            'message' => '👤 <b>فرقه برنده شد!</b>'
        ];
    }

    // قاتل برنده میشه
    if ($killers >= 1 && $wolves == 0 && $cult == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0) {
        // اگه 1-2 نفر بمونن و قاتل زنده باشه
        if ($totalAlive <= 3 || ($killers == $totalAlive)) {
            return [
                'ended' => true,
                'winner' => 'killer',
                'message' => '🔪 <b>قاتل‌ها برنده شدند!</b>'
            ];
        }
    }

    // ومپایر برنده میشه
    if ($vampires > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $fireIce == 0 && $blackKnights == 0) {
        return [
            'ended' => true,
            'winner' => 'vampire',
            'message' => '🧛 <b>ومپایرها برنده شدند!</b>'
        ];
    }

    // ✅ آتش و یخ برنده میشن
    if ($fireIce > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $blackKnights == 0) {
        return [
            'ended' => true,
            'winner' => 'fire_ice',
            'message' => '🔥❄️ <b>تیم آتش و یخ برنده شد!</b>'
        ];
    }

    // ✅ شوالیه تاریکی برنده میشه
    if ($blackKnights > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0) {
        return [
            'ended' => true,
            'winner' => 'black_knight',
            'message' => '🥷 <b>شوالیه‌های تاریکی برنده شدند!</b>'
        ];
    }

    return ['ended' => false];
}
function endGame($game, $winCheck) {
    $game['status'] = 'ended';
    $game['ended'] = time();
    $game['winners'] = $winCheck['winner'];

    saveGame($game);

    $msg = "🏁 <b>بازی تمام شد!</b>\n\n";
    $msg .= $winCheck['message'] . "\n\n";
    $msg .= "📊 <b>آمار:</b>\n";

    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? false) ? '🟢' : '💀';
        $role = getRoleDisplayName($p['role']);
        $winner = ($p['alive'] ?? false) ? '👑' : '';
        $msg .= "$status {$p['name']} - $role $winner\n";
    }

    sendMessage($game['group_id'], $msg);

    scheduleGameCleanup($game['code']);
}

function cancelGame($group_id, $user_id) {
    $game = getGroupActiveGame($group_id);

    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }

    if ($user_id != $game['creator_id'] && $user_id != ADMIN_ID) {
        return ['success' => false, 'message' => '❌ فقط سازنده!'];
    }

    deleteGame($game['code']);

    return [
        'success' => true,
        'message' => '❌ بازی لغو شد!'
    ];
}

function getGameInfo($group_id) {
    $game = getGroupActiveGame($group_id);

    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }

    $msg = "🎮 <b>وضعیت بازی</b>\n\n";
    $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
    $msg .= "👤 سازنده: " . $game['creator_name'] . "\n";
    $msg .= "📊 وضعیت: " . getStatusText($game['status']) . "\n";

    if ($game['status'] == 'waiting') {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $msg .= "⏱ زمان: " . $minutes . ":" . sprintf("%02d", $seconds) . "\n";
        $msg .= "🔄 تمدیدها: " . ($game['extend_count'] ?? 0) . "/3\n";
        
        if ($game['time_set']) {
            $msg .= "⚙️ تایم: " . ($game['settings']['day_duration'] ?? 60) . "s\n";
        } else {
            $msg .= "⚠️ تایم تنظیم نشده!\n";
        }
    }

    if ($game['status'] == 'started') {
        $msg .= "🌙 شب: " . $game['night_count'] . "\n";
        $msg .= "☀️ روز: " . $game['day_count'] . "\n";
        $msg .= "🔄 فاز: " . getPhaseText($game['phase']) . "\n";
    }

    $msg .= "\n👥 بازیکنان (" . count($game['players']) . "):\n";
    
    // ✅ صفحه‌بندی برای 60 نفر
    $playerCount = 0;
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? true) ? '🟢' : '💀';
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "$status {$p['name']} $creator\n";
        $playerCount++;
        
        if ($playerCount % 30 == 0 && $playerCount < count($game['players'])) {
            $msg .= "... (ادامه داره)\n";
            break;
        }
    }

    return ['success' => true, 'message' => $msg];
}

function getStatusText($status) {
    $map = [
        'waiting' => '⏳ انتظار',
        'started' => '▶️ اجرا',
        'ended' => '🏁 تمام'
    ];
    return $map[$status] ?? $status;
}

function getPhaseText($phase) {
    $map = [
        'night' => '🌙 شب',
        'day' => '☀️ روز',
        'vote' => '🗳️ رأی'
    ];
    return $map[$phase] ?? $phase;
}

/**
 * 🔄 تبدیل بازیکن به فرقه (با قطع چت قبلی)
 */
function convertToCult($game, $targetId) {
    foreach ($game['players'] as &$p) {
        if ($p['id'] == $targetId) {
            // ذخیره تیم قبلی
            $oldRole = $p['role'];
            $oldTeam = detectTeam($oldRole);
            
            $p['role'] = 'cultist';
            $p['original_role'] = $oldRole;
            $p['converted_at'] = time();
            $p['converted_from'] = $oldTeam;
            $p['converted_to'] = 'cult';
            
            break;
        }
    }
    saveGame($game);
    return $game;
}

/**
 * 🔍 تشخیص تیم از روی نقش
 */
function detectTeam($role) {
    $teams = [
        'werewolf' => ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'],
        'vampire' => ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'],
        'cult' => ['cultist', 'royce', 'frankenstein', 'monk_black'],
        'killer' => ['serial_killer', 'archer', 'davina'],
        'fire_ice' => ['fire_king', 'ice_queen', 'lilith', 'magento'],
        'black_knight' => ['black_knight', 'bride_dead'],
        'joker' => ['joker', 'harly'],
    ];
    
    foreach ($teams as $team => $roles) {
        if (in_array($role, $roles)) return $team;
    }
    return 'independent'; // مستقل‌ها
}

function setLovers($game, $id1, $id2) {
    $game['lovers'][] = [$id1, $id2];
    saveGame($game);
    return $game;
}

/**
 * 💬 پردازش چت تیمی
 */
function handleTeamChat($user_id, $message, $gameCode) {
    $game = getGame($gameCode);
    if (!$game || $game['phase'] != 'night') {
        return ['success' => false, 'message' => '❌ الان زمان چت تیمی نیست! فقط شب‌ها می‌توانید چت کنید.'];
    }
    
    $player = getPlayerById($game, $user_id);
    if (!$player || !($player['alive'] ?? false)) {
        return ['success' => false, 'message' => '💀 شما مرده‌اید!'];
    }
    
    // چک کردن زندانی
    if (!empty($player['imprisoned'])) {
        return ['success' => false, 'message' => '🔒 شما زندانی کلانتر هستید! نمی‌توانید چت کنید.'];
    }
    
    $role = RoleFactory::create($player['role'], $player, $game);
    $team = $role->getTeam();
    
    // فقط تیم‌های منفی
    $evilTeams = ['werewolf', 'cult', 'vampire', 'killer', 'fire_ice', 'black_knight', 'joker'];
    if (!in_array($team, $evilTeams)) {
        return ['success' => false, 'message' => '❌ تیم شما به چت تیمی دسترسی ندارد!'];
    }
    
    // پیدا کردن هم‌تیمی‌ها
    $teamMates = [];
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) continue;
        if (!($p['alive'] ?? false)) continue;
        if (!empty($p['imprisoned'])) continue;
        
        $mateRole = RoleFactory::create($p['role'], $p, $game);
        $mateTeam = $mateRole->getTeam();
        
        if (!empty($p['converted_to'])) {
            $mateTeam = $p['converted_to'];
        }
        
        if ($mateTeam == $team) {
            $teamMates[] = $p;
        }
    }
    
    if (empty($teamMates)) {
        return ['success' => false, 'message' => '❌ هم‌تیمی فعالی ندارید!'];
    }
    
    // ارسال پیام
    $senderName = $player['name'];
    $teamIcons = [
        'werewolf' => '🐺', 'vampire' => '🧛', 'cult' => '👤',
        'killer' => '🔪', 'fire_ice' => '🔥❄️', 'black_knight' => '🥷', 'joker' => '🤡'
    ];
    $icon = $teamIcons[$team] ?? '👥';
    $formattedMsg = "$icon <b>[$senderName]:</b>\n$message";
    
    foreach ($teamMates as $mate) {
        sendPrivateMessage($mate['id'], $formattedMsg);
    }
    
    return [
        'success' => true, 
        'message' => "✅ پیام به " . count($teamMates) . " هم‌تیمی ارسال شد!"
    ];
}

// STUBS
function scheduleGameStart($gameCode) {}
function scheduleNightEnd($gameCode, $seconds) {}
function scheduleGameCleanup($gameCode) {}