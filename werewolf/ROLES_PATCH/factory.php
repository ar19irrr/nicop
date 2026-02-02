<?php
/**
 * 🏭 فکتوری ساخت نقش‌ها (نسخه نهایی WEREWOLF_V2)
 */

require_once __DIR__ . '/base.php';

class RoleFactory {
    
    /**
     * نقشه کلاس‌های نقش - هر نقش فقط یک بار
     */
    private static $roleClasses = [
        // ========== تیم روستا (Villager Team) ==========
        'villager' => 'Villager',           // 👨‍🌾 روستایی ساده
        'seer' => 'Seer',                   // 👳🏻‍♂️ پیشگو
        'apprentice_seer' => 'ApprenticeSeer', // 🙇🏻‍♂️ شاگرد پیشگو
        'guardian_angel' => 'GuardianAngel',   // 👼🏻 فرشته نگهبان
        'knight' => 'Knight',               // 🗡 شوالیه
        'hunter' => 'Hunter',               // 👮🏻‍♂️ کلانتر
        'harlot' => 'Harlot',               // 💋 ناتاشا
        'builder' => 'Builder',             // 👷🏻‍♂️ بنا
        'blacksmith' => 'Blacksmith',       // ⚒ آهنگر
        'gunner' => 'Gunner',               // 🔫 تفنگدار
        'mayor' => 'Mayor',                 // 🎖 کدخدا
        'prince' => 'Prince',               // 🤴🏻 شاهزاده
        'detective' => 'Detective',         // 🕵🏻‍♂️ کاراگاه
        'cupid' => 'Cupid',                 // 💘 الهه عشق
        'beholder' => 'Beholder',           // 👁 شاهد
        'phoenix' => 'Phoenix',             // 🪶 ققنوس
        'huntsman' => 'Huntsman',           // 🪓 هانتسمن
        'trouble' => 'Trouble',             // 👩🏻‍🌾 دختر دردسرساز
        'chemist' => 'Chemist',             // 👨‍🔬 شیمیدان
        'fool' => 'Fool',                   // 🃏 احمق
        'clumsy' => 'Clumsy',               // 🤕 پسر گیج
        'cursed' => 'Cursed',               // 😾 نفرین شده
        'traitor' => 'Traitor',             // 🖕🏿 خائن
        'wild_child' => 'WildChild',        // 👶🏻 بچه وحشی
        'wise_elder' => 'WiseElder',        // 📚 ریش سفید
        'sandman' => 'Sandman',             // 💤 خوابگذار
        'sweetheart' => 'Sweetheart',       // 👰🏻 دلبر
        'ruler' => 'Ruler',                 // 👑 حاکم
        'spy' => 'Spy',                     // 🦹🏻‍♂️ جاسوس
        'marouf' => 'Marouf',               // 🛡️🌿 معروف
        'cult_hunter' => 'CultHunter',      // 💂🏻‍♂️ شکارچی
        'hamal' => 'Hamal',                 // 🛒 حمال
        'jumong' => 'Jumong',               // 🏹⚔️ جومونگ
        'princess' => 'Princess',           // 👸🏻 پرنسس
        'wolf_man' => 'WolfMan',            // 🌑👨🏻 گرگنما
        'drunk' => 'Drunk',                 // 🍻 مست
        // ========== تیم گرگ (Werewolf Team) ==========
        'werewolf' => 'Werewolf',           // 🐺 گرگینه
        'alpha_wolf' => 'AlphaWolf',        // ⚡️🐺 گرگ آلفا
        'wolf_cub' => 'WolfCub',            // 🐶 توله گرگ
        'lycan' => 'Lycan',                 // 🌝🐺 گرگ ایکس
        'forest_queen' => 'ForestQueen',    // 🧝🏻‍♀️🐺 ملکه جنگل
        'white_wolf' => 'WhiteWolf',        // 🌩🐺 گرگ سفید
        'beta_wolf' => 'BetaWolf',          // 💤🐺 گرگ خوابالو
        'ice_wolf' => 'IceWolf',            // ☃️🐺 گرگ برفی
        'enchanter' => 'Enchanter',         // 🧙🏻‍♂️ افسونگر
        'honey' => 'Honey',                 // 🧙🏻‍♀️ عجوزه
        'sorcerer' => 'Sorcerer',           // 🔮 جادوگر
        
        // ========== تیم ومپایر (Vampire Team) ==========
        'vampire' => 'Vampire',             // 🧛🏻‍♂️ ومپایر
        'bloodthirsty' => 'Bloodthirsty',   // 🧛🏻‍♀️ ومپایر اصیل
        'kent_vampire' => 'KentVampire',    // 💍🧛🏻 کنت ومپایر
        'chiang' => 'Chiang',               // 👩‍🦳 چیانگ
        
        // ========== تیم قاتل (Killer Team) ==========
        'serial_killer' => 'SerialKiller',  // 🔪 قاتل زنجیره‌ای
        'archer' => 'Archer',               // 🏹 کماندار
        'davina' => 'Davina',               // 🍾 داوینا
        
        // ========== تیم شوالیه تاریکی (Black Knight Team) ==========
        'black_knight' => 'BlackKnight',    // 🥷🗡 شوالیه تاریکی
        'bride_dead' => 'BrideDead',        // 👰‍♀☠️ عروس مردگان
        
        // ========== تیم جوکر (Joker Team) ==========
        'joker' => 'Joker',                 // 🤡 جوکر
        'harly' => 'Harly',                 // 👩🏻‍🎤 هارلی کویین
        
        // ========== تیم آتش و یخ (Fire & Ice Team) ==========
        'fire_king' => 'FireKing',          // 🔥🤴🏻 پادشاه آتش
        'ice_queen' => 'IceQueen',          // ❄️👸🏻 ملکه یخی
        'lilith' => 'Lilith',               // 🐍👩🏻‍🦳 لیلیث
        'magento' => 'Magento',             // 🧲 مگنیتو
        
        // ========== تیم فرقه (Cult Team) ==========
        'cultist' => 'Cultist',             // 👤 فرقه‌گرا
        'royce' => 'Royce',                 // 🎩 رئیس
        'frankenstein' => 'Frankenstein',   // 🧟‍♂️🪖 فرانکشتاین
        'monk_black' => 'MonkBlack',        // 🦇 راهب سیاه 

        // ========== نقش‌های مستقل (Independent) ==========
        'dian' => 'Dian',                   // 🧞‍♂️ دیان
        'dinamit' => 'Dinamit',             // 🧨 دینامیت
        'bomber' => 'Bomber',               // 💣 بمب‌گذار
        'tso' => 'Tso',                     // ⚔️ تسو
        'tanner' => 'Tanner',               // 👺 منافق
        'lucifer' => 'Lucifer',             // 😈 لوسیفر

        // ========== نقش‌های تکمیلی ==========
        'doppelganger' => 'Doppelganger',   // 👯 همزاد
    ];
    
