<?php
/**
 * 📟 پردازش دستورات - نسخه اصلاح شده
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'game.php';
require_once 'ROLES_PATCH/factory.php';

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

    // ===== دستورات =====
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
            $start = microtime(true);
            $msg = "🏓 <b>Pong!</b>\n\n";
            $msg .= "🤖 ربات آنلاین و فعاله\n";
            $msg .= "⏰ " . date('Y-m-d H:i:s') . "\n";
        
            $allGames = getAllGames();
            $activeGames = count(array_filter($allGames, fn($g) => in_array($g['status'], ['waiting', 'started'])));
            $msg .= "🎮 بازی‌های فعال: {$activeGames}\n";
        
            sendMessage($chat_id, $msg);
        
            $end = microtime(true);
            $ms = round(($end - $start) * 1000);
            sendMessage($chat_id, "⚡️ سرعت پاسخ: {$ms}ms");
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
            $chatText = trim(substr($text, 5)); // برداشتن "/team"
            
            if (empty($chatText)) {
                sendMessage($chat_id, "❌ پیام خالی!\n\nاستفاده صحیح:\n<code>/team سلام بچه‌ها، امشب کیو می‌خوریم؟</code>");
                break;
            }
            
            $game = getPlayerActiveGame($user_id);
            if (!$game) {
                sendMessage($chat_id, "❌ شما در بازی فعالی نیستید!");
                break;
            }
            
            $result = handleTeamChat($user_id, $chatText, $game['code']);
            sendMessage($chat_id, $result['message']);
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
            
        // ===== دستورات جدید =====
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
    }
}

/**
 * 👥 لیست بازیکنان (اصلاح شده)
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
        $alive = count(array_filter($game['players'], fn($p) => $p['alive'] ?? false));
        $dead = count($game['players']) - $alive;
        $msg .= "\n🟢 زنده: {$alive} | 💀 مرده: {$dead}";
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * ⏰ دستور /extend - تمدید زمان انتظار
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
 * ⚙️ دستور /timing - تنظیم یا تغییر تایم بازی
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
    
    // اگه پارامتر نداد، منو نشون بده
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
    
    // اعتبارسنجی پارامتر
    if (!in_array($param, ['fast', 'normal', 'slow'])) {
        sendMessage($chat_id, "❌ گزینه نامعتبر!\n\nاستفاده صحیح:\n/timing fast\n/timing normal\n/timing slow");
        return;
    }
    
    // ست کردن یا تغییر تایم
    if ($game['time_set']) {
        // تغییر تایم
        $result = changeGameTiming($chat_id, $user_id, $param);
    } else {
        // تنظیم اولیه
        $result = setGameTiming($chat_id, $user_id, $param);
    }
    
    sendMessage($chat_id, $result['message']);
}

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
 * 🎮 دستور /game
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

    // بررسی ادمین بودن
    if (!isAdmin($user_id, $chat_id)) {
        sendMessage($chat_id, "❌ فقط ادمین‌های گروه می‌تونن بازی بسازن!");
        return;
    }

    cleanupOldGames();
    $result = createGame($chat_id, $user_id, $first_name);

    if ($result['success']) {
        // اگه نیاز به تنظیم تایم داره
        if ($result['need_time_setup'] ?? false) {
            $msg = $result['message'];
            
            // کیبورد انتخاب تایم
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
        
        // اطلاع به گروه
        $group_msg = "✅ <b>" . $first_name . "</b> به بازی پیوست!\n";
        $group_msg .= "👥 الان " . $result['player_count'] . " نفر هستیم";
        sendMessage($game['group_id'], $group_msg);

        // پنل به کاربر
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
    $msg .= "2️⃣ ادمین تایم بازی رو انتخاب می‌کنه (۶۰، ۹۰ یا ۱۲۰ ثانیه)\n";
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
    $msg .= "• تایم هر فاز: ۶۰ ثانیه (قابل تغییر توسط ادمین)\n";
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
        ['seer', 'پیشگو', 'خیر', 'هر شب هویت یکی رو می‌بینه'],
        ['hunter', 'شکارچی', 'خیر', 'فرقه‌گراها رو شکار می‌کنه'],
        ['guard', 'فرشته نگهبان', 'خیر', 'هر شب یکی رو محافظت می‌کنه'],
        ['cultist', 'فرقه‌گرا', 'شر', 'هر شب یکی رو به فرقه دعوت می‌کنه'],
        ['serial_killer', 'قاتل', 'شر', 'هر شب یکی رو می‌کشه'],
        ['fool', 'احمق', 'خیر', 'فکر می‌کنه پیشگوه ولی نیست!'],
        ['tanner', 'منافق', 'تک‌نفره', 'باید اعدام بشه تا برنده شه']
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
 * 💀 دستور /kill - خروج اجباری از بازی
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
        
        $msg = "💀 <b>" . $player['name'] . "</b> از بازی حذف شد!\n";
        $msg .= "🎭 نقشش: " . getRoleDisplayName($player['role']);
        
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
 * 📊 دستور /groupstats - آمار گروه
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
            
            $alive = count(array_filter($game['players'], fn($p) => $p['alive'] ?? false));
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
    $groupGames = array_filter($allGames, fn($g) => $g['group_id'] == $chat_id);
    
    $totalGames = count($groupGames);
    $completedGames = count(array_filter($groupGames, fn($g) => $g['status'] == 'ended'));
    
    $msg .= "\n📈 <b>آمار کلی گروه:</b>\n";
    $msg .= "🎮 کل بازی‌ها: $totalGames\n";
    $msg .= "🏁 بازی‌های تکمیل شده: $completedGames\n";
    
    sendMessage($chat_id, $msg);
}

/**
 * 👥 دستور /grouplist - لیست گروه‌های فعال
 */
