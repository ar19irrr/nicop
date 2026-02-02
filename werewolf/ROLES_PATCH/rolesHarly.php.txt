<?php
/**
 * 👩🏻‍🎤 هارلی کویین (Harly)
 * تیم: جوکر (Joker Team)
 */

require_once __DIR__ . '/base.php';

class Harly extends Role {
    
    protected $jokerId = null;        // آیدی جوکر
    protected $jokerDead = false;     // آیا جوکر مرده؟
    protected $scrollsFound = 0;      // تعداد کتیبه‌های پیدا شده
    protected $lastCraftNight = 0;    // شب آخرین ساخت کتیبه
    
    public function getName() {
        return 'هارلی کویین';
    }
    
    public function getEmoji() {
        return '👩🏻‍🎤';
    }
    
    public function getTeam() {
        return 'joker';
    }
    
    public function getDescription() {
        $jokerName = $this->getJokerName();
        $status = $this->jokerDead ? " (مرده)" : "";
        return "تو هارلی کویین 👩🏻‍🎤 هستی، معشوقه و پزشک جوکر {$jokerName}{$status}. وظیفت محافظت از جوکر در شب‌هاست. اگر جوکر بمیره، تو باید کار ناتمومش رو ادامه بدی. هر ۳ شب می‌تونی یه کتیبه بسازی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // اگر جوکر مرده، هارلی دنبال کتیبه می‌گرده
        if ($this->jokerDead) {
            return $this->searchForScroll($targetPlayer);
        }
        
        // بررسی آیا شب ۳ هست برای ساخت کتیبه
        $currentNight = $this->getCurrentNight();
        if (($currentNight - $this->lastCraftNight) >= 3) {
            $this->lastCraftNight = $currentNight;
            $this->scrollsFound++;
            $this->sendMessageToPlayer($this->jokerId, "📜 هارلی کویین موفق شد یکی دیگه از کتیبه‌های انفجاری رو برات بسازه. حالا تو {$this->scrollsFound} کتیبه داری!");
            
            return [
                'success' => true,
                'message' => "🔬 یواشکی به کارخونه‌ی ساخت مواد شیمیایی رفتی و با لبخند شیطانیت کتیبه‌ی جدید ساختی!",
                'action' => 'craft_scroll'
            ];
        }
        
        // محافظت از جوکر
        return [
            'success' => true,
            'message' => "🛡️ امشب مراقب جوکر بودی...",
            'action' => 'guard'
        ];
    }
    
    public function onJokerDeath() {
        $this->jokerDead = true;
        $this->sendMessageToPlayer($this->getId(), "💔 دیگه کاری از دستت برای جوکر بر نمیاد، حالا بهتره گریه رو بذاری کنار و کاری که اون می‌خواست انجام بده رو از سر بگیری!");
    }
    
    public function protectJoker($attackerId, $attackerRole) {
        // محافظت از جوکر در برابر حمله
        $this->sendMessageToPlayer($this->jokerId, "🛡️ دیشب بهت حمله شده بود ولی هارلی از جونت مراقبت کرد و در امانتی!");
        
        // اگر گرگ حمله کرده، هارلی یه گرگ رو می‌کشه
        if (strpos($attackerRole, 'wolf') !== false) {
            $this->sendMessageToGroup("🦇 دیشب گرگ‌ها به جوکر 🤡 حمله کردند و هارلی کویین👩‍🎤 یکی از گرگ‌ها رو با ضربات باتوم از پا درآورد!");
            return true;
        }
        
        return true;
    }
    
    private function searchForScroll($targetPlayer) {
        $rand = rand(1, 100);
        if ($rand <= 33) { // ۳۳٪ شانس پیدا کردن کتیبه
            $this->scrollsFound++;
            return [
                'success' => true,
                'message' => "📜 دیشب به خونه‌ی {$targetPlayer['name']} رفتی و یکی از کتیبه‌ها رو پیدا کردی!",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 دیشب به خونه‌ی {$targetPlayer['name']} رفتی ولی آثاری از کتیبه پیدا نکردی!",
            'found' => false
        ];
    }
    
    public function setJokerId($id) {
        $this->jokerId = $id;
    }
    
    private function getJokerName() {
        if ($this->jokerId) {
            $joker = $this->getPlayerById($this->jokerId);
            return $joker ? $joker['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'harly_' . $p['id']
            ];
        }
        return $targets;
    }
}