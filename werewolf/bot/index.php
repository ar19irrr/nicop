<?php
// index.php - نسخه اصلاح‌شده (فقط رفع باگ‌ها، بدون حذف هیچ بخشی)

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// ============================================================
// 1. تنظیمات اولیه
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

if (file_exists(__DIR__ . '/lang.php')) {
    require_once __DIR__ . '/lang.php';
} else {
    $lang = [];
}

$roles_path = __DIR__ . '/ROLES_PATCH/';
if (is_dir($roles_path) && file_exists($roles_path . 'factory.php')) {
    require_once $roles_path . 'factory.php';
    require_once $roles_path . 'base.php';
}

// ============================================================
// 2. دیتابیس‌ها و توابع کمکی
// ============================================================

function loadGames() {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'games.json';
    if (!file_exists($file)) file_put_contents($file, '{}');
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveGames($games) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    file_put_contents($data_path . 'games.json', json_encode($games, JSON_PRETTY_PRINT));
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

function getPlayerById($game, $id) {
    foreach ($game['players'] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

function getAlivePlayers($game) {
    return array_filter($game['players'] ?? [], fn($p) => $p['alive'] ?? false);
}

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
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

// ============================================================
// 3. سیستم‌های سکه، رتبه‌بندی، گزارش و تنظیمات (دست‌نخورده)
// ============================================================

function loadCoins() {
    global $data_path;
    $file = $data_path . 'coins.json';
    if (!file_exists($file)) file_put_contents($file, '{}');
    return json_decode(file_get_contents($file), true) ?: [];
}
function saveCoins($coins) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    file_put_contents($data_path . 'coins.json', json_encode($coins, JSON_PRETTY_PRINT));
}
function getCoin($user_id) { $coins = loadCoins(); return $coins[$user_id] ?? 0; }
function addCoin($user_id, $amount) { $coins = loadCoins(); $coins[$user_id] = ($coins[$user_id] ?? 0) + $amount; saveCoins($coins); return $coins[$user_id]; }
function removeCoin($user_id, $amount) { $coins = loadCoins(); $current = $coins[$user_id] ?? 0; if ($current < $amount) return false; $coins[$user_id] = $current - $amount; saveCoins($coins); return true; }

function loadRanks() {
    global $data_path;
    $file = $data_path . 'ranks.json';
    if (!file_exists($file)) { if (!is_dir($data_path)) mkdir($data_path, 0777, true); file_put_contents($file, '{}'); return []; }
    return json_decode(file_get_contents($file), true) ?: [];
}
function saveRanks($ranks) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    file_put_contents($data_path . 'ranks.json', json_encode($ranks, JSON_PRETTY_PRINT));
}

// ============================================================
// 4. توابع اصلی بازی
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    if ($count <= 4) { $roles = ['villager', 'villager', 'werewolf', 'seer']; shuffle($roles); return $roles; }
    if ($count <= 6) { $roles = array_merge(array_fill(0, $count - 3, 'villager'), ['werewolf', 'werewolf'], ['seer']); shuffle($roles); return $roles; }
    if ($count <= 8) { $roles = array_merge(array_fill(0, $count - 4, 'villager'), ['werewolf', 'werewolf'], ['seer', 'guardian_angel']); shuffle($roles); return $roles; }
    if ($count <= 10) { $roles = array_merge(array_fill(0, $count - 5, 'villager'), ['werewolf', 'werewolf'], ['seer', 'guardian_angel', 'hunter']); shuffle($roles); return $roles; }
    if ($count <= 14) { $special = ['seer', 'guardian_angel', 'hunter', 'detective']; $roles = array_merge(array_fill(0, $count - 6, 'villager'), ['werewolf', 'werewolf', 'werewolf'], $special); shuffle($roles); return $roles; }
    if ($count <= 18) { $special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight']; $roles = array_merge(array_fill(0, $count - 7, 'villager'), ['werewolf', 'werewolf', 'werewolf'], $special); shuffle($roles); return $roles; }
    $wolf_count = round($count * 0.2); $special_count = round($count * 0.15);
    $available_special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble'];
    shuffle($available_special); $special = array_slice($available_special, 0, $special_count);
    $villager_count = $count - $wolf_count - count($special);
    if ($villager_count < 2) $villager_count = 2;
    $roles = array_merge(array_fill(0, $villager_count, 'villager'), array_fill(0, $wolf_count, 'werewolf'), $special);
    shuffle($roles);
    return $roles;
}

function createGame($group_id, $creator_id, $creator_name, $mode = 'normal') {
    $games = loadGames();
    foreach ($games as $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return ['success' => false, 'message' => '⏳ یک بازی فعال در این گروه وجود دارد!'];
        }
    }
    $code = generateGameCode();
    $modes = ['normal' => 'عادی', 'easy' => 'آسان 🎈', 'mafia' => 'مافیا 🐺', 'vampire' => 'ومپایری 🧛‍♂️', 'werewolf' => 'ورولف 🐺', 'bomber' => 'بمب‌گذار 💣', 'foolish' => 'احمقانه 🃏', 'mighty' => 'قدرتی ♨️', 'romantic' => 'عاشقانه 👨‍❤️‍👨', 'coin' => 'سکه‌ای 💰'];
    $mode_name = $modes[$mode] ?? 'عادی';
    
    $games[$code] = [
        'code' => $code, 'group_id' => $group_id, 'creator_id' => $creator_id, 'creator_name' => $creator_name,
        'mode' => $mode, 'mode_name' => $mode_name, 'players' => [],
        'status' => 'waiting', 'created' => time(), 'wait_until' => time() + 300, 'extend_count' => 0,
        'phase' => null, 'night_count' => 0, 'day_count' => 0, 'night_actions' => [], 'votes' => [],
        'night_end_time' => 0, 'day_end_time' => 0, 'vote_end_time' => 0,
        'day_duration' => 60, 'night_duration' => 60, 'vote_duration' => 60, 'afk_counts' => []
    ];
    saveGames($games);
    
    $remaining = 300;
    $msg = "🎮 <b>بازی {$mode_name} ساخته شد!</b>\n\n🎲 کد: <code>$code</code>\n👤 سازنده: $creator_name\n👥 تعداد: ۰ نفر\n\n⏱ زمان باقیمانده: ۵ دقیقه\n\n👇 برای ورود به بازی روی دکمه زیر کلیک کن:";
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $code]],
        [['text' => '⚡ شروع زودهنگام (فقط ادمین)', 'callback_data' => 'force_start_' . $code]]
    ]];
    
    sendMessage($group_id, $msg, $keyboard);
    return ['success' => true, 'message' => $msg, 'code' => $code];
}

