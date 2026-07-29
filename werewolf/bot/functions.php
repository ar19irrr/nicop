<?php
// functions.php - ساده و بدون خطا

function logMessage($msg) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - [FUNCTIONS] " . $msg . "\n", FILE_APPEND);
}

logMessage("functions.php loaded");

// ===== توابع کمکی =====

function sendMessage($chat_id, $text, $keyboard = null, $parse_mode = 'HTML') {
    $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => $parse_mode];
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
    
    logMessage("sendMessage result: " . substr($result, 0, 100));
    return json_decode($result, true);
}

function sendPrivateMessage($user_id, $text, $keyboard = null) {
    return sendMessage($user_id, $text, $keyboard);
}

function answerCallbackQuery($callback_id, $text, $show_alert = false) {
    $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
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

function apiRequest($url, $data = []) {
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

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function mentionUser($user_id, $name) {
    return "<a href='tg://user?id={$user_id}'>" . htmlspecialchars($name) . "</a>";
}

function playerCountText($count) {
    return $count . " " . ($count == 1 ? "نفر" : "نفر");
}

function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return "همین الان";
    if ($diff < 3600) return floor($diff / 60) . " دقیقه پیش";
    if ($diff < 86400) return floor($diff / 3600) . " ساعت پیش";
    return floor($diff / 86400) . " روز پیش";
}

function formatTime($seconds) {
    if ($seconds < 60) return $seconds . " ثانیه";
    if ($seconds < 3600) return floor($seconds / 60) . " دقیقه";
    if ($seconds < 86400) return floor($seconds / 3600) . " ساعت";
    return floor($seconds / 86400) . " روز";
}

function getStatusText($status) {
    $map = ['waiting' => '⏳ در انتظار', 'started' => '▶️ در حال اجرا', 'ended' => '🏁 تمام شده'];
    return $map[$status] ?? $status;
}

function getPhaseText($phase) {
    $map = ['night' => '🌙 شب', 'day' => '☀️ روز', 'vote' => '🗳️ رأی', 'discussion' => '🗣️ بحث'];
    return $map[$phase] ?? $phase;
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
        'joker' => '🤡',
        'tanner' => '👺'
    ];
    return $icons[$role] ?? '❓';
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
        'joker' => 'جوکر',
        'tanner' => 'منافق'
    ];
    return $names[$role] ?? $role;
}

function getRoleDisplayName($role) {
    return getRoleIcon($role) . ' ' . getRoleName($role);
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
        'joker' => "🤡 شما جوکر هستید!\nسعی کنید اعدام شوید تا برنده شوید!",
        'tanner' => "👺 شما منافق هستید!\nباید اعدام شوید تا برنده شوید!"
    ];
    return $desc[$role] ?? "🎭 شما " . getRoleName($role) . " هستید!";
}

function detectTeam($role) {
    $teams = [
        'villager' => ['villager', 'seer', 'guardian_angel', 'hunter'],
        'werewolf' => ['werewolf'],
        'vampire' => ['vampire'],
        'killer' => ['serial_killer'],
        'joker' => ['joker'],
        'independent' => ['tanner']
    ];
    foreach ($teams as $team => $roles) {
        if (in_array($role, $roles)) return $team;
    }
    return 'unknown';
}

function getTeamIcon($team) {
    $icons = [
        'villager' => '🏘️',
        'werewolf' => '🐺',
        'vampire' => '🧛',
        'killer' => '🔪',
        'joker' => '🤡',
        'independent' => '⚡',
        'unknown' => '❓'
    ];
    return $icons[$team] ?? '❓';
}

function buildInlineKeyboard($buttons, $columns = 2) {
    $keyboard = array_chunk($buttons, $columns);
    return ['inline_keyboard' => $keyboard];
}

function buildKeyboard($buttons, $columns = 2, $resize = true) {
    $keyboard = array_chunk($buttons, $columns);
    return ['keyboard' => $keyboard, 'resize_keyboard' => $resize, 'one_time_keyboard' => true];
}

function isAdmin($user_id, $chat_id) {
    return true;
}

function setWebhook($url) {
    $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
    $apiUrl = "https://api.telegram.org/bot$token/setWebhook";
    return apiRequest($apiUrl, ['url' => $url]);
}

function deleteWebhook() {
    $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
    $url = "https://api.telegram.org/bot$token/deleteWebhook";
    return apiRequest($url);
}
