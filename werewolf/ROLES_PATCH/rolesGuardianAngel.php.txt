<?php
/**
 * 👼🏻 فرشته نگهبان (GuardianAngel)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class GuardianAngel extends Role {
    
    protected $lastGuarded = null;    // آخرین کسی که محافظت کرده
    protected $guardWolfDeath = false; // آیا در اثر محافظت از گرگ مرده؟
    protected $convertedToVillager = false; // آیا تبدیل به روستایی شده؟
    
    public function getName() {
        return 'فرشته نگهبان';
    }
    
    public function getEmoji() {
        return '👼🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو فرشته نگهبان 👼🏻 هستی! هر شب می‌تونی از یه نفر محافظت کنی. اگر اون شخص گرگ باشه، ۵۰٪ احتمال داره که خودت بمیری! ⚠️ توجه: در مقابل قاتل روانی هیچ کاری از دستت بر نمیاد! ⚠️ اگه از لوسیفر محافظت کنی، جفتتون روستایی ساده می‌شین!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ بنظرت امشب چه کسی نیاز به محافظت داره؟'
            ];
        }
        
        // نمی‌تونه دو شب پیاپی از یکی محافظت کنه
        if ($target == $this->lastGuarded) {
            return [
                'success' => false,
                'message' => '⚠️ نمی‌تونی دو شب پیاپی از یک نفر محافظت کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->lastGuarded = $target;
        
        // ⚠️ اگه لوسیفر باشه، جفتشون روستایی ساده می‌شن!
        if ($targetPlayer['role'] == 'lucifer') {
            return $this->convertBothToVillager($targetPlayer);
        }
        
        // بررسی آیا گرگ است
        if ($this->isWolf($targetPlayer['role'])) {
            $deathChance = rand(1, 100);
            if ($deathChance <= 50) {
                $this->guardWolfDeath = true;
                $this->killPlayer($this->getId(), 'guardian_wolf');
                
                return [
                    'success' => true,
                    'message' => "😇 رفتی از {$targetPlayer['name']} محافظت کنی ولی اون گرگ بود و تورو خورد! به جمع مردگان پیوستی...",
                    'died' => true
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت کردی!" . ($this->isKiller($targetPlayer['role']) ? "\n⚠️ ولی بدون که اگه قاتل بیاد، کاری از دستت بر نمیاد!" : ""),
            'guarding' => $target
        ];
    }
    
    /**
     * تبدیل جفتشون به روستایی ساده وقتی فرشته از لوسیفر محافظت می‌کنه
     */
    private function convertBothToVillager($luciferPlayer) {
        $this->convertedToVillager = true;
        
        // تبدیل فرشته به روستایی ساده
        $this->setPlayerRole($this->getId(), 'villager');
        
        // تبدیل لوسیفر به روستایی ساده
        $this->setPlayerRole($luciferPlayer['id'], 'villager');
        
        // پیام به فرشته
        $this->sendMessageToPlayer($this->getId(), "😇 رفتی از {$luciferPlayer['name']} محافظت کنی ولی اون لوسیفر 👹 بود! نور مقدس فرشته و تاریکی شیطان با هم برخورد کرد و هر دوتاتون تبدیل به روستایی ساده 👨🏻 شدین!");
        
        // پیام به لوسیفر
        $this->sendMessageToPlayer($luciferPlayer['id'], "👹 فرشته نگهبان اومد خونه‌ت و ازت محافظت کرد! نور مقدسش با تاریکی درونت برخورد کرد و هر دوتاتون تبدیل به روستایی ساده 👨🏻 شدین!");
        
        // اعلام در گروه
        $this->sendMessageToGroup("✨ یه معجزه رخ داد! فرشته نگهبان و لوسیفر با هم ملاقات کردن و نور، تاریکی رو شکست داد! هر دوتاشون الان روستایی ساده 👨🏻 هستن!");
        
        return [
            'success' => true,
            'message' => "✨ از {$luciferPlayer['name']} محافظت کردی ولی اون لوسیفر بود! جفتتون تبدیل به روستایی ساده شدین!",
            'converted' => true,
            'both_converted' => true
        ];
    }
    
    /**
     * وقتی هدف مورد حمله قرار می‌گیره
     */
    public function onAttackTarget($targetId, $attackerRole = null) {
        // اگه تبدیل به روستایی شده، دیگه نمی‌تونه محافظت کنه
        if ($this->convertedToVillager) {
            return ['protected' => false, 'converted' => true];
        }
        
        if ($this->lastGuarded != $targetId) {
            return ['protected' => false];
        }
        
        $target = $this->getPlayerById($targetId);
        
        // ⚠️ قاتل روانی - فرشته هیچ کاری نمی‌تونه بکنه!
        if ($attackerRole == 'serial_killer' || $attackerRole == 'killer') {
            $this->sendMessageToPlayer($this->getId(), "😰 سعی کردی از {$target['name']} محافظت کنی ولی قاتل ازت رد شد و کشتش! دست خالی برگشتی!");
            $this->sendMessageToPlayer($targetId, "🔪 قاتل اومد خونه‌ت و فرشته نگهبان سعی کرد جلوت رو بگیره ولی قاتل ازش رد شد و تو رو کشت!");
            
            return [
                'protected' => false,
                'killer_dominance' => true,
                'message' => "👼🏻 فرشته سعی کرد از {$target['name']} محافظت کنه ولی قاتل ازش رد شد!"
            ];
        }
        
        // حمله گرگ
        if ($this->isWolf($attackerRole)) {
            $this->sendMessageToPlayer($targetId, "🛡️ باید خوشحال باشی که هنوز زنده‌ای... دیشب گرگ‌ها بهت حمله کردن ولی 👼🏻 فرشته نگهبان جونتو نجات داد!");
            return [
                'protected' => true,
                'message' => "👼🏻 فرشته از {$target['name']} در برابر گرگ محافظت کرد!"
            ];
        }
        
        // سایر حملات
        $this->sendMessageToPlayer($targetId, "🛡️ باید خوشحال باشی که هنوز زنده‌ای... دیشب می‌خواستن بکشننت ولی 👼🏻 فرشته نگهبان جونتو نجات داد!");
        
        return [
            'protected' => true,
            'message' => "👼🏻 فرشته از {$target['name']} محافظت کرد!"
        ];
    }
    
    /**
     * بررسی گرگ بودن
     */
    private function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
    
    /**
     * بررسی قاتل بودن
     */
    private function isKiller($role) {
        return in_array($role, ['serial_killer', 'killer', 'archer']);
    }
    
    public function getValidTargets($phase = 'night') {
        // اگه تبدیل به روستایی شده، دیگه اکشن نداره
        if ($this->convertedToVillager) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه از خودش محافظت کنه
            if ($p['id'] == $this->getId()) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'guardian_' . $p['id']
            ];
        }
        return $targets;
    }
}