function cmdGroupList($chat_id, $user_id, $chat_type) {
    $allGames = getAllGames();
    
    $activeGames = array_filter($allGames, fn($g) => in_array($g['status'], ['waiting', 'started']));
    
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
 * 📋 دستور /getstatus - وضعیت بازی
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
            $aliveCount = count(array_filter($game['players'], fn($p) => $p['alive'] ?? false));
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
 * ⚡️ دستور /smite - حذف بازیکن توسط ادمین
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
 * 🔗 دستور /setlink - تنظیم/دریافت لینک گروه
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
 * 📊 دستور /stats پیشرفته - جایگزین stats قدیمی
 */
function cmdStatsAdvanced($chat_id, $user_id, $param) {
    $allGames = getAllGames();
    $today = strtotime('today');
    $weekAgo = strtotime('-7 days');
    
    $stats = [
        'total' => count($allGames),
        'waiting' => 0,
        'started' => 0,
        'ended' => 0,
        'today' => 0,
        'this_week' => 0,
        'total_players' => 0
    ];
    
    foreach ($allGames as $game) {
        $stats[$game['status']]++;
        if ($game['created'] >= $today) $stats['today']++;
        if ($game['created'] >= $weekAgo) $stats['this_week']++;
        $stats['total_players'] += count($game['players']);
    }
    
    $msg = "📊 <b>آمار " . BOT_NAME . "</b>\n\n";
    $msg .= "🎮 بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "  ⏳ در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "  ▶️ در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "  🏁 تمام شده: " . $stats['ended'] . "\n\n";
    $msg .= "📅 امروز: " . $stats['today'] . " | این هفته: " . $stats['this_week'] . "\n";
    $msg .= "👥 کل بازیکنان: " . $stats['total_players'] . " نفر";
    
    if ($user_id == ADMIN_ID && !empty($param)) {
        $msg .= "\n\n🔧 ادمین: " . getDatabaseSize();
    }
    
    sendMessage($chat_id, $msg);
}

/**
 * 👆 پردازش Callback ها
 */
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
        // ===== منوهای اصلی =====
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
            } else {
                handleRoleAction($param, $callback_id, $chat_id, $message_id, $user_id, $param);
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

        // ===== تنظیم تایم =====
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

        // ===== مدیریت بازی =====
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
        case 'list':
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

        // ===== اکشن‌های نقش =====
        case 'startvote':
            startVoting($chat_id, $user_id, $param);
            break;

        case 'vote':
            handleVote($callback_id, $chat_id, $message_id, $user_id, $param);
            break;

        // نقش‌های مختلف
        case 'seer':
        case 'doctor':
        case 'hunter':
        case 'guard':
        case 'cultist':
        case 'serial':
        case 'killer':
        case 'fool':
        case 'tanner':
        case 'detective':
        case 'gunner':
        case 'mayor':
        case 'prince':
        case 'harlot':
        case 'blacksmith':
        case 'sandman':
            handleRoleAction($action, $callback_id, $chat_id, $message_id, $user_id, $param);
            break;

        case 'werewolf':
        case 'alpha':
        case 'wolfcub':
            handleWolfAction($callback_id, $chat_id, $message_id, $user_id, $param);
            break;

        case 'cupid':
            handleCupidAction($callback_id, $chat_id, $message_id, $user_id, $param);
            break;

        // رد کردن
        case 'skip':
            $game = getPlayerActiveGame($user_id);
            if ($game) {
                foreach ($game['players'] as $p) {
                    if ($p['id'] == $user_id) {
                        handleRoleAction($p['role'], $callback_id, $chat_id, $message_id, $user_id, 'skip');
                        break;
                    }
                }
            }
            break;

        default:
            answerCallbackQuery($callback_id, "❓ دستور نامشخص!", true);
    }
}