    /**
     * ساخت نمونه از نقش
     */
    public static function create($role, $player, $game) {
        $role = strtolower($role);
        $className = self::$roleClasses[$role] ?? null;
        
        if (!$className) {
            return new SimpleRole($player, $game, $role);
        }
        
        $roleFile = __DIR__ . '/ROLES_PATCH/' . $className . '.php'; //
        
        if (!file_exists($roleFile)) {
            return new SimpleRole($player, $game, $role);
        }
        
        require_once $roleFile;
        
        if (!class_exists($className)) {
            return new SimpleRole($player, $game, $role);
        }
        
        return new $className($player, $game);
    }
    
    /**
     * دریافت نام کلاس نقش
     */
    public static function getRoleClass($role) {
        return self::$roleClasses[strtolower($role)] ?? null;
    }
    
    /**
     * بررسی وجود نقش
     */
    public static function roleExists($role) {
        return isset(self::$roleClasses[strtolower($role)]);
    }
    
    /**
     * دریافت لیست تمام نقش‌ها
     */
    public static function getAllRoles() {
        return array_keys(self::$roleClasses);
    }
    
    /**
     * دریافت نقش‌ها بر اساس تیم
     */
    public static function getRolesByTeam($team) {
        $teams = [
            'villager' => [
                'villager', 'seer', 'apprentice_seer', 'guardian_angel', 'knight', 
                'hunter', 'harlot', 'builder', 'blacksmith', 'gunner', 
                'mayor', 'prince', 'detective', 'cupid', 'beholder', 'phoenix',
                'huntsman', 'trouble', 'chemist', 'fool', 'clumsy', 'cursed', 
                'traitor', 'wild_child', 'wise_elder', 'sandman', 'sweetheart', 
                'ruler', 'spy', 'marouf', 'cult_hunter', 'hamal', 'jumong',
                'princess', 'wolf_man'
            ],
            
            'werewolf' => [
                'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'
            ],
            
            'vampire' => [
                'vampire', 'bloodthirsty', 'kent_vampire', 'chiang'
            ],
            
            'killer' => [
                'serial_killer', 'archer', 'davina'
            ],
            
            'cult' => [
                'cultist', 'royce', 'frankenstein', 'monk_black'
            ],
            
            'joker' => [
                'joker', 'harly'
            ],
            
            'fire_ice' => [
                'fire_king', 'ice_queen', 'lilith', 'magento'
            ],
            
            'black_knight' => [
                'black_knight', 'bride_dead'
            ],
            
            'independent' => [
                'dian', 'dinamit', 'bomber', 'tso', 'tanner', 'lucifer'
            ]
        ];
        
        return $teams[$team] ?? [];
    }
}