function joinGame($code, $user_id, $user_name) {
    $games = loadGames();
    if (!isset($games[$code])) return ['success' => false, 'message' => '❌ بازی با این کد پیدا نشد!'];
    $game = $games[$code];
    if ($game['status'] != 'waiting') return ['success' => false, 'message' => '⏳ این بازی قبلاً شروع شده!'];
    if (time() > $game['wait_until']) return ['success' => false, 'message' => '⏰ زمان انتظار تمام شده!'];
    foreach ($game['players'] as $p) { if ($p['id'] == $user_id) return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!']; }
    $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
    $games[$code] = $game;
    saveGames($games);
    sendPlayerList($game['group_id'], $game);
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!", 'game' => $game];
}

function forceStartGame($group_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && $game['status'] == 'waiting') {
            // FIX 1: شرط isAdmin درست کار می‌کند
            if (!isAdmin($user_id, $group_id)) return ['success' => false, 'message' => '❌ فقط ادمین‌ها می‌توانند زودتر شروع کنند!'];
            if (count($game['players']) < 4) return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
            
            $roles = selectBalancedRoles(count($game['players']));
            shuffle($roles);
            $i = 0;
            foreach ($game['players'] as &$p) {
                // FIX 2: چک کردن وجود کلید id برای جلوگیری از ارور Undefined array key
                if (!isset($p['id']) || empty($p['id'])) continue;
                $p['role'] = $roles[$i] ?? 'villager';
                $p['afk_count'] = 0;
                $i++;
            }
            unset($p);
            
            $game['status'] = 'started';
            $game['phase'] = 'night';
            $game['night_count'] = 1;
            $game['night_actions'] = [];
            $game['votes'] = [];
            $game['night_end_time'] = time() + $game['night_duration'];
            $games[$code] = $game;
            saveGames($games);
            
            foreach ($game['players'] as $p) {
                $role_name = getRoleDisplayName($p['role']);
                sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n🌙 شب اول شروع شد...");
                sendNightPanel($p, $game);
            }
            sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه");
            return ['success' => true, 'message' => '⚡ بازی توسط ادمین زودتر شروع شد!'];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای شروع وجود ندارد!'];
}

// ============================================================
// 5. نمایش نقش‌ها
// ============================================================

function getRoleDisplayName($role) {
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getEmoji() . ' ' . $role_obj->getName();
    }
    $names = [
        'villager' => '👨‍🌾 روستایی ساده', 'seer' => '👳🏻‍♂️ پیشگو', 'werewolf' => '🐺 گرگینه',
        'guardian_angel' => '👼🏻 فرشته نگهبان', 'hunter' => '👮🏻‍♂️ کلانتر', 'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'cultist' => '👤 فرقه‌گرا', 'cult_hunter' => '💂🏻‍♂️ شکارچی', 'serial_killer' => '🔪 قاتل زنجیره‌ای',
        'archer' => '🏹 کماندار', 'vampire' => '🧛🏻‍♂️ ومپایر', 'phoenix' => '🪶 ققنوس',
        'fire_king' => '🔥🤴🏻 پادشاه آتش', 'ice_queen' => '❄️👸🏻 ملکه یخی',
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $night_count = $game['night_count'] ?? 1;
    
    $nightRoles = ['werewolf', 'seer', 'guardian_angel', 'serial_killer', 'vampire', 'cultist'];
    if (!in_array($role, $nightRoles)) return; // روستایی‌ها اسکیپ نمی‌شوند
    
    // FIX 3: اصلاح لیست هدف‌ها برای جلوگیری از ارور Undefined array key
    $targets = [];
    foreach ($game['players'] as $p) {
        if (isset($p['id']) && ($p['alive'] ?? false) && $p['id'] != $player['id']) {
            $targets[] = ['id' => $p['id'], 'name' => $p['name']];
        }
    }
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب $night_count</b>\n\n⏳ هدفی نیست!");
        return;
    }
    
    $msg = "🌙 <b>شب $night_count</b>\n\n🎭 نقش: " . getRoleDisplayName($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => 'night_' . $role . '_' . $target['id']];
        if (count($row) == 2) { $keyboard[] = $row; $row = []; }
    }
    if (!empty($row)) $keyboard[] = $row;
    $keyboard[] = [['text' => '⏭️ اسکیپ', 'callback_data' => 'night_skip_' . $role]];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

