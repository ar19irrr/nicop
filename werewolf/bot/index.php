<?php
// index.php - نسخه کامل با دکمه شیشه‌ای ورود و لیست بازیکنان

// ============================================================
// 1. تنظیمات اولیه
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';

// ============================================================
// 2. دیتابیس‌ها
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

// ============================================================
// 3. سیستم درجه‌بندی
// ============================================================

function getRankName($rank) {
    $rank_names = [
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
    return $rank_names[$rank] ?? 'افسانه‌ای 🏆';
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
    
    if ($new_rank > $old_rank) {
        $old_name = getRankName($old_rank);
        $new_name = getRankName($new_rank);
        
        return [
            'ranked_up' => true,
            'old_rank' => $old_rank,
            'new_rank' => $new_rank,
            'old_name' => $old_name,
            'new_name' => $new_name,
            'xp' => $ranks[$user_id]['xp'],
            'next_xp' => getXPForNextRank($new_rank)
        ];
    }
    
    return [
        'ranked_up' => false,
        'xp' => $ranks[$user_id]['xp'],
        'next_xp' => getXPForNextRank($ranks[$user_id]['rank'])
    ];
}

function getRankInfo($user_id) {
    $ranks = loadRanks();
    $data = $ranks[$user_id] ?? ['xp' => 0, 'rank' => 1];
    $rank = $data['rank'];
    $xp = $data['xp'];
    $next_xp = getXPForNextRank($rank);
    $rank_name = getRankName($rank);
    
    return [
        'rank' => $rank,
        'xp' => $xp,
        'next_xp' => $next_xp,
        'rank_name' => $rank_name,
        'progress' => round(($xp / $next_xp) * 100, 1)
    ];
}

function addXPAfterGame($game) {
    $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    $dead = array_filter($game['players'], fn($p) => !($p['alive'] ?? false));
    
    foreach ($alive as $p) {
        $result = addXP($p['id'], 50);
        if ($result['ranked_up']) {
            sendRankUpMessage($p['id'], $result, $p['name']);
        }
    }
    
    foreach ($dead as $p) {
        $result = addXP($p['id'], 20);
        if ($result['ranked_up']) {
            sendRankUpMessage($p['id'], $result, $p['name']);
        }
    }
}

function sendRankUpMessage($user_id, $result, $user_name = 'کاربر عزیز') {
    $msg = "☀️☀️بهت کلی تبریک میگم میدونی  چرا؟\n";
    $msg .= "چون ارتقا درجه پیدا کردی 🎖\n";
    $msg .= "به دستور <b>{$user_name}</b> 👑 تو از درجه <b>{$result['old_name']}</b> به درجه <b>{$result['new_name']}</b> ارتقا پیدا کردی.🏅\n";
    $msg .= "حالا برو جلوی دوستات پز بده 👬";
    
    sendMessage($user_id, $msg);
}

// ============================================================
// 4. توابع اصلی بازی
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
        if (isset($game['group_id']) && $game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
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
        'vote_duration' => 60,
        'game_mode' => 'normal'
    ];
    
    saveGames($games);
    
    $remaining = $game['wait_until'] - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    // ===== پیام با دکمه شیشه‌ای =====
    $msg = "🎮 <b>بازی جدید ساخته شد!</b>\n\n";
    $msg .= "🎲 کد: <code>$code</code>\n";
    $msg .= "👤 سازنده: $creator_name\n";
    $msg .= "👥 تعداد: ۱ نفر\n\n";
    $msg .= "⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds) . "\n\n";
    $msg .= "👇 برای ورود به بازی روی دکمه زیر کلیک کن:";
    
    // ===== دکمه شیشه‌ای =====
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $code]
            ]
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
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر"];
}