/**
 * 🎮 پنل مدیریت بازی
 */
function showGameManagePanel($chat_id, $message_id, $code) {
    $game = getGame($code);
    if (!$game) {
        editMessageText($chat_id, $message_id, "❌ بازی یافت نشد!");
        return;
    }

    $msg = "🎮 <b>مدیریت بازی</b> - کد: <code>" . $code . "</code>\n\n";
    $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر\n";
    
    // نمایش زمان باقیمانده اگه در انتظار باشه
    if ($game['status'] == 'waiting' && isset($game['wait_until'])) {
        $remaining = max(0, $game['wait_until'] - time());
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $msg .= "⏱ زمان باقیمانده جوین: " . $minutes . ":" . sprintf("%02d", $seconds) . "\n";
        $msg .= "🔄 تمدیدهای استفاده شده: " . ($game['extend_count'] ?? 0) . "/3\n";
        
        // نمایش تایم تنظیم شده
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

/**
 * 🏠 ویرایش به منوی اصلی
 */
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

/**
 * 📜 ویرایش به قوانین
 */
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

/**
 * 🎭 ویرایش به نقش‌ها
 */
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

/**
 * ❓ ویرایش به راهنما
 */
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

/**
 * 📊 ویرایش به آمار
 */
function editToStats($chat_id, $message_id, $user_id) {
    $stats = getGameStats();
    
    $msg =  "📊 <b>آمار " . BOT_NAME . "</b>\n\n";
    $msg .= "🎮 کل بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "⏳ در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "▶️ در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "🏁 تمام شده: " . $stats['ended'] . "\n";
    $msg .= "📅 امروز: " . $stats['today'] . "\n\n";
    
    if ($user_id == ADMIN_ID) {
        $msg .= "🔧 <b>پنل ادمین فعال</b>";
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

/**
 * 📋 وضعیت بازی کاربر
 */
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
        
        // پیدا کردن نقش کاربر
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                if ($p['alive']) {
                    $role = RoleFactory::create($p['role'], $p, $game);
                    $msg .= "🎭 نقش شما: " . $role->getEmoji() . " " . $role->getName() . "\n";
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

/**
 * 🎯 پردازش اکشن نقش
 */
function handleRoleAction($roleType, $callback_id, $chat_id, $message_id, $user_id, $target) {
    // پیدا کردن بازی فعال کاربر
    $game = getPlayerActiveGame($user_id);
    
    if (!$game) {
        answerCallbackQuery($callback_id, "❌ شما در بازی نیستید!", true);
        return;
    }
    
    if ($game['status'] != 'started') {
        answerCallbackQuery($callback_id, "⏳ بازی هنوز شروع نشده!", true);
        return;
    }
    
    if ($game['phase'] != 'night') {
        answerCallbackQuery($callback_id, "☀️ الان شب نیست!", true);
        return;
    }
    
    // پیدا کردن بازیکن
    $player = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            $player = $p;
            break;
        }
    }
    
    if (!$player || !($player['alive'] ?? false)) {
        answerCallbackQuery($callback_id, "💀 شما مرده‌اید!", true);
        return;
    }
    
    // بررسی نقش
    if ($player['role'] != $roleType && $player['original_role'] != $roleType) {
        answerCallbackQuery($callback_id, "❌ شما این نقش رو ندارید!", true);
        return;
    }
    
    // اجرای اکشن
    $role = RoleFactory::create($player['role'], $player, $game);
    $result = $role->performNightAction($target == 'skip' ? null : $target);
    
    if ($result['success']) {
        // آپدیت بازی
        $game = getGame($game['code']);
        
        $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
        $msg .= $result['message'];
        
        // اگه اجماع لازم نیست یا اکشن کامل شده
        if (!isset($result['consensus']) || $result['consensus']) {
            $msg .= "\n\n✅ انتخاب شما ثبت شد. منتظر بقیه بمانید...";
            editMessageText($chat_id, $message_id, $msg, [
                'inline_keyboard' => [
                    [['text' => '🔄 تغییر انتخاب', 'callback_data' => $roleType . '_menu']]
                ]
            ]);
        } else {
            // منتظر بقیه گرگ‌ها
            editMessageText($chat_id, $message_id, $msg);
        }
        
        answerCallbackQuery($callback_id, "✅ ثبت شد!");
    } else {
        answerCallbackQuery($callback_id, $result['message'], true);
    }
}

/**
 * 🐺 پردازش اکشن گرگ
 */
function handleWolfAction($callback_id, $chat_id, $message_id, $user_id, $target) {
    $game = getPlayerActiveGame($user_id);
    
    if (!$game) {
        answerCallbackQuery($callback_id, "❌ شما در بازی نیستید!", true);
        return;
    }
    
    if ($game['phase'] != 'night') {
        answerCallbackQuery($callback_id, "☀️ الان شب نیست!", true);
        return;
    }
    
    // پیدا کردن گرگ
    $player = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            $player = $p;
            break;
        }
    }
    
    if (!$player || !($player['alive'] ?? false)) {
        answerCallbackQuery($callback_id, "💀 شما مرده‌اید!", true);
        return;
    }
    
    if (!in_array($player['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan'])) {
        answerCallbackQuery($callback_id, "❌ شما گرگ نیستید!", true);
        return;
    }
    
    // اجرای اکشن
    $role = RoleFactory::create($player['role'], $player, $game);
    $result = $role->performNightAction($target == 'skip' ? null : $target);
    
    if ($result['success']) {
        $game = getGame($game['code']);
        
        if ($result['consensus'] ?? false) {
            // اجماع حاصل شد
            editMessageText($chat_id, $message_id, 
                "🐺 <b>شب " . $game['night_count'] . "</b>\n\n" .
                "✅ همه گرگ‌ها موافقن!\n" .
                "🎯 هدف: " . $result['target_name'] . "\n\n" .
                "منتظر پایان شب..."
            );
        } else {
            // منتظر بقیه گرگ‌ها
            $team = $role->getWolfTeam();
            $msg = "🐺 <b>شب " . $game['night_count'] . "</b>\n\n";
            $msg .= "🗳️ نظر شما ثبت شد.\n";
            $msg .= "⏳ در انتظار بقیه گرگ‌ها...\n\n";
            if (!empty($team)) {
                $msg .= "👥 بقیه گرگ‌ها: " . implode(', ', $team);
            }
            
            editMessageText($chat_id, $message_id, $msg, [
                'inline_keyboard' => [
                    [['text' => '🔄 تغییر نظر', 'callback_data' => 'werewolf_menu']]
                ]
            ]);
        }
        
        answerCallbackQuery($callback_id, "✅ ثبت شد!");
    } else {
        answerCallbackQuery($callback_id, $result['message'], true);
    }
}

/**
 * 💘 پردازش اکشن الهه عشق
 */
function handleCupidAction($callback_id, $chat_id, $message_id, $user_id, $target) {
    $game = getPlayerActiveGame($user_id);
    
    if (!$game || $game['phase'] != 'night' || $game['night_count'] != 1) {
        answerCallbackQuery($callback_id, "❌ الان نمی‌تونی این کار رو بکنی!", true);
        return;
    }
    
    $player = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            $player = $p;
            break;
        }
    }
    
    if (!$player || $player['role'] != 'cupid') {
        answerCallbackQuery($callback_id, "❌ شما الهه عشق نیستید!", true);
        return;
    }
    
    $role = RoleFactory::create('cupid', $player, $game);
    $result = $role->performNightAction($target);
    
    if ($result['success']) {
        if ($result['need_second'] ?? false) {
            // نیاز به انتخاب دوم
            editMessageText($chat_id, $message_id,
                "💘 <b>الهه عشق</b>\n\n" .
                "✅ اولین عاشق انتخاب شد!\n\n" .
                "👇 حالا نفر دوم رو انتخاب کن:",
                buildTargetKeyboard($game, $user_id, 'cupid')
            );
        } else {
            // تکمیل شد
            editMessageText($chat_id, $message_id,
                "💘 <b>الهه عشق</b>\n\n" .
                "✅ عاشق‌ها انتخاب شدند!\n" .
                "💕 " . $result['lovers'][0] . " و " . $result['lovers'][1] . "\n\n" .
                "منتظر پایان شب..."
            );
        }
        answerCallbackQuery($callback_id, "✅ ثبت شد!");
    } else {
        answerCallbackQuery($callback_id, $result['message'], true);
    }
}

