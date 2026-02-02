<?php
/**
 * 👳🏻‍♂️ پیشگو (Seer)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Seer extends Role {
    
    protected $seenPlayers = [];      // بازیکنانی که دیده
    
    public function getName() {
        return 'پیشگو';
    }
    
    public function getEmoji() {
        return '👳🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو پیشگو 👳🏻‍♂️ هستی! هر شب می‌تونی نقش یک نفر رو ببینی. باید سعی کنی نقش‌های منفی مثل گرگ، قاتل، ومپایر رو پیدا کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ نقش چه کسی رو می‌خوای ببینی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $realRole = $targetPlayer['role'];
        $this->seenPlayers[$target] = $realRole;
        
        // بررسی طلسم عجوزه
        if (isset($targetPlayer['cursed_by_honey']) && $targetPlayer['cursed_by_honey']) {
            $displayRole = 'werewolf'; // نشون دادن به عنوان گرگ
        }
        // بررسی گرگ ایکس - شاهزاده نشون داده می‌شه
        elseif ($realRole == 'lycan') {
            $displayRole = 'prince'; // نشون دادن به عنوان شاهزاده
        }
        // بررسی گرگنما
        elseif ($realRole == 'wolf_man') {
            $displayRole = 'werewolf'; // نشون دادن به عنوان گرگ
        }
        else {
            $displayRole = $realRole;
        }
        
        $roleName = $this->getRoleDisplayName($displayRole);
        
        return [
            'success' => true,
            'message' => "👁️ تو دیدی که {$targetPlayer['name']} یه {$roleName} هست!",
            'seen_role' => $displayRole
        ];
    }
    
    private function getRoleDisplayName($role) {
        // لیست کامل همه نقش‌های بازی
        $names = [
            // تیم روستا
            'seer' => '👳🏻‍♂️ پیشگو',
            'villager' => '👨🏻 روستایی ساده',
            'guardian_angel' => '👼🏻 فرشته نگهبان',
            'knight' => '🗡 شوالیه',
            'hunter' => '👮🏻‍♂️ کلانتر',
            'harlot' => '💋 ناتاشا',
            'mason' => '👷🏻‍♂️ بنّا',
            'blacksmith' => '⚒ آهنگر',
            'gunner' => '🔫 تفنگدار',
            'mayor' => '🎖 کدخدا',
            'prince' => '🤴🏻 شاهزاده',
            'detective' => '🕵🏻‍♂️ کاراگاه',
            'cupid' => '💘 الهه عشق',
            'apprentice_seer' => '🙇🏻‍♂️ شاگرد پیشگو',
            'beholder' => '👁 شاهد',
            'gravedigger' => '☠️ گورکن',
            'aurora' => '🦅 رمال',
            'phoenix' => '🪶 ققنوس',
            'huntsman' => '🪓 هانتسمن',
            'botanist' => '🍂 گیاه‌شناس',
            'trouble' => '👩🏻‍🌾 دختر دردسرساز',
            'ghost' => '👻 روح',
            'chemist' => '👨‍🔬 شیمیدان',
            'fool' => '🃏 احمق',
            'clumsy' => '🤕 پسر گیج',
            'cursed' => '😾 نفرین شده',
            'traitor' => '🖕🏿 خائن',
            'wild_child' => '👶🏻 بچه وحشی',
            'wise_elder' => '📚 ریش سفید',
            'pacifist' => '☮️ صلح‌طلب',
            'sandman' => '💤 خوابگذار',
            'oracle' => '🌀 پیشگوی نگاتیوی',
            'sweetheart' => '👰🏻 دلبر',
            'ruler' => '👑 حاکم',
            'tanner' => '👺 منافق',
            
            // تیم گرگ
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
            'wolf_man' => '🌑👨🏻 گرگنما',
            
            // تیم قاتل
            'killer' => '🔪 قاتل',
            'archer' => '🏹 کماندار',
            'davina' => '🍾 داوینا',
            
            // تیم ومپایر
            'vampire' => '🧛🏻‍♂️ ومپایر',
            'bloodthirsty' => '🧛🏻‍♀️ ومپایر اصیل',
            'kent_vampire' => '💍🧛🏻 کنت ومپایر',
            'chiang' => '👩‍🦳 چیانگ',
            
            // تیم شوالیه تاریکی
            'black_knight' => '🥷🗡 شوالیه تاریکی',
            'bride_dead' => '👰‍♀☠️ عروس مردگان',
            
            // تیم جوکر
            'joker' => '🤡 جوکر',
            'harly' => '👩🏻‍🎤 هارلی کویین',
            
            // تیم مگنیتو
            'magento' => '🧲 مگنیتو',
            
            // تیم آتش و یخ
            'firefighter' => '🔥🤴🏻 پادشاه آتش',
            'ice_queen' => '❄️👸🏻 ملکه یخی',
            'lilis' => '🐍👩🏻‍🦳 لیلیث',
            
            // تیم فرقه
            'cultist' => '👤 فرقه‌گرا',
            'cult_hunter' => '💂🏻‍♂️ شکارچی',
            'royce' => '🎩 رئیس',
            'franc' => '🧟‍♂️🪖 فرانکشتاین',
            'mummy' => '⚰️ مومیایی',
            
            // مستقل
            'dian' => '🧞‍♂️ دیان',
            'dinamit' => '🧨 دینامیت',
            'bomber' => '💣 بمب‌گذار',
            'princess' => '👸🏻 پرنسس',
            'serial_killer' => '🔪 قاتل زنجیره‌ای',
        ];
        
        return $names[$role] ?? "❓ {$role}";
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'seer_' . $p['id']
            ];
        }
        return $targets;
    }
}