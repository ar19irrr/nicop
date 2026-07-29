<?php
/**
 * 🛠️ توابع کمکی
 */

require_once 'config.php';

// ==================== توابع API تلگرام ====================

/**
 * 📨 ارسال پیام به تلگرام
 */
function sendMessage($chat_id, $text, $keyboard = null, $parse_mode = 'HTML') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode
    ];

    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }

    return apiRequest($url, $data);
}

/**
 * ✏️ ویرایش پیام
 */
function editMessageText($chat_id, $message_id, $text, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";

    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }

    return apiRequest($url, $data);
}

/**
 * 🗑️ حذف پیام
 */
function deleteMessage($chat_id, $message_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    return apiRequest($url, [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ]);
}

/**
 * 📨 ارسال پیام خصوصی
 */
function sendPrivateMessage($user_id, $text, $keyboard = null) {
    return sendMessage($user_id, $text, $keyboard);
}

/**
 * 🔔 پاسخ به Callback
 */
function answerCallbackQuery($callback_id, $text, $show_alert = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";

    $data = [
        'callback_query_id' => $callback_id,
        'text' => $text,
        'show_alert' => $show_alert
    ];

    return apiRequest($url, $data);
}

/**
 * 🌐 درخواست به API تلگرام
 */
function apiRequest($url, $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("cURL Error: " . $error);
        return false;
    }

    return json_decode($result, true);
}

/**
 * 🔗 ست کردن Webhook
 */
function setWebhook($url) {
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook";
    return apiRequest($apiUrl, ['url' => $url]);
}

/**
 * ❌ حذف Webhook
 */
function deleteWebhook() {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook";
    return apiRequest($url);
}

/**
 * 📊 دریافت اطلاعات Webhook
 */
function getWebhookInfo() {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo";
    return apiRequest($url);
}

// ==================== توابع کمکی عمومی ====================

/**
 * 🎲 ساخت کد تصادفی ۶ رقمی
 */
function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * 👤 منشن کردن کاربر
 */
function mentionUser($user_id, $name) {
    return "<a href='tg://user?id={$user_id}'>" . htmlspecialchars($name) . "</a>";
}

/**
 * 📊 تعداد بازیکنان به فارسی
 */
function playerCountText($count) {
    return $count . " " . ($count == 1 ? "نفر" : "نفر");
}

/**
 * ⏰ زمان به فارسی (نسبی)
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;

    if ($diff < 60) return "همین الان";
    if ($diff < 3600) return floor($diff / 60) . " دقیقه پیش";
    if ($diff < 86400) return floor($diff / 3600) . " ساعت پیش";
    if ($diff < 604800) return floor($diff / 86400) . " روز پیش";
    return floor($diff / 604800) . " هفته پیش";
}

/**
 * ⏱ فرمت زمان (ثانیه به دقیقه:ثانیه)
 */
function formatTime($seconds) {
    if ($seconds < 60) return $seconds . " ثانیه";
    if ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . ":" . sprintf("%02d", $secs) . " دقیقه";
    }
    if ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . " ساعت " . $minutes . " دقیقه";
    }
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    return $days . " روز " . $hours . " ساعت";
}

/**
 * 🔍 بررسی ادمین بودن کاربر در گروه
 */
function isAdmin($user_id, $chat_id) {
    // ادمین اصلی
    if ($user_id == ADMIN_ID) return true;
    
    // چک کردن با API تلگرام
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getChatMember";
    $result = apiRequest($url, [
        'chat_id' => $chat_id,
        'user_id' => $user_id
    ]);
    
    if ($result && isset($result['ok']) && $result['ok']) {
        $status = $result['result']['status'] ?? 'member';
        return in_array($status, ['creator', 'administrator']);
    }
    
    return false;
}

// ==================== توابع نقش‌ها ====================

/**
 * 🎭 آیکون نقش
 */
