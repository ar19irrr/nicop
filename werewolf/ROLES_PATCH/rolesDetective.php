<?php
/**
 * 🕵🏻‍♂️ کاراگاه (Detective)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Detective extends Role {
    
    protected $investigatedPlayers = []; // بازیکنانی که تحقیق کرده
    
    public function getName() {
        return 'کاراگاه';
    }
    
    public function getEmoji() {
        return '🕵🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو کاراگاه 🕵🏻‍♂️ هستی! هر روز می‌تونی در مورد یک نفر تحقیق کنی. ولی در حین انجام تحقیقات، ۴۰٪ احتمال داره که گرگ‌ها بشناسنت!";
    }
    
    public function hasDayAction() {
        return true;
    }
    
    public function performDayAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کیو می‌خوای استعلام کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->investigatedPlayers[$target] = $targetPlayer['role'];
        
        // ۴۰٪ احتمال شناسایی توسط گرگ‌ها
        $discovered = rand(1, 100) <= 40;
        if ($discovered) {
            $this->notifyWolves("🐺 کاراگاه داره تحقیق می‌کنه! {$this->getPlayerName()} رو پیدا کردیم!");
        }
        
        // بررسی طلسم عجوزه
        if (isset($targetPlayer['cursed_by_honey']) && $targetPlayer['cursed_by_honey']) {
            $displayRole = 'werewolf'; // نشون دادن به عنوان گرگ
        }
        // بررسی گرگ ایکس - شاهزاده نشون داده می‌شه
        elseif ($targetPlayer['role'] == 'lycan') {
            $displayRole = 'prince'; // نشون دادن به عنوان شاهزاده
        }
        // بررسی گرگنما
        elseif ($targetPlayer['role'] == 'wolf_man') {
            $displayRole = 'werewolf'; // نشون دادن به عنوان گرگ
        }
        else {
            $displayRole = $targetPlayer['role'];
        }
        
        $roleName = $this->getRoleDisplayName($displayRole);
        
        return [
            'success' => true,
            'message' => "🕵🏻‍♂️ بعد از کلی تحقیق و تجسسِ مخفیانه در مورد {$targetPlayer['name']}، بالاخره فهمیدی که اون یه {$roleName} هست!" . ($discovered ? "\n\n⚠️ متأسفانه گرگ‌ها متوجه تحقیقاتت شدن!" : ""),
            'investigated_role' => $displayRole,
            'discovered_by_wolves' => $discovered
        ];
    }
    
    private function getRoleDisplayName($role) {
        // لیست کامل همه نقش‌های بازی (مثل پیشگو)
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
    
    private function notifyWolves($message) {
        // ارسال پیام به تیم گرگ
        $wolves = $this->getPlayersByTeam('werewolf');
        foreach ($wolves as $wolf) {
            if ($wolf['alive']) {
                $this->sendMessageToPlayer($wolf['id'], $message);
            }
        }
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase != 'day') {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'detective_' . $p['id']
            ];
        }
        return $targets;
    }
}