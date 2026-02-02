<?php
/**
 * 🪓 هانتسمن (Huntsman)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Huntsman extends Role {
    
    protected $traps = 2;             // تعداد تله‌های باقی‌مانده
    protected $placedTraps = [];      // تله‌های کار گذاشته شده
    protected $isHunter = false;      // آیا تبدیل به شکارچی شده؟
    
    public function getName() {
        return 'هانتسمن';
    }
    
    public function getEmoji() {
        return '🪓';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو هانتسمن 🪓 هستی، شاگرد شکارچی! هر شب می‌تونی جلوی خونه روستایی‌ها تله بزاری (فقط ۲ تا). اگر نقش شب‌کار بیاد خونشون، ۵۰٪ امکان داره توی تله گیر کنه و زخمی شه و تو قبل از بیدار شدن روستایی‌ها با کمک سگ شکاریت پیداش می‌کنی و می‌کشی!";
    }
    
    public function hasNightAction() {
        return !$this->isHunter && $this->traps > 0;
    }
    
    public function performNightAction($target = null) {
        if ($this->isHunter) {
            return [
                'success' => false,
                'message' => '❌ الان شکارچی شدی و باید از قابلیت شکارچی استفاده کنی!'
            ];
        }
        
        if ($this->traps <= 0) {
            return [
                'success' => false,
                'message' => '❌ تله‌ات تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => "❌ امشب می‌خوای جلو خونه چه کسی تله بزاری؟ ({$this->traps} تا تله داری)"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->traps--;
        $this->placedTraps[$target] = true;
        
        return [
            'success' => true,
            'message' => "🕳️ خب تو دیشب رفتی دم خونه {$targetPlayer['name']} تله گذاشتی!",
            'trap_placed' => $target
        ];
    }
    
    public function onVisitor($visitorId, $visitorRole) {
        // بررسی آیا تله کار گذاشته
        if (!isset($this->placedTraps[$visitorId])) {
            return;
        }
        
        // ۵۰٪ شانس گیر افتادن
        $catchChance = rand(1, 100);
        if ($catchChance > 50) {
            return; // فرار کرد
        }
        
        // گیر افتادن در تله
        $visitor = $this->getPlayerById($visitorId);
        
        // کشتن
        $this->killPlayer($visitorId, 'huntsman_trap');
        
        $this->sendMessageToGroup("🪓 دیشب {$visitor['name']} در دام تله‌ای که هانتسمن گذاشته بود گرفتار می‌شه و با بدن زخمی به سمت جنگل فرار می‌کنه اما دم دم‌های صبح قبل از روشن شدن هوا هانتسمن با کمک سگ شکاریش رد قطره‌های خون رو دنبال می‌کنه و {$visitor['name']} رو در جنگل پیدا می‌کنه و می‌کشه!");
        
        return ['cancelled' => true, 'killed' => true];
    }
    
    public function becomeHunter() {
        $this->isHunter = true;
        $this->sendMessageToPlayer($this->getId(), "🏹 متاسفانه برای شکارچی اتفاق بدی افتاده و الان تو شکارچی جدید روستا هستی! موفق باشی!");
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->isHunter || $this->traps <= 0) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'huntsman_' . $p['id']
            ];
        }
        return $targets;
    }
}