/**
 * 🗳️ پردازش رأی
 */
function handleVote($callback_id, $chat_id, $message_id, $user_id, $target) {
    $game = getPlayerActiveGame($user_id);
    
    if (!$game || $game['phase'] != 'vote') {
        answerCallbackQuery($callback_id, "⏳ الان زمان رأی نیست!", true);
        return;
    }
    
    // بررسی زنده بودن
    $voter = null;
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            $voter = $p;
            break;
        }
    }
    
    if (!$voter || !($voter['alive'] ?? false)) {
        answerCallbackQuery($callback_id, "💀 شما مرده‌اید!", true);
        return;
    }
    
    // ثبت رأی
    if (!isset($game['votes'])) {
        $game['votes'] = [];
    }
    $game['votes'][$user_id] = $target;
    saveGame($game);
    
    // نمایش تعداد رأی‌ها
    $voteCount = count($game['votes']);
    $aliveCount = count(array_filter($game['players'], function($p) {
        return $p['alive'] ?? false;
    }));
    
    answerCallbackQuery($callback_id, "🗳️ رأی شما ثبت شد! ($voteCount / $aliveCount)", false);
    
    // آپدیت پیام
    editMessageText($chat_id, $message_id,
        "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n" .
        "👇 یک نفر رو برای اعدام انتخاب کن:\n\n" .
        "📊 رأی داده شده: $voteCount / $aliveCount",
        buildVoteKeyboard($game, $user_id)
    );
    
    // بررسی تکمیل رأی‌گیری
    if ($voteCount >= $aliveCount) {
        // پردازش نتیجه رأی
        processVoteResult($game);
    }
}

