<?php
// index.php - نسخه نهایی و کامل (همه سیستم‌ها و ۷۵ نقش + ویژگی‌های جدید + رفع ارورهای تابع)

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

// ============================================================
// 2. بارگذاری فایل‌های اصلی
// ============================================================

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
// 3. دیتابیس‌ها
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

function removeCoin($user_id, $amount) {
    $coins = loadCoins();
    $current = $coins[$user_id] ?? 0;
    if ($current < $amount) return false;
    $coins[$user_id] = $current - $amount;
    saveCoins($coins);
    return true;
}

function getShopItems() {
    return [
        ['id' => 'role_guardian', 'name' => '👼 فرشته نگهبان', 'price' => 500],
        ['id' => 'role_hunter', 'name' => '👮 کلانتر', 'price' => 800],
        ['id' => 'role_serial', 'name' => '🔪 قاتل زنجیره‌ای', 'price' => 1200],
        ['id' => 'role_vampire', 'name' => '🧛 ومپایر', 'price' => 1500],
        ['id' => 'role_cultist', 'name' => '👤 فرقه‌گرا', 'price' => 1000],
        ['id' => 'role_joker', 'name' => '🤡 جوکر', 'price' => 2000],
        ['id' => 'reset_role', 'name' => '🔄 ریست نقش', 'price' => 300],
        ['id' => 'free_coin', 'name' => '🪙 ۱۰۰ سکه رایگان', 'price' => 0]
    ];
}

