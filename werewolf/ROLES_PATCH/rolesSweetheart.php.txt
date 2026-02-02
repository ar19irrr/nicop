<?php
/**
 * 👰🏻 دلبر (Sweetheart)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Sweetheart extends Role {
    
    protected $loverId = null;        // آیدی معشوق
    protected $isLover = false;       // آیا عاشق شده؟
    
    public function getName() {
        return 'دلبر';
    }
    
    public function getEmoji() {
        return '👰🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو دلبر 👰🏻 هستی! یه روستایی مهربون و زیبا. اگر هر کدوم از اهالی روستا توی شب بیاد خونت (حتی اگر قصد کشتنت رو داشته باشه)، بجای اینکه بکشتت با دیدن چهره زیبا و چشمای خوشگلت عاشقت می‌شه و تو هم عاشقش می‌شی. (اگر اون بمیره تو هم می‌میری!)";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onVisitor($visitorId, $visitorRole) {
        // هر کسی بهش حمله کنه، عاشقش می‌شه
        if ($this->isLover) {
            return; // قبلاً عاشق شده
        }
        
        $visitor = $this->getPlayerById($visitorId);
        if (!$visitor) return;
        
        $this->loverId = $visitorId;
        $this->isLover = true;
        
        // پیام به دلبر
        $this->sendMessageToPlayer($this->getId(), "💖 توی حیاط خونه‌ت قدم می‌زدی که یهو {$visitor['name']} رو جلوت می‌بینی که بهت خیره شده، از چشماش معلومه که بدجوری مجذوب چهره‌ی زیبای تو شده! اولش شوکه شدی، ولی بعدش قلب مهربونت متوجه‌ی عشقی که {$visitor['name']} نسبت به تو داره می‌شه. همدیگرو بغل کردین و تو هم فکر می‌کنی که خوبه اونو به عنوان عشق جدیدت قبول کنی.");
        
        // پیام به بازدیدکننده
        $this->sendMessageToPlayer($visitorId, "💖 رفتی خونه {$this->getPlayerName()} که... دیدی خوابیده. پتوشو کنار کشیدی... صورت ناز و خوشگلش ناگهان باعث شد چاقوت/نیتت رو به زمین بندازی و به کلی فراموش کنی چرا اومده بودی خونه‌ش! چهره‌ی ماهش باعث شد عاشقش بشی. اونم بیدار شد و باهات عشق بازی کرد... از حالا شما دوتا عاشق همین!");
        
        // اگر گرگ باشه، کل تیم رو مطلع کنیم
        if ($this->isWolf($visitorRole)) {
            $this->notifyWolfTeam("🐺❤️ یکی از گرگ‌ها عاشق دلبر شد و حمله متوقف شد!");
        }
        
        // جلوگیری از کشتن
        return ['cancel_action' => true];
    }
    
    public function onLoverDeath() {
        if (!$this->isLover || !$this->loverId) {
            return;
        }
        
        $lover = $this->getPlayerById($this->loverId);
        if ($lover && !$lover['alive']) {
            // خودکشی
            $this->killPlayer($this->getId(), 'sweetheart_suicide');
            $this->sendMessageToGroup("💔 {$this->getPlayerName']} از غم مرگ معشوقش خودکشی کرد!");
        }
    }
    
    private function isWolf($role) {
        return in_array($role, ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf']);
    }
}