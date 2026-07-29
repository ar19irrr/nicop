<?php
// index.php - نسخه کامل با 70+ نقش

$token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
$bot_username = 'Ni_cop_bot';
$data_path = __DIR__ . '/data/';

// ============================================================
// دیتابیس
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

// ============================================================
// بالانس همه نقش‌ها
// ============================================================

function selectBalancedRoles($count) {
    $roles = [];
    
    // لیست کامل نقش‌ها بر اساس تیم
    $villager_roles = ['villager', 'seer', 'apprentice_seer', 'guardian_angel', 'knight', 
                       'hunter', 'harlot', 'builder', 'blacksmith', 'gunner', 'mayor',
                       'prince', 'detective', 'cupid', 'beholder', 'phoenix', 'huntsman',
                       'trouble', 'chemist', 'fool', 'clumsy', 'cursed', 'traitor',
                       'wild_child', 'wise_elder', 'sandman', 'sweetheart', 'ruler',
                       'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong', 'princess',
                       'wolf_man', 'drunk'];
    
    $wolf_roles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                   'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
    
    $vampire_roles = ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'];
    
    $cult_roles = ['cultist', 'royce', 'frankenstein', 'monk_black'];
    
    $killer_roles = ['serial_killer', 'archer', 'davina'];
    
    $fire_ice_roles = ['fire_king', 'ice_queen', 'lilith', 'magento'];
    
    $black_knight_roles = ['black_knight', 'bride_dead'];
    
    $joker_roles = ['joker', 'harly'];
    
    $independent_roles = ['dian', 'dinamit', 'bomber', 'tso', 'tanner', 'lucifer', 'doppelganger'];
    
    // تعیین تعداد نقش‌ها بر اساس تعداد بازیکنان
    if ($count <= 4) {
        // 4 نفر: 1 گرگ + 3 روستایی
        $roles = ['villager', 'villager', 'villager', 'werewolf'];
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 6) {
        // 5-6 نفر: 2 گرگ + بقیه روستایی
        $roles = array_merge(
            array_fill(0, $count - 2, 'villager'),
            ['werewolf', 'werewolf']
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 8) {
        // 7-8 نفر: 2 گرگ + 1 نقش ویژه
        $special = ['seer'];
        $roles = array_merge(
            array_fill(0, $count - 3, 'villager'),
            ['werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 10) {
        // 9-10 نفر: 2 گرگ + 2 نقش ویژه
        $special = ['seer', 'guardian_angel'];
        $roles = array_merge(
            array_fill(0, $count - 4, 'villager'),
            ['werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 14) {
        // 11-14 نفر: 3 گرگ + 3 نقش ویژه
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 3);
        $roles = array_merge(
            array_fill(0, $count - 6, 'villager'),
            ['werewolf', 'werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 20) {
        // 15-20 نفر: 4 گرگ + 4 نقش ویژه
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 4);
        $roles = array_merge(
            array_fill(0, $count - 8, 'villager'),
            ['werewolf', 'werewolf', 'werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    if ($count <= 30) {
        // 21-30 نفر: 5 گرگ + 6 نقش ویژه
        shuffle($villager_roles);
        $special = array_slice($villager_roles, 0, 6);
        $roles = array_merge(
            array_fill(0, $count - 11, 'villager'),
            ['werewolf', 'werewolf', 'werewolf', 'werewolf', 'werewolf'],
            $special
        );
        shuffle($roles);
        return $roles;
    }
    
    // 31+ نفر: ترکیب کامل
    $wolf_count = round($count * 0.2);
    $special_count = round($count * 0.15);
    
    $all_special = array_merge($villager_roles, $cult_roles, $killer_roles, 
                                $vampire_roles, $fire_ice_roles, $black_knight_roles,
                                $joker_roles, $independent_roles);
    shuffle($all_special);
    $special = array_slice($all_special, 0, $special_count);
    
    $roles = array_merge(
        array_fill(0, $count - $wolf_count - count($special), 'villager'),
        array_fill(0, $wolf_count, 'werewolf'),
        $special
    );
    
    shuffle($roles);
    return $roles;
}

// ============================================================
// توابع اصلی بازی
// ============================================================

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function createGame($group_id, $creator_id, $creator_name) {
    $games = loadGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return ['success' => false, 'message' => '⏳ یک بازی فعال در این گروه وجود دارد!'];
        }
    }
    
    $code = generateGameCode();
    $games[$code] = [
        'code' => $code,
        'group_id' => $group_id,
        'creator_id' => $creator_id,
        'creator_name' => $creator_name,
        'players' => [
            ['id' => $creator_id, 'name' => $creator_name, 'alive' => true, 'role' => null]
        ],
        'status' => 'waiting',
        'created' => time(),
        'phase' => null,
        'night_count' => 0,
        'day_count' => 0,
        'night_actions' => [],
        'votes' => [],
        'night_end_time' => 0,
        'day_end_time' => 0,
        'vote_end_time' => 0
    ];
    saveGames($games);
    
    return ['success' => true, 'message' => "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>\n👤 سازنده: $creator_name"];
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
    foreach ($game['players'] as $p) {
        if ($p['id'] == $user_id) {
            return ['success' => false, 'message' => '❌ شما قبلاً در این بازی هستید!'];
        }
    }
    $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true, 'role' => null];
    $games[$code] = $game;
    saveGames($games);
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!\n👥 تعداد: " . count($game['players']) . " نفر"];
}

function getGameInfo($group_id) {
    $games = loadGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
}

function startGame($group_id, $user_id) {
    $games = loadGames();
    $game = null;
    $game_code = null;
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $group_id && $g['status'] == 'waiting') {
            $game = $g;
            $game_code = $code;
            break;
        }
    }
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی فعالی برای شروع وجود ندارد!'];
    }
    if (count($game['players']) < 4) {
        return ['success' => false, 'message' => '❌ حداقل ۴ نفر نیاز است! (' . count($game['players']) . '/4)'];
    }
    
    // تخصیص نقش‌ها با بالانس
    $roles = selectBalancedRoles(count($game['players']));
    shuffle($roles);
    
    foreach ($game['players'] as $i => &$p) {
        $p['role'] = $roles[$i];
    }
    
    $game['status'] = 'started';
    $game['phase'] = 'night';
    $game['night_count'] = 1;
    $game['night_actions'] = [];
    $game['votes'] = [];
    $game['night_end_time'] = time() + 60;
    $games[$game_code] = $game;
    saveGames($games);
    
    // ارسال نقش به هر بازیکن
    foreach ($game['players'] as $p) {
        $role_name = getRoleDisplayName($p['role']);
        $role_desc = getRoleDescription($p['role']);
        sendPrivateMessage($p['id'], "🎭 <b>نقش شما: " . $role_name . "</b>\n\n" . $role_desc . "\n\n🌙 شب اول شروع شد...");
        sendNightPanel($p, $game);
    }
    
    sendMessage($group_id, "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ ۶۰ ثانیه تا صبح");
    
    return ['success' => true, 'message' => "🎮 <b>بازی شروع شد!</b>\n\n👥 " . count($game['players']) . " نفر\n🌙 شب اول..."];
}

function sendNightPanel($player, $game) {
    $role = $player['role'];
    
    // نقش‌هایی که اکشن شب دارن
    $nightRoles = [
        'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
        'seer', 'guardian_angel', 'knight', 'hunter', 'harlot',
        'detective', 'cupid', 'phoenix', 'sandman', 'spy',
        'cultist', 'royce', 'frankenstein', 'monk_black',
        'serial_killer', 'archer', 'davina',
        'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
        'fire_king', 'ice_queen', 'lilith', 'magento',
        'black_knight', 'bride_dead',
        'joker', 'harly',
        'dian', 'dinamit', 'bomber', 'tso', 'lucifer'
    ];
    
    if (!in_array($role, $nightRoles)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n💤 تو می‌تونی بخوابی...");
        return;
    }
    
    $targets = [];
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
    });
    
    // محدودیت‌های هر نقش
    foreach ($alivePlayers as $p) {
        // گرگ‌ها نمی‌تونن به گرگ دیگه حمله کنن
        if (in_array($role, ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                             'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer']) &&
            in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                                  'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            continue;
        }
        $targets[] = ['id' => $p['id'], 'name' => $p['name']];
    }
    
    if (empty($targets)) {
        sendPrivateMessage($player['id'], "🌙 <b>شب " . $game['night_count'] . "</b>\n\n⏳ هیچ هدف معتبری وجود ندارد!");
        return;
    }
    
    $msg = "🌙 <b>شب " . $game['night_count'] . "</b>\n\n";
    $msg .= "🎭 نقش: " . getRoleDisplayName($role) . "\n\n";
    $msg .= getRoleActionDescription($role) . "\n\n";
    $msg .= "👇 یک نفر رو انتخاب کن (یا Skip):";
    
    $keyboard = [];
    $row = [];
    foreach ($targets as $target) {
        $row[] = ['text' => $target['name'], 'callback_data' => $role . '_' . $target['id']];
        if (count($row) == 2) {
            $keyboard[] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard[] = $row;
    }
    // دکمه اسکیپ
    $keyboard[] = [['text' => '⏭️ Skip (رد کردن)', 'callback_data' => $role . '_skip']];
    
    sendPrivateMessage($player['id'], $msg, ['inline_keyboard' => $keyboard]);
}

