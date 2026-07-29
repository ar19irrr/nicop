<?php
// index.php - نسخه نهایی با همه قوانین نقش‌ها

// ============================================================
// 1. تنظیمات اولیه
// ============================================================

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';
$admin_id = 1095925103;

// ============================================================
// 2. لود کردن سیستم نقش‌ها
// ============================================================

$roles_path = __DIR__ . '/ROLES_PATCH/';
if (is_dir($roles_path) && file_exists($roles_path . 'factory.php')) {
    require_once $roles_path . 'factory.php';
    require_once $roles_path . 'base.php';
}

// ============================================================
// 3. دیتابیس
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
// 4. سیستم بالانس با قوانین خاص
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    
    // ===== نقش‌های الزامی بر اساس تعداد =====
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
    
    // ===== ۱۹+ نفر: نقش‌های پیشرفته با قوانین خاص =====
    $wolf_count = round($count * 0.2);
    $special_count = round($count * 0.15);
    
    // نقش‌های ویژه موجود
    $available_special = ['seer', 'guardian_angel', 'hunter', 'detective', 'knight', 
                          'cupid', 'beholder', 'phoenix', 'huntsman', 'trouble'];
    
    shuffle($available_special);
    $special = array_slice($available_special, 0, $special_count);
    
    // ===== قوانین خاص برای نقش‌های ویژه =====
    $all_roles = [
        'villager', 'werewolf', 'seer', 'guardian_angel', 'hunter'
    ];
    
    // اگه کلانتر هست، ومپایر اصیل هم باشه
    if (in_array('hunter', $special) && $count >= 15) {
        $special[] = 'bloodthirsty';
        $all_roles[] = 'bloodthirsty';
    }
    
    // اگه فرقه هست، شکارچی هم باشه
    if (rand(1, 100) <= 40 && $count >= 15) {
        $special[] = 'cultist';
        $special[] = 'cult_hunter';
        $all_roles[] = 'cultist';
        $all_roles[] = 'cult_hunter';
    }
    
    // قاتل زنجیره‌ای برای بازی‌های بزرگ
    if ($count >= 20) {
        $special[] = 'serial_killer';
        $all_roles[] = 'serial_killer';
        if ($count >= 25) {
            $special[] = 'archer';
            $all_roles[] = 'archer';
        }
    }
    
    // ومپایرها برای بازی‌های بزرگ
    if ($count >= 22 && !in_array('bloodthirsty', $special)) {
        $special[] = 'vampire';
        $all_roles[] = 'vampire';
    }
    
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
// 5. توابع اصلی بازی
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

