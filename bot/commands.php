<?php
/**
 * 📟 پردازش دستورات - نسخه کامل
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';
require_once 'game.php';

// بارگذاری factory نقش‌ها در صورت وجود
$rolesPath = BASE_PATH . 'ROLES_PATCH/';
if (is_dir($rolesPath) && file_exists($rolesPath . 'factory.php')) {
    require_once $rolesPath . 'factory.php';
}

// ==================== تابع اصلی پردازش ====================

/**
 * 🎯 ورودی اصلی
 */
function processUpdate($update) {
    // Callback (دکمه شیشه‌ای)
    if (isset($update['callback_query'])) {
        processCallback($update['callback_query']);
        return;
    }

    if (!isset($update['message'])) {
        return;
    }

    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $text = $message['text'] ?? '';
    $chat_type = $message['chat']['type'];
    $first_name = $message['from']['first_name'] ?? 'Unknown';

    if (empty($text)) {
        return;
    }

    // پاک کردن @username از دستور
    $text = preg_replace('/@' . BOT_USERNAME . '$/i', '', $text);

    // تقسیم دستور و پارامتر
    $parts = explode(' ', $text);
    $command = strtolower($parts[0]);
    $param = $parts[1] ?? '';

    // ===== پردازش دستورات =====
    switch ($command) {
        case '/start':
            cmdStart($chat_id, $user_id, $first_name, $chat_type, $param);
            break;

        case '/game':
            cmdGame($chat_id, $user_id, $first_name, $chat_type);
            break;

        case '/join':
            cmdJoin($chat_id, $user_id, $first_name, $param);
            break;

        case '/panel':
        case '/menu':
            showMainPanel($chat_id, $chat_type);
            break;

        case '/leave':
            cmdLeave($chat_id, $user_id);
            break;

        case '/players':
        case '/list':
            cmdPlayers($chat_id, $user_id, $chat_type);
            break;

        case '/startgame':
            cmdStartGame($chat_id, $user_id, $chat_type);
            break;

        case '/stop':
        case '/cancel':
            cmdCancel($chat_id, $user_id, $chat_type);
            break;
            
        case '/extend':
            cmdExtend($chat_id, $user_id, $chat_type);
            break;
            
        case '/timing':
            cmdTiming($chat_id, $user_id, $chat_type, $param);
            break;

        case '/info':
        case '/status':
            cmdInfo($chat_id, $chat_type);
            break;

        case '/help':
            cmdHelp($chat_id);
            break;

        case '/rules':
            cmdRules($chat_id);
            break;

        case '/roles':
            cmdRoles($chat_id);
            break;

        case '/ping':
            cmdPing($chat_id);
            break;

        case '/stats':
            cmdStats($chat_id, $user_id);
            break;

        case '/smite':
            cmdSmite($chat_id, $user_id, $chat_type, $param);
            break;

        case '/setlink':
            cmdSetLink($chat_id, $user_id, $chat_type, $param);
            break;
            
        case '/sponsers':
            cmdSponsers($chat_id);
            break;    
         
        case '/team':
            cmdTeam($chat_id, $user_id, $text);
            break;

        case '/kill':
            cmdKill($chat_id, $user_id, $chat_type);
            break;

        case '/groupstats':
            cmdGroupStats($chat_id, $user_id, $chat_type);
            break;

        case '/grouplist':
            cmdGroupList($chat_id, $user_id, $chat_type);
            break;

        case '/getstatus':
        case '/gstatus':
            cmdGetStatus($chat_id, $user_id, $chat_type);
            break;

        // دستورات ادمین
        case '/admin':
            if ($user_id == ADMIN_ID) {
                showAdminPanel($chat_id);
            }
            break;
            
        case '/broadcast':
            if ($user_id == ADMIN_ID && !empty($param)) {
                broadcastMessage($param);
                sendMessage($chat_id, "✅ پیام ارسال شد!");
            }
            break;
    }
}

// ==================== دستورات ====================

/**
 * 👋 دستور /start
 */
function cmdStart($chat_id, $user_id, $first_name, $chat_type, $param = '') {
    // اگه پارامتر join داشته باشه
    if (strpos($param, 'join_') === 0) {
        $code = substr($param, 5);
        cmdJoin($chat_id, $user_id, $first_name, $code);
        return;
    }

    $msg = "👋 سلام <b>" . htmlspecialchars($first_name) . "</b>!\n\n";
    $msg .= "🐺 به ربات <b>" . BOT_NAME . "</b> خوش اومدی!\n\n";

    if ($chat_type == 'private') {
        $msg .= "📱 یکی از گزینه‌ها رو انتخاب کن:";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎮 ساخت بازی جدید', 'callback_data' => 'create_game'],
                    ['text' => '🔗 پیوستن به بازی', 'callback_data' => 'join_menu']
                ],
                [
                    ['text' => '📜 قوانین بازی', 'callback_data' => 'rules'],
                    ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
                ],
                [
                    ['text' => '📊 آمار', 'callback_data' => 'stats'],
                    ['text' => '❓ راهنما', 'callback_data' => 'help']
                ]
            ]
        ];

        sendMessage($chat_id, $msg, $keyboard);
    } else {
        $msg .= "📱 برای استفاده از ربات، پیام خصوصی رو باز کن\n";
        $msg .= "یا روی دکمه زیر بزن:";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎮 باز کردن ربات', 'url' => 'https://t.me/' . BOT_USERNAME]
                ]
            ]
        ];

        sendMessage($chat_id, $msg, $keyboard);
    }
}

/**
 * 🎮 دستور /game - ساخت بازی
 */
function cmdGame($chat_id, $user_id, $first_name, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        $msg = "❌ <b>ساخت بازی فقط در گروه ممکنه!</b>\n\n";
        $msg .= "📌 مراحل:\n";
        $msg .= "1️⃣ بات رو به گروه اضافه کن\n";
        $msg .= "2️⃣ ادمینش کن\n";
        $msg .= "3️⃣ دستور /game رو بزن\n\n";
        $msg .= "👇 یا روی دکمه زیر بزن:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '➕ افزودن به گروه', 'url' => 'https://t.me/' . BOT_USERNAME . '?startgroup=true']
                ],
                [
                    ['text' => '🔄 تلاش مجدد', 'callback_data' => 'create_game']
                ]
            ]
        ];

        sendMessage($chat_id, $msg, $keyboard);
        return;
    }

    if (!isAdmin($user_id, $chat_id)) {
        sendMessage($chat_id, "❌ فقط ادمین‌های گروه می‌تونن بازی بسازن!");
        return;
    }

    cleanupOldGames();
    $result = createGame($chat_id, $user_id, $first_name);

    if ($result['success']) {
        if ($result['need_time_setup'] ?? false) {
            $msg = $result['message'];
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⚡ سریع (۶۰s)', 'callback_data' => 'timing_fast'],
                        ['text' => '🐢 عادی (۹۰s)', 'callback_data' => 'timing_normal'],
                        ['text' => '🐌 آرام (۱۲۰s)', 'callback_data' => 'timing_slow']
                    ],
                    [
                        ['text' => '🔗 پیوستن به بازی', 'url' => 'https://t.me/' . BOT_USERNAME . '?start=join_' . $result['code']]
                    ]
                ]
            ];
            
            sendMessage($chat_id, $msg, $keyboard);
        } else {
            $msg = $result['message'] . "\n\n";
            $msg .= "🎲 <b>کد بازی:</b> <code>" . $result['code'] . "</code>\n";
            $msg .= "👤 سازنده: " . $first_name . "\n";
            $msg .= "👥 بازیکنان: 1\n\n";
            $msg .= "📌 <b>دوستانت رو دعوت کن:</b>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔗 پیوستن به بازی', 'url' => 'https://t.me/' . BOT_USERNAME . '?start=join_' . $result['code']]
                    ],
                    [
                        ['text' => '▶️ شروع بازی', 'callback_data' => 'startgame_' . $result['code']],
                        ['text' => '❌ لغو بازی', 'callback_data' => 'cancel_' . $result['code']]
                    ],
                    [
                        ['text' => '📋 لیست بازیکنان', 'callback_data' => 'players_' . $result['code']],
                        ['text' => '📢 اطلاع‌رسانی', 'callback_data' => 'notify_' . $result['code']]
                    ]
                ]
            ];

            sendMessage($chat_id, $msg, $keyboard);
        }
    } else {
        if (isset($result['code'])) {
            showGameManagePanel($chat_id, null, $result['code']);
        } else {
            sendMessage($chat_id, $result['message']);
        }
    }
}