/**
 * 🛠️ توابع کمکی
 */

/**
 * ساخت کیبورد اهداف
 */
function buildTargetKeyboard($game, $user_id, $prefix) {
    $buttons = [];
    
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) continue;
        if (!($p['alive'] ?? false)) continue;
        
        $buttons[] = [
            'text' => $p['name'],
            'callback_data' => $prefix . '_' . $p['id']
        ];
    }
    
    return ['inline_keyboard' => array_chunk($buttons, 2)];
}

/**
 * ساخت کیبورد رأی
 */
function buildVoteKeyboard($game, $user_id) {
    $buttons = [];
    
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) continue;
        if (!($p['alive'] ?? false)) continue;
        
        // شمارش رأی‌های این شخص
        $votes = count(array_filter($game['votes'] ?? [], function($v) use ($p) {
            return $v == $p['id'];
        }));
        
        $buttons[] = [
            'text' => $p['name'] . " ($votes)",
            'callback_data' => 'vote_' . $p['id']
        ];
    }
    
    return ['inline_keyboard' => array_chunk($buttons, 2)];
}

/**
 * پردازش نتیجه رأی
 */
function processVoteResult($game) {
    // شمارش رأی‌ها
    $counts = [];
    foreach ($game['votes'] as $voter => $target) {
        $counts[$target] = ($counts[$target] ?? 0) + 1;
    }
    
    // پیدا کردن بیشترین رأی
    arsort($counts);
    $max = reset($counts);
    $targets = array_keys($counts, $max);
    
    $msg = "🗳️ <b>نتیجه رأی‌گیری</b>\n\n";
    
    if (count($targets) == 1) {
        // یک نفر بیشترین رأی رو داره
        $targetId = $targets[0];
        $targetPlayer = null;
        foreach ($game['players'] as $p) {
            if ($p['id'] == $targetId) {
                $targetPlayer = $p;
                break;
            }
        }
        
        $game = killPlayer($game, $targetId, 'lynch');
        $role = getRoleDisplayName($targetPlayer['role']);
        
        $msg .= "💀 <b>" . $targetPlayer['name'] . "</b> اعدام شد!\n";
        $msg .= "🎭 نقشش: $role\n\n";
        
        // بررسی منافق
        if ($targetPlayer['role'] == 'tanner') {
            $msg .= "🎉 <b>منافق اعدام شد و برنده بازی شد!</b>";
            endGame($game, ['ended' => true, 'winner' => 'tanner', 'message' => $msg]);
            return;
        }
    } else {
        // مساوی شد
        $msg .= "⚖️ <b>رأی‌ها مساوی شد!</b>\n";
        $msg .= "هیچ‌کس اعدام نخواهد شد.";
    }
    
    sendMessage($game['group_id'], $msg);
    
    // بررسی پایان بازی
    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }
    
    // شروع شب بعد
    $game['night_count']++;
    $game['phase'] = 'night';
    $game['votes'] = [];
    saveGame($game);
    
    startNightPhase($game);
}

