<?php
/**
 * 🏭 فکتوری ساخت نقش‌ها
 */

require_once __DIR__ . '/base.php';

class RoleFactory {
    
    /**
     * نقشه کلاس‌های نقش
     */
    private static $roleClasses = [
        // ===== روستا =====
        'villager' => 'Villager',
        'seer' => 'Seer',
        'apprentice_seer' => 'ApprenticeSeer',
        'guardian_angel' => 'GuardianAngel',
        'knight' => 'Knight',
        'hunter' => 'Hunter',
        'harlot' => 'Harlot',
        'builder' => 'Builder',
        'blacksmith' => 'Blacksmith',
        'gunner' => 'Gunner',
        'mayor' => 'Mayor',
        'prince' => 'Prince',
        'detective' => 'Detective',
        'cupid' => 'Cupid',
        'beholder' => 'Beholder',
        'phoenix' => 'Phoenix',
        'huntsman' => 'Huntsman',
        'trouble' => 'Trouble',
        'chemist' => 'Chemist',
        'fool' => 'Fool',
        'clumsy' => 'Clumsy',
        'cursed' => 'Cursed',
        'traitor' => 'Traitor',
        'wild_child' => 'WildChild',
        'wise_elder' => 'WiseElder',
        'sandman' => 'Sandman',
        'sweetheart' => 'Sweetheart',
        'ruler' => 'Ruler',
        'spy' => 'Spy',
        'marouf' => 'Marouf',
        'cult_hunter' => 'CultHunter',
        'hamal' => 'Hamal',
        'jumong' => 'Jumong',
        'princess' => 'Princess',
        'wolf_man' => 'WolfMan',
        'drunk' => 'Drunk',
        
        // ===== گرگ =====
        'werewolf' => 'Werewolf',
        'alpha_wolf' => 'AlphaWolf',
        'wolf_cub' => 'WolfCub',
        'lycan' => 'Lycan',
        'forest_queen' => 'ForestQueen',
        'white_wolf' => 'WhiteWolf',
        'beta_wolf' => 'BetaWolf',
        'ice_wolf' => 'IceWolf',
        'enchanter' => 'Enchanter',
        'honey' => 'Honey',
        'sorcerer' => 'Sorcerer',
        
        // ===== ومپایر =====
        'vampire' => 'Vampire',
        'bloodthirsty' => 'Bloodthirsty',
        'kent_vampire' => 'KentVampire',
        'chiang' => 'Chiang',
        
        // ===== قاتل =====
        'serial_killer' => 'SerialKiller',
        'archer' => 'Archer',
        'davina' => 'Davina',
        
        // ===== شوالیه تاریکی =====
        'black_knight' => 'BlackKnight',
        'bride_dead' => 'BrideDead',
        
        // ===== جوکر =====
        'joker' => 'Joker',
        'harly' => 'Harly',
        
        // ===== آتش و یخ =====
        'fire_king' => 'FireKing',
        'ice_queen' => 'IceQueen',
        'lilith' => 'Lilith',
        'magento' => 'Magento',
        
        // ===== فرقه =====
        'cultist' => 'Cultist',
        'royce' => 'Royce',
        'frankenstein' => 'Frankenstein',
        'monk_black' => 'MonkBlack',
        
        // ===== مستقل =====
        'dian' => 'Dian',
        'dinamit' => 'Dinamit',
        'bomber' => 'Bomber',
        'tso' => 'Tso',
        'tanner' => 'Tanner',
        'lucifer' => 'Lucifer',
        'doppelganger' => 'Doppelganger',
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
        
        $roleFile = __DIR__ . '/' . $className . '.php';
        
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
                'princess', 'wolf_man', 'drunk'
            ],
            'werewolf' => [
                'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen',
                'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'
            ],
            'vampire' => [
                'vampire', 'bloodthirsty', 'kent_vampire', 'chiang'
            ],
            'cult' => [
                'cultist', 'royce', 'frankenstein', 'monk_black'
            ],
            'killer' => [
                'serial_killer', 'archer', 'davina'
            ],
            'fire_ice' => [
                'fire_king', 'ice_queen', 'lilith', 'magento'
            ],
            'black_knight' => [
                'black_knight', 'bride_dead'
            ],
            'joker' => [
                'joker', 'harly'
            ],
            'independent' => [
                'dian', 'dinamit', 'bomber', 'tso', 'tanner', 'lucifer', 'doppelganger'
            ]
        ];
        
        return $teams[$team] ?? [];
    }
}

/**
 * 🎭 نقش ساده پیش‌فرض (زمانی که فایل نقش وجود نداشته باشد)
 */
class SimpleRole extends Role {
    
    private $roleKey;
    
    public function __construct($player, $game, $roleKey) {
        parent::__construct($player, $game);
        $this->roleKey = $roleKey;
    }
    
    public function getName() {
        $names = [
            'villager' => 'روستایی ساده',
            'seer' => 'پیشگو',
            'werewolf' => 'گرگینه',
            'cultist' => 'فرقه‌گرا',
            'serial_killer' => 'قاتل زنجیره‌ای',
            'vampire' => 'ومپایر',
            'joker' => 'جوکر',
            'tanner' => 'منافق',
        ];
        return $names[$this->roleKey] ?? ucfirst(str_replace('_', ' ', $this->roleKey));
    }
    
    public function getEmoji() {
        $emojis = [
            'villager' => '👨‍🌾',
            'seer' => '👳🏻‍♂️',
            'werewolf' => '🐺',
            'cultist' => '👤',
            'serial_killer' => '🔪',
            'vampire' => '🧛🏻‍♂️',
            'joker' => '🤡',
            'tanner' => '👺',
        ];
        return $emojis[$this->roleKey] ?? '❓';
    }
    
    public function getTeam() {
        return detectTeam($this->roleKey);
    }
    
    public function getDescription() {
        return "تو " . $this->getName() . " " . $this->getEmoji() . " هستی!";
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}