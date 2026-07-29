<?php
/**
 * 🎮 منطق بازی گرگینه - نسخه کامل
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';

// ==================== ثابت‌ها ====================

if (!defined('WAITING_TIME')) define('WAITING_TIME', 300);
if (!defined('EXTEND_TIME')) define('EXTEND_TIME', 30);
if (!defined('MAX_EXTEND_COUNT')) define('MAX_EXTEND_COUNT', 3);
if (!defined('AFK_THRESHOLD')) define('AFK_THRESHOLD', 2);

// ==================== ساخت بازی ====================

/**
 * 🆕 ساخت بازی جدید
 */
function createGame($group_id, $creator_id, $creator_name) {
    $existing = getGroupActiveGame($group_id);
    if ($existing) {
        return [
            'success' => false,
            'message' => '⏳ یک بازی فعال در این گروه وجود دارد!',
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
                'original_role' => null,
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
        'votes' => [],
        'lovers' => [],
        'winners' => null,
        'time_set' => false,
        'settings' => [
            'day_duration' => DEFAULT_DAY_DURATION,
            'vote_duration' => DEFAULT_VOTE_DURATION,
            'night_duration' => DEFAULT_NIGHT_DURATION
        ]
    ];

    saveGame($game);

    $remaining = WAITING_TIME;
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🐺 <b>بازی جدید ساخته شد!</b>\n\n";
    $msg .= "🎲 <b>کد بازی:</b> <code>" . $code . "</code>\n";
    $msg .= "👤 سازنده: " . $creator_name . "\n";
    $msg .= "👥 بازیکنان فعلی: ۱ نفر\n\n";
    $msg .= "⏱ <b>زمان باقیمانده جوین:</b> " . $minutes . ":" . sprintf("%02d", $seconds) . "\n\n";
    $msg .= "⚙️ <b>تنظیم تایم بازی:</b>\n";
    $msg .= "ادمین گروه باید تایم هر فاز رو انتخاب کنه:\n\n";
    $msg .= "• 🌙 شب: ۶۰ ثانیه\n";
    $msg .= "• ☀️ روز (بحث): ۶۰ ثانیه\n";
    $msg .= "• 🗳️ رأی‌گیری: ۶۰ ثانیه\n\n";
    $msg .= "👇 یکی رو انتخاب کن:";

    return [
        'success' => true,
        'message' => $msg,
        'code' => $code,
        'game' => $game,
        'need_time_setup' => true
    ];
}

// ==================== پیوستن و خروج ====================

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
            return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
        }
    }

    if (count($game['players']) >= MAX_PLAYERS) {
        return ['success' => false, 'message' => '❌ ظرفیت بازی پر شده! (حداکثر ' . MAX_PLAYERS . ' نفر)'];
    }

    $game['players'][] = [
        'id' => $user_id,
        'name' => $user_name,
        'role' => null,
        'original_role' => null,
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
 * 🚪 خروج از بازی
 */
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
        'message' => '✅ از بازی خارج شدید!',
        'game' => $game
    ];
}

// ==================== تنظیم تایم ====================

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
    
    $msg = "⚙️ <b>تایم بازی تنظیم شد!</b>\n\n";
    $msg .= "🎮 حالت: <b>" . $timing_name . "</b>\n\n";
    $msg .= "⏱ تایم‌ها:\n";
    $msg .= "• 🌙 شب: " . $game['settings']['night_duration'] . " ثانیه\n";
    $msg .= "• ☀️ روز: " . $game['settings']['day_duration'] . " ثانیه\n";
    $msg .= "• 🗳️ رأی‌گیری: " . $game['settings']['vote_duration'] . " ثانیه\n\n";
    $msg .= "📌 برای تغییر، ادمین می‌تونه /timing رو بزنه.";
    
    return [
        'success' => true,
        'message' => $msg,
        'game' => $game
    ];
}

