<?php
/**
 * 🛡️🌿 معروف (Marouf) - دوست صمیمی شکار
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Marouf extends Role {
    
    protected $hunterId = null;      // آیدی شکارچی (نمی‌دونه کیه)
    protected $protectionLeft = 2;   // دو شب اول محافظت از شکارچی در شب
    
    public function getName() {
        return 'معروف';
    }
    
    public function getEmoji() {
        return '🛡️🌿';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو معروف 🛡️🌿 هستی، دوست صمیمی شکارچی! وظیفه‌ات محافظت از شکارچیه، ولی نمی‌دونی کیه! اگه شکارچی توی رای‌گیری رای بیاره، تو اجازه نمی‌دی اعدام بشه. اگه تفنگدار یا کلانتر (بعد مرگ) بخوان شکارچی رو بزنن، تو جلوی تیر رو می‌گیری. تا دو شب اول هم، اگه قاتل، گرگ یا هر نقش منفی بیاد سراغ شکارچی، تو جلوی حمله رو می‌گیری. ولی بعد از دو شب، توی شب هیچ کاری نمی‌تونی بکنی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function hasDayAction() {
        return false;
    }
    
    public function onGameStart() {
        $this->findHunter();
        $this->setData('protection_left', 2);
        
        // هیچ اطلاع‌رسانی به شکارچی و معروف نمی‌شه!
        $this->sendMessage("🛡️🌿 تو معروف هستی! یه دوست صمیمی توی روستا داری که شکارچیه، ولی نمی‌دونی کیه! باید حدس بزنی و ازش محافظت کنی!");
    }
    
    /**
     * پیدا کردن شکارچی (فقط برای سیستم، نه برای بازیکن)
     */
    private function findHunter() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'cult_hunter' && ($p['alive'] ?? false)) {
                $this->hunterId = $p['id'];
                $this->setData('hunter_id', $this->hunterId);
                break;
            }
        }
    }
    
    /**
     * بررسی رای‌گیری - جلوگیری از اعدام شکارچی
     */
    public function onLynchVote($targetId, $voteCount, $totalPlayers) {
        if (!$this->hunterId || !$this->isPlayerAlive($this->hunterId)) {
            return null;
        }
        
        if ($targetId != $this->hunterId) {
            return null;
        }
        
        $majority = ceil($totalPlayers / 2);
        if ($voteCount < $majority) {
            return null;
        }
        
        // 🛡️🌿 معروف وارد می‌شه!
        return [
            'prevent_lynch' => true,
            'skip_to_night' => true, // مستقیم شب می‌شه
            'message' => "🛡️🌿 وقتی داشتن شکارچی رو به سمت چوبه‌ی دار می‌بردن، معروف با سپر و برگش وارد میدون شد! مردم ترسیدن و اجازه ندادن شکارچی اعدام بشه! اون روز کسی اعدام نشد و شب فرا رسید...",
            'marouf_saved' => true
        ];
    }
    
    /**
     * جلوگیری از تیر تفنگدار به شکارچی
     */
    public function onGunnerShot($targetId, $gunnerId) {
        if (!$this->hunterId || $targetId != $this->hunterId) {
            return null;
        }
        
        if (!$this->isPlayerAlive($this->playerId)) {
            return null;
        }
        
        // معروف جلوی تیر رو می‌گیره
        $gunner = $this->getPlayerById($gunnerId);
        $this->sendMessageToPlayer($gunnerId, "🛡️🌿 می‌خواستی به یه نفر شلیک کنی، ولی معروف با سپرش جلوی تیر رو گرفت! نتونستی بزنی!");
        
        return [
            'prevented' => true,
            'message' => "🛡️🌿 تفنگدار می‌خواست شلیک کنه، ولی معروف با سپر و برگش جلوی تیر رو گرفت و نذاشت کسی آسیب ببینه!",
            'bullet_wasted' => false // گلوله هدر نمی‌ره، تفنگدار می‌تونه دوباره تلاش کنه
        ];
    }
    
    /**
     * جلوگیری از تیر کلانتر (بعد مرگ) به شکارچی
     */
    public function onHunterFinalShot($targetId, $hunterId) {
        if (!$this->hunterId || $targetId != $this->hunterId) {
            return null;
        }
        
        if (!$this->isPlayerAlive($this->playerId)) {
            return null;
        }
        
        $hunter = $this->getPlayerById($hunterId);
        $this->sendMessageToPlayer($hunterId, "🛡️🌿 تو آخرین لحظه می‌خواستی شلیک کنی، ولی معروف با سپرش جلوی تیر رو گرفت! نتونستی بزنی!");
        
        return [
            'prevented' => true,
            'message' => "🛡️🌿 کلانتر می‌خواست قبل مرگ شلیک کنه، ولی معروف جلوی تیر رو گرفت!"
        ];
    }
    
    /**
     * محافظت شبانه از شکارچی (فقط دو شب اول)
     */
    public function onNightAttack($targetId, $attackerRole, $attackerId) {
        if (!$this->hunterId || $targetId != $this->hunterId) {
            return null;
        }
        
        // بررسی دو شب اول
        $night = $this->game['night'] ?? 1;
        if ($night > 2) {
            return null; // بعد دو شب، معروف توی شب کاری نمی‌کنه
        }
        
        // بررسی نقش منفی
        if (!$this->isEvilRole($attackerRole)) {
            return null;
        }
        
        // معروف محافظت می‌کنه
        $this->decrementProtection();
        
        $attacker = $this->getPlayerById($attackerId);
        $this->sendMessageToPlayer($attackerId, "🛡️🌿 رفتی که شکارچی رو بکشی، ولی معروف با سپر و برگش جلوت رو گرفت! نتونستی کاری بکنی!");
        
        return [
            'prevented' => true,
            'message' => "🛡️🌿 معروف شب قبل از شکارچی محافظت کرد و حمله رو دفع کرد!",
            'night_saved' => true
        ];
    }
    
    /**
     * کاهش شمارنده محافظت
     */
    private function decrementProtection() {
        $this->protectionLeft--;
        $this->setData('protection_left', $this->protectionLeft);
        
        if ($this->protectionLeft <= 0) {
            $this->sendMessage("⚠️ دو شب محافظت تموم شد! از فردا شب نمی‌تونی توی شب از شکارچی محافظت کنی!");
        }
    }
    
    /**
     * بررسی نقش منفی
     */
    private function isEvilRole($role) {
        $evilRoles = [
            'serial_killer', 'qatel', 'killer', // قاتل
            'werewolf', 'wolf', 'alpha_wolf', 'wolf_cub', // گرگ
            'vampire', 'bloodthirsty', 'kent_vampire', // ومپایر
            'cult', 'cultist', 'royce', // فرقه
            'black_knight', // شوالیه تاریکی
            'archer', // کماندار (بعد از پیوستن به قاتل)
            'joker', 'harly', // جوکر
            'bomber', 'dinamit', // بمب‌گذار
            'lucifer', // لوسیفر
            'firefighter', 'ice_queen', // آتش و یخ (اگه منفی باشن)
            'magento', // مگنیتو
            'dian', // دیان
            'lilis', // لیلیث
            'bride_dead', // عروس مردگان
            'dozd', // دزد
            'cow', 'babr' // گاو و ببر
        ];
        
        return in_array($role, $evilRoles);
    }
    
    /**
     * وقتی شکارچی توی شب کشته می‌شه (بعد از دو شب)
     */
    public function onHunterKilledAtNight($hunterId) {
        if ($hunterId != $this->hunterId) {
            return null;
        }
        
        $night = $this->game['night'] ?? 1;
        
        if ($night <= 2) {
            // نباید اتفاق بیفته چون محافظت داره
            return null;
        }
        
        $this->sendMessage("😢 صبح بیدار شدی و دیدی دوست عزیزت (شکارچی) توی شب کشته شده... دیگه قدرت محافظت شبانه نداشتی. حالا تنها موندی!");
        
        return [
            'message' => "معروف 🛡️🌿 صبح متوجه مرگ شکارچی شد. بعد از دو شب دیگه نمی‌تونست محافظت کنه!",
            'mourning' => true
        ];
    }
    
    /**
     * وقتی خود معروف کشته می‌شه
     */
    public function onDeath($killerRole = null) {
        return [
            'team' => 'villager',
            'message' => 'معروف 🛡️🌿 مرد و دیگه کسی نیست از شکارچی محافظت کنه.'
        ];
    }
    
    /**
     * بررسی زنده بودن بازیکن
     */
    private function isPlayerAlive($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $playerId) {
                return $p['alive'] ?? false;
            }
        }
        return false;
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}