<?php
/**
 * 🛠️ توابع کمکی
 */

require_once 'config.php';

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
 * 📨 ارسال پیام خصوصی (PM)
 */
function sendPrivateMessage($user_id, $text, $keyboard = null) {
    return sendMessage($user_id, $text, $keyboard);
}

/**
 * 🔔 ارسال callback answer
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
 * 🎲 ساخت کد تصادفی
 */
function generateGameCode() {
    return strtoupper(substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6));
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
 * ⏰ زمان به فارسی
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;

    if ($diff < 60) return "همین الان";
    if ($diff < 3600) return floor($diff / 60) . " دقیقه پیش";
    if ($diff < 86400) return floor($diff / 3600) . " ساعت پیش";
    return floor($diff / 86400) . " روز پیش";
}

/**
 * 📊 فرمت زمان طولانی
 */
function formatTime($seconds) {
    if ($seconds < 60) return $seconds . " ثانیه";
    if ($seconds < 3600) return floor($seconds / 60) . " دقیقه";
    if ($seconds < 86400) return floor($seconds / 3600) . " ساعت";
    return floor($seconds / 86400) . " روز";
}

/**
 * 🎭 آیکون نقش
 */
function getRoleIcon($role) {
    $icons = [
        // ========== تیم روستا ==========
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
        
        // ========== تیم گرگ ==========
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
        
        // ========== تیم ومپایر ==========
        'vampire' => '🧛🏻‍♂️',
        'bloodthirsty' => '🧛🏻‍♀️',
        'kent_vampire' => '💍🧛🏻',
        'chiang' => '👩‍🦳',
        
        // ========== تیم قاتل ==========
        'serial_killer' => '🔪',
        'archer' => '🏹',
        'davina' => '🍾',
        
        // ========== تیم شوالیه تاریکی ==========
        'black_knight' => '🥷🗡',
        'bride_dead' => '👰‍♀☠️',
        
        // ========== تیم جوکر ==========
        'joker' => '🤡',
        'harly' => '👩🏻‍🎤',
        
        // ========== تیم آتش و یخ ==========
        'fire_king' => '🔥🤴🏻',
        'ice_queen' => '❄️👸🏻',
        'lilith' => '🐍👩🏻‍🦳',
        'magento' => '🧲',
        
        // ========== تیم فرقه ==========
        'cultist' => '👤',
        'royce' => '🎩',
        'frankenstein' => '🧟‍♂️🪖',
        'monk_black' => '🦇',
        
        // ========== نقش‌های مستقل ==========
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
        // ========== تیم روستا ==========
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
        
        // ========== تیم گرگ ==========
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
        
        // ========== تیم ومپایر ==========
        'vampire' => 'ومپایر',
        'bloodthirsty' => 'ومپایر اصیل',
        'kent_vampire' => 'کنت ومپایر',
        'chiang' => 'چیانگ',
        
        // ========== تیم قاتل ==========
        'serial_killer' => 'قاتل زنجیره‌ای',
        'archer' => 'کماندار',
        'davina' => 'داوینا',
        
        // ========== تیم شوالیه تاریکی ==========
        'black_knight' => 'شوالیه تاریکی',
        'bride_dead' => 'عروس مردگان',
        
        // ========== تیم جوکر ==========
        'joker' => 'جوکر',
        'harly' => 'هارلی کویین',
        
        // ========== تیم آتش و یخ ==========
        'fire_king' => 'پادشاه آتش',
        'ice_queen' => 'ملکه یخی',
        'lilith' => 'لیلیث',
        'magento' => 'مگنیتو',
        
        // ========== تیم فرقه ==========
        'cultist' => 'فرقه‌گرا',
        'royce' => 'رئیس فرقه',
        'frankenstein' => 'فرانکشتاین',
        'monk_black' => 'راهب سیاه',
        
        // ========== نقش‌های مستقل ==========
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
 * 📝 توضیحات نقش
 */
function getRoleDescription($role) {
    $desc = [
        'werewolf' => 'شما گرگینه هستید! 🐺\nهر شب یک نفر را می‌خورید.\nهدف: نابودی روستایی‌ها',
        'seer' => 'شما پیشگو هستید! 👳🏻‍♂️\nهر شب هویت یک نفر را می‌بینید',
        'guardian_angel' => 'شما فرشته نگهبان هستید! 👼🏻\nهر شب یک نفر را محافظت می‌کنید',
        'hunter' => 'شما کلانتر هستید! 👮🏻‍♂️\nاگر بمیرید، یک نفر را با خود می‌برید',
        'villager' => 'شما روستایی هستید! 👨‍🌾\nدر روز رأی دهید تا گرگینه‌ها را پیدا کنید',
        'serial_killer' => 'شما قاتل زنجیره‌ای هستید! 🔪\nهر شب یک نفر را می‌کشید\nهدف: بقای آخرین نفر',
        'joker' => 'شما جوکر هستید! 🤡\nباید 3 طومار جمع‌آوری کنید و اعدام شوید!',
        'fire_king' => 'شما پادشاه آتش هستید! 🔥\nبا ملکه یخی همکاری کنید',
        'ice_queen' => 'شما ملکه یخی هستید! ❄️\nبا پادشاه آتش همکاری کنید',
        'cultist' => 'شما فرقه‌گرا هستید! 👤\nبا رئیس و بقیه فرقه همکاری کنید',
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
 * 🔍 بررسی ادمین بودن
 */
function isAdmin($user_id, $chat_id) {
    if ($user_id == ADMIN_ID) return true;
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getChatMember";
    $result = apiRequest($url, [
        'chat_id' => $chat_id,
        'user_id' => $user_id
    ]);
    
    if ($result && $result['ok']) {
        $status = $result['result']['status'] ?? 'member';
        return in_array($status, ['creator', 'administrator']);
    }
    
    return false;
}