function processNight($game_code, $game) {
    $deaths = [];
    $protected = [];
    $seer_results = [];
    
    // محافظت فرشته نگهبان
    foreach ($game['night_actions'] as $action) {
        if ($action['role'] == 'guardian_angel') {
            $protected[] = $action['target'];
        }
    }
    
    // حمله گرگ‌ها
    foreach ($game['night_actions'] as $action) {
        if (in_array($action['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                                        'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            $target = $action['target'];
            if (!in_array($target, $protected)) {
                foreach ($game['players'] as &$p) {
                    if ($p['id'] == $target) {
                        $p['alive'] = false;
                        $deaths[] = $p['name'];
                        break;
                    }
                }
            }
        }
    }
    
    // نتیجه پیشگو
    foreach ($game['night_actions'] as $action) {
        if ($action['role'] == 'seer') {
            $target_id = $action['target'];
            foreach ($game['players'] as $p) {
                if ($p['id'] == $target_id) {
                    sendPrivateMessage($action['player'], "🔮 نقش " . $p['name'] . ": " . getRoleDisplayName($p['role']));
                    break;
                }
            }
        }
    }
    
    $game['phase'] = 'day';
    $game['day_count'] = ($game['day_count'] ?? 0) + 1;
    $game['votes'] = [];
    $game['night_actions'] = [];
    $game['night_end_time'] = 0;
    $game['day_end_time'] = time() + 60;
    $game['vote_end_time'] = 0;
    
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    $msg = "☀️ <b>صبح روز " . $game['day_count'] . " شد!</b>\n\n";
    if (!empty($deaths)) {
        $msg .= "💀 <b>کشته شدگان:</b>\n" . implode("\n", array_map(fn($name) => "• $name", $deaths)) . "\n\n";
    } else {
        $msg .= "✨ <b>امشب کسی نمرد!</b>\n\n";
    }
    
    $msg .= "🗣️ <b>زمان بحث!</b>\n⏱ ۶۰ ثانیه وقت دارید.\nبعدش رأی‌گیری شروع می‌شه.";
    sendMessage($game['group_id'], $msg);
}

function startVoting($game) {
    $alivePlayers = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    if (count($alivePlayers) < 2) {
        endGame($game);
        return;
    }
    
    $game['phase'] = 'vote';
    $game['votes'] = [];
    $game['vote_end_time'] = time() + 60;
    
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    
    sendMessage($game['group_id'], "🗳️ <b>زمان رأی‌گیری!</b>\n⏱ ۶۰ ثانیه وقت دارید.\nبه صورت خصوصی به ربات پیام دهید.");
    
    foreach ($alivePlayers as $p) {
        sendVotePanel($p, $game);
    }
}

function sendVotePanel($player, $game) {
    $alivePlayers = array_filter($game['players'], function($p) use ($player) {
        return ($p['alive'] ?? false) && $p['id'] != $player['id'];
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
    $counts = [];
    
    foreach ($votes as $voter_id => $target_id) {
        if ($target_id == 'skip') continue;
        $counts[$target_id] = ($counts[$target_id] ?? 0) + 1;
    }
    
    arsort($counts);
    $max = reset($counts) ?? 0;
    $targets = array_keys($counts, $max);
    
    if ($max > 0 && count($targets) == 1) {
        $target_id = $targets[0];
        foreach ($game['players'] as &$p) {
            if ($p['id'] == $target_id) {
                $p['alive'] = false;
                sendMessage($game['group_id'], "💀 <b>" . $p['name'] . "</b> اعدام شد!\n🎭 نقش: " . getRoleDisplayName($p['role']));
                break;
            }
        }
    } else {
        sendMessage($game['group_id'], "⚖️ <b>رأی‌ها مساوی شد! کسی اعدام نشد.</b>");
    }
    
    $game['votes'] = [];
    $game['vote_end_time'] = 0;
    $game['day_end_time'] = 0;
    $games = loadGames();
    $games[$game_code] = $game;
    saveGames($games);
    
    checkWinCondition($game_code);
}

function checkWinCondition($game_code) {
    $games = loadGames();
    $game = $games[$game_code] ?? null;
    if (!$game) return;
    
    $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
    $wolves = array_filter($alive, fn($p) => in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
                                                                    'forest_queen', 'white_wolf', 'beta_wolf', 
                                                                    'ice_wolf', 'enchanter', 'honey', 'sorcerer']));
    $villagers = array_filter($alive, fn($p) => !in_array($p['role'], ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
                                                                        'forest_queen', 'white_wolf', 'beta_wolf', 
                                                                        'ice_wolf', 'enchanter', 'honey', 'sorcerer']));
    
    if (count($wolves) == 0) {
        sendMessage($game['group_id'], "🎉 <b>روستایی‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        return;
    }
    
    if (count($wolves) >= count($villagers)) {
        sendMessage($game['group_id'], "🎉 <b>گرگ‌ها برنده شدند!</b>");
        $game['status'] = 'ended';
        $games[$game_code] = $game;
        saveGames($games);
        return;
    }
    
    // شب بعد
    $game['phase'] = 'night';
    $game['night_count'] = ($game['night_count'] ?? 0) + 1;
    $game['night_actions'] = [];
    $game['night_end_time'] = time() + 60;
    $games[$game_code] = $game;
    saveGames($games);
    
    sendMessage($game['group_id'], "🌙 <b>شب " . $game['night_count'] . "!</b>\n\nهمه بخوابید...\n⏱ ۶۰ ثانیه تا صبح");
    
    foreach ($game['players'] as $p) {
        if ($p['alive'] ?? false) {
            sendNightPanel($p, $game);
        }
    }
}

function endGame($game) {
    $game['status'] = 'ended';
    $games = loadGames();
    foreach ($games as $code => $g) {
        if ($g['group_id'] == $game['group_id']) {
            $games[$code] = $game;
            break;
        }
    }
    saveGames($games);
    sendMessage($game['group_id'], "🏁 <b>بازی تمام شد!</b>");
}

// ============================================================
// توابع نقش‌ها (کامل)
// ============================================================

function getRoleDisplayName($role) {
    $names = [
        // ===== روستا =====
        'villager' => '👨‍🌾 روستایی ساده',
        'seer' => '👳🏻‍♂️ پیشگو',
        'apprentice_seer' => '🙇🏻‍♂️ شاگرد پیشگو',
        'guardian_angel' => '👼🏻 فرشته نگهبان',
        'knight' => '🗡 شوالیه',
        'hunter' => '👮🏻‍♂️ کلانتر',
        'harlot' => '💋 ناتاشا',
        'builder' => '👷🏻‍♂️ بنا',
        'blacksmith' => '⚒ آهنگر',
        'gunner' => '🔫 تفنگدار',
        'mayor' => '🎖 کدخدا',
        'prince' => '🤴🏻 شاهزاده',
        'detective' => '🕵🏻‍♂️ کاراگاه',
        'cupid' => '💘 الهه عشق',
        'beholder' => '👁 شاهد',
        'phoenix' => '🪶 ققنوس',
        'huntsman' => '🪓 هانتسمن',
        'trouble' => '👩🏻‍🌾 دختر دردسرساز',
        'chemist' => '👨‍🔬 شیمیدان',
        'fool' => '🃏 احمق',
        'clumsy' => '🤕 پسر گیج',
        'cursed' => '😾 نفرین شده',
        'traitor' => '🖕🏿 خائن',
        'wild_child' => '👶🏻 بچه وحشی',
        'wise_elder' => '📚 ریش سفید',
        'sandman' => '💤 خوابگذار',
        'sweetheart' => '👰🏻 دلبر',
        'ruler' => '👑 حاکم',
        'spy' => '🦹🏻‍♂️ جاسوس',
        'marouf' => '🛡️🌿 معروف',
        'cult_hunter' => '💂🏻‍♂️ شکارچی فرقه',
        'hamal' => '🛒 حمال',
        'jumong' => '🏹⚔️ جومونگ',
        'princess' => '👸🏻 پرنسس',
        'wolf_man' => '🌑👨🏻 گرگنما',
        'drunk' => '🍻 مست',
        
        // ===== گرگ =====
        'werewolf' => '🐺 گرگینه',
        'alpha_wolf' => '⚡️🐺 گرگ آلفا',
        'wolf_cub' => '🐶 توله گرگ',
        'lycan' => '🌝🐺 گرگ ایکس',
        'forest_queen' => '🧝🏻‍♀️🐺 ملکه جنگل',
        'white_wolf' => '🌩🐺 گرگ سفید',
        'beta_wolf' => '💤🐺 گرگ خوابالو',
        'ice_wolf' => '☃️🐺 گرگ برفی',
        'enchanter' => '🧙🏻‍♂️ افسونگر',
        'honey' => '🧙🏻‍♀️ عجوزه',
        'sorcerer' => '🔮 جادوگر',
        
        // ===== ومپایر =====
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
        'kent_vampire' => '💍🧛🏻 کنت ومپایر',
        'chiang' => '👩‍🦳 چیانگ',
        
        // ===== فرقه =====
        'cultist' => '👤 فرقه‌گرا',
        'royce' => '🎩 رئیس فرقه',
        'frankenstein' => '🧟‍♂️🪖 فرانکشتاین',
        'monk_black' => '🦇 راهب سیاه',
        
        // ===== قاتل =====
        'serial_killer' => '🔪 قاتل زنجیره‌ای',
        'archer' => '🏹 کماندار',
        'davina' => '🍾 داوینا',
        
        // ===== آتش و یخ =====
        'fire_king' => '🔥🤴🏻 پادشاه آتش',
        'ice_queen' => '❄️👸🏻 ملکه یخی',
        'lilith' => '🐍👩🏻‍🦳 لیلیث',
        'magento' => '🧲 مگنیتو',
        
        // ===== شوالیه تاریکی =====
        'black_knight' => '🥷🗡 شوالیه تاریکی',
        'bride_dead' => '👰‍♀☠️ عروس مردگان',
        
        // ===== جوکر =====
        'joker' => '🤡 جوکر',
        'harly' => '👩🏻‍🎤 هارلی کویین',
        
        // ===== مستقل =====
        'dian' => '🧞‍♂️ دیان',
        'dinamit' => '🧨 دینامیت',
        'bomber' => '💣 بمب‌گذار',
        'tso' => '⚔️ تسو',
        'tanner' => '👺 منافق',
        'lucifer' => '😈 لوسیفر',
        'doppelganger' => '👯 همزاد'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getRoleDescription($role) {
    $desc = [
        // ===== روستا =====
        'villager' => '👨‍🌾 شما یک روستایی ساده هستید. در روز رأی می‌دهید.',
        'seer' => '👳🏻‍♂️ شما پیشگو هستید! هر شب نقش یک نفر را می‌بینید.',
        'apprentice_seer' => '🙇🏻‍♂️ شما شاگرد پیشگو هستید! اگر پیشگو بمیرد، جای او را می‌گیرید.',
        'guardian_angel' => '👼🏻 شما فرشته نگهبان هستید! هر شب از یک نفر محافظت می‌کنید.',
        'knight' => '🗡 شما شوالیه هستید! هر شب می‌توانید از یک نفر محافظت کنید.',
        'hunter' => '👮🏻‍♂️ شما کلانتر هستید! اگر بمیرید، می‌توانید به یک نفر شلیک کنید.',
        'harlot' => '💋 شما ناتاشا هستید! هر شب می‌توانید به خانه یک نفر بروید.',
        'builder' => '👷🏻‍♂️ شما بنا هستید! نقش خاصی ندارید اما با بناهای دیگر هم‌تیمی هستید.',
        'blacksmith' => '⚒ شما آهنگر هستید! می‌توانید نقره بپاشید یا شمشیر بسازید.',
        'gunner' => '🔫 شما تفنگدار هستید! ۲ گلوله دارید و می‌توانید به کسی شلیک کنید.',
        'mayor' => '🎖 شما کدخدا هستید! می‌توانید نقش خود را اعلام کنید و رأی شما ۲ برابر می‌شود.',
        'prince' => '🤴🏻 شما شاهزاده هستید! یک بار می‌توانید جلوی اعدام خود را بگیرید.',
        'detective' => '🕵🏻‍♂️ شما کاراگاه هستید! هر شب یک نفر را تحقیق می‌کنید.',
        'cupid' => '💘 شما الهه عشق هستید! دو نفر را عاشق هم می‌کنید.',
        'beholder' => '👁 شما شاهد هستید! می‌دانید پیشگو چه کسی است.',
        'phoenix' => '🪶 شما ققنوس هستید! می‌توانید یک نفر را از مرگ نجات دهید.',
        'huntsman' => '🪓 شما هانتسمن هستید! می‌توانید تله بگذارید.',
        'trouble' => '👩🏻‍🌾 شما دختر دردسرساز هستید! می‌توانید باعث شوید دو بار رأی‌گیری شود.',
        'chemist' => '👨‍🔬 شما شیمیدان هستید! می‌توانید به کسی معجون بدهید.',
        'fool' => '🃏 شما احمق هستید! فکر می‌کنید پیشگو هستید اما نتیجه‌ها اشتباه است.',
        'clumsy' => '🤕 شما پسر گیج هستید! ۵۰٪ احتمال دارد رأی شما تغییر کند.',
        'cursed' => '😾 شما نفرین شده هستید! اگر گرگ‌ها به شما حمله کنند، تبدیل به گرگ می‌شوید.',
        'traitor' => '🖕🏿 شما خائن هستید! اگر همه گرگ‌ها بمیرند، شما تبدیل به گرگ می‌شوید.',
        'wild_child' => '👶🏻 شما بچه وحشی هستید! اگر الگوی شما بمیرد، تبدیل به گرگ می‌شوید.',
        'wise_elder' => '📚 شما ریش سفید هستید! یک بار می‌توانید از مرگ نجات پیدا کنید.',
        'sandman' => '💤 شما خوابگذار هستید! یک بار می‌توانید همه را بخوابانید.',
        'sweetheart' => '👰🏻 شما دلبر هستید! هر کسی به شما حمله کند، عاشق شما می‌شود.',
        'ruler' => '👑 شما حاکم هستید! یک بار می‌توانید تصمیم بگیرید چه کسی اعدام شود.',
        'spy' => '🦹🏻‍♂️ شما جاسوس هستید! می‌توانید بفهمید کسی توانایی کشتن دارد یا نه.',
        'marouf' => '🛡️🌿 شما معروف هستید! از شکارچی محافظت می‌کنید.',
        'cult_hunter' => '💂🏻‍♂️ شما شکارچی فرقه هستید! فرقه‌گراها را شکار می‌کنید.',
        'hamal' => '🛒 شما حمال هستید! می‌توانید یک نفر را نگه دارید.',
        'jumong' => '🏹⚔️ شما جومونگ هستید! باید ۳ نشان پیدا کنید.',
        'princess' => '👸🏻 شما پرنسس هستید! می‌توانید یک نفر را زندانی کنید.',
        'wolf_man' => '🌑👨🏻 شما گرگنما هستید! شب‌ها تبدیل به گرگ می‌شوید.',
        'drunk' => '🍻 شما مست هستید! اگر گرگ‌ها شما را بخورند، مسموم می‌شوند.',
        
        // ===== گرگ =====
        'werewolf' => '🐺 شما گرگینه هستید! هر شب یک نفر را می‌خورید.',
        'alpha_wolf' => '⚡️🐺 شما گرگ آلفا هستید! رهبر گرگ‌ها، ۲۰٪ شانس آلوده کردن دارید.',
        'wolf_cub' => '🐶 شما توله گرگ هستید! اگر بمیرید، گرگ‌ها ۲ نفر را می‌خورند.',
        'lycan' => '🌝🐺 شما گرگ ایکس هستید! پیشگو شما را شاهزاده می‌بیند.',
        'forest_queen' => '🧝🏻‍♀️🐺 شما ملکه جنگل هستید! معشوقه گرگ آلفا.',
        'white_wolf' => '🌩🐺 شما گرگ سفید هستید! از گرگ‌ها محافظت می‌کنید.',
        'beta_wolf' => '💤🐺 شما گرگ خوابالو هستید! هر دو شب یک بار می‌توانید بخوابید.',
        'ice_wolf' => '☃️🐺 شما گرگ برفی هستید! می‌توانید یک نفر را منجمد کنید.',
        'enchanter' => '🧙🏻‍♂️ شما افسونگر هستید! می‌توانید یک نفر را طلسم کنید.',
        'honey' => '🧙🏻‍♀️ شما عجوزه هستید! می‌توانید نقش یک نفر را تغییر دهید.',
        'sorcerer' => '🔮 شما جادوگر هستید! می‌توانید نقش‌های خاص را ببینید.',
        
        // ===== ومپایر =====
        'vampire' => '🧛🏻‍♂️ شما ومپایر هستید! هر شب به یک نفر حمله می‌کنید.',
        'bloodthirsty' => '🧛🏻‍♀️ شما ومپایر اصیل هستید! رهبر ومپایرها.',
        'kent_vampire' => '💍🧛🏻 شما کنت ومپایر هستید! می‌توانید یک نفر را زیر نظر بگیرید.',
        'chiang' => '👩‍🦳 شما چیانگ هستید! می‌توانید نقش‌های منفی را پیدا کنید.',
        
        // ===== فرقه =====
        'cultist' => '👤 شما فرقه‌گرا هستید! هر شب یک نفر را به فرقه دعوت می‌کنید.',
        'royce' => '🎩 شما رئیس فرقه هستید! رهبر فرقه‌گراها.',
        'frankenstein' => '🧟‍♂️🪖 شما فرانکشتاین هستید! از فرقه‌گراها محافظت می‌کنید.',
        'monk_black' => '🦇 شما راهب سیاه هستید! هر ۲ شب یک بار دعوت می‌کنید.',
        
        // ===== قاتل =====
        'serial_killer' => '🔪 شما قاتل زنجیره‌ای هستید! هر شب یک نفر را می‌کشید.',
        'archer' => '🏹 شما کماندار هستید! هر ۲ شب یک بار تیراندازی می‌کنید.',
        'davina' => '🍾 شما داوینا هستید! می‌توانید یک روز را سکوت کنید.',
        
        // ===== آتش و یخ =====
        'fire_king' => '🔥🤴🏻 شما پادشاه آتش هستید! می‌توانید خونه‌ها را آتش بزنید.',
        'ice_queen' => '❄️👸🏻 شما ملکه یخی هستید! می‌توانید یک نفر را منجمد کنید.',
        'lilith' => '🐍👩🏻‍🦳 شما لیلیث هستید! می‌توانید لوسیفر را پیدا کنید.',
        'magento' => '🧲 شما مگنیتو هستید! می‌توانید یک نفر را جذب کنید.',
        
        // ===== شوالیه تاریکی =====
        'black_knight' => '🥷🗡 شما شوالیه تاریکی هستید! می‌توانید از خود دفاع کنید.',
        'bride_dead' => '👰‍♀☠️ شما عروس مردگان هستید! هر شب یک نفر را می‌کشید.',
        
        // ===== جوکر =====
        'joker' => '🤡 شما جوکر هستید! باید کتیبه‌ها را جمع کنید.',
        'harly' => '👩🏻‍🎤 شما هارلی کویین هستید! از جوکر محافظت می‌کنید.',
        
        // ===== مستقل =====
        'dian' => '🧞‍♂️ شما دیان هستید! باید هدف خود را اعلام کنید.',
        'dinamit' => '🧨 شما دینامیت هستید! باید عناصر را پیدا کنید.',
        'bomber' => '💣 شما بمب‌گذار هستید! باید بمب بگذارید.',
        'tso' => '⚔️ شما تسو هستید! باید جومونگ را پیدا کنید.',
        'tanner' => '👺 شما منافق هستید! باید اعدام شوید.',
        'lucifer' => '😈 شما لوسیفر هستید! می‌توانید تیم خود را انتخاب کنید.',
        'doppelganger' => '👯 شما همزاد هستید! می‌توانید نقش یک نفر را بگیرید.'
    ];
    return $desc[$role] ?? '🎭 شما ' . getRoleDisplayName($role) . ' هستید!';
}

function getRoleActionDescription($role) {
    $actions = [
        'werewolf' => '🐺 یک نفر را برای خوردن انتخاب کن.',
        'alpha_wolf' => '⚡️🐺 یک نفر را برای حمله انتخاب کن (۲۰٪ شانس آلوده کردن).',
        'wolf_cub' => '🐶 یک نفر را برای خوردن انتخاب کن.',
        'lycan' => '🌝🐺 یک نفر را برای خوردن انتخاب کن.',
        'forest_queen' => '🧝🏻‍♀️🐺 یک نفر را برای خوردن انتخاب کن.',
        'white_wolf' => '🌩🐺 یک نفر را برای محافظت انتخاب کن.',
        'beta_wolf' => '💤🐺 یک نفر را برای خوردن یا خوابیدن انتخاب کن.',
        'ice_wolf' => '☃️🐺 یک نفر را برای منجمد کردن انتخاب کن.',
        'enchanter' => '🧙🏻‍♂️ یک نفر را برای طلسم کردن انتخاب کن.',
        'honey' => '🧙🏻‍♀️ یک نفر را برای تغییر نقش انتخاب کن.',
        'sorcerer' => '🔮 یک نفر را برای دیدن نقش انتخاب کن.',
        'seer' => '👁️ یک نفر را برای دیدن نقش انتخاب کن.',
        'guardian_angel' => '🛡️ یک نفر را برای محافظت انتخاب کن.',
        'knight' => '🗡 یک نفر را برای محافظت انتخاب کن.',
        'hunter' => '🔫 یک نفر را برای شلیک انتخاب کن.',
        'harlot' => '💋 یک نفر را برای ملاقات انتخاب کن.',
        'detective' => '🔍 یک نفر را برای تحقیق انتخاب کن.',
        'cupid' => '💘 دو نفر را برای عاشق کردن انتخاب کن.',
        'phoenix' => '🪶 یک نفر را برای نجات انتخاب کن.',
        'sandman' => '💤 همه را بخوابان.',
        'spy' => '🦹🏻‍♂️ یک نفر را برای جاسوسی انتخاب کن.',
        'cultist' => '👤 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'royce' => '🎩 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'frankenstein' => '🧟‍♂️🪖 یک نفر را برای محافظت انتخاب کن.',
        'monk_black' => '🦇 یک نفر را برای دعوت به فرقه انتخاب کن.',
        'serial_killer' => '🔪 یک نفر را برای کشتن انتخاب کن.',
        'archer' => '🏹 یک نفر را برای تیراندازی انتخاب کن.',
        'davina' => '🍾 یک روز را سکوت کن.',
        'vampire' => '🧛🏻‍♂️ یک نفر را برای حمله انتخاب کن.',
        'bloodthirsty' => '🧛🏻‍♀️ یک نفر را برای حمله انتخاب کن.',
        'kent_vampire' => '💍🧛🏻 یک نفر را برای زیر نظر گرفتن انتخاب کن.',
        'chiang' => '👩‍🦳 یک نفر را برای بررسی انتخاب کن.',
        'fire_king' => '🔥🤴🏻 یک خونه را برای نفت‌پاشی انتخاب کن.',
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
    return $actions[$role] ?? '';
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
           "👮🏻‍♂️ کلانتر - اگر بمیرد، می‌تواند به یک نفر شلیک کند\n" .
           "🕵🏻‍♂️ کاراگاه - هر شب یک نفر را تحقیق می‌کند\n" .
           "🗡 شوالیه - هر شب از یک نفر محافظت می‌کند\n" .
           "🔪 قاتل زنجیره‌ای - هر شب یک نفر را می‌کشد\n" .
           "🧛🏻‍♂️ ومپایر - هر شب به یک نفر حمله می‌کند\n" .
           "👤 فرقه‌گرا - هر شب یک نفر را به فرقه دعوت می‌کند\n" .
           "🤡 جوکر - کتیبه‌ها را جمع می‌کند\n" .
           "👺 منافق - باید اعدام شود تا برنده شود";
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

// ============================================================
// پردازش اصلی
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

// ===== پردازش دکمه‌های شیشه‌ای =====
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $user_id = $callback['from']['id'];
    
    // پردازش اکشن شب (با پشتیبانی از همه نقش‌ها)
    $parts = explode('_', $data);
    if (count($parts) == 2) {
        $role = $parts[0];
        $target_id = $parts[1] == 'skip' ? 'skip' : (int)$parts[1];
        
        // لیست همه نقش‌هایی که اکشن شب دارن
        $all_night_roles = [
            'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
            'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer',
            'seer', 'guardian_angel', 'knight', 'hunter', 'harlot',
            'detective', 'cupid', 'phoenix', 'sandman', 'spy',
            'cultist', 'royce', 'frankenstein', 'monk_black',
            'serial_killer', 'archer', 'davina',
            'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
            'fire_king', 'ice_queen', 'lilith', 'magento',
            'black_knight', 'bride_dead',
            'joker', 'harly',
            'dian', 'dinamit', 'bomber', 'tso', 'lucifer'
        ];
        
        if (in_array($role, $all_night_roles)) {
            $games = loadGames();
            $game = null;
            $game_code = null;
            foreach ($games as $code => $g) {
                foreach ($g['players'] as $p) {
                    if ($p['id'] == $user_id && in_array($g['status'], ['started']) && $g['phase'] == 'night') {
                        $game = $g;
                        $game_code = $code;
                        break 2;
                    }
                }
            }
            
            if ($game) {
                if ($target_id != 'skip') {
                    $game['night_actions'][] = ['player' => $user_id, 'role' => $role, 'target' => $target_id];
                    $games[$game_code] = $game;
                    saveGames($games);
                    answerCallbackQuery($callback_id, "✅ انتخاب شما ثبت شد!");
                    sendPrivateMessage($user_id, "✅ انتخاب شما برای شب " . $game['night_count'] . " ثبت شد.");
                } else {
                    answerCallbackQuery($callback_id, "⏭️ شما این شب رو رد کردید.");
                    sendPrivateMessage($user_id, "⏭️ شما این شب رو رد کردید.");
                }
            } else {
                answerCallbackQuery($callback_id, "❌ خطا: بازی پیدا نشد!", true);
            }
            
            http_response_code(200);
            echo '{"ok":true}';
            exit;
        }
    }
    
    // پردازش رأی
    $parts = explode('_', $data);
    if (count($parts) == 2 && $parts[0] == 'vote') {
        $target_id = $parts[1] == 'skip' ? 'skip' : (int)$parts[1];
        
        $games = loadGames();
        $game = null;
        $game_code = null;
        foreach ($games as $code => $g) {
            foreach ($g['players'] as $p) {
                if ($p['id'] == $user_id && in_array($g['status'], ['started']) && $g['phase'] == 'vote') {
                    $game = $g;
                    $game_code = $code;
                    break 2;
                }
            }
        }
        
        if ($game) {
            $game['votes'][$user_id] = $target_id;
            $games[$game_code] = $game;
            saveGames($games);
            answerCallbackQuery($callback_id, "✅ رأی شما ثبت شد!");
            
            $alive = array_filter($game['players'], fn($p) => $p['alive'] ?? false);
            if (count($game['votes']) >= count($alive)) {
                processVotes($game_code, $game);
            }
        } else {
            answerCallbackQuery($callback_id, "❌ خطا: بازی پیدا نشد!", true);
        }
        
        http_response_code(200);
        echo '{"ok":true}';
        exit;
    }
    
    // دکمه‌های منو
    $response = "";
    switch ($data) {
        case 'create_game': $response = "🎮 برای ساخت بازی، به یک گروه بروید و دستور /game را بزنید."; break;
        case 'join_menu': $response = "🔗 کد بازی را وارد کنید:\nمثال: /join AB12CD"; break;
        case 'rules': $response = getRules(); break;
        case 'roles': $response = getRolesList(); break;
        case 'help': $response = "📚 راهنما:\n/start - منو\n/game - ساخت بازی\n/join [کد] - پیوستن\n/players - لیست بازیکنان\n/startgame - شروع بازی\n/ping - تست"; break;
        case 'stats':
            $games = loadGames();
            $total = count($games);
            $waiting = count(array_filter($games, fn($g) => $g['status'] == 'waiting'));
            $started = count(array_filter($games, fn($g) => $g['status'] == 'started'));
            $response = "📊 <b>آمار ربات</b>\n\n" . "🎮 کل بازی‌ها: $total\n" . "⏳ در انتظار: $waiting\n" . "▶️ در حال اجرا: $started";
            break;
        default: $response = "✅ دکمه $data فشار داده شد!"; break;
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

$parts = explode(' ', $text);
$command = strtolower($parts[0]);
$param = $parts[1] ?? '';

// ===== چک کردن تایمرها =====
$games = loadGames();
foreach ($games as $code => $game) {
    if ($game['group_id'] == $chat_id && $game['status'] == 'started') {
        if ($game['phase'] == 'night' && $game['night_end_time'] > 0 && time() >= $game['night_end_time']) {
            processNight($code, $game);
            sendMessage($chat_id, "⏰ شب به پایان رسید! صبح شد...");
        }
        elseif ($game['phase'] == 'day' && $game['day_end_time'] > 0 && time() >= $game['day_end_time']) {
            startVoting($game);
            sendMessage($chat_id, "⏰ زمان بحث تمام شد! رأی‌گیری شروع می‌شود...");
        }
        elseif ($game['phase'] == 'vote' && $game['vote_end_time'] > 0 && time() >= $game['vote_end_time']) {
            processVotes($code, $game);
        }
        break;
    }
}

switch ($command) {
    case '/start':
        $msg = "👋 سلام <b>$first_name</b>!\n🐺 ربات گرگینه فعاله!\n\n📱 یکی رو انتخاب کن:";
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎮 ساخت بازی', 'callback_data' => 'create_game'],
                    ['text' => '🔗 پیوستن', 'callback_data' => 'join_menu']
                ],
                [
                    ['text' => '📜 قوانین', 'callback_data' => 'rules'],
                    ['text' => '🎭 نقش‌ها', 'callback_data' => 'roles']
                ],
                [
                    ['text' => '❓ راهنما', 'callback_data' => 'help'],
                    ['text' => '📊 آمار', 'callback_data' => 'stats']
                ]
            ]
        ];
        sendMessage($chat_id, $msg, $keyboard);
        break;
        
    case '/game':
        if ($chat_type == 'private') {
            sendMessage($chat_id, "❌ ساخت بازی فقط در گروه ممکن است!");
        } else {
            $result = createGame($chat_id, $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/join':
        if (empty($param)) {
            sendMessage($chat_id, "❌ کد بازی را وارد کنید!\nمثال: /join AB12CD");
        } else {
            $code = strtoupper(trim($param));
            $result = joinGame($code, $user_id, $first_name);
            sendMessage($chat_id, $result['message']);
        }
        break;
        
    case '/players':
        $game = getGameInfo($chat_id);
        if (!$game) {
            sendMessage($chat_id, "❌ بازی فعالی در این گروه وجود ندارد!");
        } else {
            $msg = "👥 <b>بازیکنان</b> - کد: <code>" . $game['code'] . "</code>\n\n";
            $msg .= "👤 تعداد: " . count($game['players']) . " نفر\n\n";
            foreach ($game['players'] as $p) {
                $creator = ($p['id'] == $game['creator_id']) ? '👑' : '';
                $you = ($p['id'] == $user_id) ? ' (شما)' : '';
                $msg .= "• {$p['name']} $creator$you\n";
            }
            sendMessage($chat_id, $msg);
        }
        break;
        
    case '/startgame':
        $result = startGame($chat_id, $user_id);
        sendMessage($chat_id, $result['message']);
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

// ============================================================
// توابع کمکی
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