/**
 * ➕ پیوستن به بازی
 */
function cmdJoin($chat_id, $user_id, $first_name, $code) {
    if (empty($code)) {
        sendMessage($chat_id, "❌ کد بازی رو وارد کن!\n\nمثال: <code>/join AB12CD</code>", [
            'inline_keyboard' => [
                [['text' => '📝 وارد کردن کد', 'callback_data' => 'join_menu']]
            ]
        ]);
        return;
    }

    $code = strtoupper(trim($code));
    $result = joinGame($code, $user_id, $first_name);

    if ($result['success']) {
        $game = $result['game'];
        
        $group_msg = "✅ <b>" . $first_name . "</b> به بازی پیوست!\n";
        $group_msg .= "👥 الان " . $result['player_count'] . " نفر هستیم";
        sendMessage($game['group_id'], $group_msg);

        $user_msg = "✅ <b>شما به بازی پیوستید!</b>\n\n";
        $user_msg .= "🎮 کد: <code>" . $code . "</code>\n";
        $user_msg .= "👥 تعداد بازیکنان: " . $result['player_count'] . "\n\n";
        $user_msg .= "📌 وقتی بازی شروع شد، نقشت تو پیام خصوصی میاد!";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 وضعیت بازی', 'callback_data' => 'status_' . $code],
                    ['text' => '🚪 خروج', 'callback_data' => 'leave_' . $code]
                ],
                [
                    ['text' => '📢 دعوت دوستان', 'url' => 'https://t.me/' . BOT_USERNAME . '?start=join_' . $code]
                ]
            ]
        ];

        sendMessage($chat_id, $user_msg, $keyboard);
    } else {
        $msg = "❌ " . $result['message'] . "\n\n";
        $msg .= "👇 دوباره تلاش کن:";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔄 تلاش مجدد', 'callback_data' => 'join_menu']],
                [['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu']]
            ]
        ];

        sendMessage($chat_id, $msg, $keyboard);
    }
}

/**
 * 🚪 خروج از بازی
 */
function cmdLeave($chat_id, $user_id) {
    $result = leaveGame($user_id, $chat_id);
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu'],
                ['text' => '🎮 بازی جدید', 'callback_data' => 'create_game']
            ]
        ]
    ];

    sendMessage($chat_id, $result['message'], $keyboard);
}

/**
 * 👥 لیست بازیکنان
 */
function cmdPlayers($chat_id, $user_id, $chat_type) {
    $game = null;
    
    if (in_array($chat_type, ['group', 'supergroup'])) {
        $game = getGroupActiveGame($chat_id);
    } else {
        $game = getPlayerActiveGame($user_id);
    }
    
    if (!$game) {
        sendMessage($chat_id, "❌ شما در هیچ بازی فعالی نیستید!\n\nبرای پیوستن: /join [کد]");
        return;
    } 
    
    $msg = "👥 <b>بازیکنان بازی</b> - کد: <code>" . $game['code'] . "</code>\n\n";
    $msg .= "📊 وضعیت: " . getStatusText($game['status']) . "\n";
    $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
    
    $index = 1;
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? true) ? '🟢' : '💀';
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $you = ($p['id'] == $user_id) ? '(شما)' : '';
        
        $msg .= "{$index}. {$status} <b>{$p['name']}</b> {$creator} {$you}\n";
        
        if ($game['status'] == 'started' && ($p['id'] == $user_id || !($p['alive'] ?? true))) {
            $role = getRoleDisplayName($p['role']);
            $msg .= "   └ 🎭 {$role}\n";
        }
        
        $index++;
    }
    
    if ($game['status'] == 'started') {
        $alive = count(array_filter($game['players'], function($p) {
            return $p['alive'] ?? false;
        }));
        $dead = count($game['players']) - $alive;
        $msg .= "\n🟢 زنده: {$alive} | 💀 مرده: {$dead}";
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * ▶️ شروع بازی
 */
function cmdStartGame($chat_id, $user_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ فقط تو گروه!");
        return;
    }

    $result = startGame($chat_id, $user_id);
    sendMessage($chat_id, $result['message']);
}

/**
 * ❌ لغو بازی
 */
function cmdCancel($chat_id, $user_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ فقط تو گروه!");
        return;
    }

    $result = cancelGame($chat_id, $user_id);
    
    if ($result['success']) {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🎮 بازی جدید', 'callback_data' => 'create_game']]
            ]
        ];
        sendMessage($chat_id, $result['message'], $keyboard);
    } else {
        sendMessage($chat_id, $result['message']);
    }
}

/**
 * ⏰ تمدید زمان
 */
function cmdExtend($chat_id, $user_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ این دستور فقط در گروه کار می‌کنه!");
        return;
    }

    $result = extendWaitingTime($chat_id, $user_id);
    sendMessage($chat_id, $result['message']);
}

/**
 * ⚙️ تنظیم تایم
 */
function cmdTiming($chat_id, $user_id, $chat_type, $param) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ فقط در گروه!");
        return;
    }
    
    $game = getGroupActiveGame($chat_id);
    if (!$game) {
        sendMessage($chat_id, "❌ بازی فعالی نیست!");
        return;
    }
    
    if (empty($param)) {
        $current = $game['settings']['day_duration'] ?? 60;
        $msg = "⚙️ <b>تنظیم تایم بازی</b>\n\n";
        $msg .= "⏱ تایم فعلی: <b>" . $current . " ثانیه</b>\n\n";
        $msg .= "👇 یکی رو انتخاب کن:\n\n";
        $msg .= "/timing fast - سریع (۶۰ ثانیه)\n";
        $msg .= "/timing normal - عادی (۹۰ ثانیه)\n";
        $msg .= "/timing slow - آرام (۱۲۰ ثانیه)\n\n";
        
        if (!$game['time_set']) {
            $msg .= "⚠️ <b>هشدار:</b> تایم هنوز تنظیم نشده!\n";
            $msg .= "ادمین گروه باید یکی رو انتخاب کنه.";
        } else {
            $msg .= "📌 فقط ادمین گروه می‌تونه تغییر بده.";
        }
        
        sendMessage($chat_id, $msg);
        return;
    }
    
    if (!in_array($param, ['fast', 'normal', 'slow'])) {
        sendMessage($chat_id, "❌ گزینه نامعتبر!\n\nاستفاده صحیح:\n/timing fast\n/timing normal\n/timing slow");
        return;
    }
    
    if ($game['time_set']) {
        $result = changeGameTiming($chat_id, $user_id, $param);
    } else {
        $result = setGameTiming($chat_id, $user_id, $param);
    }
    
    sendMessage($chat_id, $result['message']);
}

/**
 * ℹ️ وضعیت بازی
 */
function cmdInfo($chat_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ فقط تو گروه!");
        return;
    }

    $result = getGameInfo($chat_id);
    sendMessage($chat_id, $result['message']);
}