// ============================================================
// 6. پردازش شب و روز
// ============================================================

function processNight($game_code, $game) {
    $deaths = [];
    $protected = [];
    
    foreach ($game['night_actions'] as $action) {
        $role = $action['role'];
        $target = $action['target'];
        
        if ($role == 'guardian_angel') { $protected[] = $target; continue; }
        if (in_array($role, ['werewolf', 'serial_killer', 'vampire'])) {
            if (!in_array($target, $protected) && $target != 'skip') {
                $target_player = getPlayerById($game, $target);
                if ($target_player && ($target_player['alive'] ?? false)) {
                    $game = killPlayer($game, $target, $role);
                    $deaths[] = $target_player['name'];
                }
            }
            continue;
        }
        if ($role == 'seer' && $target != 'skip') {
            $target_player = getPlayerById($game, $target);
            if ($target_player && ($target_player['alive'] ?? false)) {
                sendPrivateMessage($action['player'], "🔮 نقش {$target_player['name']}: " . getRoleDisplayName($target_player['role']));
            }
            continue;
        }
    }
    
    $game['phase'] = 'day';
    $game['day_count'] = ($game['day_count'] ?? 0) + 1;
    $game['votes'] = [];
    $game['night_actions'] = [];
    $game['night_end_time'] = 0;
    $game['day_end_time'] = time() + $game['day_duration'];
    
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    if (!empty($deaths)) {
        $msg .= "💀 <b>کشته شدگان شب:</b>\n";
        foreach ($deaths as $name) $msg .= "• $name\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    $alive = getAlivePlayers($game);
    $msg .= "👥 <b>بازیکنان زنده (" . count($alive) . "):</b>\n";
    foreach ($alive as $p) $msg .= "• " . $p['name'] . "\n";
    $msg .= "\n🗣️ <b>زمان بحث!</b>\n⏱ " . $game['day_duration'] . " ثانیه.";
    
    sendMessage($game['group_id'], $msg);
}

function startVoting($game) {
    $alivePlayers = getAlivePlayers($game);
    if (count($alivePlayers) < 2) { endGame($game, ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردند!']); return; }
    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_end_time'] = time() + $game['vote_duration'];
    $games = loadGames();
    foreach ($games as $code => $g) { if ($g['group_id'] == $game['group_id']) { $games[$code] = $game; break; } }
    saveGames($games);
    sendMessage($game['group_id'], "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "!</b>\n⏱ {$game['vote_duration']} ثانیه.");
    foreach ($alivePlayers as $p) { sendVotePanel($p, $game); }
}

function sendVotePanel($player, $game) {
    $alivePlayers = array_filter(getAlivePlayers($game), fn($p) => $p['id'] != $player['id']);
    if (empty($alivePlayers)) { sendPrivateMessage($player['id'], "❌ هیچ بازیکن زنده دیگری وجود ندارد!"); return; }
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n👇 یک نفر رو برای اعدام انتخاب کن:";
    $keyboard = []; $row = [];
    foreach ($alivePlayers as $p) {
        $row[] = ['text' => $p['name'], 'callback_data' => 'vote_' . $p['id']];
        if (count($row) == 2) { $keyboard[] = $row; $row = []; }
    }
    if (!empty($row)) $keyboard[] = $row;
    $keyboard[] = [['text' => '⚪ رأی سفید', 'callback_data' => 'vote_skip']];
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processVotes($game_code, $game) {
    $votes = $game['votes'] ?? [];
    $alivePlayers = getAlivePlayers($game);
    $counts = []; $skipCount = 0;
    foreach ($votes as $voter_id => $target_id) {
        if ($target_id == 'skip') $skipCount++;
        else $counts[$target_id] = ($counts[$target_id] ?? 0) + 1;
    }
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    $msg = "🗳️ <b>نتیجه رأی‌گیری روز " . $game['day_count'] . "</b>\n\n📊 آرا: " . count($votes) . " | سفید: $skipCount\n";
    if ($max > 0 && count($targets) == 1) {
        $target_id = $targets[0];
        foreach ($game['players'] as &$p) {
            if ($p['id'] == $target_id) {
                $p['alive'] = false;
                $msg .= "💀 <b>" . $p['name'] . "</b> اعدام شد!\n🎭 نقش: " . getRoleDisplayName($p['role']);
                break;
            }
        }
    } else {
        $msg .= "⚖️ <b>رأی‌ها مساوی شد! کسی اعدام نشد.</b>";
    }
    sendMessage($game['group_id'], $msg);
    $game['phase'] = 'night';
    $game['night_count'] = ($game['night_count'] ?? 0) + 1;
    $game['night_actions'] = [];
    $game['votes'] = [];
    $game['night_end_time'] = time() + $game['night_duration'];
    $game['day_end_time'] = 0;
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...");
    foreach ($game['players'] as $p) { if ($p['alive'] ?? false) sendNightPanel($p, $game); }
}

function checkGameTimers() {
    $games = loadGames();
    $now = time();
    foreach ($games as $code => $game) {
        if ($game['status'] == 'waiting' && isset($game['wait_until']) && $now >= $game['wait_until']) {
            if (count($game['players']) < 4) { unset($games[$code]); saveGames($games); sendMessage($game['group_id'], "⏰ زمان تمام شد! بازی لغو شد!"); continue; }
            else { forceStartGame($game['group_id'], $game['creator_id']); continue; }
        }
        if ($game['status'] != 'started') continue;
        if ($game['phase'] == 'night' && $now >= $game['night_end_time']) {
            // اگر کسی اکشن نداده باشد، برای شب‌کارها اسکیپ ثبت می‌شود
            foreach ($game['players'] as $p) {
                if (($p['alive'] ?? false) && in_array($p['role'], ['werewolf', 'seer', 'guardian_angel', 'serial_killer', 'vampire', 'cultist'])) {
                    $has_action = false;
                    foreach ($game['night_actions'] as $a) { if ($a['player'] == $p['id']) { $has_action = true; break; } }
                    if (!$has_action) {
                        $game['night_actions'][] = ['player' => $p['id'], 'role' => $p['role'], 'target' => 'skip'];
                    }
                }
            }
            processNight($code, $game);
            return;
        }
        if ($game['phase'] == 'day' && $now >= $game['day_end_time']) { startVoting($game); return; }
        if ($game['phase'] == 'vote' && $now >= $game['vote_end_time']) { processVotes($code, $game); return; }
    }
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
    curl_exec($ch);
    curl_close($ch);
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
    curl_exec($ch);
    curl_close($ch);
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
    curl_exec($ch);
    curl_close($ch);
}

function sendPlayerList($chat_id, $game) {
    $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    foreach ($game['players'] as $p) {
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $msg .= "• {$p['name']} $creator\n";
    }
    if ($game['status'] == 'waiting') {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $msg .= "\n⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds);
        
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $game['code']]]
        ]];
        sendMessage($chat_id, $msg, $keyboard);
        
        if ($remaining <= 60 && $remaining > 0) {
            sendMessage($chat_id, "⏰ <b>۱ دقیقه تا پایان زمان انتظار!</b>\nبرای پیوستن عجله کن:", $keyboard);
        }
    } else {
        sendMessage($chat_id, $msg);
    }
}

// ============================================================
// 8. پردازش Webhook
// ============================================================

$json = file_get_contents('php://input');
if (empty($json)) { http_response_code(200); echo '{"ok":true}'; exit; }
$update = json_decode($json, true);
if (!$update) { http_response_code(200); echo '{"ok":true}'; exit; }

checkGameTimers();

if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    if (strpos($data, 'join_') === 0) {
        $code = substr($data, 5);
        $games = loadGames();
        if (!isset($games[$code])) { answerCallbackQuery($callback_id, "❌ پیدا نشد!", true); exit; }
        $game = $games[$code];
        if ($game['status'] != 'waiting' || time() > $game['wait_until']) { answerCallbackQuery($callback_id, "⏳ زمان تمام شده یا بازی شروع شده!", true); exit; }
        foreach ($game['players'] as $p) { if ($p['id'] == $user_id) { answerCallbackQuery($callback_id, "❌ قبلاً در بازی هستید!", true); exit; } }
        $user_name = $callback['from']['first_name'] ?? 'کاربر';
        $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
        $games[$code] = $game;
        saveGames($games);
        answerCallbackQuery($callback_id, "✅ به بازی پیوستید!", false);
        sendPlayerList($chat_id, $game);
        http_response_code(200); echo '{"ok":true}'; exit;
    }
    
    if (strpos($data, 'force_start_') === 0) {
        $result = forceStartGame($chat_id, $user_id);
        answerCallbackQuery($callback_id, $result['message'], true);
        http_response_code(200); echo '{"ok":true}'; exit;
    }
    
    if (strpos($data, 'night_') === 0) {
        $parts = explode('_', $data);
        $role = $parts[1] ?? ''; $target = $parts[2] ?? '';
        $games = loadGames();
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && $g['status'] == 'started' && $g['phase'] == 'night') {
                    $g['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => ($target == 'skip' ? 'skip' : (int)$target)];
                    $games[$code] = $g;
                    saveGames($games);
                    answerCallbackQuery($callback_id, "✅ انتخاب ثبت شد!", false);
                    
                    // اگر همه اکشن‌ها انجام شد، بلافاصله پردازش کن
                    $alive = getAlivePlayers($g);
                    $done = 0;
                    foreach ($alive as $p) {
                        if (in_array($p['role'], ['werewolf', 'seer', 'guardian_angel', 'serial_killer', 'vampire', 'cultist'])) {
                            $found = false;
                            foreach ($g['night_actions'] as $a) { if ($a['player'] == $p['id']) { $found = true; break; } }
                            if ($found) $done++;
                        }
                    }
                    if ($done >= count(array_filter($alive, fn($p) => in_array($p['role'], ['werewolf', 'seer', 'guardian_angel', 'serial_killer', 'vampire', 'cultist'])))) {
                        processNight($code, $g);
                    }
                    http_response_code(200); echo '{"ok":true}'; exit;
                }
            }
        }
        answerCallbackQuery($callback_id, "❌ بازی پیدا نشد!", true);
        http_response_code(200); echo '{"ok":true}'; exit;
    }
    
    if (strpos($data, 'vote_') === 0) {
        $target = substr($data, 5);
        $games = loadGames();
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && $g['status'] == 'started' && $g['phase'] == 'vote') {
                    $g['votes'][$user_id] = ($target == 'skip' ? 'skip' : (int)$target);
                    $games[$code] = $g;
                    saveGames($games);
                    
                    $target_name = ($target == 'skip') ? 'سفید' : (getPlayerById($g, $target)['name'] ?? 'نامشخص');
                    answerCallbackQuery($callback_id, "✅ رأی شما به «$target_name» ثبت شد!", false);
                    
                    $alive = getAlivePlayers($g);
                    if (count($g['votes']) >= count($alive)) {
                        processVotes($code, $g);
                    }
                    http_response_code(200); echo '{"ok":true}'; exit;
                }
            }
        }
        answerCallbackQuery($callback_id, "❌ بازی پیدا نشد!", true);
        http_response_code(200); echo '{"ok":true}'; exit;
    }
}