/**
 * ⏱ تمدید زمان انتظار
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
        return ['success' => false, 'message' => '❌ حداکثر ۳ بار تمدید امکان‌پذیر است!'];
    }
    
    $game['wait_until'] += EXTEND_TIME;
    $game['extend_count']++;
    saveGame($game);
    
    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "⏱ <b>زمان انتظار تمدید شد!</b>\n\n";
    $msg .= "➕ ۳۰ ثانیه اضافه شد\n";
    $msg .= "📊 تمدیدها: " . $game['extend_count'] . "/3\n";
    $msg .= "⏳ باقیمانده: " . $minutes . ":" . sprintf("%02d", $seconds);
    
    return [
        'success' => true,
        'message' => $msg,
        'game' => $game
    ];
}

// ==================== شروع بازی ====================

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
            'message' => '❌ تعداد بازیکنان کافی نبود! (' . $playerCount . '/' . MIN_PLAYERS . ')\nبازی لغو شد.'
        ];
    }

    if (!$game['time_set']) {
        $game['settings']['day_duration'] = DEFAULT_DAY_DURATION;
        $game['settings']['vote_duration'] = DEFAULT_VOTE_DURATION;
        $game['settings']['night_duration'] = DEFAULT_NIGHT_DURATION;
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

    $msg = "🎮 <b>بازی شروع شد!</b>\n\n";
    $msg .= "👥 بازیکنان: " . $playerCount . "\n";
    $msg .= "🐺 گرگ‌ها: ~" . floor($playerCount / 5) . " نفر\n";
    $msg .= "⏱ تایم: " . $game['settings']['day_duration'] . "s / " . $game['settings']['vote_duration'] . "s\n";
    $msg .= "🎭 نقش‌ها در پیام خصوصی ارسال شد\n";
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
 * ❌ لغو بازی
 */
function cancelGame($group_id, $user_id) {
    $game = getGroupActiveGame($group_id);

    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی نیست!'];
    }

    if ($user_id != $game['creator_id'] && $user_id != ADMIN_ID) {
        return ['success' => false, 'message' => '❌ فقط سازنده بازی می‌تونه لغو کنه!'];
    }

    deleteGame($game['code']);

    return [
        'success' => true,
        'message' => '❌ بازی لغو شد!'
    ];
}

// ==================== سیستم نقش‌دهی ====================

/**
 * 🎲 تخصیص نقش‌ها
 */
