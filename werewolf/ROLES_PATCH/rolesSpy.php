<?php
/**
 * 🦹🏻‍♂️ جاسوس (Spy) - آپدیت شده با کنترل ذهنی
 * 
 * تیم: روستا
 * هر روز یک نفر رو زیر نظر می‌گیره و می‌فهمه توانایی کشتن داره یا نه
 * 30% احتمال کشتن حمله‌کننده به خاطر کنترل ذهنی
 */

require_once __DIR__ . '/base.php';

class Spy extends Role {
    
    public function getName() {
        return 'جاسوس';
    }
    
    public function getEmoji() {
        return '🦹🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو جاسوس🦹🏻‍♂️ هستی! توانایی ذهنی داری و می‌تونی ذهن افراد رو بخونی. هر روز یک نفر رو انتخاب می‌کنی و می‌فهمی توانایی کشتن داره یا نه (ولی نمی‌فهمی نقشش چیه). همچنین به خاطر کنترل ذهنی، ۳۰٪ احتمال داره هر کسی که بهت حمله کنه رو بکشی!";
    }
    
    public function hasNightAction() {
        return false; // جاسوس روزکاره
    }
    
    public function hasDayAction() {
        return true;
    }
    
    public function performDayAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو برای جاسوسی انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer) {
            return [
                'success' => false,
                'message' => '❌ بازیکن یافت نشد!'
            ];
        }
        
        if (!$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ این بازیکن مرده!'
            ];
        }
        
        $this->logAction('spy', $target);
        
        // بررسی توانایی کشتن
        $canKill = $this->canKill($targetPlayer);
        
        if ($canKill) {
            return [
                'success' => true,
                'message' => "🕵️ رفتی زیر نظر {$targetPlayer['name']} رو گرفتی و دیدی رفتارهای مشکوکی داره! متوجه شدی که {$targetPlayer['name']} توانایی کشتنِ بقیه رو داره! (ولی نمی‌دونی دقیقاً کیه)",
                'can_kill' => true
            ];
        } else {
            return [
                'success' => true,
                'message' => "🕵️ رفتی زیر نظر {$targetPlayer['name']} رو گرفتی. نتیجه تحقیقات نشون داد که {$targetPlayer['name']} یه شخص بی‌آزاره و نمی‌تونه کسی رو بکشه.",
                'can_kill' => false
            ];
        }
    }
    
    /**
     * 🧠 کنترل ذهنی - 30% احتمال کشتن حمله‌کننده
     */
    public function onAttacked($attackerRole, $attackerId) {
        $rand = rand(1, 100);
        
        if ($rand <= 30) {
            // موفق شد حمله‌کننده رو بکشه با کنترل ذهنی
            $attacker = $this->getPlayerById($attackerId);
            $this->killPlayer($attackerId, 'spy_mind_control');
            
            return [
                'died' => false,
                'killed_attacker' => true,
                'message' => "🦹🏻‍♂️ {$attacker['name']} بهت حمله کرد، ولی با توانایی کنترل ذهنیت، تونستی ذهنش رو کنترل کنی و خودشو کشتی! (۳۰٪ شانس)"
            ];
        }
        
        // مرد
        return ['died' => true];
    }
    
    /**
     * 🔍 بررسی آیا بازیکن توانایی کشتن داره
     */
    private function canKill($player) {
        $killerRoles = [
            'serial_killer',    // قاتل
            'werewolf',         // گرگ
            'alpha_wolf',       // گرگ آلفا
            'wolf_cub',         // توله گرگ
            'lycan',            // گرگ ایکس
            'forest_queen',     // ملکه جنگل
            'white_wolf',       // گرگ سفید
            'beta_wolf',        // گرگ خوابالو
            'ice_wolf',         // گرگ برفی
            'archer',           // کماندار
            'vampire',          // ومپایر
            'bloodthirsty',     // ومپایر اصیل
            'kent_vampire',     // کنت ومپایر
            'kentvampire',      // کنت ومپایر (alias)
            'hunter',           // کلانتر
            'knight',           // شوالیه
            'black_knight',     // شوالیه تاریکی
            'bride_dead',       // عروس مردگان
            'blacksmith',       // آهنگر (نقره پاشی + شمشیر)
            'bomber',           // بمب‌گذار
            'dinamit',          // دینامیت
            'joker',            // جوکر
            'harly',            // هارلی کویین
            'harley_quinn',     // هارلی کویین (alias)
            'lilith',           // لیلیث
            'lilis',            // لیلیث (alias)
            'lucifer',          // لوسیفر
            'fire_king',        // پادشاه آتش
            'firefighter',      // پادشاه آتش (alias)
            'ice_queen',        // ملکه یخی
            'magento',          // مگنیتو
            'dian',             // دیان
            'djinn',            // دیان (alias)
            'chiang',           // چیانگ
            'royce',            // رئیس فرقه
            'frankenstein',     // فرانکشتاین
            'franc',            // فرانکشتاین (alias)
            'enchanter',        // افسونگر
            'honey',            // عجوزه
            'honey_witch',      // عجوزه (alias)
            'huntsman',         // هانتسمن
            'princess',         // پرنسس (دستگیری)
            'ruler',            // حاکم
            'tanner',           // منافق
            'tso',              // تسو
            'fool',             // احمق
            'cow',              // گاو
            'babr',             // ببر
            'phoenix',          // ققنوس (شب ۳ و ۵)
            'chemist',          // شیمیدان
            'grave_digger',     // گورکن
            'gravedigger',      // گورکن (alias)
            'botanist',         // گیاه شناس
            'augur',            // رمال
            'aurora',           // رمال (alias)
            'harlot',           // ناتاشا
            'guardian_angel',   // فرشته نگهبان
            'guard',            // فرشته نگهبان (alias)
            'seer',             // پیشگو
            'apprentice_seer',  // شاگرد پیشگو
            'shapred_seer',     // شاگرد پیشگو (alias)
            'detective',        // کاراگاه
            'cupid',            // الهه عشق
            'sweetheart',       // دلبر
            'trouble',          // دختر دردسرساز
            'davina',           // داوینا
            'ghost',            // روح
            'mummy',            // مومیایی
            'watermelon',       // هندوانه
            'dozd',             // دزد
            'monk_black',       // راهب سیاه
        ];
        
        // نقش‌هایی که فقط توی شرایط خاص می‌تونن بکشن
        $conditionalKillers = [
            'gunner' => function($p) {
                // تفنگدار اگه گلوله داشته باشه
                return ($p['role_data']['bullets'] ?? 0) > 0;
            },
            'cult_hunter' => function($p) {
                // شکارچی توانایی کشتن داره (مخصوصاً فرقه)
                return true;
            },
            'cultist' => function($p) {
                // فرقه‌گرا نمی‌تونه بکشه (فقط دعوت می‌کنه)
                return false;
            },
            'pacifist' => function($p) {
                // صلح‌طلب نمی‌تونه بکشه
                return false;
            },
            'sandman' => function($p) {
                // خوابگزار نمی‌تونه بکشه
                return false;
            },
            'mayor' => function($p) {
                // کدخدا نمی‌تونه بکشه
                return false;
            },
            'prince' => function($p) {
                // شاهزاده نمی‌تونه بکشه
                return false;
            },
            'wise_elder' => function($p) {
                // ریش سفید نمی‌تونه بکشه
                return false;
            },
            'builder' => function($p) {
                // بنا نمی‌تونه بکشه
                return false;
            },
            'beholder' => function($p) {
                // شاهد نمی‌تونه بکشه
                return false;
            },
            'mason' => function($p) {
                // فراماسون نمی‌تونه بکشه
                return false;
            },
            'cursed' => function($p) {
                // نفرین شده فقط بعد تبدیل به گرگ می‌تونه بکشه
                return false;
            },
            'traitor' => function($p) {
                // خائن فقط بعد تبدیل به گرگ می‌تونه بکشه
                return false;
            },
            'wild_child' => function($p) {
                // بچه وحشی فقط بعد تبدیل به گرگ می‌تونه بکشه
                return false;
            },
            'doppelganger' => function($p) {
                // همزاد فقط بعد گرفتن نقش می‌تونه بکشه
                return false;
            },
            'village_idiot' => function($p) {
                // احمق نمی‌تونه بکشه
                return false;
            },
            'drunk' => function($p) {
                // مست نمی‌تونه بکشه
                return false;
            },
            'clumsy' => function($p) {
                // پسر گیج نمی‌تونه بکشه
                return false;
            },
            'oracle' => function($p) {
                // پیشگوی نگاتیو نمی‌تونه بکشه
                return false;
            },
        ];
        
        $role = $player['role'] ?? '';
        
        // چک کردن نقش‌های ثابت
        if (in_array($role, $killerRoles)) {
            return true;
        }
        
        // چک کردن نقش‌های شرطی
        if (isset($conditionalKillers[$role])) {
            return $conditionalKillers[$role]($player);
        }
        
        return false;
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
                'callback' => 'spy_' . $p['id']
            ];
        }
        return $targets;
    }
}