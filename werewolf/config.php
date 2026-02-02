
config_content = '''<?php
/**
 * ⚙️ تنظیمات ربات Ni_cop_bot
 */

// 🔑 توکن بات
define('BOT_TOKEN', '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA');

// 👤 ایدی عددی ادمین
define('ADMIN_ID', 1095925103);

// 🤖 نام بات
define('BOT_USERNAME', 'Ni_cop_bot');
define('BOT_NAME', 'Ni Cop');

// 📁 مسیرها
define('BASE_PATH', __DIR__ . '/');
define('DATA_PATH', __DIR__ . '/../bot/');
define('ROLES_PATH', __DIR__ . '/ROLES_PATCH/');

// ⚙️ تنظیمات بازی
define('MIN_PLAYERS', 4);
define('MAX_PLAYERS', 60);  // ✅ تغییر از 25 به 60
define('GAME_TIMEOUT', 300); // 5 دقیقه

// 🌙 زمان‌بندی شب و روز (ثانیه)
define('NIGHT_DURATION', 60);
define('DAY_DURATION', 60);
define('VOTE_DURATION', 60);

// 🐛 حالت دیباگ
define('DEBUG', false);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 🎭 لیست تمام نقش‌ها
define('ALL_ROLES', [
    // ========== تیم روستا (Villager Team) ==========
    'villager',           // 👨‍🌾 روستایی ساده
    'seer',               // 👳🏻‍♂️ پیشگو
    'apprentice_seer',    // 🙇🏻‍♂️ شاگرد پیشگو
    'guardian_angel',     // 👼🏻 فرشته نگهبان
    'knight',             // 🗡 شوالیه
    'hunter',             // 👮🏻‍♂️ کلانتر
    'harlot',             // 💋 ناتاشا
    'builder',            // 👷🏻‍♂️ بنا
    'blacksmith',         // ⚒ آهنگر
    'gunner',             // 🔫 تفنگدار
    'mayor',              // 🎖 کدخدا
    'prince',             // 🤴🏻 شاهزاده
    'detective',          // 🕵🏻‍♂️ کاراگاه
    'cupid',              // 💘 الهه عشق
    'beholder',           // 👁 شاهد
    'phoenix',            // 🪶 ققنوس
    'huntsman',           // 🪓 هانتسمن
    'trouble',            // 👩🏻‍🌾 دختر دردسرساز
    'chemist',            // 👨‍🔬 شیمیدان
    'fool',               // 🃏 احمق
    'clumsy',             // 🤕 پسر گیج
    'cursed',             // 😾 نفرین شده
    'traitor',            // 🖕🏿 خائن
    'wild_child',         // 👶🏻 بچه وحشی
    'wise_elder',         // 📚 ریش سفید
    'sandman',            // 💤 خوابگذار
    'sweetheart',         // 👰🏻 دلبر
    'ruler',              // 👑 حاکم
    'spy',                // 🦹🏻‍♂️ جاسوس
    'marouf',             // 🛡️🌿 معروف
    'cult_hunter',        // 💂🏻‍♂️ شکارچی
    'hamal',              // 🛒 حمال
    'jumong',             // 🏹⚔️ جومونگ
    'princess',           // 👸🏻 پرنسس
    'wolf_man',           // 🌑👨🏻 گرگنما
    
    // ========== تیم گرگ (Werewolf Team) ==========
    'werewolf',           // 🐺 گرگینه
    'alpha_wolf',         // ⚡️🐺 گرگ آلفا
    'wolf_cub',           // 🐶 توله گرگ
    'lycan',              // 🌝🐺 گرگ ایکس
    'forest_queen',       // 🧝🏻‍♀️🐺 ملکه جنگل
    'white_wolf',         // 🌩🐺 گرگ سفید
    'beta_wolf',          // 💤🐺 گرگ خوابالو
    'ice_wolf',           // ☃️🐺 گرگ برفی
    'enchanter',          // 🧙🏻‍♂️ افسونگر
    'honey',              // 🧙🏻‍♀️ عجوزه
    'sorcerer',           // 🔮 جادوگر
    
    // ========== تیم ومپایر (Vampire Team) ==========
    'vampire',            // 🧛🏻‍♂️ ومپایر
    'bloodthirsty',       // 🧛🏻‍♀️ ومپایر اصیل
    'kent_vampire',       // 💍🧛🏻 کنت ومپایر
    'chiang',             // 👩‍🦳 چیانگ
    
    // ========== تیم قاتل (Killer Team) ==========
    'serial_killer',      // 🔪 قاتل زنجیره‌ای
    'archer',             // 🏹 کماندار
    'davina',             // 🍾 داوینا
    
    // ========== تیم شوالیه تاریکی (Black Knight Team) ==========
    'black_knight',       // 🥷🗡 شوالیه تاریکی
    'bride_dead',         // 👰‍♀☠️ عروس مردگان
    
    // ========== تیم جوکر (Joker Team) ==========
    'joker',              // 🤡 جوکر
    'harly',              // 👩🏻‍🎤 هارلی کویین
    
    // ========== تیم آتش و یخ (Fire & Ice Team) ==========
    'fire_king',          // 🔥🤴🏻 پادشاه آتش
    'ice_queen',          // ❄️👸🏻 ملکه یخی
    'lilith',             // 🐍👩🏻‍🦳 لیلیث
    'lucifer',            // 😈 لوسیفر
    'magento',            // 🧲 مگنیتو
    
    // ========== تیم فرقه (Cult Team) ==========
    'cultist',            // 👤 فرقه‌گرا
    'royce',              // 🎩 رئیس
    'frankenstein',       // 🧟‍♂️🪖 فرانکشتاین
    'monk_black',         // 🦇 راهب سیاه 

    // ========== نقش‌های مستقل (Independent) ==========
    'dian',               // 🧞‍♂️ دیان
    'dinamit',            // 🧨 دینامیت
    'bomber',             // 💣 بمب‌گذار
    'tso',                // ⚔️ تسو
    'tanner',             // 👺 منافق
    
    // ========== نقش‌های تکمیلی ==========
    'doppelganger',       // 👯 همزاد
]);

// ⚖️ وزن نقش‌ها برای بالانس
define('ROLE_WEIGHTS', [
    // ========== تیم روستا (Villager Team) ==========
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
    
    // ========== تیم گرگ (Werewolf Team) ==========
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
    
    // ========== تیم ومپایر (Vampire Team) ==========
    'vampire' => -6,
    'bloodthirsty' => -7,
    'kent_vampire' => -6,
    'chiang' => -5,
    
    // ========== تیم قاتل (Killer Team) ==========
    'serial_killer' => -7,
    'archer' => -6,
    'davina' => -5,
    
    // ========== تیم شوالیه تاریکی (Black Knight Team) ==========
    'black_knight' => -7,
    'bride_dead' => -6,
    
    // ========== تیم جوکر (Joker Team) ==========
    'joker' => -6,
    'harly' => -5,
    
    // ========== تیم آتش و یخ (Fire & Ice Team) ==========
    'fire_king' => -6,
    'ice_queen' => -6,
    'lilith' => -6,
    
    
    // ========== تیم فرقه (Cult Team) ==========
    'cultist' => -4,
    'royce' => -5,
    'frankenstein' => -5,
    'monk_black' => -4,

    // ========== نقش‌های مستقل (Independent) ==========
    'dian' => -7,
    'dinamit' => -6,
    'bomber' => -6,
    'tso' => -2,
    'tanner' => -3,
    'lucifer' => -6,
    'magento' => -6,

    // ========== نقش‌های تکمیلی ==========
    'doppelganger' => 0,
]);

'''

with open('/mnt/kimi/output/config.php', 'w', encoding='utf-8') as f:
    f.write(config_content)

print("✅ config.php ذخیره شد")