function getRoleIcon($role) {
    $icons = [
        // ===== روستا =====
        'villager' => '👨‍🌾',
        'seer' => '👳🏻‍♂️',
        'apprentice_seer' => '🙇🏻‍♂️',
        'guardian_angel' => '👼🏻',
        'knight' => '🗡',
        'hunter' => '👮🏻‍♂️',
        'harlot' => '💋',
        'builder' => '👷🏻‍♂️',
        'blacksmith' => '⚒',
        'gunner' => '🔫',
        'mayor' => '🎖',
        'prince' => '🤴🏻',
        'detective' => '🕵🏻‍♂️',
        'cupid' => '💘',
        'beholder' => '👁',
        'phoenix' => '🪶',
        'huntsman' => '🪓',
        'trouble' => '👩🏻‍🌾',
        'chemist' => '👨‍🔬',
        'fool' => '🃏',
        'clumsy' => '🤕',
        'cursed' => '😾',
        'traitor' => '🖕🏿',
        'wild_child' => '👶🏻',
        'wise_elder' => '📚',
        'sandman' => '💤',
        'sweetheart' => '👰🏻',
        'ruler' => '👑',
        'spy' => '🦹🏻‍♂️',
        'marouf' => '🛡️🌿',
        'cult_hunter' => '💂🏻‍♂️',
        'hamal' => '🛒',
        'jumong' => '🏹⚔️',
        'princess' => '👸🏻',
        'wolf_man' => '🌑👨🏻',
        'drunk' => '🍻',
        
        // ===== گرگ =====
        'werewolf' => '🐺',
        'alpha_wolf' => '⚡️🐺',
        'wolf_cub' => '🐶',
        'lycan' => '🌝🐺',
        'forest_queen' => '🧝🏻‍♀️🐺',
        'white_wolf' => '🌩🐺',
        'beta_wolf' => '💤🐺',
        'ice_wolf' => '☃️🐺',
        'enchanter' => '🧙🏻‍♂️',
        'honey' => '🧙🏻‍♀️',
        'sorcerer' => '🔮',
        
        // ===== ومپایر =====
        'vampire' => '🧛🏻‍♂️',
        'bloodthirsty' => '🧛🏻‍♀️',
        'kent_vampire' => '💍🧛🏻',
        'chiang' => '👩‍🦳',
        
        // ===== قاتل =====
        'serial_killer' => '🔪',
        'archer' => '🏹',
        'davina' => '🍾',
        
        // ===== شوالیه تاریکی =====
        'black_knight' => '🥷🗡',
        'bride_dead' => '👰‍♀☠️',
        
        // ===== جوکر =====
        'joker' => '🤡',
        'harly' => '👩🏻‍🎤',
        
        // ===== آتش و یخ =====
        'fire_king' => '🔥🤴🏻',
        'ice_queen' => '❄️👸🏻',
        'lilith' => '🐍👩🏻‍🦳',
        'magento' => '🧲',
        
        // ===== فرقه =====
        'cultist' => '👤',
        'royce' => '🎩',
        'frankenstein' => '🧟‍♂️🪖',
        'monk_black' => '🦇',
        
        // ===== مستقل =====
        'dian' => '🧞‍♂️',
        'lucifer' => '😈',
        'dinamit' => '🧨',
        'bomber' => '💣',
        'tso' => '⚔️',
        'tanner' => '👺',
        'doppelganger' => '👯',
    ];
    return $icons[$role] ?? '❓';
}

/**
 * 🎭 نام نقش به فارسی
 */
