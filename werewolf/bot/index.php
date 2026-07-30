<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// ============================================================
// 1. تنظیمات اولیه و بارگذاری فایل‌ها
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

// بارگذاری فایل زبان
if (file_exists(__DIR__ . '/lang.php')) {
    require_once __DIR__ . '/lang.php';
} else {
    $lang = [];
}

// بارگذاری نقش‌ها
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
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
        return [];
    }
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
// 3. سیستم‌های سکه، رتبه‌بندی و فروشگاه
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

function getRoleDisplayName($role) {
    if (class_exists('RoleFactory')) {
        $role_obj = RoleFactory::create($role, [], []);
        return $role_obj->getEmoji() . ' ' . $role_obj->getName();
    }
    $names = ['villager' => '👨‍🌾 روستایی ساده', 'seer' => '👳🏻‍♂️ پیشگو', 'werewolf' => '🐺 گرگینه', 'guardian_angel' => '👼🏻 فرشته نگهبان', 'hunter' => '👮🏻‍♂️ کلانتر', 'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل', 'cultist' => '👤 فرقه‌گرا', 'cult_hunter' => '💂🏻‍♂️ شکارچی', 'serial_killer' => '🔪 قاتل زنجیره‌ای', 'archer' => '🏹 کماندار', 'vampire' => '🧛🏻‍♂️ ومپایر', 'phoenix' => '🪶 ققنوس', 'fire_king' => '🔥🤴🏻 پادشاه آتش', 'ice_queen' => '❄️👸🏻 ملکه یخی'];
    return $names[$role] ?? '❓ ' . $role;
}

// ============================================================
// 4. توابع اصلی بازی و بالانس
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
        'mode' => $mode, 'mode_name' => $mode_name, 'players' => [['id' => $creator_id, 'name' => $creator_name, 'alive' => true, 'role' => null]],
        'status' => 'waiting', 'created' => time(), 'wait_until' => time() + 300, 'extend_count' => 0,
        'phase' => null, 'night_count' => 0, 'day_count' => 0, 'night_actions' => [], 'votes' => [],
        'night_end_time' => 0, 'day_end_time' => 0, 'vote_end_time' => 0,
        'day_duration' => 60, 'night_duration' => 60, 'vote_duration' => 60, 'afk_counts' => []
    ];
    saveGames($games);
    
    $remaining = 300; $minutes = floor($remaining / 60); $seconds = $remaining % 60;
    $msg = "🎮 <b>بازی {$mode_name} ساخته شد!</b>\n\n🎲 کد: <code>$code</code>\n👤 سازنده: $creator_name\n👥 تعداد: ۱ نفر\n\n⏱ زمان باقیمانده: $minutes:" . sprintf("%02d", $seconds) . "\n\n👇 برای ورود به بازی روی دکمه زیر کلیک کن:";
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '🎯 ورود به روستا', 'callback_data' => 'join_' . $code]],
        [['text' => '⚡ شروع زودهنگام (فقط ادمین)', 'callback_data' => 'force_start_' . $code]]
    ]];
    
    sendMessage($group_id, $msg, $keyboard);
    return ['success' => true, 'message' => $msg, 'code' => $code];
}

function forceStartGame($group_id, $user_id) {
    $games = loadGames();
    foreach ($games as $code => $game) {
        if (isset($game['group_id']) && $game['group_id'] == $group_id && $game['status'] == 'waiting') {
            if (!isAdmin($user_id, $group_id)) return ['success' => false, 'message' => '❌ فقط ادمین‌ها می‌توانند زودتر شروع کنند!'];
            if (count($game['players']) < 4) return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
            
            $roles = selectBalancedRoles(count($game['players']));
            shuffle($roles);
            foreach ($game['players'] as $i => &$p) { $p['role'] = $roles[$i]; $p['afk_count'] = 0; }
            $game['status'] = 'started'; $game['phase'] = 'night'; $game['night_count'] = 1;
            $game['night_actions'] = []; $game['votes'] = []; $game['night_end_time'] = time() + $game['night_duration'];
            $games[$code] = $game; saveGames($games);
            
            foreach ($game['players'] as $p) {
                $role_name = getRoleDisplayName($p['role']);
                sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n🌙 شب اول شروع شد...");
                sendNightPanel($p, $game);
            }
            sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ {$game['night_duration']} ثانیه تا صبح");
            return ['success' => true, 'message' => '⚡ بازی توسط ادمین زودتر شروع شد!'];
        }
    }
    return ['success' => false, 'message' => '❌ بازی فعالی برای شروع وجود ندارد!'];
}

// ============================================================
// 5. توابع ارسال پیام
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
// 6. پردازش اصلی وب‌هوک
// ============================================================

$json = file_get_contents('php://input');
if (empty($json)) { http_response_code(200); echo '{"ok":true}'; exit; }
$update = json_decode($json, true);
if (!$update) { http_response_code(200); echo '{"ok":true}'; exit; }

if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    if (strpos($data, 'join_') === 0) {
        $code = substr($data, 5);
        $games = loadGames();
        if (!isset($games[$code])) { answerCallbackQuery($callback_id, "❌ بازی با این کد پیدا نشد!", true); exit; }
        $game = $games[$code];
        if ($game['status'] != 'waiting') { answerCallbackQuery($callback_id, "⏳ این بازی قبلاً شروع شده!", true); exit; }
        if (time() > $game['wait_until']) { answerCallbackQuery($callback_id, "⏰ زمان انتظار تمام شده!", true); exit; }
        foreach ($game['players'] as $p) { if ($p['id'] == $user_id) { answerCallbackQuery($callback_id, "❌ شما قبلاً در این بازی هستید!", true); exit; } }
        $user_name = $callback['from']['first_name'] ?? 'کاربر';
        $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
        $games[$code] = $game; saveGames($games);
        answerCallbackQuery($callback_id, "✅ به بازی پیوستید!", false);
        sendPrivateMessage($user_id, "✅ شما به بازی با کد <code>$code</code> پیوستید!\n👥 تعداد: " . count($game['players']) . " نفر");
        sendMessage($chat_id, "✅ <b>$user_name</b> به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر");
        http_response_code(200); echo '{"ok":true}'; exit;
    }
    
    if (strpos($data, 'force_start_') === 0) {
        $code = substr($data, 12);
        $result = forceStartGame($chat_id, $user_id);
        answerCallbackQuery($callback_id, $result['message'], true);
        if ($result['success']) sendMessage($chat_id, $result['message']);
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
$param = $parts[1] ?? '';

if ($command == '/start' && $chat_type == 'private') {
    $keyboard = ['inline_keyboard' => [
        [['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'], ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']],
        [['text' => '📜 قوانین', 'callback_data' => 'rules'], ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']],
        [['text' => '❓ راهنما', 'callback_data' => 'help'], ['text' => '📊 آمار', 'callback_data' => 'stats']]
    ]];
    sendMessage($chat_id, "👋 سلام <b>$first_name</b>!\n🐺 به ربات گرگینه خوش اومدی!", $keyboard);
    http_response_code(200); echo '{"ok":true}'; exit;
}

if ($command == '/game' || $command == '/startgame') {
    if ($chat_type == 'private') { sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!"); }
    else {
        $result = createGame($chat_id, $user_id, $first_name, 'normal');
        if (!$result['success']) sendMessage($chat_id, $result['message']);
    }
    http_response_code(200); echo '{"ok":true}'; exit;
}

http_response_code(200);
echo '{"ok":true}';
?>