function getGameInfo($group_id) {
    $games = loadGames();
    foreach ($games as $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
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

function getAlivePlayers($game) {
    return array_filter($game['players'] ?? [], fn($p) => $p['alive'] ?? false);
}

function getPlayerById($game, $id) {
    foreach ($game['players'] ?? [] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

function cancelGame($chat_id, $user_id) {
    $games = loadGames();
    $found = false;
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
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

function leaveGame($chat_id, $user_id) {
    $games = loadGames();
    $found = false;
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

function extendWaitingTime($chat_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
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
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            $times = ['fast' => 60, 'normal' => 90, 'slow' => 120];
            if (!isset($times[$timing])) {
                return ['success' => false, 'message' => '❌ گزینه نامعتبر!'];
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
        sendNightPanel($p, $game);
    }
    
    sendMessage($group_id, "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>\n\n👥 " . count($game['players']) . " نفر\n🌙 شب اول..."];
}

function endGame($game) {
    $game['status'] = 'ended';
    $games = loadGames();
    foreach ($games as $code => $g) {
        if (isset($g['group_id']) && $g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    sendMessage($game['group_id'], "🏁 <b>بازی تمام شد!</b>");
    addXPAfterGame($game);
}

// ============================================================
// 5. فازهای بازی
// ============================================================

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $nightRoles = ['werewolf', 'seer', 'guardian_angel', 'hunter'];
    
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
        if ($action['role'] == 'werewolf') {
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
    $game['day_end_time'] = time() + $game['day_duration'];
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
    
    $msg .= "🗣️ <b>زمان بحث!</b>\n⏱ {$game['day_duration']} ثانیه وقت دارید.\nبعدش رأی‌گیری شروع می‌شه.";
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
    $game['vote_end_time'] = time() + $game['vote_duration'];
    
    $games = loadGames();
    foreach ($games as $code => $g) {
        if (isset($g['group_id']) && $g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    
    sendMessage($game['group_id'], "🗳️ <b>زمان رأی‌گیری!</b>\n⏱ {$game['vote_duration']} ثانیه وقت دارید.\nبه صورت خصوصی به ربات پیام دهید.");
    
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
    $wolves = array_filter($alive, fn($p) => $p['role'] == 'werewolf');
    $villagers = array_filter($alive, fn($p) => $p['role'] != 'werewolf');
    
    if (count($wolves) == 0) {
        sendMessage($game['group_id'], "🎉 <b>روستایی‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        addXPAfterGame($game);
        return;
    }
    
    if (count($wolves) >= count($villagers)) {
        sendMessage($game['group_id'], "🎉 <b>گرگ‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        addXPAfterGame($game);
        return;
    }
    
    $game['phase'] = 'night';
    $game['night_count'] = ($game['night_count'] ?? 0) + 1;
    $game['night_actions'] = [];
    $game['night_end_time'] = time() + $game['night_duration'];
    $games[$game_code] = $game;
    saveGames($games);
    
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    
    foreach ($game['players'] as $p) {
        if ($p['alive'] ?? false) {
            sendNightPanel($p, $game);
        }
    }
}

// ============================================================
// 6. توابع کمکی نقش‌ها
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

function getRoleActionDescription($role) {
    $actions = [
        'werewolf' => '🐺 یک نفر را برای خوردن انتخاب کن.',
        'seer' => '👁️ یک نفر را برای دیدن نقش انتخاب کن.',
        'guardian_angel' => '🛡️ یک نفر را برای محافظت انتخاب کن.',
        'hunter' => '🔫 یک نفر را برای شلیک انتخاب کن.'
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
           "👮🏻‍♂️ کلانتر - اگر بمیرد، می‌تواند به یک نفر شلیک کند";
}

// ============================================================
// 7. توابع ارسال پیام
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
// 8. تابع نمایش لیست بازیکنان (برای بعد از جوین)
// ============================================================

function sendPlayerList($chat_id, $game) {
    $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    
    foreach ($game['players'] as $p) {
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "• {$p['name']} $creator\n";
    }
    
    $msg .= "\n⏱ زمان باقیمانده: " . floor(max(0, $game['wait_until'] - time()) / 60) . " دقیقه";
    
    sendMessage($chat_id, $msg);
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

// ===== پردازش دکمه‌های شیشه‌ای =====
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    // ===== دکمه ورود به بازی (join_) =====
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
        
        // چک کن که بازی در حال انتظار باشه
        if ($game['status'] != 'waiting') {
            answerCallbackQuery($callback_id, "⏳ این بازی قبلاً شروع شده!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        // چک کن که کاربر قبلاً توی بازی نیست
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                answerCallbackQuery($callback_id, "❌ شما قبلاً در این بازی هستید!", true);
                http_response_code(200);
                echo '{"ok":true}';
                exit;
            }
        }
        
        // اضافه کردن کاربر به بازی
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
        
        // پاسخ به کاربر
        answerCallbackQuery($callback_id, "✅ به بازی پیوستید!", false);
        
        // ارسال پیام تایید به کاربر
        sendPrivateMessage($user_id, "✅ شما به بازی با کد <code>$code</code> پیوستید!\n👥 تعداد: " . count($game['players']) . " نفر");
        
        // اطلاع به گروه
        sendMessage($chat_id, "✅ <b>$user_name</b> به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر");
        
        // ===== نمایش لیست بازیکنان =====
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
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و دستور /game را بزنید."; break;
        case 'join_menu': $response = "🔗 کد بازی را وارد کنید:\nمثال: /join AB12CD"; break;
        case 'rules': $response = getRules(); break;
        case 'roles': $response = getRolesList(); break;
        case 'help': $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join [کد] - پیوستن\n/players - لیست بازیکنان\n/startgame - شروع بازی\n/stop - لغو\n/leave - خروج\n/ping - تست"; break;
        case 'stats':
            $games = loadGames();
            $total = count($games);
            $waiting = count(array_filter($games, fn($g) => $g['status'] == 'waiting'));
            $started = count(array_filter($games, fn($g) => $g['status'] == 'started'));
            $response = "📊 <b>آمار ربات</b>\n\n" .
                       "🎮 کل بازی‌ها: $total\n" .
                       "⏳ در انتظار: $waiting\n" .
                       "▶️ در حال اجرا: $started";
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

// اگه پیام با / شروع نشه، نادیده بگیر
if (substr($text, 0, 1) !== '/') {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

// ===== چک کردن تایمرها =====
$games = loadGames();
foreach ($games as $code => $game) {
    if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'started') {
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

// ============================================================
// 10. پردازش دستورات
// ============================================================

switch ($command) {
    case '/start':
        $msg = "👋 سلام <b>$first_name</b>!\n🐺 به ربات گرگینه خوش اومدی!\n\n📱 یکی رو انتخاب کن:";
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
            if ($result['success']) {
                // پیام قبلاً توسط createGame ارسال شده
            } else {
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
            
            // بعد از جوین، لیست بازیکنان رو نمایش بده
            if ($result['success']) {
                $game = getGame($code);
                if ($game) {
                    sendPlayerList($chat_id, $game);
                }
            }
        }
        break;
        
    case '/players':
        $game = getGameInfo($chat_id);
        if (!$game) {
            sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        } else {
            sendPlayerList($chat_id, $game);
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
        sendMessage($chat_id, "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join [کد] - پیوستن\n/players - لیست بازیکنان\n/startgame - شروع بازی\n/stop - لغو\n/leave - خروج\n/ping - تست");
        break;
        
    case '/rules':
        sendMessage($chat_id, getRules());
        break;
        
    case '/roles':
        sendMessage($chat_id, getRolesList());
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