if (!isset($update['message'])) { http_response_code(200); echo '{"ok":true}'; exit; }

$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$text = $message['text'] ?? '';
$first_name = $message['from']['first_name'] ?? 'کاربر';
$chat_type = $message['chat']['type'] ?? 'private';

if (substr($text, 0, 1) !== '/') { http_response_code(200); echo '{"ok":true}'; exit; }

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
if (strpos($command, '@') !== false) {
    $command_parts = explode('@', $command);
    $command = $command_parts[0];
}
$param = $parts[1] ?? '';

switch ($command) {
    case '/start':
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'], ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']],
            [['text' => '📜 قوانین', 'callback_data' => 'rules'], ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']]
        ]];
        sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 به ربات گرگینه خوش اومدی!", $keyboard);
        break;
    
    case '/game':
        if ($chat_type == 'private') { sendMessage($chat_id, "❌ فقط در گروه!"); }
        else {
            $result = createGame($chat_id, $user_id, $first_name);
            if (!$result['success']) sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/join':
        if (empty($param)) { sendMessage($chat_id, "❌ کد را وارد کنید! مثال: /join AB12CD"); }
        else {
            $result = joinGame(strtoupper(trim($param)), $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/players':
        $game = getGroupActiveGame($chat_id);
        if (!$game) sendMessage($chat_id, "❌ بازی فعالی نیست!");
        else sendPlayerList($chat_id, $game);
        break;
    
    case '/stop':
        $result = cancelGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
    
    case '/extend':
        if ($chat_type == 'private') { sendMessage($chat_id, "❌ فقط در گروه!"); }
        else {
            $result = extendWaitingTime($chat_id, $user_id);
            sendMessage($chat_id, $result['message']);
        }
        break;
    
    default:
        sendMessage($chat_id, "❌ دستور نامشخص! /help");
        break;
}

http_response_code(200);
echo '{"ok":true}';
?>