function buyItem($user_id, $item_id) {
    $items = getShopItems();
    $item = null;
    foreach ($items as $i) {
        if ($i['id'] == $item_id) {
            $item = $i;
            break;
        }
    }
    if (!$item) return ['success' => false, 'message' => '❌ آیتم نامعتبر!'];
    if ($item['price'] == 0) {
        addCoin($user_id, 100);
        return ['success' => true, 'message' => "✅ ۱۰۰ سکه رایگان دریافت شد!"];
    }
    if (!removeCoin($user_id, $item['price'])) {
        return ['success' => false, 'message' => '❌ سکه کافی نیست!'];
    }
    return ['success' => true, 'message' => "✅ {$item['name']} خریداری شد!"];
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
        1 => 'نوب پلیر 😣', 2 => 'پلیر 😕', 3 => 'روستایی 👨🏻', 4 => 'روستایی پررو 😌',
        5 => 'دلبر روستا 👰🏻', 6 => 'کلانتر روستا 👮🏻‍♂️', 7 => 'پیشگو روستا 👳🏻‍♂️',
        8 => 'فرشته روستا 👼🏻', 9 => 'شکارچی روستا 💂🏻‍♂️', 10 => 'پررو پلیر روستا 👽',
        11 => 'فرقه‌گرای نوب 🤮', 12 => 'فرقه‌گرا 👤', 13 => 'رئیس فرقه 🎩',
        14 => 'پررو پلیر فرقه 🗣', 15 => 'ومپایر نوب 👩‍🦲', 16 => 'ومپایر 🧛🏻‍♂️',
        17 => 'ومپایر اصیل 🧛🏻‍♀️', 18 => 'گرگینه 🐺', 19 => 'گرگ ایکس 🌕🐺',
        20 => 'گرگ توله 🐶', 21 => 'گرگ آلفا ⚡️🐺', 22 => 'ملکه جنگل 🧝🏻‍♀️🐺',
        23 => 'قاتل زنجیره‌ای 🔪', 24 => 'قاتل سریالی ⚰️', 25 => 'کماندار جنگل 🏹',
        26 => 'شوالیه پررو 🎠', 27 => 'اونیکس پلیر 🥉', 28 => 'پایه بالای اونیکس 🥈',
        29 => 'پررو پلیر اونیکس 🥇', 30 => 'پادشاه اونیکس 👑'
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

// ============================================================
// 6. سیستم گزارش
// ============================================================

function loadReports() {
    global $data_path;
    $file = $data_path . 'reports.json';
    if (!file_exists($file)) {
        file_put_contents($file, '[]');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveReports($reports) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'reports.json';
    file_put_contents($file, json_encode($reports, JSON_PRETTY_PRINT));
}

function addReport($reporter_id, $reported_id, $reason, $game_code = null) {
    $reports = loadReports();
    $reports[] = [
        'id' => count($reports) + 1,
        'reporter_id' => $reporter_id,
        'reported_id' => $reported_id,
        'reason' => $reason,
        'game_code' => $game_code,
        'status' => 'pending',
        'created' => time()
    ];
    saveReports($reports);
    return true;
}

// ============================================================
// 7. سیستم تنظیمات گروه
// ============================================================

function loadGroupSettings() {
    global $data_path;
    $file = $data_path . 'group_settings.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveGroupSettings($settings) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'group_settings.json';
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
}

function setGroupSetting($group_id, $key, $value) {
    $settings = loadGroupSettings();
    if (!isset($settings[$group_id])) $settings[$group_id] = [];
    $settings[$group_id][$key] = $value;
    saveGroupSettings($settings);
}

function loadLinks() {
    global $data_path;
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

function getGroupLink($chat_id) {
    $links = loadLinks();
    if (isset($links[$chat_id])) {
        return ['success' => true, 'link' => $links[$chat_id]['link']];
    }
    return ['success' => false, 'message' => '❌ لینکی برای این گروه تنظیم نشده است!'];
}

function setGroupLink($chat_id, $user_id, $link) {
    if (!preg_match('/^https?:\/\/[^\s]+$/', $link)) {
        return ['success' => false, 'message' => '❌ لینک نامعتبر است!'];
    }
    $links = loadLinks();
    $links[$chat_id] = ['link' => $link, 'set_by' => $user_id, 'set_at' => time()];
    saveLinks($links);
    return ['success' => true, 'message' => "✅ لینک گروه ذخیره شد:\n$link"];
}

function removeGroupLink($chat_id, $user_id) {
    $links = loadLinks();
    if (isset($links[$chat_id])) {
        unset($links[$chat_id]);
        saveLinks($links);
        return ['success' => true, 'message' => '✅ لینک گروه حذف شد!'];
    }
    return ['success' => false, 'message' => '❌ لینکی برای این گروه وجود ندارد!'];
}

// ============================================================
// 8. سیستم امتیاز
// ============================================================

function loadScores() {
    global $data_path;
    $file = $data_path . 'scores.json';
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveScores($scores) {
    global $data_path;
    if (!is_dir($data_path)) mkdir($data_path, 0777, true);
    $file = $data_path . 'scores.json';
    file_put_contents($file, json_encode($scores, JSON_PRETTY_PRINT));
}

function getScore($user_id) {
    $scores = loadScores();
    return $scores[$user_id] ?? 0;
}

// ============================================================
// 9. توابع اصلی بازی
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    
    if ($count <= 4) {
        $roles = ['villager', 'villager', 'werewolf', 'seer'];
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 6) {
        $roles = array_merge(
            array_fill(0, $count - 3, 'villager'),
            ['werewolf', 'werewolf'],
            ['seer']
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 8) {
        $roles = array_merge(
            array_fill(0, $count - 4, 'villager'),
            ['werewolf', 'werewolf'],
            ['seer', 'guardian_angel']
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 10) {
        $roles = array_merge(
            array_fill(0, $count - 5, 'villager'),
            ['werewolf', 'werewolf'],
            ['seer', 'guardian_angel', 'hunter']
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 14) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective'];
        $roles = array_merge(
            array_fill(0, $count - 6, 'villager'),
            ['werewolf', 'werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 18) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight'];
        $roles = array_merge(
            array_fill(0, $count - 7, 'villager'),
            ['werewolf', 'werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // ۱۹+ نفر
    $wolf_count = round($count * 0.2);
    $special_count = round($count * 0.15);
    
    $available_special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 
                          'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble'];
    shuffle($available_special);
    $special = array_slice($available_special, 0, $special_count);
    
    $villager_count = $count - $wolf_count - count($special);
    if ($villager_count < 2) $villager_count = 2;
    
    $roles = array_merge(
        array_fill(0, $villager_count, 'villager'),
        array_fill(0, $wolf_count, 'werewolf'),
        $special
    );
    shuffle($roles);
    return $roles;
}

// ============================================================
// 10. توابع بازی با ویژگی‌های جدید
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
        'vote_duration' => 60,
        'afk_counts' => []
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
            [['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $code]],
            [['text' => '⚡ شروع زودهنگام (فقط ادمین)', 'callback_data' => 'force_start_' . $code]]
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

function extendWaitingTime($chat_id, $user_id) {
    $games = loadGames();
    if (!isAdmin($user_id, $chat_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین‌های گروه می‌توانند زمان را تمدید کنند!'];
    }
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $chat_id && $game['status'] == 'waiting') {
            $game['wait_until'] += 30;
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

function forceStartGame($group_id, $user_id) {
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
    if (!isAdmin($user_id, $group_id)) {
        return ['success' => false, 'message' => '❌ فقط ادمین‌های گروه می‌توانند زودتر شروع کنند!'];
    }
    if (count($game['players']) < 4) {
        return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
    }
    
            $roles = selectBalancedRoles(count($game['players']));
            shuffle($roles);
            $i = 0;
            foreach ($game['players'] as &$p) {
                if (!isset($p['id']) || empty($p['id'])) continue; // اگر بازیکن خراب بود ردش کن
                $p['role'] = $roles[$i] ?? 'villager';
                $p['afk_count'] = 0;
                $i++;
            }
            unset($p);
    }
    
    $game['status'] = 'started';
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    $game['night_actions'] = [];
    $game['votes'] = [];
    $game['night_end_time'] = time() + $game['night_duration'];
    $game['day_end_time'] = 0;
    $game['vote_end_time'] = 0;
    
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    foreach ($game['players'] as $p) {
        $role_name = getRoleDisplayName($p['role']);
        // استفاده از تابع تغییر نام داده شده برای جلوگیری از تداخل با lang.php
        sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n" .
            getRoleDescriptionLocal($p['role']) . "\n\n🌙 شب اول شروع شد...");
        sendNightPanel($p, $game);
    }
    
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>"];
}

// ============================================================
// 11. پنل شب و توابع مربوطه
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
        'hunter' => '👮🏻‍♂️ کلانتر',
        'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'cultist' => '👤 فرقه‌گرا',
        'cult_hunter' => '💂🏻‍♂️ شکارچی',
        'serial_killer' => '🔪 قاتل زنجیره‌ای',
        'archer' => '🏹 کماندار',
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'phoenix' => '🪶 ققنوس',
        'fire_king' => '🔥🤴🏻 پادشاه آتش',
        'ice_queen' => '❄️👸🏻 ملکه یخی',
    ];
    return $names[$role] ?? '❓ ' . $role;
}

// این تابع تغییر نام داده شده است تا با lang.php تداخل نداشته باشد
function getRoleDescriptionLocal($role) {
    global $lang;
    $key = 'role_' . $role;
    if (isset($lang[$key])) {
        return $lang[$key];
    }
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getDescription();
    }
    return "🎭 شما " . getRoleDisplayName($role) . " هستید!";
}

function getRoleActionDescription($role) {
    $actions = [
        'werewolf' => '🐺 یک نفر را برای خوردن انتخاب کن.',
        'alpha_wolf' => '⚡️🐺 یک نفر را برای حمله انتخاب کن.',
        'wolf_cub' => '🐶 یک نفر را برای خوردن انتخاب کن.',
        'lycan' => '🌝🐺 یک نفر را برای خوردن انتخاب کن.',
        'forest_queen' => '🧝🏻‍♀️🐺 یک نفر را برای خوردن انتخاب کن.',
        'white_wolf' => '🌩🐺 یک نفر را برای محافظت انتخاب کن.',
        'beta_wolf' => '💤🐺 یک نفر را برای خوردن انتخاب کن.',
        'ice_wolf' => '☃️🐺 یک نفر را برای منجمد کردن انتخاب کن.',
        'enchanter' => '🧙🏻‍♂️ یک نفر را برای طلسم کردن انتخاب کن.',
        'honey' => '🧙🏻‍♀️ یک نفر را برای تغییر نقش انتخاب کن.',
        'sorcerer' => '🔮 یک نفر را برای دیدن نقش انتخاب کن.',
        'vampire' => '🧛🏻‍♂️ یک نفر را برای حمله انتخاب کن.',
        'bloodthirsty' => '🧛🏻‍♀️ شما زندانی هستید! ومپایرها باید شما رو آزاد کنن.',
        'kent_vampire' => '💍🧛🏻 یک نفر را برای زیر نظر گرفتن انتخاب کن.',
        'chiang' => '👩‍🦳 یک نفر را برای بررسی انتخاب کن.',
        'serial_killer' => '🔪 یک نفر را برای کشتن انتخاب کن.',
        'archer' => '🏹 یک نفر را برای تیراندازی انتخاب کن.',
        'davina' => '🍾 یک روز را سکوت کن.',
        'seer' => '👁️ یک نفر را برای دیدن نقش انتخاب کن.',
        'guardian_angel' => '🛡️ یک نفر را برای محافظت انتخاب کن.',
        'knight' => '🗡 یک نفر را برای محافظت انتخاب کن.',
        'hunter' => '🔫 کلانتر هستید! ومپایر اصیل در زندان شماست.',
        'harlot' => '💋 یک نفر را برای ملاقات انتخاب کن.',
        'detective' => '🔍 یک نفر را برای تحقیق انتخاب کن.',
        'cupid' => '💘 دو نفر را برای عاشق کردن انتخاب کن.',
        'phoenix' => '🪶 شب ۳ و ۵ می‌تونی اشک بدی (۲ تا).',
        'sandman' => '💤 همه را بخوابان.',
        'spy' => '🦹🏻‍♂️ یک نفر را برای جاسوسی انتخاب کن.',
        'cultist' => '👤 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'royce' => '🎩 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'frankenstein' => '🧟‍♂️🪖 یک نفر را برای محافظت انتخاب کن.',
        'monk_black' => '🦇 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'fire_king' => '🔥🤴🏻 شب اول نفت بپاش، شب دوم آتش بزن.',
        'ice_queen' => '❄️👸🏻 یک نفر را برای منجمد کردن انتخاب کن.',
        'lilith' => '🐍👩🏻‍🦳 یک نفر را برای جستجو انتخاب کن.',
        'magento' => '🧲 یک نفر را برای جذب انتخاب کن.',
        'black_knight' => '🥷🗡 یک نفر را برای دفاع انتخاب کن.',
        'bride_dead' => '👰‍♀☠️ یک نفر را برای کشتن انتخاب کن.',
        'joker' => '🤡 یک نفر را برای جستجو انتخاب کن.',
        'harly' => '👩🏻‍🎤 از جوکر محافظت کن.',
        'dian' => '🧞‍♂️ هدف خود را انتخاب کن.',
        'dinamit' => '🧨 یک خونه را برای جستجو انتخاب کن.',
        'bomber' => '💣 یک خونه را برای بمب‌گذاری انتخاب کن.',
        'tso' => '⚔️ یک نفر را برای جستجوی جومونگ انتخاب کن.',
        'lucifer' => '😈 یک نفر را برای گول زدن انتخاب کن.'
    ];
    return $actions[$role] ?? '🎭 یک نفر را انتخاب کن.';
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $night_count = $game['night_count'] ?? 1;
    
    $nightRoles = [
        'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
        'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
        'serial_killer', 'archer', 'davina',
        'seer', 'guardian_angel', 'knight', 'harlot',
        'detective', 'cupid', 'sandman', 'spy',
        'cultist', 'royce', 'frankenstein', 'monk_black',
        'ice_queen', 'lilith', 'magento',
        'black_knight', 'bride_dead',
        'joker', 'harly',
        'dian', 'dinamit', 'bomber', 'tso', 'lucifer'
    ];
    
    if ($role == 'phoenix' && !in_array($night_count, [3, 5])) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 ققنوس فقط شب‌های ۳ و ۵ می‌تونه اشک بده.");
        return;
    }
    
    if ($role == 'phoenix') {
        $used_tears = $player['role_data']['tears_used'] ?? 0;
        if ($used_tears >= 2) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 اشک‌هات تموم شده!");
            return;
        }
    }
    
    if ($role == 'bloodthirsty') {
        $is_free = $player['role_data']['is_free'] ?? false;
        if (!$is_free) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⛓️ شما توسط کلانتر زندانی هستید!");
            return;
        }
    }
    
    if ($role == 'fire_king') {
        $oiled_houses = $player['role_data']['oiled_houses'] ?? [];
        $detonated = $player['role_data']['detonated'] ?? false;
        if ($detonated) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 قبلاً آتش زدی!");
            return;
        }
        $targets = getValidNightTargets($role, $game, $player['id']);
        if (empty($targets)) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⏳ هیچ هدفی نیست!");
            return;
        }
        sendFireKingPanel($player, $game, $targets);
        return;
    }
    
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 تو می‌تونی بخوابی...");
        return;
    }
    
    $targets = getValidNightTargets($role, $game, $player['id']);
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
        return;
    }
    
    $msg = "🌙 <b>شب " . $night_count . "</b>\n\n";
    $msg .= "🎭 نقش شما: " . getRoleDisplayName($role) . "\n\n";
    $msg .= getRoleActionDescription($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن:";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => 'night_' . $role . '_' . $target['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    $keyboard[] = [['text' => '⏭️ اسکیپ', 'callback_data' => 'night_skip_' . $role]];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function getValidNightTargets($role, $game, $playerId) {
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($playerId) {
        return ($p['alive'] ?? false) && $p['id'] != $playerId;
    });
    
    $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                  'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
    $vampireRoles = ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'];
    
    foreach ($alivePlayers as $p) {
        if (in_array($role, $wolfRoles) && in_array($p['role'], $wolfRoles)) continue;
        if (in_array($role, $vampireRoles) && in_array($p['role'], $vampireRoles)) continue;
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    return $targets;
}

function sendFireKingPanel($player, $game, $targets) {
    $oiled_houses = $player['role_data']['oiled_houses'] ?? [];
    $msg = "🔥 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: پادشاه آتش 🔥🤴🏻\n\n";
    if (!empty($oiled_houses)) {
        $msg .= "💥 خونه‌های نفتی: " . count($oiled_houses) . " خونه\n";
        $msg .= "🔥 می‌تونی همه رو آتش بزنی!\n\n";
    }
    $msg .= "👇 انتخاب کن:";
    
    $keyboard = [];
    if (!empty($oiled_houses)) {
        $keyboard[] = [['text' => '💥 آتش زدن همه خونه‌های نفتی', 'callback_data' => 'night_fireking_detonate']];
    }
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => '🛢️ ' . $target['name'], 'callback_data' => 'night_fireking_oil_' . $target['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    $keyboard[] = [['text' => '⏭️ اسکیپ', 'callback_data' => 'night_skip_fireking']];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

// ============================================================
// 12. پردازش شب و روز (این بخش‌ها خطاهای قبلی را رفع می‌کنند)
// ============================================================

function processNight($game_code, $game) {
    $deaths = [];
    $protected = [];
    $seer_results = [];
    $fire_deaths = [];
    
    foreach ($game['night_actions'] as $action) {
        $role = $action['role'];
        $target = $action['target'];
        $player = $action['player'];
        
        if ($role == 'guardian_angel') {
            $protected[] = $target;
            continue;
        }
        
        if ($role == 'fireking_oil') {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player) {
                    if (!isset($p['role_data']['oiled_houses'])) $p['role_data']['oiled_houses'] = [];
                    if (!in_array($target, $p['role_data']['oiled_houses'])) {
                        $p['role_data']['oiled_houses'][] = $target;
                    }
                    break;
                }
            }
            continue;
        }
        
        if ($role == 'fireking_detonate') {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player) {
                    $oiled = $p['role_data']['oiled_houses'] ?? [];
                    foreach ($oiled as $house_id) {
                        $target_player = getPlayerById($game, $house_id);
                        if ($target_player && ($target_player['alive'] ?? false) && !in_array($house_id, $protected)) {
                            $game = killPlayer($game, $house_id, 'fire');
                            $fire_deaths[] = $target_player['name'];
                        }
                    }
                    $p['role_data']['detonated'] = true;
                    $p['role_data']['oiled_houses'] = [];
                    break;
                }
            }
            continue;
        }
        
        if (in_array($role, ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                             'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            if (!in_array($target, $protected) && $target != 'skip') {
                $target_player = getPlayerById($game, $target);
                if ($target_player && ($target_player['alive'] ?? false)) {
                    $game = killPlayer($game, $target, 'werewolf');
                    $deaths[] = $target_player['name'];
                }
            }
            continue;
        }
        
        if (in_array($role, ['serial_killer', 'archer', 'davina'])) {
            if (!in_array($target, $protected) && $target != 'skip') {
                $target_player = getPlayerById($game, $target);
                if ($target_player && ($target_player['alive'] ?? false)) {
                    $game = killPlayer($game, $target, 'serial_killer');
                    $deaths[] = $target_player['name'];
                }
            }
            continue;
        }
        
        if (in_array($role, ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'])) {
            if (!in_array($target, $protected) && $target != 'skip') {
                $target_player = getPlayerById($game, $target);
                if ($target_player && ($target_player['alive'] ?? false)) {
                    $game = killPlayer($game, $target, 'vampire');
                    $deaths[] = $target_player['name'];
                }
            }
            continue;
        }
        
        if ($role == 'seer' && $target != 'skip') {
            $target_player = getPlayerById($game, $target);
            if ($target_player && ($target_player['alive'] ?? false)) {
                $seer_results[] = ['player' => $player, 'target' => $target_player['name'], 'role' => getRoleDisplayName($target_player['role'])];
            }
            continue;
        }
        
        if ($role == 'detective' && $target != 'skip') {
            $target_player = getPlayerById($game, $target);
            if ($target_player && ($target_player['alive'] ?? false)) {
                $can_kill = in_array($target_player['role'], ['serial_killer', 'werewolf', 'vampire', 'cultist', 'alpha_wolf']);
                sendPrivateMessage($player, "🕵️‍♂️ تحقیق:\n" . $target_player['name'] . " " . ($can_kill ? "🔪 توانایی کشتن دارد!" : "✅ توانایی کشتن ندارد!"));
            }
            continue;
        }
    }
    
    foreach ($seer_results as $result) {
        sendPrivateMessage($result['player'], "🔮 نقش " . $result['target'] . ": " . $result['role']);
    }
    
    $deaths = array_merge($deaths, $fire_deaths);
    
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
        $msg .= "💀 <b>کشته شدگان شب:</b>\n";
        foreach ($deaths as $name) $msg .= "• $name\n";
        $msg .= "\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    
    $alive = getAlivePlayers($game);
    $msg .= "👥 <b>بازیکنان زنده (" . count($alive) . "):</b>\n";
    foreach ($alive as $p) $msg .= "• " . $p['name'] . "\n";
    $msg .= "\n🗣️ <b>زمان بحث!</b>\n⏱ " . $game['day_duration'] . " ثانیه وقت دارید.\nبعدش رأی‌گیری شروع می‌شه.";
    
    sendMessage($game['group_id'], $msg);
}