function getRoleDescription($role) {
    $desc = [
        'villager' => '👨‍🌾 شما یک روستایی ساده هستید.',
        'seer' => '👳🏻‍♂️ شما پیشگو هستید! هر شب نقش یک نفر را می‌بینید.',
        'werewolf' => '🐺 شما گرگینه هستید! هر شب یک نفر را می‌خورید.',
        'guardian_angel' => '👼🏻 شما فرشته نگهبان هستید! هر شب از یک نفر محافظت می‌کنید.',
        'hunter' => '👮🏻‍♂️ شما کلانتر هستید! ومپایر اصیل زیرزمین خونه‌تون زندانی شده.',
        'bloodthirsty' => '🧛🏻‍♀️ شما ومپایر اصیل هستید! توسط کلانتر زندانی شدید.',
        'cultist' => '👤 شما فرقه‌گرا هستید! هر شب یک نفر رو دعوت می‌کنید.',
        'cult_hunter' => '💂🏻‍♂️ شما شکارچی فرقه هستید! فرقه‌ها رو شکار می‌کنید.',
        'serial_killer' => '🔪 شما قاتل زنجیره‌ای هستید! هر شب یک نفر رو می‌کشید.',
        'archer' => '🏹 شما کماندار هستید! هر ۲ شب یکبار تیراندازی می‌کنید.',
        'vampire' => '🧛🏻‍♂️ شما ومپایر هستید! هر شب به یک نفر حمله می‌کنید.',
        'phoenix' => '🪶 شما ققنوس هستید! شب‌های ۳ و ۵ می‌تونید اشک بدید.',
        'fire_king' => '🔥🤴🏻 شما پادشاه آتش هستید! هر شب نفت می‌پاشید و بعدش آتش می‌زنید.',
        'ice_queen' => '❄️👸🏻 شما ملکه یخی هستید! هر شب یک نفر رو منجمد می‌کنید.',
    ];
    return $desc[$role] ?? '🎭 شما ' . getRoleDisplayName($role) . ' هستید!';
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
// 6. پنل شب (با همه قوانین خاص)
// ============================================================

function sendNightPanel($player, $game) {
    $role = $player['role'];
    $night_count = $game['night_count'] ?? 1;
    
    // ===== لیست کامل نقش‌های شب‌کار =====
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
    
    // ===== قوانین خاص =====
    
    // 1. ققنوس: فقط شب‌های ۳ و ۵
    if ($role == 'phoenix' && !in_array($night_count, [3, 5])) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 ققنوس فقط شب‌های ۳ و ۵ می‌تونه اشک بده.");
        return;
    }
    
    // 2. ققنوس: حداکثر ۲ اشک
    if ($role == 'phoenix') {
        $used_tears = $player['role_data']['tears_used'] ?? 0;
        if ($used_tears >= 2) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 اشک‌هات تموم شده!");
            return;
        }
    }
    
    // 3. ومپایر اصیل: زندانی
    if ($role == 'bloodthirsty') {
        $is_free = $player['role_data']['is_free'] ?? false;
        if (!$is_free) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⛓️ شما توسط کلانتر زندانی هستید!\nمنتظر باشید ومپایرها شما رو آزاد کنن.");
            return;
        }
    }
    
    // 4. پادشاه آتش
    if ($role == 'fire_king') {
        $oiled_houses = $player['role_data']['oiled_houses'] ?? [];
        $detonated = $player['role_data']['detonated'] ?? false;
        
        // اگه قبلاً آتش زده، دیگه نمی‌تونه
        if ($detonated) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 قبلاً آتش زدی!");
            return;
        }
        
        // اگه نفت پاشی کرده و شب دوم هست، می‌تونه آتش بزنه
        if (!empty($oiled_houses) && $night_count >= 2) {
            // گزینه آتش زدن و نفت پاشی جدید
            $targets = getFireKingTargets($player, $game);
            if (empty($targets)) {
                sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⏳ هیچ هدفی برای نفت پاشی یا آتش زدن نیست!");
                return;
            }
            sendFireKingPanel($player, $game, $targets);
            return;
        }
        
        // شب اول یا بعد از آتش زدن: فقط نفت پاشی
        $targets = getValidNightTargets($role, $game, $player['id']);
        if (empty($targets)) {
            sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
            return;
        }
        sendSimpleNightPanel($player, $game, $targets, $role);
        return;
    }
    
    // 5. نقش‌های معمولی
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n💤 تو می‌تونی بخوابی...");
        return;
    }
    
    $targets = getValidNightTargets($role, $game, $player['id']);
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $night_count . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
        return;
    }
    
    sendSimpleNightPanel($player, $game, $targets, $role);
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
        // گرگ‌ها نمی‌تونن به گرگ دیگه حمله کنن
        if (in_array($role, $wolfRoles) && in_array($p['role'], $wolfRoles)) continue;
        
        // ومپایرها نمی‌تونن به ومپایر دیگه حمله کنن
        if (in_array($role, $vampireRoles) && in_array($p['role'], $vampireRoles)) continue;
        
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    
    return $targets;
}

