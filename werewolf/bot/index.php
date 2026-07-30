<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// یک پیام تست به خودتان (ادمین) می‌فرستد تا مطمئن شوید ربات روشن است
$admin_id = 1095925103;
$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$url = "https://api.telegram.org/bot$token/sendMessage";
$data = ['chat_id' => $admin_id, 'text' => "✅ ربات با موفقیت روی سرور روشن شد! ساعت: " . date('H:i:s')];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
curl_close($ch);
// ============================================================
// 1. تنظیمات اولیه و بارگذاری فایل‌ها
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

// بارگذاری فایل زبان (مهم برای جلوگیری از ارور)
if (file_exists(__DIR__ . '/lang.php')) {
    require_once __DIR__ . '/lang.php';
} else {
    // اگر فایل lang پیدا نشد، یک آرایه خالی تعریف کن تا کد کرش نکند
    $lang = [];
}

// بارگذاری نقش‌ها
$roles_path = __DIR__ . '/ROLES_PATCH/';
if (is_dir($roles_path) && file_exists($roles_path . 'factory.php')) {
    require_once $roles_path . 'factory.php';
    require_once $roles_path . 'base.php';
}

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
// 3. سیستم سکه
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
// 5. سیستم گزارش
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
// 6. سیستم تنظیمات گروه
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
// 7. بالانس نقش‌ها
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    if ($count <= 4) {
        $roles = ['villager', 'villager', 'werewolf', 'seer'];
        shuffle($roles);
        return $roles;
    }
    if ($count <= 6) {
        $roles = array_merge(array_fill(0, $count - 3, 'villager'), ['werewolf', 'werewolf'], ['seer']);
        shuffle($roles);
        return $roles;
    }
    if ($count <= 8) {
        $roles = array_merge(array_fill(0, $count - 4, 'villager'), ['werewolf', 'werewolf'], ['seer', 'guardian_angel']);
        shuffle($roles);
        return $roles;
    }
    if ($count <= 10) {
        $roles = array_merge(array_fill(0, $count - 5, 'villager'), ['werewolf', 'werewolf'], ['seer', 'guardian_angel', 'hunter']);
        shuffle($roles);
        return $roles;
    }
    if ($count <= 14) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective'];
        $roles = array_merge(array_fill(0, $count - 6, 'villager'), ['werewolf', 'werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    if ($count <= 18) {
        $special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight'];
        $roles = array_merge(array_fill(0, $count - 7, 'villager'), ['werewolf', 'werewolf', 'werewolf'], $special);
        shuffle($roles);
        return $roles;
    }
    $wolf_count = round($count * 0.2);
    $special_count = round($count * 0.15);
    $available_special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble'];
    shuffle($available_special);
    $special = array_slice($available_special, 0, $special_count);
    $villager_count = $count - $wolf_count - count($special);
    if ($villager_count < 2) $villager_count = 2;
    $roles = array_merge(array_fill(0, $villager_count, 'villager'), array_fill(0, $wolf_count, 'werewolf'), $special);
    shuffle($roles);
    return $roles;
}

// ============================================================
// 8. توابع اصلی بازی (با تغییرات درخواستی)
// ============================================================

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
        'mode' => $mode, 'mode_name' => $mode_name,
        'players' => [['id' => $creator_id, 'name' => $creator_name, 'alive' => true, 'role' => null]],
        'status' => 'waiting', 'created' => time(), 'wait_until' => time() + 300, 'extend_count' => 0,
        'phase' => null, 'night_count' => 0, 'day_count' => 0, 'night_actions' => [], 'votes' => [],
        'night_end_time' => 0, 'day_end_time' => 0, 'vote_end_time' => 0,
        'day_duration' => 60, 'night_duration' => 60, 'vote_duration' => 60, 'afk_counts' => []
    ];
    saveGames($games);
    
    $remaining = 300;
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    $msg = "🎮 <b>بازی {$mode_name} ساخته شد!</b>\n\n🎲 کد: <code>$code</code>\n👤 سازنده: $creator_name\n👥 تعداد: ۱ نفر\n\n⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds) . "\n\n👇 برای ورود به بازی روی دکمه زیر کلیک کن:";
    
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
    if (!isset($games[$code])) return ['success' => false, 'message' => '❌ بازی با این کد پیدا نشد!'];
    $game = $games[$code];
    if ($game['status'] != 'waiting') return ['success' => false, 'message' => '⏳ این بازی قبلاً شروع شده!'];
    if (time() > $game['wait_until']) return ['success' => false, 'message' => '⏰ زمان انتظار تمام شده!'];
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
    }
    $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
    $games[$code] = $game;
    saveGames($games);
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!", 'game' => $game];
}