/**
 * ❓ راهنما
 */
function cmdHelp($chat_id) {
    $msg = "📚 <b>راهنمای " . BOT_NAME . "</b>\n\n";

    $msg .= "🎮 <b>نحوه بازی:</b>\n";
    $msg .= "1️⃣ سازنده بازی رو توی گروه می‌سازه\n";
    $msg .= "2️⃣ ادمین تایم بازی رو انتخاب می‌کنه\n";
    $msg .= "3️⃣ بقیه با کد بازی یا لینک می‌پیوندم\n";
    $msg .= "4️⃣ بازی خودکار شروع می‌شه\n";
    $msg .= "5️⃣ نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "6️⃣ شب و روز بازی می‌کنید!\n\n";

    $msg .= "📱 <b>دستورات:</b>\n";
    $msg .= "/game - ساخت بازی (گروه)\n";
    $msg .= "/timing - تنظیم تایم بازی (ادمین)\n";
    $msg .= "/join [کد] - پیوستن\n";
    $msg .= "/startgame - شروع بازی\n";
    $msg .= "/stop - لغو بازی\n";
    $msg .= "/extend - تمدید زمان جوین (ادمین)\n";
    $msg .= "/players - لیست بازیکنان\n";
    $msg .= "/panel - منوی شیشه‌ای\n";
    $msg .= "/kill - خروج اجباری (ادمین)\n";
    $msg .= "/groupstats - آمار گروه\n";
    $msg .= "/grouplist - لیست گروه‌ها\n";
    $msg .= "/getstatus - وضعیت بازی\n\n";

    $msg .= "⚠️ <b>نکات:</b>\n";
    $msg .= "• حداقل ۴ نفر برای شروع نیازه\n";
    $msg .= "• تایم هر فاز قابل تغییر توسط ادمین\n";
    $msg .= "• نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "• گرگینه‌ها همدیگه رو می‌شناسن\n";
    $msg .= "• سازنده فقط یک بازیکن عادیه، کنترل خاصی نداره";

    sendMessage($chat_id, $msg);
}

/**
 * 📜 قوانین
 */
function cmdRules($chat_id) {
    $msg = "📜 <b>قوانین بازی گرگینه</b>\n\n";

    $msg .= "🌙 <b>شب:</b>\n";
    $msg .= "• گرگینه‌ها یک نفر رو انتخاب می‌کنن برای خوردن\n";
    $msg .= "• دیده‌بان هویت یک نفر رو می‌فهمه\n";
    $msg .= "• دکتر یک نفر رو نجات میده\n";
    $msg .= "• شکارچی فرقه‌گراها رو شکار می‌کنه\n";
    $msg .= "• فرشته نگهبان یکی رو محافظت می‌کنه\n\n";

    $msg .= "☀️ <b>روز:</b>\n";
    $msg .= "• نتایج شب اعلام می‌شه\n";
    $msg .= "• همه بحث می‌کنن و گرگ رو پیدا می‌کنن\n";
    $msg .= "• رأی‌گیری می‌شه\n";
    $msg .= "• یک نفر اعدام می‌شه\n\n";

    $msg .= "🏆 <b>شرایط برد:</b>\n";
    $msg .= "• 👨‍🌾 روستایی‌ها: همه گرگینه‌ها بمیرن\n";
    $msg .= "• 🐺 گرگینه‌ها: تعدادشون با روستایی‌ها برابر شه\n";
    $msg .= "• 👤 فرقه: تعدادشون از همه بیشتر شه\n";
    $msg .= "• 🔪 قاتل: همه رو بکشه\n";
    $msg .= "• 👺 منافق: اعدام بشه\n\n";

    $msg .= "⚠️ <b>نکات مهم:</b>\n";
    $msg .= "• نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "• گرگینه‌ها همدیگه رو می‌شناسن\n";
    $msg .= "• اگه عاشقت بمیره، تو هم می‌میری\n";
    $msg .= "• بعضی نقش‌ها می‌تونن تبدیل بشن";

    sendMessage($chat_id, $msg);
}

/**
 * 🎭 لیست نقش‌ها
 */
function cmdRoles($chat_id) {
    $msg = "🎭 <b>نقش‌های بازی</b>\n\n";

    $roles = [
        ['werewolf', 'گرگینه', 'شر', 'هر شب یکی رو می‌خورند'],
        ['alpha_wolf', 'گرگ آلفا', 'شر', 'سر دسته گرگ‌ها، ۲۰٪ شانس آلوده کردن'],
        ['seer', 'پیشگو', 'خیر', 'هر شب هویت یکی رو می‌بینه'],
        ['hunter', 'کلانتر', 'خیر', 'فرقه‌گراها رو شکار می‌کنه'],
        ['guardian_angel', 'فرشته نگهبان', 'خیر', 'هر شب یکی رو محافظت می‌کنه'],
        ['cultist', 'فرقه‌گرا', 'شر', 'هر شب یکی رو به فرقه دعوت می‌کنه'],
        ['serial_killer', 'قاتل زنجیره‌ای', 'شر', 'هر شب یکی رو می‌کشه'],
        ['fool', 'احمق', 'خیر', 'فکر می‌کنه پیشگوه ولی نیست!'],
        ['tanner', 'منافق', 'تک‌نفره', 'باید اعدام بشه تا برنده شه'],
        ['joker', 'جوکر', 'تک‌نفره', 'باید اعدام بشه تا برنده شه'],
        ['vampire', 'ومپایر', 'شر', 'هر شب یکی رو می‌خوره'],
        ['black_knight', 'شوالیه تاریکی', 'تک‌نفره', 'توانایی دفاع و فرار از اعدام'],
        ['fire_king', 'پادشاه آتش', 'شر', 'همکاری با ملکه یخی'],
        ['ice_queen', 'ملکه یخی', 'شر', 'همکاری با پادشاه آتش'],
    ];

    foreach ($roles as $role) {
        $icon = getRoleIcon($role[0]);
        $team = $role[2] == 'شر' ? '🔴' : ($role[2] == 'تک‌نفره' ? '🟡' : '🟢');
        
        $msg .= $icon . " <b>" . $role[1] . "</b> " . $team . "\n";
        $msg .= "└ " . $role[3] . "\n\n";
    }
    
    $msg .= "📌 برای دیدن همه نقش‌ها، منوی اصلی رو باز کنید.";

    sendMessage($chat_id, $msg);
}

/**
 * 📊 آمار
 */
function cmdStats($chat_id, $user_id) {
    $stats = getGameStats();
    
    $msg = "📊 <b>آمار " . BOT_NAME . "</b>\n\n";
    $msg .= "🎮 بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "⏳ در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "▶️ در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "🏁 تمام شده: " . $stats['ended'] . "\n";
    $msg .= "📅 امروز: " . $stats['today'] . "\n\n";
    
    if ($user_id == ADMIN_ID) {
        $msg .= "🔧 <b>پنل ادمین</b>\n";
        $msg .= "برای مدیریت از دستورات ادمین استفاده کن.";
    }

    sendMessage($chat_id, $msg);
}

/**
 * 🏓 دستور /ping
 */
function cmdPing($chat_id) {
    $start = microtime(true);
    $msg = "🏓 <b>Pong!</b>\n\n";
    $msg .= "🤖 ربات آنلاین و فعاله\n";
    $msg .= "⏰ " . date('Y-m-d H:i:s') . "\n";

    $allGames = getAllGames();
    $activeGames = count(array_filter($allGames, function($g) {
        return in_array($g['status'], ['waiting', 'started']);
    }));
    $msg .= "🎮 بازی‌های فعال: {$activeGames}\n";

    sendMessage($chat_id, $msg);

    $end = microtime(true);
    $ms = round(($end - $start) * 1000);
    sendMessage($chat_id, "⚡️ سرعت پاسخ: {$ms}ms");
}