function sendSimpleNightPanel($player, $game, $targets, $role) {
    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
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

// ===== پنل مخصوص پادشاه آتش =====
function sendFireKingPanel($player, $game, $targets) {
    $oiled_houses = $player['role_data']['oiled_houses'] ?? [];
    $msg = "🔥 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: پادشاه آشت 🔥🤴🏻\n\n";
    
    if (!empty($oiled_houses)) {
        $msg .= "💥 خونه‌های نفتی: " . count($oiled_houses) . " خونه\n";
        $msg .= "🔥 می‌تونی همه رو آتش بزنی!\n\n";
    }
    
    $msg .= "👇 انتخاب کن:";
    
    $keyboard = [];
    
    // دکمه آتش زدن
    if (!empty($oiled_houses)) {
        $keyboard[] = [['text' => '💥 آتش زدن همه خونه‌های نفتی', 'callback_data' => 'night_fireking_detonate']];
    }
    
    // دکمه‌های نفت پاشی
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

function getFireKingTargets($player, $game) {
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
    });
    
    foreach ($alivePlayers as $p) {
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    return $targets;
}

// ============================================================
// 7. پردازش شب
// ============================================================

function processNight($game_code, $game) {
    $deaths = [];
    $protected = [];
    $seer_results = [];
    $fire_deaths = [];
    
    // ===== پردازش اکشن‌های شب =====
    foreach ($game['night_actions'] as $action) {
        $role = $action['role'];
        $target = $action['target'];
        $player = $action['player'];
        
        // ===== فرشته نگهبان - محافظت =====
        if ($role == 'guardian_angel') {
            $protected[] = $target;
            continue;
        }
        
        // ===== پادشاه آتش - نفت پاشی =====
        if ($role == 'fireking_oil') {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player) {
                    if (!isset($p['role_data']['oiled_houses'])) {
                        $p['role_data']['oiled_houses'] = [];
                    }
                    if (!in_array($target, $p['role_data']['oiled_houses'])) {
                        $p['role_data']['oiled_houses'][] = $target;
                    }
                    break;
                }
            }
            continue;
        }
        
        // ===== پادشاه آتش - آتش زدن =====
        if ($role == 'fireking_detonate') {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player) {
                    $oiled = $p['role_data']['oiled_houses'] ?? [];
                    foreach ($oiled as $house_id) {
                        $target_player = getPlayerById($game, $house_id);
                        if ($target_player && ($target_player['alive'] ?? false)) {
                            if (!in_array($house_id, $protected)) {
                                $game = killPlayer($game, $house_id, 'fire');
                                $fire_deaths[] = $target_player['name'];
                            }
                        }
                    }
                    $p['role_data']['detonated'] = true;
                    $p['role_data']['oiled_houses'] = [];
                    break;
                }
            }
            continue;
        }
        
        // ===== گرگ‌ها - حمله =====
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
        
        // ===== قاتل - کشتن =====
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
        
        // ===== ومپایرها - حمله =====
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
        
        // ===== پیشگو - دیدن نقش =====
        if ($role == 'seer' && $target != 'skip') {
            $target_player = getPlayerById($game, $target);
            if ($target_player && ($target_player['alive'] ?? false)) {
                $seer_results[] = [
                    'player' => $player,
                    'target' => $target_player['name'],
                    'role' => getRoleDisplayName($target_player['role'])
                ];
            }
            continue;
        }
        
        // ===== کاراگاه - تحقیق =====
        if ($role == 'detective' && $target != 'skip') {
            $target_player = getPlayerById($game, $target);
            if ($target_player && ($target_player['alive'] ?? false)) {
                $can_kill = in_array($target_player['role'], ['serial_killer', 'werewolf', 'vampire', 'cultist', 'alpha_wolf']);
                sendPrivateMessage($player, "🕵️‍♂️ تحقیق شما:\n" . $target_player['name'] . " " . ($can_kill ? "🔪 توانایی کشتن دارد!" : "✅ توانایی کشتن ندارد!"));
            }
            continue;
        }
    }
    
    // ===== ارسال نتیجه پیشگو =====
    foreach ($seer_results as $result) {
        sendPrivateMessage($result['player'], "🔮 نقش " . $result['target'] . ": " . $result['role']);
    }
    
    // ===== اضافه کردن تلفات آتش =====
    $deaths = array_merge($deaths, $fire_deaths);
    
    // ===== شروع روز =====
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
    
    // ===== پیام روز =====
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    
    if (!empty($deaths)) {
        $msg .= "💀 <b>کشته شدگان شب:</b>\n";
        foreach ($deaths as $name) {
            $msg .= "• $name\n";
        }
        $msg .= "\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    
    $alive = getAlivePlayers($game);
    $msg .= "👥 <b>بازیکنان زنده (" . count($alive) . "):</b>\n";
    foreach ($alive as $p) {
        $msg .= "• " . $p['name'] . "\n";
    }
    $msg .= "\n";
    
    $msg .= "🗣️ <b>زمان بحث!</b>\n⏱ " . $game['day_duration'] . " ثانیه وقت دارید.\nبعدش رأی‌گیری شروع می‌شه.";
    
    sendMessage($game['group_id'], $msg);
}

