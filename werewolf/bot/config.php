<?php
/**
 * ⚙️ تنظیمات ربات Werewolf Bot
 */

// ==================== توکن و اطلاعات بات ====================

// 🔑 توکن بات - از environment variables بگیر، fallback فقط برای تست
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA');

// 👤 آیدی عددی ادمین اصلی
define('ADMIN_ID', (int)(getenv('ADMIN_ID') ?: 1095925103));

// 🤖 اطلاعات بات
define('BOT_USERNAME', 'Ni_cop_bot');
define('BOT_NAME', '🐺 Ni Cop');

// ==================== مسیرها ====================

define('BASE_PATH', __DIR__ . '/');
define('DATA_PATH', BASE_PATH . 'data/');
define('ROLES_PATH', BASE_PATH . 'ROLES_PATCH/');

// ==================== تنظیمات بازی ====================

// تعداد بازیکنان
define('MIN_PLAYERS', 4);
define('MAX_PLAYERS', 60);
define('GAME_TIMEOUT', 300); // 5 دقیقه

// ==================== تنظیمات زمان (ثانیه) ====================

// زمان‌های پیش‌فرض
define('DEFAULT_DAY_DURATION', 60);
define('DEFAULT_NIGHT_DURATION', 60);
define('DEFAULT_VOTE_DURATION', 60);
define('DEFAULT_WAITING_TIME', 300); // 5 دقیقه

// زمان تمدید
define('EXTEND_TIME', 30);
define('MAX_EXTEND_COUNT', 3);

// AFK
define('AFK_THRESHOLD', 2);

// ==================== تنظیمات فنی ====================

// 🐛 حالت دیباگ
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==================== لیست نقش‌ها ====================

define('ALL_ROLES', [
    // ===== روستا =====
    'villager', 'seer', 'apprentice_seer', 'guardian_angel', 'knight',
    'hunter', 'harlot', 'builder', 'blacksmith', 'gunner', 'mayor',
    'prince', 'detective', 'cupid', 'beholder', 'phoenix', 'huntsman',
    'trouble', 'chemist', 'fool', 'clumsy', 'cursed', 'traitor',
    'wild_child', 'wise_elder', 'sandman', 'sweetheart', 'ruler',
    'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong', 'princess',
    'wolf_man', 'drunk',
    
    // ===== گرگ =====
    'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
    'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey',
    'sorcerer',
    
    // ===== ومپایر =====
    'vampire', 'bloodthirsty', 'kent_vampire', 'chiang',
    
    // ===== فرقه =====
    'cultist', 'royce', 'frankenstein', 'monk_black',
    
    // ===== قاتل =====
    'serial_killer', 'archer', 'davina',
    
    // ===== آتش و یخ =====
    'fire_king', 'ice_queen', 'lilith', 'magento',
    
    // ===== شوالیه تاریکی =====
    'black_knight', 'bride_dead',
    
    // ===== جوکر =====
    'joker', 'harly',
    
    // ===== مستقل =====
    'dian', 'dinamit', 'bomber', 'tso', 'tanner', 
    'lucifer', 'doppelganger'
]);

// ==================== وزن نقش‌ها ====================

define('ROLE_WEIGHTS', [
    // مثبت = روستا، منفی = شر
    'villager' => 1,
    'seer' => 6,
    'apprentice_seer' => 3,
    'guardian_angel' => 5,
    'knight' => 4,
    'hunter' => 4,
    'harlot' => 3,
    'builder' => 2,
    'blacksmith' => 4,
    'gunner' => 5,
    'mayor' => 2,
    'prince' => 2,
    'detective' => 4,
    'cupid' => 1,
    'beholder' => 2,
    'phoenix' => 3,
    'huntsman' => 4,
    'trouble' => 2,
    'chemist' => 3,
    'fool' => 1,
    'clumsy' => 1,
    'cursed' => -3,
    'traitor' => -4,
    'wild_child' => 2,
    'wise_elder' => 3,
    'sandman' => 2,
    'sweetheart' => 2,
    'ruler' => 3,
    'spy' => 3,
    'marouf' => 3,
    'cult_hunter' => 6,
    'hamal' => 3,
    'jumong' => 2,
    'princess' => 2,
    'wolf_man' => -4,
    'drunk' => 1,
    
    // گرگ
    'werewolf' => -5,
    'alpha_wolf' => -7,
    'wolf_cub' => -5,
    'lycan' => -5,
    'forest_queen' => -6,
    'white_wolf' => -5,
    'beta_wolf' => -5,
    'ice_wolf' => -5,
    'enchanter' => -5,
    'honey' => -4,
    'sorcerer' => -4,
    
    // ومپایر
    'vampire' => -6,
    'bloodthirsty' => -7,
    'kent_vampire' => -6,
    'chiang' => -5,
    
    // قاتل
    'serial_killer' => -7,
    'archer' => -6,
    'davina' => -5,
    
    // شوالیه تاریکی
    'black_knight' => -7,
    'bride_dead' => -6,
    
    // جوکر
    'joker' => -6,
    'harly' => -5,
    
    // آتش و یخ
    'fire_king' => -6,
    'ice_queen' => -6,
    'lilith' => -6,
    'magento' => -6,
    
    // فرقه
    'cultist' => -4,
    'royce' => -5,
    'frankenstein' => -5,
    'monk_black' => -4,
    
    // مستقل
    'dian' => -7,
    'dinamit' => -6,
    'bomber' => -6,
    'tso' => -2,
    'tanner' => -3,
    'lucifer' => -6,
    'doppelganger' => 0,
]);