/**
 * گرفتن متن وضعیت
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
 * پیدا کردن بازی فعال بازیکن
 */
function getPlayerActiveGame($user_id) {
    $games = getAllGames();
    foreach ($games as $game) {
        if (in_array($game['status'], ['waiting', 'started'])) {
            foreach ($game['players'] as $p) {
                if ($p['id'] == $user_id) {
                    return $game;
                }
            }
        }
    }
    return null;
}

/**
 * گرفتن آمار بازی
 */
function getGameStats() {
    $games = getAllGames();
    $today = strtotime('today');
    
    return [
        'total' => count($games),
        'waiting' => count(array_filter($games, fn($g) => $g['status'] == 'waiting')),
        'started' => count(array_filter($games, fn($g) => $g['status'] == 'started')),
        'ended' => count(array_filter($games, fn($g) => $g['status'] == 'ended')),
        'today' => count(array_filter($games, fn($g) => ($g['created'] ?? 0) > $today))
    ];
}

/**
 * 🔧 پنل ادمین
 */
function showAdminPanel($chat_id) {
    $stats = getGameStats();
    
    $msg = "🔧 <b>پنل مدیریت</b>\n\n";
    $msg .= "📊 آمار کلی:\n";
    $msg .= "• کل بازی‌ها: " . $stats['total'] . "\n";
    $msg .= "• در انتظار: " . $stats['waiting'] . "\n";
    $msg .= "• در حال اجرا: " . $stats['started'] . "\n";
    $msg .= "• تمام شده: " . $stats['ended'] . "\n\n";
    
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

/**
 * 📢 ارسال پیام همگانی
 */
function broadcastMessage($text) {
    // این تابع باید لیست کاربران رو از دیتابیس بگیره
    // فعلاً فقط به ادمین می‌گه
    sendMessage(ADMIN_ID, "📢 پیام برای ارسال:\n\n$text\n\n⚠️ این قسمت نیاز به دیتابیس کاربران داره!");
}

/**
 * 🗑️ پاک کردن پیام
 */
function deleteMessage($chat_id, $message_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    return apiRequest($url, $data);
}

/**
 * ✏️ ویرایش پیام
 */
function editMessage($chat_id, $message_id, $text, $keyboard = null) {
    return editMessageText($chat_id, $message_id, $text, $keyboard);
}

/**
 * 🗳️ شروع رأی‌گیری
 */
function startVoting($chat_id, $user_id, $game_code) {
    $game = getGame($game_code);
    
    if (!$game || $game['status'] != 'started') {
        sendMessage($chat_id, "❌ بازی فعال نیست!");
        return;
    }
    
    if ($game['phase'] != 'day') {
        sendMessage($chat_id, "⏳ الان زمان رأی‌گیری نیست!");
        return;
    }
    
    // فقط سازنده یا ادمین
    if ($user_id != $game['creator_id'] && $user_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ فقط سازنده می‌تونه رأی‌گیری رو شروع کنه!");
        return;
    }
    
    $game['phase'] = 'vote';
    $game['votes'] = [];
    saveGame($game);
    
    $alive = count(array_filter($game['players'], fn($p) => $p['alive'] ?? false));
    $voteDuration = $game['settings']['vote_duration'] ?? 60;
    
    $groupMsg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . " شروع شد!</b>\n\n";
    $groupMsg .= "⏱ <b>" . $voteDuration . " ثانیه</b> وقت دارید\n";
    $groupMsg .= "👥 بازیکنان زنده: " . $alive . " نفر\n\n";
    $groupMsg .= "📩 <b>به صورت خصوصی به ربات پیام بدید و رأی بدید!</b>";
    
    sendMessage($game['group_id'], $groupMsg);
    
    // ارسال پنل رأی
    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;
        sendPrivateVotePanel($player, $game);
    }
    
    // تایمر برای پایان رأی‌گیری
    // در عمل با cron یا job queue
}