function startVoting($game) {
    $alivePlayers = getAlivePlayers($game);
    if (count($alivePlayers) < 2) {
        endGame($game);
        return;
    }
    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_end_time'] = time() + $game['vote_duration'];
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) { $games[$code] = $game; break; }
    }
    saveGames($games);
    sendMessage($game['group_id'], "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "!</b>\n⏱ {$game['vote_duration']} ثانیه وقت دارید.");
    foreach ($alivePlayers as $p) { sendVotePanel($p, $game); }
}

function sendVotePanel($player, $game) {
    $alivePlayers = array_filter(getAlivePlayers($game), function($p) use ($player) { return $p['id'] != $player['id']; });
    if (empty($alivePlayers)) {
        sendPrivateMessage($player['id'], "❌ هیچ بازیکن زنده دیگری برای رأی دادن وجود ندارد!");
        return;
    }
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n👇 یک نفر رو برای اعدام انتخاب کن:";
    $keyboard = [];
    $row = [];
    foreach ($alivePlayers as $p) {
        $row[] = ['text' => $p['name'], 'callback_data' => 'vote_' . $p['id']];
        if (count($row) == 2) { $keyboard[] = $row; $row = []; }
    }
    if (!empty($row)) $keyboard[] = $row;
    $keyboard[] = [['text' => '⚪ رأی سفید', 'callback_data' => 'vote_skip']];
    
    // ارسال کیبورد
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processVotes($game_code, $game) {
    $votes = $game['votes'] ?? [];
    $alivePlayers = getAlivePlayers($game);
    $afk_players = [];
    foreach ($alivePlayers as $p) {
        if (!isset($votes[$p['id']])) {
            foreach ($game['players'] as &$player) {
                if ($player['id'] == $p['id']) {
                    $player['afk_count'] = ($player['afk_count'] ?? 0) + 1;
                    if ($player['afk_count'] >= 2) $afk_players[] = $player;
                    break;
                }
            }
        }
    }
    foreach ($afk_players as $afk) {
        $game = killPlayer($game, $afk['id'], 'afk');
        sendMessage($game['group_id'], "😴 <b>" . $afk['name'] . "</b> به خاطر غیرفعالی حذف شد!");
    }
    $counts = [];
    $skipCount = 0;
    foreach ($votes as $voter_id => $target_id) {
        if ($target_id == 'skip') $skipCount++;
        else $counts[$target_id] = ($counts[$target_id] ?? 0) + 1;
    }
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    $msg = "🗳️ <b>نتیجه رأی‌گیری روز " . $game['day_count'] . "</b>\n\n📊 آرا: " . count($votes) . " | سفید: $skipCount\n";
    if (!empty($afk_players)) $msg .= "💀 حذف شدگان: " . count($afk_players) . "\n";
    $msg .= "\n";
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
    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) { endGame($game, $winCheck); return; }
    $game['phase'] = 'night';
    $game['night_count'] = ($game['night_count'] ?? 0) + 1;
    $game['night_actions'] = [];
    $game['votes'] = [];
    $game['night_end_time'] = time() + $game['night_duration'];
    $game['day_end_time'] = 0;
    $game['vote_end_time'] = 0;
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
    foreach ($game['players'] as $p) {
        if ($p['alive'] ?? false) sendNightPanel($p, $game);
    }
}