// ==================== توابع کمکی ====================

/**
 * بررسی اینکه آیا نقش در لیست هست
 */
function isValidRole($role) {
    return in_array($role, ALL_ROLES);
}

/**
 * گرفتن وزن نقش
 */
function getRoleWeight($role) {
    $weights = ROLE_WEIGHTS;
    return $weights[$role] ?? 0;
}

/**
 * گرفتن تیم نقش
 */
function getRoleTeam($role) {
    $teams = [
        'villager' => 'villager',
        'seer' => 'villager',
        'apprentice_seer' => 'villager',
        'guardian_angel' => 'villager',
        'knight' => 'villager',
        'hunter' => 'villager',
        'harlot' => 'villager',
        'builder' => 'villager',
        'blacksmith' => 'villager',
        'gunner' => 'villager',
        'mayor' => 'villager',
        'prince' => 'villager',
        'detective' => 'villager',
        'cupid' => 'villager',
        'beholder' => 'villager',
        'phoenix' => 'villager',
        'huntsman' => 'villager',
        'trouble' => 'villager',
        'chemist' => 'villager',
        'fool' => 'villager',
        'clumsy' => 'villager',
        'cursed' => 'villager',
        'traitor' => 'villager',
        'wild_child' => 'villager',
        'wise_elder' => 'villager',
        'sandman' => 'villager',
        'sweetheart' => 'villager',
        'ruler' => 'villager',
        'spy' => 'villager',
        'marouf' => 'villager',
        'cult_hunter' => 'villager',
        'hamal' => 'villager',
        'jumong' => 'villager',
        'princess' => 'villager',
        'wolf_man' => 'villager',
        'drunk' => 'villager',
        
        'werewolf' => 'werewolf',
        'alpha_wolf' => 'werewolf',
        'wolf_cub' => 'werewolf',
        'lycan' => 'werewolf',
        'forest_queen' => 'werewolf',
        'white_wolf' => 'werewolf',
        'beta_wolf' => 'werewolf',
        'ice_wolf' => 'werewolf',
        'enchanter' => 'werewolf',
        'honey' => 'werewolf',
        'sorcerer' => 'werewolf',
        
        'vampire' => 'vampire',
        'bloodthirsty' => 'vampire',
        'kent_vampire' => 'vampire',
        'chiang' => 'vampire',
        
        'cultist' => 'cult',
        'royce' => 'cult',
        'frankenstein' => 'cult',
        'monk_black' => 'cult',
        
        'serial_killer' => 'killer',
        'archer' => 'killer',
        'davina' => 'killer',
        
        'fire_king' => 'fire_ice',
        'ice_queen' => 'fire_ice',
        'lilith' => 'fire_ice',
        'magento' => 'fire_ice',
        
        'black_knight' => 'black_knight',
        'bride_dead' => 'black_knight',
        
        'joker' => 'joker',
        'harly' => 'joker',
        
        'dian' => 'independent',
        'dinamit' => 'independent',
        'bomber' => 'independent',
        'tso' => 'independent',
        'tanner' => 'independent',
        'lucifer' => 'independent',
        'doppelganger' => 'independent',
    ];
    
    return $teams[$role] ?? 'unknown';
}