/**
 * 🏠 پنل اصلی
 */
function showMainPanel($chat_id, $chat_type) {
    if ($chat_type != 'private') {
        sendMessage($chat_id, "❌ این پنل فقط در چت خصوصی کار می‌کند!");
        return;
    }

    $msg = "🐺 <b>منوی اصلی " . BOT_NAME . "</b>\n\n";
    $msg .= "یکی از گزینه‌ها رو انتخاب کن:";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎮 ساخت بازی جدید', 'callback_data' => 'create_game'],
                ['text' => '🔗 پیوستن به بازی', 'callback_data' => 'join_menu']
            ],
            [
                ['text' => '📜 قوانین بازی', 'callback_data' => 'rules'],
                ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
            ],
            [
                ['text' => '📊 وضعیت بازی فعال', 'callback_data' => 'my_status'],
                ['text' => '❓ راهنما', 'callback_data' => 'help']
            ]
        ]
    ];

    sendMessage($chat_id, $msg, $keyboard);
}

/**
 * 💀 دستور /kill
 */
function cmdKill($chat_id, $user_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ این دستور فقط در گروه کار می‌کنه!");
        return;
    }
    
    $game = getPlayerActiveGame($user_id);
    
    if (!$game) {
        sendMessage($chat_id, "❌ شما در هیچ بازی فعالی نیستید!");
        return;
    }
    
    $player = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            $player = $p;
            break;
        }
    }
    
    if (!$player) {
        sendMessage($chat_id, "❌ شما در این بازی نیستید!");
        return;
    }
    
    if ($game['status'] == 'started') {
        if (!isAdmin($user_id, $chat_id) && $user_id != ADMIN_ID) {
            sendMessage($chat_id, "❌ فقط ادمین گروه می‌تونه کیل کنه!");
            return;
        }
        
        $game = killPlayer($game, $user_id, 'suicide');
        saveGame($game);
        
        $msg = "💀 <b>" . $player['name'] . "</b> از بازی حذف شد!";
        sendMessage($game['group_id'], $msg);
        
        $winCheck = checkWinCondition($game);
        if ($winCheck['ended']) {
            endGame($game, $winCheck);
        }
    } else {
        $result = leaveGame($user_id, $chat_id);
        sendMessage($chat_id, $result['message']);
    }
}

/**
 * 📊 آمار گروه
 */
function cmdGroupStats($chat_id, $user_id, $chat_type) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ این دستور فقط در گروه کار می‌کنه!");
        return;
    }
    
    $game = getGroupActiveGame($chat_id);
    
    $msg = "📊 <b>آمار گروه</b>\n\n";
    
    if ($game) {
        $msg .= "🎮 <b>بازی فعال:</b>\n";
        $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
        $msg .= "📊 وضعیت: " . getStatusText($game['status']) . "\n";
        $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر\n";
        
        if ($game['status'] == 'started') {
            $msg .= "🌙 شب: " . ($game['night_count'] ?? 0) . "\n";
            $msg .= "☀️ روز: " . ($game['day_count'] ?? 0) . "\n";
            $msg .= "🔄 فاز: " . getPhaseText($game['phase']) . "\n";
            
            $alive = count(array_filter($game['players'], function($p) {
                return $p['alive'] ?? false;
            }));
            $dead = count($game['players']) - $alive;
            $msg .= "🟢 زنده: $alive | 💀 مرده: $dead\n";
        }
        
        if ($game['status'] == 'waiting') {
            $remaining = max(0, $game['wait_until'] - time());
            $minutes = floor($remaining / 60);
            $seconds = $remaining % 60;
            $msg .= "⏱ زمان باقیمانده: " . $minutes . ":" . sprintf("%02d", $seconds) . "\n";
        }
    } else {
        $msg .= "❌ <b>بازی فعالی در این گروه نیست!</b>\n\n";
        $msg .= "برای شروع: /game";
    }
    
    $allGames = getAllGames();
    $groupGames = array_filter($allGames, function($g) use ($chat_id) {
        return $g['group_id'] == $chat_id;
    });
    
    $totalGames = count($groupGames);
    $completedGames = count(array_filter($groupGames, function($g) {
        return $g['status'] == 'ended';
    }));
    
    $msg .= "\n📈 <b>آمار کلی گروه:</b>\n";
    $msg .= "🎮 کل بازی‌ها: $totalGames\n";
    $msg .= "🏁 بازی‌های تکمیل شده: $completedGames\n";
    
    sendMessage($chat_id, $msg);
}

/**
 * 👥 لیست گروه‌های فعال
 */