function forceStartGame($group_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && $game['status'] == 'waiting') {
            if (!isAdmin($user_id, $group_id)) {
                return ['success' => false, 'message' => '❌ فقط ادمین‌های گروه می‌توانند زودتر شروع کنند!'];
            }
            if (count($game['players']) < 4) {
                return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
            }
            $roles = selectBalancedRoles(count($game['players']));
            shuffle($roles);
            foreach ($game['players'] as $i => &$p) {
                $p['role'] = $roles[$i];
                $p['afk_count'] = 0;
            }
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
                $role_desc = getRoleDescription($p['role']);
                sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n" . $role_desc . "\n\n🌙 شب اول شروع شد...");
                sendNightPanel($p, $game);
            }
            sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
            return ['success' => true, 'message' => '⚡ بازی توسط ادمین زودتر از موعد شروع شد!'];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای شروع وجود ندارد!'];
}

// ============================================================
// 9. سیستم رای‌گیری، پردازش شب و روز و چک کردن تایمرها
// ============================================================

function getRoleDisplayName($role) {
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getEmoji() . ' ' . $role_obj->getName();
    }
    $names = ['villager' => '👨‍🌾 روستایی ساده', 'seer' => '👳🏻‍♂️ پیشگو', 'werewolf' => '🐺 گرگینه', 'guardian_angel' => '👼🏻 فرشته نگهبان', 'hunter' => '👮🏻‍♂️ کلانتر', 'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل', 'cultist' => '👤 فرقه‌گرا', 'cult_hunter' => '💂🏻‍♂️ شکارچی', 'serial_killer' => '🔪 قاتل زنجیره‌ای', 'archer' => '🏹 کماندار', 'vampire' => '🧛🏻‍♂️ ومپایر', 'phoenix' => '🪶 ققنوس', 'fire_king' => '🔥🤴🏻 پادشاه آتش', 'ice_queen' => '❄️👸🏻 ملکه یخی'];
    return $names[$role] ?? '❓ ' . $role;
}


// ... (بقیه توابع getRoleActionDescription, sendNightPanel, getValidNightTargets, processNight, startVoting, checkWinCondition, endGame, getRules, sendMessage, sendPrivateMessage, answerCallbackQuery و ... دقیقاً مثل کد اصلی شما باقی می‌مانند. برای جلوگیری از طولانی شدن بیش از حد، این بخش‌ها حذف شدند اما در کد اصلی شما هستند) ...

// ============================================================
// 10. پردازش اصلی Webhook
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

// چک کردن تایمرها
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
            exit;
        }
        $game = $games[$code];
        if ($game['status'] != 'waiting') {
            answerCallbackQuery($callback_id, "⏳ این بازی قبلاً شروع شده!", true);
            exit;
        }
        if (time() > $game['wait_until']) {
            answerCallbackQuery($callback_id, "⏰ زمان انتظار تمام شده!", true);
            exit;
        }
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                answerCallbackQuery($callback_id, "❌ شما قبلاً در این بازی هستید!", true);
                exit;
            }
        }
        $user_name = $callback['from']['first_name'] ?? 'کاربر';
        $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
        $games[$code] = $game;
        saveGames($games);
        answerCallbackQuery($callback_id, "✅ به بازی پیوستید!", false);
        sendPrivateMessage($user_id, "✅ شما به بازی با کد <code>$code</code> پیوستید!\n👥 تعداد: " . count($game['players']) . " نفر");
        sendMessage($chat_id, "✅ <b>$user_name</b> به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر");
        // ارسال لیست بازیکنان
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
    
    // ... (بقیه پردازش‌های callback برای night_ و vote_ و ...)
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

// ============================================================
// 11. پردازش دستورات
// ============================================================

switch ($command) {
    case '/start':
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'], ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']],
            [['text' => '📜 قوانین', 'callback_data' => 'rules'], ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']],
            [['text' => '❓ راهنما', 'callback_data' => 'help'], ['text' => '📊 آمار', 'callback_data' => 'stats']]
        ]];
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
    
    // ... (بقیه دستورات /players, /stop, /leave, /extend, /timing, /setlink, /removelink, /setlang, /setmode, /setnight, /setday, /setvote, /rules, /roles, /myrank, /coin, /shop, /buy, /report, /score, /help, /ping و ... دقیقاً مثل کد اصلی شما)
    
    default:
        sendMessage($chat_id, "❌ دستور نامشخص!\nبرای راهنما /help را بزنید.");
        break;
}

http_response_code(200);
echo '{"ok":true}';
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - END\n", FILE_APPEND);
?>
