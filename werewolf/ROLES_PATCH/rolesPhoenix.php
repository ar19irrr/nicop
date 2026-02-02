<?php
/**
 * 🪶 ققنوس (Phoenix)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Phoenix extends Role {
    
    protected $tears = 2;             // تعداد اشک‌های باقی‌مانده
    protected $tearTargets = [];      // کسانی که اشک بهشون داده شده
    protected $giveNight = [3, 5];    // شب‌هایی که می‌تونه اشک بده
    
    public function getName() {
        return 'ققنوس';
    }
    
    public function getEmoji() {
        return '🪶';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو ققنوس 🪶 هستی! پرنده‌ای زیبا که اشک تو خاصیت درمان داره! دارای ۲ اشک مقدسی که در شب‌های ۳ و ۵ می‌تونی به پیکر یکی از اهالی هدیه کنی و جونش رو در برابر خطرات شب نجات بدی!";
    }
    
    public function hasNightAction() {
        $currentNight = $this->getCurrentNight();
        return in_array($currentNight, $this->giveNight) && $this->tears > 0;
    }
    
    public function performNightAction($target = null) {
        $currentNight = $this->getCurrentNight();
        
        if (!in_array($currentNight, $this->giveNight)) {
            return [
                'success' => false,
                'message' => '⏳ هنوز زمان هدیه دادن اشک نرسیده! فقط شب‌های ۳ و ۵ می‌تونی!'
            ];
        }
        
        if ($this->tears <= 0) {
            return [
                'success' => false,
                'message' => '❌ اشک‌هایت تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب باید یک نفر رو انتخاب کنی تا اشک مقدست بتونه به روستایی‌ها کمک کنه!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->tears--;
        $this->tearTargets[] = $target;
        
        // پیام به هدف
        $this->sendMessageToPlayer($target, "✨ همونطور که در حال قدم زدن توی محوطه‌ی خونه‌ت بودی صدای مهیب بال‌های ققنوس 🪶 رو می‌شنوی که به تو نزدیک می‌شه و روی شونه‌هات می‌شینه؛ به چشمانش خیره شدی و متوجه قطره اشکی شدی که داره به تو تقدیم می‌شه!");
        
        return [
            'success' => true,
            'message' => "🪶 با موفقیت قطره اشکت رو به {$targetPlayer['name']} تقدیم کردی!",
            'tear_given' => $target
        ];
    }
    
    public function onAttack($targetId) {
        // بررسی آیا این شخص اشک داره
        if (!in_array($targetId, $this->tearTargets)) {
            return ['protected' => false];
        }
        
        // نجات از حمله
        $this->sendMessageToPlayer($targetId, "🛡️ دیشب بهت حمله شد و به شدت زخمی شدی اما متوجه شدی زخمات به سرعت سربسته می‌شن! درست متوجه شدی! اشک ققنوس 🪶 توی رگ‌هات جاریه و کسی نمی‌تونه بهت صدمه بزنه!");
        
        // حذف اشک
        $key = array_search($targetId, $this->tearTargets);
        unset($this->tearTargets[$key]);
        
        return ['protected' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $currentNight = $this->getCurrentNight();
        if (!in_array($currentNight, $this->giveNight) || $this->tears <= 0) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'phoenix_' . $p['id']
            ];
        }
        return $targets;
    }
}