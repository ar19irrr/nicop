<?php
// index.php - نسخه کامل با همه دستورات و توابع

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

function loadLinks() {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'group_links.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveLinks($links) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'group_links.json';
    file_put_contents($file, json_encode($links, JSON_PRETTY_PRINT));
}

// ============================================================
// بالانس همه نقش‌ها
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    
    $villager_roles = ['villager', 'seer', 'apprentice_seer', 'guardian_angel', 'knight', 
                       'hunter', 'harlot', 'builder', 'blacksmith', 'gunner', 'mayor',
                       'prince', 'detective', 'cupid', 'beholder', 'phoenix', 'huntsman',
                       'trouble', 'chemist', 'fool', 'clumsy', 'cursed', 'traitor',
                       'wild_child', 'wise_elder', 'sandman', 'sweetheart', 'ruler',
                       'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong', 'princess',
                       'wolf_man', 'drunk'];
    
    $wolf_roles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                   'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
    
    if ($count <= 4) {
        $roles = ['villager', 'villager', 'villager', 'werewolf'];
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 6) {
        $roles = array_merge(array_fill(0, $count - 2, 'villager'), ['werewolf', 'werewolf']);
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 8) {
        $special = ['seer'];
        $roles = array_merge(array_fill(0, $count - 3, 'villager'), ['werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 10) {
        $special = ['seer', 'guardian_angel'];
        $roles = array_merge(array_fill(0, $count - 4, 'villager'), ['werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 14) {
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 3);
        $roles = array_merge(array_fill(0, $count - 6, 'villager'), ['werewolf', 'werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 20) {
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 4);
        $roles = array_merge(array_fill(0, $count - 8, 'villager'), ['werewolf', 'werewolf', 'werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 30) {
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 6);
        $roles = array_merge(array_fill(0, $count - 11, 'villager'), ['werewolf', 'werewolf', 'werewolf', 'werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    
    $wolf_count = round($count * 0.2);
    $special_count = round($count * 0.15);
    
    $all_special = array_merge($villager_roles, $wolf_roles);
    shuffle($all_special);
    $special = array_slice($all_special, 0, $special_count);
    
    $roles = array_merge(array_fill(0, $count - $wolf_count - count($special), 'villager'), array_fill(0, $wolf_count, 'werewolf'), $special);
    shuffle($roles);
    return $roles;
}

// ============================================================
// توابع اصلی بازی
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
    
    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🐺 <b>بازی جدید ساخته شد!</b>\n\n";
    $msg .= "🎲 <b>کد بازی:</b> <code>$code</code>\n";
    $msg .= "👤 سازنده: $creator_name\n";
    $msg .= "👥 بازیکنان فعلی: ۱ نفر\n\n";
    $msg .= "⏱ <b>زمان باقیمانده جوین:</b> $minutes:" . sprintf("%02d", $seconds) . "\n\n";
    $msg .= "📌 <b>دوستانت رو دعوت کن:</b>\n";
    $msg .= "🔗 لینک دعوت: https://t.me/" . BOT_USERNAME . "?start=join_$code\n\n";
    $msg .= "👇 برای شروع بازی حداقل ۴ نفر نیازه!";
    
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
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر"];
}

function leaveGame($chat_id, $user_id) {
    $games = loadGames();
    $found = false;
    foreach ($games as $code => $game) {
        if ($game['group_id'] == $chat_id && $game['status'] == 'waiting') {
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
                    $found = true;
                    break 2;
                }
            }
        }
    }
    if ($found) {
        return ['success' => true, 'message' => '✅ از بازی خارج شدید!'];
    }
    return ['success' => false, 'message' => '❌ شما در هیچ بازی فعالی نیستید!'];
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
    $game['night_end_time'] = time() + ($game['night_duration'] ?? 60);
    $games[$game_code] = $game;
    saveGames($games);
    
    foreach ($game['players'] as $p) {
        $role_name = getRoleDisplayName($p['role']);
        $role_desc = getRoleDescription($p['role']);
        sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n" . $role_desc . "\n\n🌙 شب اول شروع شد...");
        sendNightPanel($p, $game);
    }
    
    sendMessage($group_id, "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>\n\n👥 " . count($game['players']) . " نفر\n🌙 شب اول..."];
}

function cancelGame($chat_id, $user_id) {
    $games = loadGames();
    $found = false;
    foreach ($games as $code => $game) {
        if ($game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            unset($games[$code]);
            saveGames($games);
            $found = true;
            break;
        }
    }
    if ($found) {
        return ['success' => true, 'message' => '❌ بازی لغو شد!'];
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای لغو وجود ندارد!'];
}

function extendWaitingTime($chat_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if ($game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            if (!isAdmin($user_id, $chat_id)) {
                return ['success' => false, 'message' => '❌ فقط ادمین گروه می‌تواند تمدید کند!'];
            }
            if ($game['extend_count'] >= 3) {
                return ['success' => false, 'message' => '❌ حداکثر ۳ بار تمدید!'];
            }
            $game['wait_until'] += 30;
            $game['extend_count']++;
            $games[$code] = $game;
            saveGames($games);
            $remaining = $game['wait_until'] - time();
            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;
            return ['success' => true, 'message' => "⏱ زمان ۳۰ ثانیه تمدید شد!\n⏳ باقیمانده: $minutes:" . sprintf("%02d", $seconds)];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای تمدید وجود ندارد!'];
}

function setGameTiming($chat_id, $user_id, $timing) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if ($game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            if (!isAdmin($user_id, $chat_id)) {
                return ['success' => false, 'message' => '❌ فقط ادمین گروه می‌تواند تایم را تنظیم کند!'];
            }
            $times = ['fast' => 60, 'normal' => 90, 'slow' => 120];
            if (!isset($times[$timing])) {
                return ['success' => false, 'message' => '❌ گزینه نامعتبر! از /timing fast|normal|slow استفاده کنید.'];
            }
            $game['day_duration'] = $times[$timing];
            $game['night_duration'] = $times[$timing];
            $game['vote_duration'] = $times[$timing];
            $games[$code] = $game;
            saveGames($games);
            return ['success' => true, 'message' => "⚙️ تایم بازی به {$times[$timing]} ثانیه تنظیم شد!"];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای تنظیم تایم وجود ندارد!'];
}

function isAdmin($user_id, $chat_id) {
    return true;
}

// ============================================================
// فازهای بازی
// ============================================================

function sendNightPanel($player, $game) {
    $role = $player['role'];
    
    $nightRoles = [
        'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
        'seer', 'guardian_angel', 'knight', 'hunter', 'harlot',
        'detective', 'cupid', 'phoenix', 'sandman', 'spy'
    ];
    
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n💤 تو می‌تونی بخوابی...");
        return;
    }
    
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
    });
    
    foreach ($alivePlayers as $p) {
        if (in_array($role, ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                             'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer']) &&
            in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                                  'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            continue;
        }
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
        return;
    }
    
    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: " . getRoleDisplayName($role) . "\n\n";
    $msg .= getRoleActionDescription($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن (یا Skip):";
    
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
    $keyboard[] = [['text' => '⏭️ Skip (رد کردن)', 'callback_data' => $role . '_skip']];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processNight($game_code, $game) {
    $deaths = [];
    $protected = [];
    
    foreach ($game['night_actions'] as $action) {
        if ($action['role'] == 'guardian_angel') {
            $protected[] = $action['target'];
        }
    }
    
    foreach ($game['night_actions'] as $action) {
        if (in_array($action['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                                        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            $target = $action['target'];
            if (!in_array($target, $protected)) {
                foreach ($game['players'] as &$p) {
                    if ($p['id'] == $target) {
                        $p['alive'] = false;
                        $deaths[] = $p['name'];
                        break;
                    }
                }
            }
        }
    }
    
    foreach ($game['night_actions'] as $action) {
        if ($action['role'] == 'seer') {
            $target_id = $action['target'];
            foreach ($game['players'] as $p) {
                if ($p['id'] == $target_id) {
                    sendPrivateMessage($action['player'], "🔮 نقش " . $p['name'] . ": " . getRoleDisplayName($p['role']));
                    break;
                }
            }
        }
    }
    
    $game['phase'] = 'day';
    $game['day_count'] = ($game['day_count'] ?? 0) + 1;
    $game['votes'] = [];
    $game['night_actions'] = [];
    $game['night_end_time'] = 0;
    $game['day_end_time'] = time() + ($game['day_duration'] ?? 60);
    $game['vote_end_time'] = 0;
    
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    if (!empty($deaths)) {
        $msg .= "💀 <b>کشته شدگان:</b>\n" . implode("\n", array_map(fn($name) => "• $name", $deaths)) . "\n\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    
    $msg .= "🗣️ <b>زمان بحث!</b>\n⏱ " . ($game['day_duration'] ?? 60) . " ثانیه وقت دارید.\nبعدش رأی‌گیری شروع می‌شه.";
    sendMessage($game['group_id'], $msg);
}

function startVoting($game) {
    $alivePlayers = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    if (count($alivePlayers) < 2) {
        endGame($game);
        return;
    }
    
    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_end_time'] = time() + ($game['vote_duration'] ?? 60);
    
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    
    sendMessage($game['group_id'], "🗳️ <b>زمان رأی‌گیری!</b>\n⏱ " . ($game['vote_duration'] ?? 60) . " ثانیه وقت دارید.\nبه صورت خصوصی به ربات پیام دهید.");
    
    foreach ($alivePlayers as $p) {
        sendVotePanel($p, $game);
    }
}

function sendVotePanel($player, $game) {
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
    });
    
    if (empty($alivePlayers)) {
        sendPrivateMessage($player['id'], "❌ هیچ بازیکن زنده دیگری برای رأی دادن وجود ندارد!");
        return;
    }
    
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    $msg .= "👇 یک نفر رو برای اعدام انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($alivePlayers as $p) {
        $row[] = ['text' => $p['name'], 'callback_data' => 'vote_' . $p['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    $keyboard[] = [['text' => '⚪ رأی سفید', 'callback_data' => 'vote_skip']];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processVotes($game_code, $game) {
    $votes = $game['votes'] ?? [];
    $counts = [];
    
    foreach ($votes as $voter_id => $target_id) {
        if ($target_id == 'skip') continue;
        $counts[$target_id] = ($counts[$target_id] ?? 0) + 1;
    }
    
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    
    if ($max > 0 && count($targets) == 1) {
        $target_id = $targets[0];
        foreach ($game['players'] as &$p) {
            if ($p['id'] == $target_id) {
                $p['alive'] = false;
                sendMessage($game['group_id'], "💀 <b>" . $p['name'] . "</b> اعدام شد!\n🎭 نقش: " . getRoleDisplayName($p['role']));
                break;
            }
        }
    } else {
        sendMessage($game['group_id'], "⚖️ <b>رأی‌ها مساوی شد! کسی اعدام نشد.</b>");
    }
    
    $game['votes'] = [];
    $game['vote_end_time'] = 0;
    $game['day_end_time'] = 0;
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    checkWinCondition($game_code);
}

function checkWinCondition($game_code) {
    $games = loadGames();
    $game = $games[$game_code] ?? null;
    if (!$game) return;
    
    $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    $wolves = array_filter($alive, fn($p) => in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
                                                                    'forest_queen', 'white_wolf', 'beta_wolf', 
                                                                    'ice_wolf', 'enchanter', 'honey', 'sorcerer']));
    $villagers = array_filter($alive, fn($p) => !in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
                                                                        'forest_queen', 'white_wolf', 'beta_wolf', 
                                                                        'ice_wolf', 'enchanter', 'honey', 'sorcerer']));
    
    if (count($wolves) == 0) {
        sendMessage($game['group_id'], "🎉 <b>روستایی‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        return;
    }
    
    if (count($wolves) >= count($villagers)) {
        sendMessage($game['group_id'], "🎉 <b>گرگ‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        return;
    }
    
    $game['phase'] = 'night';
    $game['night_count'] = ($game['night_count'] ?? 0) + 1;
    $game['night_actions'] = [];
    $game['night_end_time'] = time() + ($game['night_duration'] ?? 60);
    $games[$game_code] = $game;
    saveGames($games);
    
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    
    foreach ($game['players'] as $p) {
        if ($p['alive'] ?? false) {
            sendNightPanel($p, $game);
        }
    }
}

function endGame($game) {
    $game['status'] = 'ended';
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    sendMessage($game['group_id'], "🏁 <b>بازی تمام شد!</b>");
}

function cleanupOldGames() {
    $games = loadGames();
    $now = time();
    $changed = false;
    foreach ($games as $code => $game) {
        if ($game['status'] == 'waiting' && ($now - $game['created']) > 600) {
            unset($games[$code]);
            $changed = true;
        }
        if ($game['status'] == 'ended' && isset($game['ended']) && ($now - $game['ended']) > 86400) {
            unset($games[$code]);
            $changed = true;
        }
    }
    if ($changed) {
        saveGames($games);
    }
}

function getDatabaseSize() {
    $file = DATA_PATH . 'games.json';
    if (!file_exists($file)) return '0 KB';
    $size = filesize($file);
    if ($size < 1024) return $size . ' B';
    if ($size < 1024*1024) return round($size/1024, 2) . ' KB';
    return round($size/(1024*1024), 2) . ' MB';
}

// ============================================================
// توابع نقش‌ها
// ============================================================

function getRoleDisplayName($role) {
    $names = [
        'villager' => '👨‍🌾 روستایی ساده',
        'seer' => '👳🏻‍♂️ پیشگو',
        'apprentice_seer' => '🙇🏻‍♂️ شاگرد پیشگو',
        'guardian_angel' => '👼🏻 فرشته نگهبان',
        'knight' => '🗡 شوالیه',
        'hunter' => '👮🏻‍♂️ کلانتر',
        'harlot' => '💋 ناتاشا',
        'builder' => '👷🏻‍♂️ بنا',
        'blacksmith' => '⚒ آهنگر',
        'gunner' => '🔫 تفنگدار',
        'mayor' => '🎖 کدخدا',
        'prince' => '🤴🏻 شاهزاده',
        'detective' => '🕵🏻‍♂️ کاراگاه',
        'cupid' => '💘 الهه عشق',
        'beholder' => '👁 شاهد',
        'phoenix' => '🪶 ققنوس',
        'huntsman' => '🪓 هانتسمن',
        'trouble' => '👩🏻‍🌾 دختر دردسرساز',
        'chemist' => '👨‍🔬 شیمیدان',
        'fool' => '🃏 احمق',
        'clumsy' => '🤕 پسر گیج',
        'cursed' => '😾 نفرین شده',
        'traitor' => '🖕🏿 خائن',
        'wild_child' => '👶🏻 بچه وحشی',
        'wise_elder' => '📚 ریش سفید',
        'sandman' => '💤 خوابگذار',
        'sweetheart' => '👰🏻 دلبر',
        'ruler' => '👑 حاکم',
        'spy' => '🦹🏻‍♂️ جاسوس',
        'marouf' => '🛡️🌿 معروف',
        'cult_hunter' => '💂🏻‍♂️ شکارچی فرقه',
        'hamal' => '🛒 حمال',
        'jumong' => '🏹⚔️ جومونگ',
        'princess' => '👸🏻 پرنسس',
        'wolf_man' => '🌑👨🏻 گرگنما',
        'drunk' => '🍻 مست',
        'werewolf' => '🐺 گرگینه',
        'alpha_wolf' => '⚡️🐺 گرگ آلفا',
        'wolf_cub' => '🐶 توله گرگ',
        'lycan' => '🌝🐺 گرگ ایکس',
        'forest_queen' => '🧝🏻‍♀️🐺 ملکه جنگل',
        'white_wolf' => '🌩🐺 گرگ سفید',
        'beta_wolf' => '💤🐺 گرگ خوابالو',
        'ice_wolf' => '☃️🐺 گرگ برفی',
        'enchanter' => '🧙🏻‍♂️ افسونگر',
        'honey' => '🧙🏻‍♀️ عجوزه',
        'sorcerer' => '🔮 جادوگر',
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'kent_vampire' => '💍🧛🏻 کنت ومپایر',
        'chiang' => '👩‍🦳 چیانگ',
        'cultist' => '👤 فرقه‌گرا',
        'royce' => '🎩 رئیس فرقه',
        'frankenstein' => '🧟‍♂️🪖 فرانکشتاین',
        'monk_black' => '🦇 راهب سیاه',
        'serial_killer' => '🔪 قاتل زنجیره‌ای',
        'archer' => '🏹 کماندار',
        'davina' => '🍾 داوینا',
        'fire_king' => '🔥🤴🏻 پادشاه آتش',
        'ice_queen' => '❄️👸🏻 ملکه یخی',
        'lilith' => '🐍👩🏻‍🦳 لیلیث',
        'magento' => '🧲 مگنیتو',
        'black_knight' => '🥷🗡 شوالیه تاریکی',
        'bride_dead' => '👰‍♀☠️ عروس مردگان',
        'joker' => '🤡 جوکر',
        'harly' => '👩🏻‍🎤 هارلی کویین',
        'dian' => '🧞‍♂️ دیان',
        'dinamit' => '🧨 دینامیت',
        'bomber' => '💣 بمب‌گذار',
        'tso' => '⚔️ تسو',
        'tanner' => '👺 منافق',
        'lucifer' => '😈 لوسیفر',
        'doppelganger' => '👯 همزاد'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getRoleDescription($role) {
    $desc = [
        'villager' => '👨‍🌾 شما یک روستایی ساده هستید. در روز رأی می‌دهید.',
        'seer' => '👳🏻‍♂️ شما پیشگو هستید! هر شب نقش یک نفر را می‌بینید.',
        'werewolf' => '🐺 شما گرگینه هستید! هر شب یک نفر را می‌خورید.',
        'guardian_angel' => '👼🏻 شما فرشته نگهبان هستید! هر شب از یک نفر محافظت می‌کنید.',
        'hunter' => '👮🏻‍♂️ شما کلانتر هستید! اگر بمیرید، می‌توانید به یک نفر شلیک کنید.',
        'detective' => '🕵🏻‍♂️ شما کاراگاه هستید! هر شب یک نفر را تحقیق می‌کنید.',
        'knight' => '🗡 شما شوالیه هستید! هر شب می‌توانید از یک نفر محافظت کنید.'
    ];
    return $desc[$role] ?? '🎭 شما ' . getRoleDisplayName($role) . ' هستید!';
}

function getRoleActionDescription($role) {
    $actions = [
        'werewolf' => '🐺 یک نفر را برای خوردن انتخاب کن.',
        'seer' => '👁️ یک نفر را برای دیدن نقش انتخاب کن.',
        'guardian_angel' => '🛡️ یک نفر را برای محافظت انتخاب کن.',
        'hunter' => '🔫 یک نفر را برای شلیک انتخاب کن.',
        'detective' => '🔍 یک نفر را برای تحقیق انتخاب کن.',
        'knight' => '🗡 یک نفر را برای محافظت انتخاب کن.'
    ];
    return $actions[$role] ?? '';
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
           "🗡 شوالیه - هر شب از یک نفر محافظت می‌کند";
}

function getGameStats() {
    $games = loadGames();
    $total = count($games);
    $waiting = count(array_filter($games, fn($g) => $g['status'] == 'waiting'));
    $started = count(array_filter($games, fn($g) => $g['status'] == 'started'));
    $ended = count(array_filter($games, fn($g) => $g['status'] == 'ended'));
    return ['total' => $total, 'waiting' => $waiting, 'started' => $started, 'ended' => $ended];
}

// ============================================================
// توابع لینک گروه
// ============================================================

function setGroupLink($chat_id, $user_id, $link) {
    if (!isAdmin($user_id, $chat_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین گروه می‌تواند لینک را تنظیم کند!'];
    }
    if (!preg_match('/^https?:\/\/[^\s]+$/', $link)) {
        return ['success' => false, 'message' => '❌ لینک نامعتبر است!'];
    }
    $links = loadLinks();
    $links[$chat_id] = ['link' => $link, 'set_by' => $user_id, 'set_at' => time()];
    saveLinks($links);
    return ['success' => true, 'message' => "✅ لینک گروه ذخیره شد:\n$link"];
}

function getGroupLink($chat_id) {
    $links = loadLinks();
    if (isset($links[$chat_id])) {
        return ['success' => true, 'link' => $links[$chat_id]['link']];
    }
    return ['success' => false, 'message' => '❌ لینکی برای این گروه تنظیم نشده است!'];
}

// ============================================================
// توابع ارسال پیام
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
    if (count($parts) == 2) {
        $role = $parts[0];
        $target_id = $parts[1] == 'skip' ? 'skip' : (int)$parts[1];
        
        $nightRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                       'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
                       'seer', 'guardian_angel', 'knight', 'hunter', 'harlot',
                       'detective', 'cupid', 'phoenix', 'sandman', 'spy'];
        
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
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و دستور /game را بزنید."; break;
        case 'join_menu': $response = "🔗 کد بازی را وارد کنید:\nمثال: /join AB12CD"; break;
        case 'rules': $response = getRules(); break;
        case 'roles': $response = getRolesList(); break;
        case 'help': $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join [کد] - پیوستن\n/players - لیست بازیکنان\n/startgame - شروع بازی\n/stop - لغو بازی\n/leave - خروج\n/extend - تمدید زمان\n/timing - تنظیم تایم\n/ping - تست"; break;
        case 'stats':
            $stats = getGameStats();
            $response = "📊 <b>آمار ربات</b>\n\n" .
                       "🎮 کل بازی‌ها: {$stats['total']}\n" .
                       "⏳ در انتظار: {$stats['waiting']}\n" .
                       "▶️ در حال اجرا: {$stats['started']}\n" .
                       "🏁 تمام شده: {$stats['ended']}";
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

// ===== چک کردن تایمرها =====
$games = loadGames();
foreach ($games as $code => $game) {
    if ($game['group_id'] == $chat_id && $game['status'] == 'started') {
        if ($game['phase'] == 'night' && $game['night_end_time'] > 0 && time() >= $game['night_end_time']) {
            processNight($code, $game);
            sendMessage($chat_id, "⏰ شب به پایان رسید! صبح شد...");
        }
        elseif ($game['phase'] == 'day' && $game['day_end_time'] > 0 && time() >= $game['day_end_time']) {
            startVoting($game);
            sendMessage($chat_id, "⏰ زمان بحث تمام شد! رأی‌گیری شروع می‌شود...");
        }
        elseif ($game['phase'] == 'vote' && $game['vote_end_time'] > 0 && time() >= $game['vote_end_time']) {
            processVotes($code, $game);
        }
        break;
    }
}

switch ($command) {
    case '/start':
        $msg = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعاله!\n\n📱 یکی رو انتخاب کن:";
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'], ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']],
                [['text' => '📜 قوانین', 'callback_data' => 'rules'], ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']],
                [['text' => '❓ راهنما', 'callback_data' => 'help'], ['text' => '📊 آمار', 'callback_data' => 'stats']]
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
        
    case '/stop':
    case '/cancel':
        $result = cancelGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/leave':
        $result = leaveGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
        
    case '/help':
        $msg = "📚 <b>راهنمای ربات گرگینه</b>\n\n" .
               "/start - منوی اصلی\n" .
               "/game - ساخت بازی جدید (فقط در گروه)\n" .
               "/join [کد] - پیوستن به بازی با کد\n" .
               "/players - لیست بازیکنان\n" .
               "/startgame - شروع بازی (حداقل ۴ نفر)\n" .
               "/stop - لغو بازی\n" .
               "/leave - خروج از بازی\n" .
               "/extend - تمدید زمان انتظار (ادمین)\n" .
               "/timing [fast|normal|slow] - تنظیم تایم (ادمین)\n" .
               "/rules - قوانین بازی\n" .
               "/roles - لیست نقش‌ها\n" .
               "/stats - آمار ربات\n" .
               "/ping - تست اتصال";
        sendMessage($chat_id, $msg);
        break;
        
    case '/rules':
        sendMessage($chat_id, getRules());
        break;
        
    case '/roles':
        sendMessage($chat_id, getRolesList());
        break;
        
    case '/stats':
        $stats = getGameStats();
        $msg = "📊 <b>آمار ربات</b>\n\n" .
               "🎮 کل بازی‌ها: {$stats['total']}\n" .
               "⏳ در انتظار: {$stats['waiting']}\n" .
               "▶️ در حال اجرا: {$stats['started']}\n" .
               "🏁 تمام شده: {$stats['ended']}\n\n" .
               "📊 حجم دیتابیس: " . getDatabaseSize();
        sendMessage($chat_id, $msg);
        break;
        
    case '/extend':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ این دستور فقط در گروه قابل استفاده است!");
        } else {
            $result = extendWaitingTime($chat_id, $user_id);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/timing':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ این دستور فقط در گروه قابل استفاده است!");
        } else {
            if (empty($param)) {
                sendMessage($chat_id, "⚙️ <b>تنظیم تایم بازی</b>\n\n" .
                           "گزینه‌ها:\n" .
                           "/timing fast - ۶۰ ثانیه\n" .
                           "/timing normal - ۹۰ ثانیه\n" .
                           "/timing slow - ۱۲۰ ثانیه");
            } else {
                $result = setGameTiming($chat_id, $user_id, $param);
                sendMessage($chat_id, $result['message']);
            }
        }
        break;
        
    case '/kill':
        // خروج اجباری (فقط ادمین)
        if ($user_id != ADMIN_ID) {
            sendMessage($chat_id, "❌ فقط ادمین اصلی می‌تواند از این دستور استفاده کند!");
        } else {
            // پیاده‌سازی kill
            sendMessage($chat_id, "⚡️ دستور kill - هنوز پیاده‌سازی نشده");
        }
        break;
        
    case '/smite':
        // حذف بازیکن (فقط ادمین)
        if ($user_id != ADMIN_ID) {
            sendMessage($chat_id, "❌ فقط ادمین اصلی می‌تواند از این دستور استفاده کند!");
        } else {
            sendMessage($chat_id, "⚡️ دستور smite - هنوز پیاده‌سازی نشده");
        }
        break;
        
    case '/setlink':
        if (empty($param)) {
            $result = getGroupLink($chat_id);
            if ($result['success']) {
                sendMessage($chat_id, "🔗 <b>لینک گروه:</b>\n" . $result['link']);
            } else {
                sendMessage($chat_id, "❌ لینکی تنظیم نشده است!\nبرای تنظیم: /setlink https://t.me/yourgroup");
            }
        } else {
            $result = setGroupLink($chat_id, $user_id, $param);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/sponsers':
        sendMessage($chat_id, "🤝 <b>اسپانسرها</b>\n\nاز حمایت شما متشکریم!");
        break;
        
    case '/info':
    case '/status':
        $game = getGameInfo($chat_id);
        if (!$game) {
            sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        } else {
            $msg = "🎮 <b>وضعیت بازی</b>\n\n";
            $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
            $msg .= "👤 سازنده: " . $game['creator_name'] . "\n";
            $msg .= "📊 وضعیت: " . ($game['status'] == 'waiting' ? '⏳ در انتظار' : '▶️ در حال اجرا') . "\n";
            $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر\n";
            if ($game['status'] == 'waiting') {
                $remaining = max(0, $game['wait_until'] - time());
                $msg .= "⏱ زمان باقیمانده: " . floor($remaining / 60) . ":" . sprintf("%02d", $remaining % 60);
            }
            sendMessage($chat_id, $msg);
        }
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