/**
 * 📨 ارسال پنل رأی خصوصی
 */
function sendPrivateVotePanel($player, $game) {
    $msg = "🗳️ <b>رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    $msg .= "👇 یک نفر رو برای <b>اعدام</b> انتخاب کن:";
    
    $buttons = [];
    foreach ($game['players'] as $p) {
        if ($p['id'] == $player['id']) continue;
        if (!($p['alive'] ?? false)) continue;
        
        $buttons[] = [
            'text' => $p['name'],
            'callback_data' => 'vote_' . $p['id']
        ];
    }
    
    $keyboard = array_chunk($buttons, 2);
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

/**
 * 📨 ارسال پیام شب به گروه
 */
function sendNightAnnouncement($game) {
    $aliveWolves = count(array_filter($game['players'], function($p) {
        return ($p['alive'] ?? false) && in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan']);
    }));
    
    $msg = "🌙 <b>شب " . $game['night_count'] . " فرا رسید!</b>\n\n";
    
    if ($aliveWolves > 0) {
        $msg .= "🐺 گرگینه‌ها بیدار شوید و یکی را انتخاب کنید...\n";
    }
    $msg .= "🔮 دیده‌بان بیدار شو...\n";
    $msg .= "👨‍⚕️ دکتر بیدار شو...\n";
    $msg .= "💂🏻‍♂️ شکارچی بیدار شو...\n";
    $msg .= "👼🏻 فرشته نگهبان بیدار شو...\n\n";
    $msg .= "⏱ " . NIGHT_DURATION . " ثانیه تا صبح";
    
    sendMessage($game['group_id'], $msg);
}

/**
 * ☀️ ارسال پیام روز به گروه
 */
function sendDayAnnouncement($game, $nightResults) {
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    
    if (!empty($nightResults['deaths'])) {
        $msg .= "💀 <b>دیشب این افراد کشته شدند:</b>\n";
        foreach ($nightResults['deaths'] as $death) {
            $msg .= "• <b>" . $death['name'] . "</b> - " . $death['role'] . "\n";
        }
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>";
    }
    
    if (!empty($nightResults['messages'])) {
        $msg .= "\n📝 <b>اتفاقات:</b>\n";
        foreach ($nightResults['messages'] as $m) {
            $msg .= "• $m\n";
        }
    }
    
    sendMessage($game['group_id'], $msg);
    
    // لیست زنده‌ها
    $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    $aliveMsg = "👥 <b>بازیکنان زنده (" . count($alive) . "):</b>\n";
    foreach ($alive as $p) {
        $aliveMsg .= "• " . $p['name'] . "\n";
    }
    sendMessage($game['group_id'], $aliveMsg);
    
    // شروع بحث
    $dayMsg = "🗣 <b>زمان بحث!</b>\n";
    $dayMsg .= "شما " . DAY_DURATION . " ثانیه وقت دارید بحث کنید.\n";
    $dayMsg .= "بعدش رأی‌گیری شروع می‌شه!";
    
    sendMessage($game['group_id'], $dayMsg, [
        'inline_keyboard' => [
            [['text' => '🗳️ شروع رأی‌گیری (فقط سازنده)', 'callback_data' => 'startvote_' . $game['code']]]
        ]
    ]);
}

/**
 * دستور /sponsers
 */
function cmdSponsers($chat_id) {
    $msg = "🤝 <b>اسپانسرها و حامیان</b>\n\n";
    $msg .= "از حمایت شما متشکریم!";
    sendMessage($chat_id, $msg);
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
    
    // چک کردن زندانی
    if (!empty($player['imprisoned'])) {
        return ['success' => false, 'message' => '🔒 شما زندانی کلانتر هستید! نمی‌توانید چت کنید.'];
    }
    
    $role = RoleFactory::create($player['role'], $player, $game);
    $team = $role->getTeam();
    
    // فقط تیم‌های منفی
    $evilTeams = ['werewolf', 'cult', 'vampire', 'killer', 'fire_ice', 'black_knight', 'joker'];
    if (!in_array($team, $evilTeams)) {
        return ['success' => false, 'message' => '❌ تیم شما به چت تیمی دسترسی ندارد!'];
    }
    
    // پیدا کردن هم‌تیمی‌ها
    $teamMates = [];
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) continue;
        if (!($p['alive'] ?? false)) continue;
        if (!empty($p['imprisoned'])) continue;
        
        $mateRole = RoleFactory::create($p['role'], $p, $game);
        $mateTeam = $mateRole->getTeam();
        
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
    
    // ارسال پیام
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