// ============================================================
// 13. شرایط برد
// ============================================================

function checkWinCondition($game) {
    $alive = getAlivePlayers($game);
    $totalAlive = count($alive);
    if ($totalAlive == 0) return ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردند!'];
    $wolves = array_filter($alive, fn($p) => in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer']));
    $villagers = array_filter($alive, fn($p) => !in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer', 'serial_killer', 'vampire', 'bloodthirsty', 'cultist']));
    $cult = array_filter($alive, fn($p) => in_array($p['role'], ['cultist', 'royce', 'frankenstein', 'monk_black']));
    $killers = array_filter($alive, fn($p) => in_array($p['role'], ['serial_killer', 'archer', 'davina']));
    $vampires = array_filter($alive, fn($p) => in_array($p['role'], ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang']));
    if (count($wolves) == 0 && count($cult) == 0 && count($killers) == 0 && count($vampires) == 0) {
        return ['ended' => true, 'winner' => 'villager', 'message' => '👨‍🌾 روستایی‌ها برنده شدند!'];
    }
    if (count($wolves) > 0 && count($wolves) >= count($villagers) && count($cult) == 0 && count($killers) == 0 && count($vampires) == 0) {
        return ['ended' => true, 'winner' => 'werewolf', 'message' => '🐺 گرگ‌ها برنده شدند!'];
    }
    if (count($cult) > $totalAlive / 2) {
        return ['ended' => true, 'winner' => 'cult', 'message' => '👤 فرقه برنده شد!'];
    }
    if (count($killers) > 0 && count($wolves) == 0 && count($cult) == 0 && count($vampires) == 0) {
        if ($totalAlive <= 3 || count($killers) == $totalAlive) {
            return ['ended' => true, 'winner' => 'killer', 'message' => '🔪 قاتل‌ها برنده شدند!'];
        }
    }
    if (count($vampires) > 0 && count($wolves) == 0 && count($cult) == 0 && count($killers) == 0) {
        if (count($vampires) >= count($villagers)) {
            return ['ended' => true, 'winner' => 'vampire', 'message' => '🧛 ومپایرها برنده شدند!'];
        }
    }
    return ['ended' => false];
}

function endGame($game, $winCheck) {
    $game['status'] = 'ended';
    $game['ended'] = time();
    $game['winners'] = $winCheck['winner'];
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) { $games[$code] = $game; break; }
    }
    saveGames($games);
    $msg = "🏁 <b>بازی تمام شد!</b>\n\n" . $winCheck['message'] . "\n\n📊 <b>نقش‌ها:</b>\n";
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? false) ? '🟢' : '💀';
        $msg .= "$status {$p['name']} - " . getRoleDisplayName($p['role']) . "\n";
    }
    sendMessage($game['group_id'], $msg);
    addXPAfterGame($game);
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
// 14. چک کردن تایمرها
// ============================================================

function checkGameTimers() {
    $games = loadGames();
    $now = time();
    foreach ($games as $code => $game) {
        
        if ($game['status'] == 'waiting' && isset($game['wait_until']) && $now >= $game['wait_until']) {
            if (count($game['players']) < 4) {
                unset($games[$code]);
                saveGames($games);
                sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! اما تعداد بازیکنان به ۴ نفر نرسید. \n❌ <b>بازی لغو شد!</b>");
                continue;
            } else {
                $result = forceStartGame($game['group_id'], $game['creator_id']);
                if ($result['success']) {
                    sendMessage($game['group_id'], "⏰ زمان انتظار تمام شد! تعداد به ۴ نفر رسید. بازی شروع می‌شود...");
                }
                continue;
            }
        }
        
        if ($game['status'] != 'started') continue;
        
        if ($game['phase'] == 'night' && isset($game['night_end_time']) && $now >= $game['night_end_time']) {
            foreach ($game['players'] as $p) {
                if (!($p['alive'] ?? false)) continue;
                $has_action = false;
                foreach ($game['night_actions'] as $action) {
                    if ($action['player'] == $p['id']) { $has_action = true; break; }
                }
                if (!$has_action) {
                    $game['night_actions'][] = ['player' => $p['id'], 'role' => $p['role'], 'target' => 'skip'];
                    sendPrivateMessage($p['id'], "⏰ زمان شب تمام شد! شما اسکیپ شدید.");
                }
            }
            processNight($code, $game);
            sendMessage($game['group_id'], "⏰ شب به پایان رسید! صبح شد...");
            return;
        }
        
        if ($game['phase'] == 'day' && isset($game['day_end_time']) && $now >= $game['day_end_time']) {
            startVoting($game);
            sendMessage($game['group_id'], "⏰ زمان بحث تمام شد! رأی‌گیری شروع می‌شود...");
            return;
        }
        
        if ($game['phase'] == 'vote' && isset($game['vote_end_time']) && $now >= $game['vote_end_time']) {
            processVotes($code, $game);
            return;
        }
    }
}

// ============================================================
// 15. توابع ارسال پیام
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
           "👮🏻‍♂️ کلانتر - ومپایر اصیل رو زندانی کرده\n" .
           "🧛🏻‍♀️ ومپایر اصیل - زندانی کلانتر\n" .
           "💂🏻‍♂️ شکارچی - فرقه‌ها رو شکار می‌کند\n" .
           "👤 فرقه‌گرا - هر شب یک نفر رو دعوت می‌کند\n" .
           "🔪 قاتل زنجیره‌ای - هر شب یک نفر رو می‌کشد\n" .
           "🪶 ققنوس - شب‌های ۳ و ۵ اشک می‌دهد\n" .
           "🔥🤴🏻 پادشاه آتش - نفت می‌پاشد و آتش می‌زند";
}