function cmdGroupList($chat_id, $user_id, $chat_type) {
    $allGames = getAllGames();
    
    $activeGames = array_filter($allGames, function($g) {
        return in_array($g['status'], ['waiting', 'started']);
    });
    
    if (empty($activeGames)) {
        sendMessage($chat_id, "❌ هیچ گروه فعالی با بازی در حال اجرا وجود ندارد!");
        return;
    }
    
    $msg = "👥 <b>لیست گروه‌های فعال</b>\n";
    $msg .= "📊 تعداد: " . count($activeGames) . " گروه\n\n";
    
    $index = 1;
    foreach ($activeGames as $game) {
        $status = $game['status'] == 'waiting' ? '⏳' : '▶️';
        $playerCount = count($game['players']);
        
        $msg .= "$index. $status <b>گروه " . $game['group_id'] . "</b>\n";
        $msg .= "   🎲 کد: <code>" . $game['code'] . "</code>\n";
        $msg .= "   👥 بازیکنان: $playerCount نفر\n";
        
        if ($game['status'] == 'waiting') {
            $remaining = max(0, $game['wait_until'] - time());
            $msg .= "   ⏱ " . floor($remaining / 60) . " دقیقه\n";
        } else {
            $msg .= "   🌙 شب " . ($game['night_count'] ?? 0) . "\n";
        }
        
        $msg .= "\n";
        $index++;
        
        if ($index > 15) {
            $msg .= "➕ و " . (count($activeGames) - 15) . " گروه دیگر...";
            break;
        }
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * 📋 وضعیت بازی
 */
function cmdGetStatus($chat_id, $user_id, $chat_type) {
    $game = null;
    
    if (in_array($chat_type, ['group', 'supergroup'])) {
        $game = getGroupActiveGame($chat_id);
    } else {
        $game = getPlayerActiveGame($user_id);
    }
    
    if (!$game) {
        $msg = "❌ <b>شما در هیچ بازی فعالی نیستید!</b>\n\n";
        $msg .= "برای پیوستن به بازی:\n";
        $msg .= "1️⃣ به گروه برید\n";
        $msg .= "2️⃣ دستور /join [کد] رو بزنید";
        
        sendMessage($chat_id, $msg);
        return;
    }
    
    $msg = "📋 <b>وضعیت بازی</b>\n\n";
    $msg .= "🎲 <b>کد بازی:</b> <code>" . $game['code'] . "</code>\n";
    $msg .= "👤 <b>سازنده:</b> " . $game['creator_name'] . "\n";
    $msg .= "📊 <b>وضعیت:</b> " . getStatusText($game['status']) . "\n\n";
    
    $msg .= "👥 <b>بازیکنان (" . count($game['players']) . "):</b>\n";
    
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? true) ? '🟢' : '💀';
        $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
        $you = ($p['id'] == $user_id) ? '(شما)' : '';
        
        $msg .= "$status {$p['name']} $creator $you\n";
        
        if ($game['status'] == 'started' && ($p['id'] == $user_id || !($p['alive'] ?? true))) {
            $role = getRoleDisplayName($p['role']);
            $msg .= "   └ 🎭 $role\n";
        }
    }
    
    if ($game['status'] == 'started') {
        $msg .= "\n🌙 <b>شب:</b> " . ($game['night_count'] ?? 0) . "\n";
        $msg .= "☀️ <b>روز:</b> " . ($game['day_count'] ?? 0) . "\n";
        $msg .= "🔄 <b>فاز فعلی:</b> " . getPhaseText($game['phase']) . "\n";
        
        $now = time();
        if ($game['phase'] == 'day' && isset($game['discussion_end'])) {
            $remaining = max(0, $game['discussion_end'] - $now);
            $msg .= "⏱ <b>زمان بحث:</b> " . $remaining . " ثانیه\n";
        } elseif ($game['phase'] == 'vote' && isset($game['vote_end'])) {
            $remaining = max(0, $game['vote_end'] - $now);
            $msg .= "⏱ <b>زمان رأی:</b> " . $remaining . " ثانیه\n";
        } elseif ($game['phase'] == 'night' && isset($game['night_end'])) {
            $remaining = max(0, $game['night_end'] - $now);
            $msg .= "⏱ <b>زمان شب:</b> " . $remaining . " ثانیه\n";
        }
        
        if ($game['phase'] == 'vote' && isset($game['votes'])) {
            $voteCount = count($game['votes']);
            $aliveCount = count(array_filter($game['players'], function($p) {
                return $p['alive'] ?? false;
            }));
            $msg .= "🗳️ <b>رأی‌ها:</b> $voteCount / $aliveCount\n";
        }
        
    } elseif ($game['status'] == 'waiting') {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        
        $msg .= "\n⏱ <b>زمان باقیمانده جوین:</b> " . $minutes . ":" . sprintf("%02d", $seconds) . "\n";
        $msg .= "🔄 <b>تمدیدها:</b> " . ($game['extend_count'] ?? 0) . "/3\n";
        
        if ($game['time_set']) {
            $msg .= "⚙️ <b>تایم بازی:</b> " . ($game['settings']['day_duration'] ?? 60) . " ثانیه\n";
        } else {
            $msg .= "⚠️ <b>تایم هنوز تنظیم نشده!</b>\n";
        }
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * ⚡️ دستور /smite
 */
function cmdSmite($chat_id, $user_id, $chat_type, $param) {
    if ($user_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ فقط ادمین اصلی می‌تونه از این دستور استفاده کنه!");
        return;
    }
    
    if (empty($param)) {
        sendMessage($chat_id, "❌ آیدی عددی بازیکن رو وارد کن!\n\nمثال: <code>/smite 123456789</code>");
        return;
    }
    
    $target_id = (int) trim($param);
    $game = getPlayerActiveGame($target_id);
    
    if (!$game) {
        sendMessage($chat_id, "❌ این کاربر در هیچ بازی فعالی نیست!");
        return;
    }
    
    $target = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $target_id) {
            $target = $p;
            break;
        }
    }
    
    if (!$target) {
        sendMessage($chat_id, "❌ بازیکن یافت نشد!");
        return;
    }
    
    if (!($target['alive'] ?? false)) {
        sendMessage($chat_id, "❌ این بازیکن قبلاً مرده!");
        return;
    }
    
    $game = killPlayer($game, $target_id, 'smite');
    saveGame($game);
    
    $msg = "⚡️ <b>صاعقه زده شد!</b>\n\n";
    $msg .= "💀 <b>" . $target['name'] . "</b> توسط ادمین حذف شد!\n";
    $msg .= "🎭 نقشش: " . getRoleDisplayName($target['role']) . "\n";
    $msg .= "🎮 کد بازی: <code>" . $game['code'] . "</code>";
    
    sendMessage($game['group_id'], $msg);
    sendMessage($chat_id, "✅ بازیکن با موفقیت حذف شد!");
    
    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
    }
}

/**
 * 🔗 دستور /setlink
 */
function cmdSetLink($chat_id, $user_id, $chat_type, $param) {
    if (!in_array($chat_type, ['group', 'supergroup'])) {
        sendMessage($chat_id, "❌ این دستور فقط در گروه کار می‌کنه!");
        return;
    }
    
    if (!empty($param)) {
        if (!isAdmin($user_id, $chat_id)) {
            sendMessage($chat_id, "❌ فقط ادمین گروه می‌تونه لینک رو تنظیم کنه!");
            return;
        }
        
        if (!preg_match('/^https:\/\/t\.me\/[a-zA-Z0-9_]+$/', $param)) {
            sendMessage($chat_id, "❌ لینک نامعتبر!\n\nمثال: <code>https://t.me/mygroup</code>");
            return;
        }
        
        $links = getGroupLinks();
        $links[$chat_id] = [
            'link' => $param,
            'set_by' => $user_id,
            'set_at' => time()
        ];
        saveGroupLinks($links);
        
        sendMessage($chat_id, "✅ <b>لینک گروه ذخیره شد!</b>\n\n" . $param);
        return;
    }
    
    $links = getGroupLinks();
    
    if (isset($links[$chat_id])) {
        $msg = "🔗 <b>لینک گروه:</b>\n\n";
        $msg .= "<code>" . $links[$chat_id]['link'] . "</code>\n\n";
        $msg .= "📅 تنظیم شده: " . timeAgo($links[$chat_id]['set_at']) . " پیش";
    } else {
        $msg = "❌ <b>لینکی تنظیم نشده!</b>\n\n";
        $msg .= "برای تنظیم:\n<code>/setlink https://t.me/yourgroup</code>";
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * 🤝 دستور /sponsers
 */
function cmdSponsers($chat_id) {
    $msg = "🤝 <b>اسپانسرها و حامیان</b>\n\n";
    $msg .= "از حمایت شما متشکریم!";
    sendMessage($chat_id, $msg);
}

/**
 * 💬 دستور /team
 */
function cmdTeam($chat_id, $user_id, $text) {
    $chatText = trim(substr($text, 5));
    
    if (empty($chatText)) {
        sendMessage($chat_id, "❌ پیام خالی!\n\nاستفاده صحیح:\n<code>/team سلام بچه‌ها، امشب کیو می‌خوریم؟</code>");
        return;
    }
    
    $game = getPlayerActiveGame($user_id);
    if (!$game) {
        sendMessage($chat_id, "❌ شما در بازی فعالی نیستید!");
        return;
    }
    
    $result = handleTeamChat($user_id, $chatText, $game['code']);
    sendMessage($chat_id, $result['message']);
}

/**
 * 💬 پردازش چت تیمی
 */
function handleTeamChat($user_id, $message, $gameCode) {
    $game = getGame($gameCode);
    if (!$game || $game['phase'] != 'night') {
        return ['success' => false, 'message' => '❌ الان زمان چت تیمی نیست! فقط شب‌ها می‌توانید چت کنید.'];
    }
    
    $player = getPlayerById($game, $user_id);
    if (!$player || !($player['alive'] ?? false)) {
        return ['success' => false, 'message' => '💀 شما مرده‌اید!'];
    }
    
    if (!empty($player['imprisoned'])) {
        return ['success' => false, 'message' => '🔒 شما زندانی کلانتر هستید! نمی‌توانید چت کنید.'];
    }
    
    $team = detectTeam($player['role']);
    
    $evilTeams = ['werewolf', 'cult', 'vampire', 'killer', 'fire_ice', 'black_knight', 'joker'];
    if (!in_array($team, $evilTeams)) {
        return ['success' => false, 'message' => '❌ تیم شما به چت تیمی دسترسی ندارد!'];
    }
    
    $teamMates = [];
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) continue;
        if (!($p['alive'] ?? false)) continue;
        if (!empty($p['imprisoned'])) continue;
        
        $mateTeam = detectTeam($p['role']);
        
        if (!empty($p['converted_to'])) {
            $mateTeam = $p['converted_to'];
        }
        
        if ($mateTeam == $team) {
            $teamMates[] = $p;
        }
    }
    
    if (empty($teamMates)) {
        return ['success' => false, 'message' => '❌ هم‌تیمی فعالی ندارید!'];
    }
    
    $senderName = $player['name'];
    $teamIcons = [
        'werewolf' => '🐺', 'vampire' => '🧛', 'cult' => '👤',
        'killer' => '🔪', 'fire_ice' => '🔥❄️', 'black_knight' => '🥷', 'joker' => '🤡'
    ];
    $icon = $teamIcons[$team] ?? '👥';
    $formattedMsg = "$icon <b>[$senderName]:</b>\n$message";
    
    foreach ($teamMates as $mate) {
        sendPrivateMessage($mate['id'], $formattedMsg);
    }
    
    return [
        'success' => true, 
        'message' => "✅ پیام به " . count($teamMates) . " هم‌تیمی ارسال شد!"
    ];
}