function getRoleName($role) {
    $names = [
        // ===== روستا =====
        'villager' => 'روستایی ساده',
        'seer' => 'پیشگو',
        'apprentice_seer' => 'شاگرد پیشگو',
        'guardian_angel' => 'فرشته نگهبان',
        'knight' => 'شوالیه',
        'hunter' => 'کلانتر',
        'harlot' => 'ناتاشا',
        'builder' => 'بنا',
        'blacksmith' => 'آهنگر',
        'gunner' => 'تفنگدار',
        'mayor' => 'کدخدا',
        'prince' => 'شاهزاده',
        'detective' => 'کاراگاه',
        'cupid' => 'الهه عشق',
        'beholder' => 'شاهد',
        'phoenix' => 'ققنوس',
        'huntsman' => 'هانتسمن',
        'trouble' => 'دختر دردسرساز',
        'chemist' => 'شیمیدان',
        'fool' => 'احمق',
        'clumsy' => 'پسر گیج',
        'cursed' => 'نفرین شده',
        'traitor' => 'خائن',
        'wild_child' => 'بچه وحشی',
        'wise_elder' => 'ریش سفید',
        'sandman' => 'خوابگذار',
        'sweetheart' => 'دلبر',
        'ruler' => 'حاکم',
        'spy' => 'جاسوس',
        'marouf' => 'معروف',
        'cult_hunter' => 'شکارچی فرقه',
        'hamal' => 'حمال',
        'jumong' => 'جومونگ',
        'princess' => 'پرنسس',
        'wolf_man' => 'گرگنما',
        'drunk' => 'مست',
        
        // ===== گرگ =====
        'werewolf' => 'گرگینه',
        'alpha_wolf' => 'گرگ آلفا',
        'wolf_cub' => 'توله گرگ',
        'lycan' => 'گرگ ایکس',
        'forest_queen' => 'ملکه جنگل',
        'white_wolf' => 'گرگ سفید',
        'beta_wolf' => 'گرگ خوابالو',
        'ice_wolf' => 'گرگ برفی',
        'enchanter' => 'افسونگر',
        'honey' => 'عجوزه',
        'sorcerer' => 'جادوگر',
        
        // ===== ومپایر =====
        'vampire' => 'ومپایر',
        'bloodthirsty' => 'ومپایر اصیل',
        'kent_vampire' => 'کنت ومپایر',
        'chiang' => 'چیانگ',
        
        // ===== قاتل =====
        'serial_killer' => 'قاتل زنجیره‌ای',
        'archer' => 'کماندار',
        'davina' => 'داوینا',
        
        // ===== شوالیه تاریکی =====
        'black_knight' => 'شوالیه تاریکی',
        'bride_dead' => 'عروس مردگان',
        
        // ===== جوکر =====
        'joker' => 'جوکر',
        'harly' => 'هارلی کویین',
        
        // ===== آتش و یخ =====
        'fire_king' => 'پادشاه آتش',
        'ice_queen' => 'ملکه یخی',
        'lilith' => 'لیلیث',
        'magento' => 'مگنیتو',
        
        // ===== فرقه =====
        'cultist' => 'فرقه‌گرا',
        'royce' => 'رئیس فرقه',
        'frankenstein' => 'فرانکشتاین',
        'monk_black' => 'راهب سیاه',
        
        // ===== مستقل =====
        'dian' => 'دیان',
        'lucifer' => 'لوسیفر',
        'dinamit' => 'دینامیت',
        'bomber' => 'بمب‌گذار',
        'tso' => 'تسو',
        'tanner' => 'منافق',
        'doppelganger' => 'همزاد',
    ];
    return $names[$role] ?? $role;
}

/**
 * 🎭 نام نمایشی نقش (با آیکون)
 */
function getRoleDisplayName($role) {
    return getRoleIcon($role) . ' ' . getRoleName($role);
}

/**
 * 📝 توضیحات نقش
 */
function getRoleDescription($role) {
    $desc = [
        'werewolf' => "شما گرگینه هستید! 🐺\nهر شب یک نفر را می‌خورید.\nهدف: نابودی روستایی‌ها",
        'seer' => "شما پیشگو هستید! 👳🏻‍♂️\nهر شب هویت یک نفر را می‌بینید",
        'guardian_angel' => "شما فرشته نگهبان هستید! 👼🏻\nهر شب یک نفر را محافظت می‌کنید",
        'hunter' => "شما کلانتر هستید! 👮🏻‍♂️\nاگر بمیرید، یک نفر را با خود می‌برید",
        'villager' => "شما روستایی هستید! 👨‍🌾\nدر روز رأی دهید تا گرگینه‌ها را پیدا کنید",
        'serial_killer' => "شما قاتل زنجیره‌ای هستید! 🔪\nهر شب یک نفر را می‌کشید\nهدف: بقای آخرین نفر",
        'joker' => "شما جوکر هستید! 🤡\nباید اعدام شوید تا برنده شوید!",
    ];
    return $desc[$role] ?? 'نقش نامشخص';
}