// ============================================================
// 16. پردازش اصلی
// ============================================================

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
        $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
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
    
    if (strpos($data, 'force_start_') === 0) {
        $code = substr($data, 12);
        $result = forceStartGame($chat_id, $user_id);
        answerCallbackQuery($callback_id, $result['message'], true);
        if ($result['success']) {
            sendMessage($chat_id, $result['message']);
        }
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    if (strpos($data, 'night_') === 0) {
        $parts = explode('_', $data);
        $action = $parts[1] ?? '';
        $role = $parts[2] ?? '';
        $target = $parts[3] ?? '';
        
        $games = loadGames();
        $game = null;
        $game_code = null;
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && $g['status'] == 'started' && $g['phase'] == 'night') {
                    $game = $g;
                    $game_code = $code;
                    break 2;
                }
            }
        }
        if (!$game) {
            answerCallbackQuery($callback_id, "❌ بازی پیدا نشد!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        if ($action == 'skip' || $action == 'skip_fireking') {
            $game['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => 'skip'];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "⏭️ این شب رو رد کردید!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        if ($action == 'fireking_oil') {
            $game['night_actions'][] = ['player' => $user_id, 'role' => 'fireking_oil', 'target' => (int)$target];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "🛢️ نفت پاشی شد!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        if ($action == 'fireking_detonate') {
            $game['night_actions'][] = ['player' => $user_id, 'role' => 'fireking_detonate', 'target' => 'all'];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "💥 آتش زدن!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        if (!empty($target)) {
            $game['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => (int)$target];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "✅ انتخاب شما ثبت شد!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
    }
    
    if (strpos($data, 'vote_') === 0) {
        $parts = explode('_', $data);
        $target = $parts[1] ?? '';
        $games = loadGames();
        $game = null;
        $game_code = null;
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && $g['status'] == 'started' && $g['phase'] == 'vote') {
                    $game = $g;
                    $game_code = $code;
                    break 2;
                }
            }
        }
        if (!$game) {
            answerCallbackQuery($callback_id, "❌ بازی پیدا نشد!", true);
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        $game['votes'][$user_id] = ($target == 'skip' ? 'skip' : (int)$target);
        $games[$game_code] = $game;
        saveGames($games);
        
        // این خط تغییر کرده است تا نام شخص انتخاب شده را نشان دهد
        $target_name = ($target == 'skip') ? 'سفید' : getPlayerById($game, $target)['name'];
        answerCallbackQuery($callback_id, "✅ رأی شما به «$target_name» ثبت شد!", false);
        
        $alive = getAlivePlayers($game);
        if (count($game['votes']) >= count($alive)) processVotes($game_code, $game);
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    $response = "";
    switch ($data) {
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و /game را بزنید."; break;
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

if (substr($text, 0, 1) !== '/') {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

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
    
    case '/starteasy':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'easy');
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
    
    case '/startmafia':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mafia');
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
    
    case '/removelink':
        $result = removeGroupLink($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
        break;
    
    case '/setlang':
        if ($param != 'fa' && $param != 'en') {
            sendMessage($chat_id, "❌ زبان نامعتبر!\nگزینه‌ها: fa, en");
        } else {
            setGroupSetting($chat_id, 'lang', $param);
            sendMessage($chat_id, "✅ زبان به <b>$param</b> تغییر کرد!");
        }
        break;
    
    case '/setmode':
        $modes = ['normal', 'easy', 'mafia', 'vampire', 'werewolf', 'bomber', 'foolish', 'mighty', 'romantic', 'coin'];
        if (empty($param) || !in_array($param, $modes)) {
            sendMessage($chat_id, "❌ حالت نامعتبر!\nحالت‌های موجود: " . implode(', ', $modes));
        } else {
            setGroupSetting($chat_id, 'game_mode', $param);
            sendMessage($chat_id, "✅ حالت بازی به <b>$param</b> تغییر کرد!");
        }
        break;
    
    case '/setnight':
        if (!is_numeric($param) || $param < 10) {
            sendMessage($chat_id, "❌ زمان باید عددی بیشتر از ۱۰ باشد!");
        } else {
            setGroupSetting($chat_id, 'night_time', (int)$param);
            sendMessage($chat_id, "✅ زمان شب به <b>$param</b> ثانیه تغییر کرد!");
        }
        break;
    
    case '/setday':
        if (!is_numeric($param) || $param < 10) {
            sendMessage($chat_id, "❌ زمان باید عددی بیشتر از ۱۰ باشد!");
        } else {
            setGroupSetting($chat_id, 'day_time', (int)$param);
            sendMessage($chat_id, "✅ زمان روز به <b>$param</b> ثانیه تغییر کرد!");
        }
        break;
    
    case '/setvote':
        if (!is_numeric($param) || $param < 10) {
            sendMessage($chat_id, "❌ زمان باید عددی بیشتر از ۱۰ باشد!");
        } else {
            setGroupSetting($chat_id, 'vote_time', (int)$param);
            sendMessage($chat_id, "✅ زمان رأی به <b>$param</b> ثانیه تغییر کرد!");
        }
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
    
    case '/shop':
        $items = getShopItems();
        $msg = "🛍️ <b>فروشگاه</b>\n\n";
        foreach ($items as $item) {
            $price_text = $item['price'] == 0 ? 'رایگان' : $item['price'] . ' سکه';
            $msg .= "{$item['name']} - $price_text\n";
            $msg .= "/buy {$item['id']}\n\n";
        }
        sendMessage($chat_id, $msg);
        break;
    
    case '/buy':
        if (empty($param)) {
            sendMessage($chat_id, "❌ آیتم را وارد کنید!\nاز /shop برای مشاهده آیتم‌ها استفاده کنید.");
        } else {
            $result = buyItem($user_id, $param);
            sendMessage($chat_id, $result['message']);
        }
        break;
    
    case '/report':
        $parts = explode(' ', $text);
        if (count($parts) < 3) {
            sendMessage($chat_id, "❌ استفاده صحیح:\n/report [آیدی کاربر] [دلیل]");
        } else {
            $target_id = (int)$parts[1];
            $reason = implode(' ', array_slice($parts, 2));
            addReport($user_id, $target_id, $reason);
            sendMessage($chat_id, "✅ گزارش شما ثبت شد! ادمین بررسی خواهد کرد.");
        }
        break;
    
    case '/score':
        $score = getScore($user_id);
        sendMessage($chat_id, "📊 <b>امتیاز شما:</b> $score");
        break;
    
    case '/help':
        $msg = "📚 <b>راهنمای ربات</b>\n\n" .
               "/start - منوی اصلی\n" .
               "/game - ساخت بازی (گروه)\n" .
               "/startvampire - شروع بازی ومپایری\n" .
               "/startwerewolf - شروع بازی گرگینه\n" .
               "/startbomber - شروع بازی بمب‌گذار\n" .
               "/starteasy - شروع بازی آسان\n" .
               "/startfoolish - شروع بازی احمقانه\n" .
               "/startmafia - شروع بازی مافیا\n" .
               "/startmighty - شروع بازی قدرتمند\n" .
               "/startromantic - شروع بازی عاشقانه\n" .
               "/startcoin - شروع بازی سکه‌ای\n" .
               "/join [کد] - پیوستن\n" .
               "/players - لیست بازیکنان\n" .
               "/startgame - شروع بازی\n" .
               "/stop - لغو بازی\n" .
               "/leave - خروج از بازی\n" .
               "/extend - تمدید زمان (ادمین)\n" .
               "/timing - تنظیم تایم (ادمین)\n" .
               "/setlink - تنظیم لینک گروه\n" .
               "/removelink - حذف لینک گروه\n" .
               "/setlang - تغییر زبان گروه\n" .
               "/setmode - تغییر حالت بازی\n" .
               "/setnight - تغییر زمان شب\n" .
               "/setday - تغییر زمان روز\n" .
               "/setvote - تغییر زمان رأی\n" .
               "/rules - قوانین\n" .
               "/roles - نقش‌ها\n" .
               "/myrank - درجه من\n" .
               "/coin - سکه من\n" .
               "/shop - فروشگاه\n" .
               "/report - گزارش کاربر\n" .
               "/score - امتیاز من\n" .
               "/ping - تست اتصال";
        sendMessage($chat_id, $msg);
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
?>