/**
 * 🏠 پنل مدیریت بازی
 */
function showGameManagePanel($chat_id, $message_id, $code) {
    $game = getGame($code);
    if (!$game) {
        if ($message_id) {
            editMessageText($chat_id, $message_id, "❌ بازی یافت نشد!");
        } else {
            sendMessage($chat_id, "❌ بازی یافت نشد!");
        }
        return;
    }

    $msg = "🎮 <b>مدیریت بازی</b> - کد: <code>" . $code . "</code>\n\n";
    $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر\n";
    
    if ($game['status'] == 'waiting' && isset($game['wait_until'])) {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $msg .= "⏱ زمان باقیمانده جوین: " . $minutes . ":" . sprintf("%02d", $seconds) . "\n";
        $msg .= "🔄 تمدیدهای استفاده شده: " . ($game['extend_count'] ?? 0) . "/3\n";
        
        if ($game['time_set']) {
            $msg .= "⚙️ تایم بازی: " . ($game['settings']['day_duration'] ?? 60) . " ثانیه\n";
        } else {
            $msg .= "⚠️ تایم هنوز تنظیم نشده!\n";
        }
    } else {
        $msg .= "⏱ ساخته شده: " . timeAgo($game['created']);
    }

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '▶️ شروع بازی', 'callback_data' => 'startgame_' . $code],
                ['text' => '❌ لغو بازی', 'callback_data' => 'cancel_' . $code]
            ],
            [
                ['text' => '⏰ تمدید زمان', 'callback_data' => 'extend_' . $code],
                ['text' => '⚙️ تغییر تایم', 'callback_data' => 'timing_menu']
            ],
            [
                ['text' => '📋 لیست بازیکنان', 'callback_data' => 'players_' . $code],
                ['text' => '🔗 لینک دعوت', 'callback_data' => 'link_' . $code]
            ]
        ]
    ];

    if ($message_id) {
        editMessageText($chat_id, $message_id, $msg, $keyboard);
    } else {
        sendMessage($chat_id, $msg, $keyboard);
    }
}

// ==================== توابع کمکی ====================

function getGameStats() {
    $games = getAllGames();
    $today = strtotime('today');
    
    return [
        'total' => count($games),
        'waiting' => count(array_filter($games, function($g) {
            return $g['status'] == 'waiting';
        })),
        'started' => count(array_filter($games, function($g) {
            return $g['status'] == 'started';
        })),
        'ended' => count(array_filter($games, function($g) {
            return $g['status'] == 'ended';
        })),
        'today' => count(array_filter($games, function($g) use ($today) {
            return ($g['created'] ?? 0) > $today;
        }))
    ];
}

function broadcastMessage($text) {
    sendMessage(ADMIN_ID, "📢 پیام برای ارسال:\n\n$text\n\n⚠️ این قسمت نیاز به دیتابیس کاربران داره!");
}

function changeGameTiming($group_id, $user_id, $timing_option) {
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
    
    switch ($timing_option) {
        case 'fast':
            $day = 60; $vote = 60; $night = 60;
            $timing_name = 'سریع (۶۰ ثانیه)';
            break;
        case 'normal':
            $day = 90; $vote = 60; $night = 90;
            $timing_name = 'عادی (۹۰ ثانیه)';
            break;
        case 'slow':
            $day = 120; $vote = 60; $night = 120;
            $timing_name = 'آرام (۱۲۰ ثانیه)';
            break;
        default:
            return ['success' => false, 'message' => '❌ گزینه نامعتبر!'];
    }
    
    $game['settings']['day_duration'] = $day;
    $game['settings']['vote_duration'] = $vote;
    $game['settings']['night_duration'] = $night;
    saveGame($game);
    
    $msg = "⚙️ <b>تایم بازی تغییر کرد!</b>\n\n";
    $msg .= "🎮 حالت: <b>" . $timing_name . "</b>\n\n";
    $msg .= "⏱ تایم‌ها:\n";
    $msg .= "• 🌙 شب: " . $night . " ثانیه\n";
    $msg .= "• ☀️ روز: " . $day . " ثانیه\n";
    $msg .= "• 🗳️ رأی‌گیری: " . $vote . " ثانیه";
    
    return ['success' => true, 'message' => $msg];
}

// ==================== پنل ادمین ====================

function showAdminPanel($chat_id) {
    $stats = getGameStats();
    
    $msg = "🔧 <b>پنل مدیریت</b>\n\n";
    $msg .= "📊 آمار کلی:\n";
    $msg .= "• کل بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "• در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "• در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "• تمام شده: " . $stats['ended'] . "\n\n";
    $msg .= "📊 اندازه دیتابیس: " . getDatabaseSize() . "\n\n";
    
    $msg .= "👇 یکی رو انتخاب کن:";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📢 پیام همگانی', 'callback_data' => 'admin_broadcast'],
                ['text' => '🗑️ پاک کردن بازی‌ها', 'callback_data' => 'admin_cleanup']
            ],
            [
                ['text' => '📊 آمار کامل', 'callback_data' => 'admin_stats'],
                ['text' => '🔄 ری‌استارت', 'callback_data' => 'admin_restart']
            ],
            [
                ['text' => '🔙 بستن پنل', 'callback_data' => 'main_menu']
            ]
        ]
    ];

    sendMessage($chat_id, $msg, $keyboard);
}

// ==================== پردازش Callback ====================