/**
 * 🎭 نقش ساده پیش‌فرض
 */
class SimpleRole extends Role {
    
    private $roleKey;
    private $customName;
    private $customEmoji;
    private $customTeam;
    
    public function __construct($player, $game, $roleKey) {
        parent::__construct($player, $game);
        $this->roleKey = $roleKey;
        $this->customName = $this->getDefaultName($roleKey);
        $this->customEmoji = $this->getDefaultEmoji($roleKey);
        $this->customTeam = $this->detectTeam($roleKey);
    }
    
    public function getName() {
        return $this->customName;
    }
    
    public function getEmoji() {
        return $this->customEmoji;
    }
    
    public function getTeam() {
        return $this->customTeam;
    }
    
    public function getDescription() {
        return "تو " . $this->customName . " " . $this->customEmoji . " هستی!";
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
    
    /**
     * تشخیص خودکار تیم
     */
    private function detectTeam($role) {
        $role = strtolower($role);
        
        if (in_array($role, ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'])) {
            return 'werewolf';
        }
        
        if (in_array($role, ['cultist', 'royce', 'frankenstein'])) {
            return 'cult';
        }
        
        if (in_array($role, ['serial_killer', 'archer', 'davina'])) {
            return 'killer';
        }
        
        if (in_array($role, ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'])) {
            return 'vampire';
        }
        
        if (in_array($role, ['joker', 'harly'])) {
            return 'joker';
        }
        
        if (in_array($role, ['fire_king', 'ice_queen', 'lilith', 'lucifer', 'magento'])) {
            return 'fire_ice';
        }
        
        if (in_array($role, ['black_knight', 'bride_dead'])) {
            return 'black_knight';
        }
        
        if (in_array($role, ['dian', 'dinamit', 'bomber', 'tso', 'tanner'])) {
            return 'independent';
        }
        
        return 'villager';
    }
    
    /**
     * نام پیش‌فرض نقش
     */
    private function getDefaultName($role) {
        $names = [
            'villager' => 'روستایی ساده',
            'seer' => 'پیشگو',
            'werewolf' => 'گرگینه',
            'cultist' => 'فرقه‌گرا',
            'serial_killer' => 'قاتل زنجیره‌ای',
            'vampire' => 'ومپایر',
            'joker' => 'جوکر',
            'magento' => 'مگنیتو',
            'tanner' => 'منافق',
            'cult_hunter' => 'شکارچی',
            'wolf_man' => 'گرگنما',
            'hamal' => 'حمال',
            'jumong' => 'جومونگ',
            'davina' => 'داوینا',
        ];
        
        return $names[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }
    
    /**
     * ایموجی پیش‌فرض نقش
     */
    private function getDefaultEmoji($role) {
        $emojis = [
            'villager' => '👨‍🌾',
            'seer' => '👳🏻‍♂️',
            'werewolf' => '🐺',
            'cultist' => '👤',
            'serial_killer' => '🔪',
            'vampire' => '🧛🏻‍♂️',
            'joker' => '🤡',
            'magento' => '🧲',
            'tanner' => '👺',
            'cult_hunter' => '💂🏻‍♂️',
            'wolf_man' => '🌑',
            'hamal' => '🛒',
            'jumong' => '🏹⚔️',
            'davina' => '🍾',
        ];
        
        return $emojis[$role] ?? '❓';
    }
}