/**
 * 🎯 ساخت کیبورد شیشه‌ای
 */
function buildInlineKeyboard($buttons, $columns = 2) {
    $keyboard = array_chunk($buttons, $columns);
    return ['inline_keyboard' => $keyboard];
}

/**
 * 🎯 ساخت کیبورد دکمه‌ای ساده
 */
function buildKeyboard($buttons, $columns = 2, $resize = true) {
    $keyboard = array_chunk($buttons, $columns);
    return [
        'keyboard' => $keyboard,
        'resize_keyboard' => $resize,
        'one_time_keyboard' => true
    ];
}

// ==================== توابع وضعیت ====================

/**
 * 📊 متن وضعیت بازی
 */
function getStatusText($status) {
    $map = [
        'waiting' => '⏳ در انتظار',
        'started' => '▶️ در حال اجرا',
        'ended' => '🏁 تمام شده'
    ];
    return $map[$status] ?? $status;
}

/**
 * 📊 متن فاز بازی
 */
function getPhaseText($phase) {
    $map = [
        'night' => '🌙 شب',
        'day' => '☀️ روز',
        'vote' => '🗳️ رأی‌گیری',
        'discussion' => '🗣️ بحث'
    ];
    return $map[$phase] ?? $phase;
}

/**
 * 🎯 تشخیص تیم از روی نقش
 */
function detectTeam($role) {
    $teams = [
        'villager' => ['villager', 'seer', 'apprentice_seer', 'guardian_angel', 'knight', 
                      'hunter', 'harlot', 'builder', 'blacksmith', 'gunner', 'mayor',
                      'prince', 'detective', 'cupid', 'beholder', 'phoenix', 'huntsman',
                      'trouble', 'chemist', 'fool', 'clumsy', 'cursed', 'traitor',
                      'wild_child', 'wise_elder', 'sandman', 'sweetheart', 'ruler',
                      'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong', 'princess',
                      'wolf_man', 'drunk'],
        
        'werewolf' => ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'],
        
        'vampire' => ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'],
        
        'cult' => ['cultist', 'royce', 'frankenstein', 'monk_black'],
        
        'killer' => ['serial_killer', 'archer', 'davina'],
        
        'fire_ice' => ['fire_king', 'ice_queen', 'lilith', 'magento'],
        
        'black_knight' => ['black_knight', 'bride_dead'],
        
        'joker' => ['joker', 'harly'],
        
        'independent' => ['dian', 'dinamit', 'bomber', 'tso', 'tanner', 'lucifer', 'doppelganger']
    ];
    
    foreach ($teams as $team => $roles) {
        if (in_array($role, $roles)) return $team;
    }
    return 'unknown';
}

/**
 * 🏷️ آیکون تیم
 */
function getTeamIcon($team) {
    $icons = [
        'villager' => '🏘️',
        'werewolf' => '🐺',
        'vampire' => '🧛',
        'cult' => '👤',
        'killer' => '🔪',
        'fire_ice' => '🔥❄️',
        'black_knight' => '🥷',
        'joker' => '🤡',
        'independent' => '⚡'
    ];
    return $icons[$team] ?? '❓';
}

// ==================== توابع لاگ ====================

/**
 * 📝 لاگ کردن پیام
 */
function logMessage($message, $level = 'info') {
    if (!DEBUG_MODE && $level != 'error') return;
    
    $logFile = BASE_PATH . 'logs/' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $log = date('Y-m-d H:i:s') . " [$level] " . $message . "\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}

/**
 * ⚠️ لاگ خطا
 */
function logError($message, $context = []) {
    $log = $message;
    if (!empty($context)) {
        $log .= " | Context: " . json_encode($context);
    }
    logMessage($log, 'error');
}