function processCallback($callback) {
    $callback_id = $callback['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    $chat_id = $callback['message']['chat']['id'];
    $message_id = $callback['message']['message_id'];
    $first_name = $callback['from']['first_name'] ?? 'Unknown';

    $parts = explode('_', $data);
    $action = $parts[0];
    $param = $parts[1] ?? '';

    switch ($action) {
        case 'main':
        case 'menu':
            answerCallbackQuery($callback_id, "🏠 منوی اصلی");
            editToMainMenu($chat_id, $message_id);
            break;

        case 'create':
            answerCallbackQuery($callback_id, "🎮 در حال ساخت بازی...");
            $chat_type = $callback['message']['chat']['type'];
            cmdGame($chat_id, $user_id, $first_name, $chat_type);
            break;

        case 'join':
            if ($param == 'menu') {
                answerCallbackQuery($callback_id, "🔗 وارد کردن کد بازی");
                editMessageText($chat_id, $message_id,
                    "🔗 <b>پیوستن به بازی</b>\n\n" .
                    "🎲 کد ۶ رقمی بازی رو وارد کن:\n\n" .
                    "مثال: <code>AB12CD</code>\n\n" .
                    "👇 کد رو بفرس یا تایپ کن:",
                    [
                        'inline_keyboard' => [
                            [['text' => '🔙 بازگشت', 'callback_data' => 'main_menu']]
                        ]
                    ]
                );
            }
            break;

        case 'rules':
            answerCallbackQuery($callback_id, "📜 نمایش قوانین");
            editToRules($chat_id, $message_id);
            break;

        case 'roles':
            answerCallbackQuery($callback_id, "🎭 نمایش نقش‌ها");
            editToRoles($chat_id, $message_id);
            break;

        case 'help':
            answerCallbackQuery($callback_id, "❓ نمایش راهنما");
            editToHelp($chat_id, $message_id);
            break;

        case 'stats':
            answerCallbackQuery($callback_id, "📊 نمایش آمار");
            editToStats($chat_id, $message_id, $user_id);
            break;

        case 'my':
            if ($param == 'status') {
                answerCallbackQuery($callback_id, "📊 وضعیت بازی شما");
                showMyGameStatus($chat_id, $message_id, $user_id);
            }
            break;

        case 'timing':
            answerCallbackQuery($callback_id, "⏱ در حال تنظیم تایم...");
            $result = setGameTiming($chat_id, $user_id, $param);
            
            if ($result['success']) {
                $game = getGroupActiveGame($chat_id);
                $code = $game['code'] ?? '';
                
                editMessageText($chat_id, $message_id, 
                    $result['message'] . "\n\n" .
                    "👇 حالا می‌تونی دوستانت رو دعوت کنی:",
                    [
                        'inline_keyboard' => [
                            [
                                ['text' => '🔗 پیوستن به بازی', 'url' => 'https://t.me/' . BOT_USERNAME . '?start=join_' . $code]
                            ],
                            [
                                ['text' => '▶️ شروع بازی', 'callback_data' => 'startgame_' . $code],
                                ['text' => '❌ لغو بازی', 'callback_data' => 'cancel_' . $code]
                            ]
                        ]
                    ]
                );
            } else {
                answerCallbackQuery($callback_id, $result['message'], true);
            }
            break;

        case 'startgame':
            answerCallbackQuery($callback_id, "⏳ در حال شروع بازی...");
            $result = startGame($chat_id, $user_id);
            
            if ($result['success']) {
                editMessageText($chat_id, $message_id, $result['message']);
            } else {
                answerCallbackQuery($callback_id, $result['message'], true);
            }
            break;

        case 'cancel':
            answerCallbackQuery($callback_id, "⏳ در حال لغو...");
            $result = cancelGame($chat_id, $user_id);
            
            if ($result['success']) {
                editMessageText($chat_id, $message_id, 
                    $result['message'] . "\n\n❌ بازی لغو شد.",
                    [
                        'inline_keyboard' => [
                            [['text' => '🎮 بازی جدید', 'callback_data' => 'create_game']]
                        ]
                    ]
                );
            } else {
                answerCallbackQuery($callback_id, $result['message'], true);
            }
            break;
            
        case 'extend':
            answerCallbackQuery($callback_id, "⏰ در حال تمدید...");
            $result = extendWaitingTime($chat_id, $user_id);
            answerCallbackQuery($callback_id, strip_tags($result['message']), true);
            break;

        case 'players':
            answerCallbackQuery($callback_id, "📋 در حال دریافت لیست...");
            $result = getGameInfo($chat_id);
            
            if ($result['success']) {
                editMessageText($chat_id, $message_id, $result['message'], [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔄 بروزرسانی', 'callback_data' => 'players_' . $param],
                            ['text' => '🔙 بازگشت', 'callback_data' => 'manage_' . $param]
                        ]
                    ]
                ]);
            } else {
                answerCallbackQuery($callback_id, $result['message'], true);
            }
            break;

        case 'leave':
            answerCallbackQuery($callback_id, "🚪 در حال خروج...");
            $result = leaveGame($user_id, $chat_id);
            editMessageText($chat_id, $message_id, $result['message'], [
                'inline_keyboard' => [
                    [
                        ['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu'],
                        ['text' => '🎮 بازی جدید', 'callback_data' => 'create_game']
                    ]
                ]
            ]);
            break;

        case 'status':
            answerCallbackQuery($callback_id, "📊 در حال دریافت وضعیت...");
            $result = getGameInfo($chat_id);
            answerCallbackQuery($callback_id, strip_tags($result['message']), true);
            break;

        case 'link':
            $game = getGame($param);
            if ($game) {
                $link = 'https://t.me/' . BOT_USERNAME . '?start=join_' . $param;
                answerCallbackQuery($callback_id, "🔗 لینک کپی شد!");
                sendMessage($chat_id, "🔗 <b>لینک دعوت:</b>\n<code>" . $link . "</code>");
            }
            break;

        case 'notify':
            $game = getGame($param);
            if ($game) {
                $msg = "🔔 <b>دعوت به بازی گرگینه!</b>\n\n";
                $msg .= "👤 سازنده: " . $game['creator_name'] . "\n";
                $msg .= "👥 بازیکنان فعلی: " . count($game['players']) . "\n";
                $msg .= "🎲 کد: <code>" . $param . "</code>\n\n";
                $msg .= "👇 برای پیوستن کلیک کنید:";
                
                sendMessage($chat_id, $msg, [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎮 پیوستن به بازی', 'url' => 'https://t.me/' . BOT_USERNAME . '?start=join_' . $param]
                        ]
                    ]
                ]);
                answerCallbackQuery($callback_id, "📢 پیام دعوت ارسال شد!");
            }
            break;

        case 'manage':
            showGameManagePanel($chat_id, $message_id, $param);
            break;

        case 'vote':
            handleVote($callback_id, $chat_id, $message_id, $user_id, $param);
            break;

        default:
            answerCallbackQuery($callback_id, "❓ دستور نامشخص!", true);
    }
}

// ==================== توابع ویرایش ====================

function editToMainMenu($chat_id, $message_id) {
    $msg = "🐺 <b>منوی اصلی " . BOT_NAME . "</b>\n\n";
    $msg .= "یکی از گزینه‌ها رو انتخاب کن:";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎮 ساخت بازی جدید', 'callback_data' => 'create_game'],
                ['text' => '🔗 پیوستن به بازی', 'callback_data' => 'join_menu']
            ],
            [
                ['text' => '📜 قوانین بازی', 'callback_data' => 'rules'],
                ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
            ],
            [
                ['text' => '📊 آمار', 'callback_data' => 'stats'],
                ['text' => '❓ راهنما', 'callback_data' => 'help']
            ]
        ]
    ];

    editMessageText($chat_id, $message_id, $msg, $keyboard);
}

function editToRules($chat_id, $message_id) {
    $msg = "📜 <b>قوانین بازی گرگینه</b>\n\n";

    $msg .= "🌙 <b>شب:</b>\n";
    $msg .= "• گرگینه‌ها یک نفر رو انتخاب می‌کنن برای خوردن\n";
    $msg .= "• دیده‌بان هویت یک نفر رو می‌فهمه\n";
    $msg .= "• دکتر یک نفر رو نجات میده\n";
    $msg .= "• شکارچی فرقه‌گراها رو شکار می‌کنه\n";
    $msg .= "• فرشته نگهبان یکی رو محافظت می‌کنه\n\n";

    $msg .= "☀️ <b>روز:</b>\n";
    $msg .= "• نتایج شب اعلام می‌شه\n";
    $msg .= "• همه بحث می‌کنن و گرگ رو پیدا می‌کنن\n";
    $msg .= "• رأی‌گیری می‌شه\n";
    $msg .= "• یک نفر اعدام می‌شه\n\n";

    $msg .= "🏆 <b>شرایط برد:</b>\n";
    $msg .= "• 👨‍🌾 روستایی‌ها: همه گرگینه‌ها بمیرن\n";
    $msg .= "• 🐺 گرگینه‌ها: تعدادشون با روستایی‌ها برابر شه\n";
    $msg .= "• 👤 فرقه: تعدادشون از همه بیشتر شه\n";
    $msg .= "• 🔪 قاتل: همه رو بکشه\n";
    $msg .= "• 👺 منافق: اعدام بشه\n\n";

    $msg .= "⚠️ <b>نکات مهم:</b>\n";
    $msg .= "• نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "• گرگینه‌ها همدیگه رو می‌شناسن\n";
    $msg .= "• اگه عاشقت بمیره، تو هم می‌میری\n";
    $msg .= "• بعضی نقش‌ها می‌تونن تبدیل بشن";

    editMessageText($chat_id, $message_id, $msg, [
        'inline_keyboard' => [
            [
                ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles'],
                ['text' => '🔙 بازگشت', 'callback_data' => 'main_menu']
            ]
        ]
    ]);
}