function assignRoles($game) {
    $player_count = count($game['players']);

    if ($player_count < MIN_PLAYERS) {
        return ['success' => false, 'message' => '❌ حداقل ' . MIN_PLAYERS . ' نفر نیاز است!'];
    }
    
    if ($player_count > MAX_PLAYERS) {
        return ['success' => false, 'message' => '❌ حداکثر ' . MAX_PLAYERS . ' نفر!'];
    }

    $selectedRoles = selectBalancedRoles($player_count);
    shuffle($selectedRoles);

    foreach ($game['players'] as $i => &$player) {
        $player['role'] = $selectedRoles[$i];
        $player['original_role'] = $selectedRoles[$i];
        $player['role_data'] = [];
    }

    $game['roles_assigned'] = true;
    $game['status'] = 'started';
    $game['started'] = time();
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    unset($game['wait_until'], $game['extend_count']);

    if (!$game['time_set']) {
        $game['settings']['day_duration'] = DEFAULT_DAY_DURATION;
        $game['settings']['vote_duration'] = DEFAULT_VOTE_DURATION;
        $game['settings']['night_duration'] = DEFAULT_NIGHT_DURATION;
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
 * 🎲 انتخاب نقش‌های متوازن
 */
function selectBalancedRoles($count) {
    $roles = [];
    
    $uniqueVillageRoles = [
        'seer', 'apprentice_seer', 'guardian_angel', 'knight', 'hunter', 
        'harlot', 'blacksmith', 'gunner', 'mayor', 'prince', 'detective', 
        'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble', 'chemist', 
        'fool', 'clumsy', 'cursed', 'traitor', 'wild_child', 'wise_elder', 
        'sandman', 'sweetheart', 'ruler', 'spy', 'marouf', 'cult_hunter', 
        'hamal', 'jumong', 'princess', 'wolf_man', 'drunk'
    ];
    
    $uniqueWolfRoles = [
        'alpha_wolf', 'wolf_cub', 'lycan', 'sorcerer', 'enchanter',
        'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'honey'
    ];
    
    $uniqueCultRoles = ['royce', 'frankenstein', 'monk_black'];
    $uniqueVampireRoles = ['bloodthirsty', 'kent_vampire', 'chiang'];
    $uniqueNeutralRoles = ['tanner', 'dian', 'dinamit', 'bomber', 'tso', 'doppelganger', 'lucifer', 'magento'];
    
    $wolfCount = max(1, min(12, floor($count * 0.20)));
    $cultCount = ($count >= 15) ? max(1, min(5, floor($count * 0.08))) : 0;
    $vampireCount = ($count >= 20) ? max(1, min(4, floor($count * 0.06))) : 0;
    $killerCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0;
    $fireIceCount = ($count >= 25) ? max(2, min(3, floor($count * 0.06))) : 0;
    $blackKnightCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0;
    $jokerCount = ($count >= 20) ? max(1, min(2, floor($count * 0.04))) : 0;
    $neutralCount = ($count >= 25) ? max(1, min(2, floor($count * 0.04))) : 0;

    shuffle($uniqueVillageRoles);
    $villageUniqueCount = min(count($uniqueVillageRoles), floor($count * 0.35));
    for ($i = 0; $i < $villageUniqueCount && count($roles) < $count - $wolfCount - $cultCount - $vampireCount - $killerCount - $fireIceCount - $blackKnightCount - $jokerCount - $neutralCount - 5; $i++) {
        $roles[] = $uniqueVillageRoles[$i];
    }
    
    shuffle($uniqueWolfRoles);
    $roles[] = 'alpha_wolf';
    $specialWolfCount = min(count($uniqueWolfRoles) - 1, max(0, floor($wolfCount / 2)));
    for ($i = 0; $i < $specialWolfCount; $i++) {
        if ($uniqueWolfRoles[$i] != 'alpha_wolf') {
            $roles[] = $uniqueWolfRoles[$i];
        }
    }
    $simpleWolfCount = $wolfCount - 1 - $specialWolfCount;
    for ($i = 0; $i < $simpleWolfCount; $i++) {
        $roles[] = 'werewolf';
    }
    
    shuffle($uniqueCultRoles);
    $roles[] = 'frankenstein';
    $specialCultCount = min(count($uniqueCultRoles) - 1, max(0, $cultCount - 1));
    for ($i = 0; $i < $specialCultCount; $i++) {
        if ($uniqueCultRoles[$i] != 'frankenstein') {
            $roles[] = $uniqueCultRoles[$i];
        }
    }
    $simpleCultCount = $cultCount - 1 - $specialCultCount;
    for ($i = 0; $i < $simpleCultCount; $i++) {
        $roles[] = 'cultist';
    }
    
    shuffle($uniqueVampireRoles);
    $roles[] = 'bloodthirsty';
    $specialVampCount = min(count($uniqueVampireRoles) - 1, max(0, $vampireCount - 1));
    for ($i = 0; $i < $specialVampCount; $i++) {
        if ($uniqueVampireRoles[$i] != 'bloodthirsty') {
            $roles[] = $uniqueVampireRoles[$i];
        }
    }
    $simpleVampCount = $vampireCount - 1 - $specialVampCount;
    for ($i = 0; $i < $simpleVampCount; $i++) {
        $roles[] = 'vampire';
    }
    
    $roles[] = 'serial_killer';
    if ($killerCount > 1) {
        $roles[] = (rand(0, 1) == 0) ? 'archer' : 'davina';
    }
    
    $roles[] = 'fire_king';
    $roles[] = 'ice_queen';
    if ($fireIceCount > 2) {
        $roles[] = 'lilith';
    }
    
    $roles[] = 'black_knight';
    if ($blackKnightCount > 1) {
        $roles[] = 'bride_dead';
    }
    
    $roles[] = 'joker';
    if ($jokerCount > 1) {
        $roles[] = 'harly';
    }
    
    shuffle($uniqueNeutralRoles);
    for ($i = 0; $i < $neutralCount; $i++) {
        $roles[] = $uniqueNeutralRoles[$i];
    }
    
    $masonCount = min(4, max(2, floor($count / 15)));
    for ($i = 0; $i < $masonCount && count($roles) < $count - 2; $i++) {
        $roles[] = 'builder';
    }
    
    while (count($roles) < $count) {
        $roles[] = 'villager';
    }
    
    return $roles;
}

/**
 * 📨 ارسال نقش به بازیکن
 */
function sendRoleAssignment($player, $game) {
    $roleName = getRoleName($player['role']);
    $roleIcon = getRoleIcon($player['role']);
    $team = detectTeam($player['role']);
    $teamIcon = getTeamIcon($team);

    $msg = "🎭 <b>نقش شما: " . $roleIcon . " " . $roleName . "</b>\n\n";
    $msg .= "🏷️ تیم: " . $teamIcon . " " . ucfirst($team) . "\n\n";
    $msg .= getRoleDescription($player['role']) . "\n\n";
    
    if ($team == 'werewolf') {
        $teamMates = getWolfTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🐺 <b>هم‌تیمی‌های شما (گرگ‌ها):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'cult') {
        $teamMates = getCultTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "👤 <b>هم‌تیمی‌های شما (فرقه):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'vampire') {
        $teamMates = getVampireTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🧛 <b>هم‌تیمی‌های شما (ومپایرها):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'killer') {
        $teamMates = getKillerTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🔪 <b>هم‌تیمی‌های شما (قاتل‌ها):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'fire_ice') {
        $teamMates = getFireIceTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🔥❄️ <b>هم‌تیمی‌های شما (آتش و یخ):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'black_knight') {
        $teamMates = getBlackKnightTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🥷 <b>هم‌تیمی‌های شما (شوالیه تاریکی):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    } elseif ($team == 'joker') {
        $teamMates = getJokerTeam($game, $player['id']);
        if (!empty($teamMates)) {
            $msg .= "🤡 <b>هم‌تیمی‌های شما (جوکر):</b>\n";
            foreach ($teamMates as $mate) {
                $msg .= "• " . $mate['name'] . "\n";
            }
            $msg .= "\n";
        }
    }

    $msg .= "🤫 <b>راز خود را حفظ کنید!</b>";

    sendPrivateMessage($player['id'], $msg);
}

// ==================== توابع تیم‌ها ====================

function getWolfTeam($game, $excludeId = null) {
    $team = [];
    foreach ($game['players'] as $p) {
        if (in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
            'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
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
        if (in_array($p['role'], ['fire_king', 'ice_queen', 'lilith', 'magento'])) {
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

// ==================== فازهای بازی ====================

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

    $nightDuration = $game['settings']['night_duration'] ?? DEFAULT_NIGHT_DURATION;

    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;

        $nightMsg = "🌙 <b>شب " . $game['night_count'] . " فرا رسید!</b>\n\n";
        
        $role = $player['role'];
        $roleName = getRoleName($role);
        $roleIcon = getRoleIcon($role);
        
        $nightMsg .= "🎭 نقش شما: " . $roleIcon . " " . $roleName . "\n\n";
        
        if (hasNightAction($role)) {
            $nightMsg .= "⏳ منتظر بمانید... اگر نقش شما اکشن شب دارد، پیام جداگانه دریافت خواهید کرد.";
        } else {
            $nightMsg .= "💤 شما می‌توانید بخوابید... فردا صبح بیدارتان می‌کنیم!";
        }
        
        sendPrivateMessage($player['id'], $nightMsg);
    }

    $groupMsg = "🌙 <b>شب " . $game['night_count'] . "!</b>\n\n";
    $groupMsg .= "همه بخوابید...\n";
    $groupMsg .= "⏱ " . $nightDuration . " ثانیه تا صبح";

    sendMessage($game['group_id'], $groupMsg);

    $game['night_end'] = time() + $nightDuration;
    saveGame($game);
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

    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";

    if (!empty($results['messages'])) {
        $msg .= implode("\n", $results['messages']) . "\n\n";
    }

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

    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }

    $aliveList = getAlivePlayersList($game);
    sendMessage($game['group_id'], $aliveList);

    $dayDuration = $game['settings']['day_duration'] ?? DEFAULT_DAY_DURATION;
    
    $dayMsg = "🗣 <b>زمان بحث!</b>\n\n";
    $dayMsg .= "شما " . $dayDuration . " ثانیه وقت دارید.\n";
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
    $protected = [];

    foreach ($actions as $action) {
        if ($action['action'] == 'save' || $action['action'] == 'guard') {
            $protected[] = $action['target'];
        }
    }

    $attacks = [];
    foreach ($actions as $action) {
        if (in_array($action['action'], ['vote_eat', 'kill', 'bite', 'hunt', 'shoot'])) {
            $targetId = $action['target'];

            if (in_array($targetId, $protected)) {
                $target = getPlayerById($game, $targetId);
                if ($target) {
                    $messages[] = "🛡️ " . $target['name'] . " نجات پیدا کرد!";
                }
                continue;
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
            'name' => $target['name']
        ];
    }

    saveGame($game);

    return [
        'game' => $game,
        'deaths' => $deaths,
        'messages' => $messages
    ];
}

// ==================== کشتن بازیکن ====================

/**
 * 💀 کشتن بازیکن
 */
function killPlayer($game, $playerId, $cause) {
    foreach ($game['players'] as &$p) {
        if ($p['id'] == $playerId) {
            $p['alive'] = false;
            $p['death_cause'] = $cause;
            $p['death_time'] = time();
            $p['death_night'] = $game['night_count'] ?? 0;
            break;
        }
    }

    if (!empty($game['lovers'])) {
        foreach ($game['lovers'] as $pair) {
            if ($pair[0] == $playerId && !isPlayerDead($game, $pair[1])) {
                $game = killPlayer($game, $pair[1], 'love');
                $lover = getPlayerById($game, $pair[1]);
                if ($lover) {
                    sendMessage($game['group_id'], "💔 عاشق " . $lover['name'] . " نیز به دنبال معشوق خود مرد!");
                }
            } elseif ($pair[1] == $playerId && !isPlayerDead($game, $pair[0])) {
                $game = killPlayer($game, $pair[0], 'love');
                $lover = getPlayerById($game, $pair[0]);
                if ($lover) {
                    sendMessage($game['group_id'], "💔 عاشق " . $lover['name'] . " نیز به دنبال معشوق خود مرد!");
                }
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
    $voteDuration = $game['settings']['vote_duration'] ?? DEFAULT_VOTE_DURATION;

    $groupMsg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "!</b>\n\n";
    $groupMsg .= "⏱ <b>" . $voteDuration . " ثانیه</b>\n";
    $groupMsg .= "👥 زنده‌ها: " . $aliveCount . "\n\n";
    $groupMsg .= "📩 <b>به صورت خصوصی رأی دهید!</b>";

    sendMessage($game['group_id'], $groupMsg);

    foreach ($alive as $player) {
        sendPrivateVotePanel($player, $game);
    }

    $game['vote_end'] = time() + $voteDuration;
    saveGame($game);

    return true;
}

/**
 * 📨 ارسال پنل رأی خصوصی
 */
function sendPrivateVotePanel($player, $game) {
    $alive = getAlivePlayers($game);
    $alive = array_values(array_filter($alive, function($p) use ($player) {
        return $p['id'] != $player['id'];
    }));

    if (empty($alive)) {
        sendPrivateMessage($player['id'], "❌ هیچ بازیکن زنده دیگری برای رأی دادن وجود ندارد!");
        return;
    }

    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    $msg .= "👇 یک نفر رو برای اعدام انتخاب کن:";

    $buttons = [];
    foreach ($alive as $p) {
        $buttons[] = [
            'text' => $p['name'],
            'callback_data' => 'vote_' . $p['id'] . '_' . $game['code']
        ];
    }

    $buttons[] = ['text' => '⚪ رأی سفید', 'callback_data' => 'vote_skip_' . $game['code']];

    $keyboard = array_chunk($buttons, 2);

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
        $groupMsg = "🗳️ <b>" . $voter['name'] . "</b> رأی سفید داد!\n";
    } else {
        $target = getPlayerById($game, $targetId);
        $groupMsg = "🗳️ <b>" . $voter['name'] . "</b> به <b>" . $target['name'] . "</b> رأی داد!\n";
    }
    $groupMsg .= "📊 <b>" . $voteCount . " / " . $aliveCount . "</b>";
    
    sendMessage($game['group_id'], $groupMsg);

    $confirmMsg = "✅ رأی شما ثبت شد.";
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
        sendMessage($game['group_id'], "😴 <b>" . $afkPlayer['name'] . "</b> به خاطر غیرفعالی حذف شد!");
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

    $msg = "🗳️ <b>نتیجه رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    
    $msg .= "📊 آمار:\n";
    $msg .= "• رأی‌ها: " . count($game['votes']) . "\n";
    $msg .= "• سفید: " . $skipCount . "\n";
    if (!empty($afkPlayers)) {
        $msg .= "• حذف شده: " . count($afkPlayers) . "\n";
    }
    $msg .= "\n";

    if (count($targets) == 1 && $max > 0) {
        $targetId = $targets[0];
        $targetPlayer = getPlayerById($game, $targetId);
        
        if ($targetPlayer && ($targetPlayer['alive'] ?? false)) {
            $msg .= "💀 <b>" . $targetPlayer['name'] . "</b> اعدام شد!\n";
            $msg .= "🎭 نقش: " . getRoleDisplayName($targetPlayer['role']) . "\n\n";

            if ($targetPlayer['role'] == 'tanner') {
                $msg .= "🎉 <b>منافق برنده شد!</b>";
                sendMessage($game['group_id'], $msg);
                endGame($game, ['ended' => true, 'winner' => 'tanner']);
                return;
            }

            $game = killPlayer($game, $targetId, 'lynch');
        } else {
            $msg .= "⚖️ <b>هدف قبلاً حذف شده بود!</b>";
        }

    } else {
        $msg .= "⚖️ <b>مساوی شد! کسی اعدام نمی‌شود.</b>";
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

function hasNightAction($role) {
    $rolesWithAction = [
        'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey',
        'sorcerer', 'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
        'serial_killer', 'archer', 'davina', 'seer', 'guardian_angel',
        'knight', 'hunter', 'detective', 'cupid', 'cultist', 'royce',
        'frankenstein', 'monk_black', 'fire_king', 'ice_queen', 'lilith',
        'black_knight', 'bride_dead', 'joker', 'harly', 'gunner',
        'blacksmith', 'harlot', 'phoenix', 'sandman', 'spy'
    ];
    return in_array($role, $rolesWithAction);
}

function getAlivePlayersList($game) {
    $alive = getAlivePlayers($game);
    $msg = "👥 <b>زنده‌ها (" . count($alive) . "):</b>\n";
    foreach ($alive as $p) {
        $msg .= "• " . $p['name'] . "\n";
    }
    return $msg;
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
            $msg .= "⚙️ تایم: " . ($game['settings']['day_duration'] ?? DEFAULT_DAY_DURATION) . "s\n";
        } else {
            $msg .= "⚠️ تایم تنظیم نشده!\n";
        }
    }

    if ($game['status'] == 'started') {
        $msg .= "🌙 شب: " . ($game['night_count'] ?? 0) . "\n";
        $msg .= "☀️ روز: " . ($game['day_count'] ?? 0) . "\n";
        $msg .= "🔄 فاز: " . getPhaseText($game['phase']) . "\n";
    }

    $msg .= "\n👥 بازیکنان (" . count($game['players']) . "):\n";
    
    $playerCount = 0;
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? true) ? '🟢' : '💀';
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "$status {$p['name']} $creator\n";
        $playerCount++;
        
        if ($playerCount % 30 == 0 && $playerCount < count($game['players'])) {
            $msg .= "... (ادامه دارد)\n";
            break;
        }
    }

    return ['success' => true, 'message' => $msg];
}

// ==================== شرایط برد ====================

function checkWinCondition($game) {
    $alive = getAlivePlayers($game);
    $totalAlive = count($alive);

    if ($totalAlive == 0) {
        return ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردند!'];
    }

    $teams = [];
    foreach ($alive as $p) {
        $team = detectTeam($p['role']);
        $teams[$team] = ($teams[$team] ?? 0) + 1;
    }

    $wolves = $teams['werewolf'] ?? 0;
    $villagers = ($teams['villager'] ?? 0);
    $cult = $teams['cult'] ?? 0;
    $killers = $teams['killer'] ?? 0;
    $vampires = $teams['vampire'] ?? 0;
    $jokers = $teams['joker'] ?? 0;
    $fireIce = $teams['fire_ice'] ?? 0;
    $blackKnights = $teams['black_knight'] ?? 0;
    $independent = $teams['independent'] ?? 0;

    // جوکر
    if ($jokers > 0) {
        foreach ($alive as $p) {
            if (in_array($p['role'], ['joker', 'harly'])) {
                return ['ended' => true, 'winner' => 'joker', 'message' => '🤡 <b>جوکر برنده شد!</b>'];
            }
        }
    }

    // گرگ‌ها برنده می‌شوند
    if ($wolves > 0 && $wolves >= $villagers && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0) {
        return ['ended' => true, 'winner' => 'werewolf', 'message' => '🐺 <b>گرگ‌ها برنده شدند!</b>'];
    }

    // روستایی‌ها برنده می‌شوند
    if ($wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0 && $jokers == 0) {
        return ['ended' => true, 'winner' => 'villager', 'message' => '👨‍🌾 <b>روستایی‌ها برنده شدند!</b>'];
    }

    // فرقه برنده می‌شود
    if ($cult > $totalAlive / 2) {
        return ['ended' => true, 'winner' => 'cult', 'message' => '👤 <b>فرقه برنده شد!</b>'];
    }

    // قاتل برنده می‌شود
    if ($killers > 0 && $wolves == 0 && $cult == 0 && $vampires == 0 && $fireIce == 0 && $blackKnights == 0 && $jokers == 0) {
        if ($totalAlive <= 3 || ($killers == $totalAlive)) {
            return ['ended' => true, 'winner' => 'killer', 'message' => '🔪 <b>قاتل‌ها برنده شدند!</b>'];
        }
    }

    // ومپایر برنده می‌شود
    if ($vampires > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $fireIce == 0 && $blackKnights == 0 && $jokers == 0) {
        if ($vampires >= $villagers) {
            return ['ended' => true, 'winner' => 'vampire', 'message' => '🧛 <b>ومپایرها برنده شدند!</b>'];
        }
    }

    // آتش و یخ برنده می‌شوند
    if ($fireIce > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $blackKnights == 0 && $jokers == 0) {
        if ($fireIce >= $villagers) {
            return ['ended' => true, 'winner' => 'fire_ice', 'message' => '🔥❄️ <b>تیم آتش و یخ برنده شد!</b>'];
        }
    }

    // شوالیه تاریکی برنده می‌شود
    if ($blackKnights > 0 && $wolves == 0 && $cult == 0 && $killers == 0 && $vampires == 0 && $fireIce == 0 && $jokers == 0) {
        if ($blackKnights >= $villagers) {
            return ['ended' => true, 'winner' => 'black_knight', 'message' => '🥷 <b>شوالیه‌های تاریکی برنده شدند!</b>'];
        }
    }

    return ['ended' => false];
}

/**
 * 🏁 پایان بازی
 */
function endGame($game, $winCheck) {
    $game['status'] = 'ended';
    $game['ended'] = time();
    $game['winners'] = $winCheck['winner'];

    saveGame($game);

    $msg = "🏁 <b>بازی تمام شد!</b>\n\n";
    $msg .= $winCheck['message'] . "\n\n";
    $msg .= "📊 <b>نقش‌ها:</b>\n";

    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? false) ? '🟢' : '💀';
        $role = getRoleDisplayName($p['role']);
        $winner = ($p['alive'] ?? false) ? '👑' : '';
        $msg .= "$status {$p['name']} - $role $winner\n";
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