// ============================================================
// 8. سیستم رأی‌گیری با AFK
// ============================================================

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
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    
    sendMessage($game['group_id'], "🗳️ <b>زمان رأی‌گیری روز " . $game['day_count'] . "!</b>\n⏱ {$game['vote_duration']} ثانیه وقت دارید.");
    
    foreach ($alivePlayers as $p) {
        sendVotePanel($p, $game);
    }
}

function sendVotePanel($player, $game) {
    $alivePlayers = getAlivePlayers($game);
    $alivePlayers = array_filter($alivePlayers, function($p) use ($player) {
        return $p['id'] != $player['id'];
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
    $alivePlayers = getAlivePlayers($game);
    $afk_players = [];
    
    // ===== بررسی AFK =====
    foreach ($alivePlayers as $p) {
        if (!isset($votes[$p['id']])) {
            foreach ($game['players'] as &$player) {
                if ($player['id'] == $p['id']) {
                    $player['afk_count'] = ($player['afk_count'] ?? 0) + 1;
                    if ($player['afk_count'] >= 2) {
                        $afk_players[] = $player;
                    }
                    break;
                }
            }
        }
    }
    
    // ===== حذف AFK =====
    foreach ($afk_players as $afk) {
        $game = killPlayer($game, $afk['id'], 'afk');
        sendMessage($game['group_id'], "😴 <b>" . $afk['name'] . "</b> به خاطر غیرفعالی از بازی حذف شد!");
    }
    
    // ===== شمارش آرا =====
    $counts = [];
    $skipCount = 0;
    
    foreach ($votes as $voter_id => $target_id) {
        if ($target_id == 'skip') {
            $skipCount++;
        } else {
            $counts[$target_id] = ($counts[$target_id] ?? 0) + 1;
        }
    }
    
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    
    // ===== نتیجه =====
    $msg = "🗳️ <b>نتیجه رأی‌گیری روز " . $game['day_count'] . "</b>\n\n";
    $msg .= "📊 آرا: " . count($votes) . " | سفید: $skipCount\n";
    if (!empty($afk_players)) {
        $msg .= "💀 حذف شدگان: " . count($afk_players) . "\n";
    }
    $msg .= "\n";
    
    if ($max > 0 && count($targets) == 1) {
        $target_id = $targets[0];
        foreach ($game['players'] as &$p) {
            if ($p['id'] == $target_id) {
                $p['alive'] = false;
                $role_name = getRoleDisplayName($p['role']);
                $msg .= "💀 <b>" . $p['name'] . "</b> اعدام شد!\n🎭 نقش: " . $role_name;
                break;
            }
        }
    } else {
        $msg .= "⚖️ <b>رأی‌ها مساوی شد! کسی اعدام نشد.</b>";
    }
    
    sendMessage($game['group_id'], $msg);
    
    // ===== بررسی برد =====
    $winCheck = checkWinCondition($game);
    if ($winCheck['ended']) {
        endGame($game, $winCheck);
        return;
    }
    
    // ===== شروع شب بعد =====
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
        if ($p['alive'] ?? false) {
            sendNightPanel($p, $game);
        }
    }
}

// ============================================================
// 9. شرایط برد
// ============================================================

function checkWinCondition($game) {
    $alive = getAlivePlayers($game);
    $totalAlive = count($alive);
    
    if ($totalAlive == 0) {
        return ['ended' => true, 'winner' => 'none', 'message' => '☠️ همه مردند!'];
    }
    
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
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    
    $msg = "🏁 <b>بازی تمام شد!</b>\n\n";
    $msg .= $winCheck['message'] . "\n\n";
    $msg .= "📊 <b>نقش‌ها:</b>\n";
    foreach ($game['players'] as $p) {
        $status = ($p['alive'] ?? false) ? '🟢' : '💀';
        $role = getRoleDisplayName($p['role']);
        $msg .= "$status {$p['name']} - $role\n";
    }
    sendMessage($game['group_id'], $msg);
}

// ============================================================
// 10. چک کردن تایمرها
// ============================================================

function checkGameTimers() {
    $games = loadGames();
    $now = time();
    
    foreach ($games as $code => $game) {
        if ($game['status'] != 'started') continue;
        
        if ($game['phase'] == 'night' && isset($game['night_end_time']) && $now >= $game['night_end_time']) {
            // اسکیپ خودکار
            foreach ($game['players'] as $p) {
                if (!($p['alive'] ?? false)) continue;
                $has_action = false;
                foreach ($game['night_actions'] as $action) {
                    if ($action['player'] == $p['id']) {
                        $has_action = true;
                        break;
                    }
                }
                if (!$has_action) {
                    $game['night_actions'][] = [
                        'player' => $p['id'],
                        'role' => $p['role'],
                        'target' => 'skip'
                    ];
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
// 11. توابع ارسال پیام
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

function createGame($group_id, $creator_id, $creator_name, $mode = 'normal') {
    // ... (همون کد قبلی)
}

function joinGame($code, $user_id, $user_name) {
    // ... (همون کد قبلی)
}

function startGame($group_id, $user_id) {
    // ... (همون کد قبلی که الان کامل شده)
}

function cancelGame($chat_id, $user_id) {
    // ... (همون کد قبلی)
}

function leaveGame($chat_id, $user_id) {
    // ... (همون کد قبلی)
}

function extendWaitingTime($group_id, $user_id) {
    // ... (همون کد قبلی)
}

function isAdmin($user_id, $group_id) {
    // ... (همون کد قبلی)
}

function sendPlayerList($chat_id, $game) {
    // ... (همون کد قبلی)
}

// ============================================================
// 12. پردازش اصلی (بخش کالبک)
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

// ===== پردازش کالبک =====
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    // ===== اکشن شب =====
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
        
        // اسکیپ
        if ($action == 'skip') {
            $game['night_actions'][] = [
                'player' => $user_id,
                'role' => $role,
                'target' => 'skip'
            ];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "⏭️ این شب رو رد کردید!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        // پادشاه آتش - نفت پاشی
        if ($action == 'fireking_oil') {
            $game['night_actions'][] = [
                'player' => $user_id,
                'role' => 'fireking_oil',
                'target' => (int)$target
            ];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "🛢️ نفت پاشی شد!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        // پادشاه آتش - آتش زدن
        if ($action == 'fireking_detonate') {
            $game['night_actions'][] = [
                'player' => $user_id,
                'role' => 'fireking_detonate',
                'target' => 'all'
            ];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "💥 آتش زدن! همه خونه‌های نفتی می‌سوزن!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
        
        // انتخاب معمولی
        if (!empty($target)) {
            $game['night_actions'][] = [
                'player' => $user_id,
                'role' => $role,
                'target' => (int)$target
            ];
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "✅ انتخاب شما ثبت شد!");
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
    }
    
    // ===== رأی‌گیری =====
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
        answerCallbackQuery($callback_id, "✅ رأی شما ثبت شد!");
        
        // بررسی پایان رأی‌گیری
        $alive = getAlivePlayers($game);
        if (count($game['votes']) >= count($alive)) {
            processVotes($game_code, $game);
        }
        
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    // ===== دکمه‌های منو =====
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

// ===== پردازش پیام‌ها =====
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
                [['text' => '▶️ شروع عادی', 'callback_data' => 'start_normal'], ['text' => '💪 شروع قدرتی', 'callback_data' => 'start_mighty']]
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
    
    case '/startmighty':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name, 'mighty');
            if (!$result['success']) sendMessage($chat_id, $result['message']);
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
    
    case '/help':
        $msg = "📚 <b>راهنمای ربات</b>\n\n" .
               "/start - منوی اصلی\n" .
               "/startgame - شروع بازی عادی\n" .
               "/startmighty - شروع بازی قدرتی\n" .
               "/join [کد] - پیوستن\n" .
               "/players - لیست بازیکنان\n" .
               "/stop - لغو بازی\n" .
               "/leave - خروج از بازی\n" .
               "/extend - تمدید زمان (فقط ادمین)\n" .
               "/rules - قوانین\n" .
               "/roles - نقش‌ها\n" .
               "/ping - تست اتصال";
        sendMessage($chat_id, $msg);
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