function editToRoles($chat_id, $message_id) {
    $msg = "🎭 <b>نقش‌های بازی</b>\n\n";

    $roles = [
        ['werewolf', 'شر', 'هر شب یکی رو می‌خورن'],
        ['seer', 'خیر', 'هر شب هویت یکی رو می‌فهمه'],
        ['doctor', 'خیر', 'هر شب یکی رو نجات میده'],
        ['hunter', 'خیر', 'فرقه‌گراها رو شکار می‌کنه'],
        ['guard', 'خیر', 'هر شب یکی رو محافظت می‌کنه'],
        ['cultist', 'شر', 'هر شب یکی رو به فرقه دعوت می‌کنه'],
        ['serial_killer', 'شر', 'هر شب یکی رو می‌کشه'],
        ['fool', 'خیر', 'فکر می‌کنه پیشگوه ولی نیست!'],
        ['tanner', 'تک‌نفره', 'باید اعدام بشه تا برنده شه']
    ];

    foreach ($roles as $role) {
        $icon = getRoleIcon($role[0]);
        $team = $role[1] == 'شر' ? '🔴' : ($role[1] == 'تک‌نفره' ? '🟡' : '🟢');
        
        $msg .= $icon . " <b>" . getRoleName($role[0]) . "</b> " . $team . "\n";
        $msg .= "└ " . $role[2] . "\n\n";
    }

    editMessageText($chat_id, $message_id, $msg, [
        'inline_keyboard' => [
            [
                ['text' => '📜 قوانین', 'callback_data' => 'rules'],
                ['text' => '🔙 بازگشت', 'callback_data' => 'main_menu']
            ]
        ]
    ]);
}

function editToHelp($chat_id, $message_id) {
    $msg = "📚 <b>راهنمای " . BOT_NAME . "</b>\n\n";

    $msg .= "🎮 <b>نحوه بازی:</b>\n";
    $msg .= "1️⃣ سازنده بازی رو توی گروه می‌سازه\n";
    $msg .= "2️⃣ ادمین تایم بازی رو انتخاب می‌کنه\n";
    $msg .= "3️⃣ بقیه با کد بازی یا لینک می‌پیوندم\n";
    $msg .= "4️⃣ بازی خودکار شروع می‌شه\n";
    $msg .= "5️⃣ نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "6️⃣ شب و روز بازی می‌کنید!\n\n";

    $msg .= "⚠️ <b>نکات:</b>\n";
    $msg .= "• حداقل ۴ نفر برای شروع نیازه\n";
    $msg .= "• تایم هر فاز: ۶۰ ثانیه (قابل تغییر)\n";
    $msg .= "• نقش‌ها در پیام خصوصی ارسال می‌شه\n";
    $msg .= "• گرگینه‌ها همدیگه رو می‌شناسن\n";
    $msg .= "• سازنده فقط یک بازیکن عادیه";

    editMessageText($chat_id, $message_id, $msg, [
        'inline_keyboard' => [
            [
                ['text' => '📜 قوانین', 'callback_data' => 'rules'],
                ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
            ],
            [
                ['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu']
            ]
        ]
    ]);
}

function editToStats($chat_id, $message_id, $user_id) {
    $stats = getGameStats();
    
    $msg = "📊 <b>آمار " . BOT_NAME . "</b>\n\n";
    $msg .= "🎮 کل بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "⏳ در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "▶️ در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "🏁 تمام شده: " . $stats['ended'] . "\n";
    $msg .= "📅 امروز: " . $stats['today'] . "\n\n";
    $msg .= "📊 اندازه دیتابیس: " . getDatabaseSize();
    
    if ($user_id == ADMIN_ID) {
        $msg .= "\n\n🔧 <b>پنل ادمین فعال</b>";
    }

    editMessageText($chat_id, $message_id, $msg, [
        'inline_keyboard' => [
            [
                ['text' => '🔄 بروزرسانی', 'callback_data' => 'stats'],
                ['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu']
            ]
        ]
    ]);
}

function showMyGameStatus($chat_id, $message_id, $user_id) {
    $game = getPlayerActiveGame($user_id);
    
    if (!$game) {
        editMessageText($chat_id, $message_id, 
            "❌ شما در هیچ بازی فعالی نیستید!\n\n" .
            "👇 از منوی اصلی بازی جدید بساز یا به یکی بپیوند:",
            [
                'inline_keyboard' => [
                    [
                        ['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'],
                        ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']
                    ]
                ]
            ]
        );
        return;
    }

    $msg = "🎮 <b>وضعیت بازی شما</b>\n\n";
    $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
    $msg .= "📊 وضعیت: " . getStatusText($game['status']) . "\n";
    
    if ($game['status'] == 'started') {
        $msg .= "🌙 شب: " . ($game['night_count'] ?? 0) . "\n";
        $msg .= "☀️ روز: " . ($game['day_count'] ?? 0) . "\n";
        
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                if ($p['alive']) {
                    $msg .= "🎭 نقش شما: " . getRoleDisplayName($p['role']) . "\n";
                } else {
                    $msg .= "💀 وضعیت: مرده\n";
                }
                break;
            }
        }
    }
    
    $msg .= "\n👥 تعداد بازیکنان: " . count($game['players']) . " نفر";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 جزئیات بیشتر', 'callback_data' => 'status_' . $game['code']],
                ['text' => '🚪 خروج از بازی', 'callback_data' => 'leave_' . $game['code']]
            ],
            [
                ['text' => '🏠 منوی اصلی', 'callback_data' => 'main_menu']
            ]
        ]
    ];

    editMessageText($chat_id, $message_id, $msg, $keyboard);
}

function handleVote($callback_id, $chat_id, $message_id, $user_id, $param) {
    $parts = explode('_', $param);
    $target = $parts[0] ?? '';
    $gameCode = $parts[1] ?? '';
    
    if (empty($gameCode)) {
        answerCallbackQuery($callback_id, "❌ خطا در دریافت کد بازی!", true);
        return;
    }
    
    $game = getGame($gameCode);
    if (!$game || $game['phase'] != 'vote') {
        answerCallbackQuery($callback_id, "⏳ الان زمان رأی نیست!", true);
        return;
    }
    
    $voter = getPlayerById($game, $user_id);
    if (!$voter || !($voter['alive'] ?? false)) {
        answerCallbackQuery($callback_id, "💀 شما مرده‌اید!", true);
        return;
    }
    
    if ($target == 'skip') {
        $result = castVote($user_id, 'skip', $gameCode);
    } else {
        $result = castVote($user_id, (int)$target, $gameCode);
    }
    
    if ($result['success']) {
        answerCallbackQuery($callback_id, "✅ رأی شما ثبت شد!", false);
    } else {
        answerCallbackQuery($callback_id, $result['message'], true);
    }
}