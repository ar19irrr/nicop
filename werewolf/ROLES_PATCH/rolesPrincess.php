<?php
/**
 * 👸🏻 پرنسس (Princess)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Princess extends Role {
    
    protected $prisoners = [];        // زندانی‌ها
    protected $cooldown = 3;          // تعداد شب‌های اولیه
    protected $currentNight = 0;      // شب فعلی
    
    public function getName() {
        return 'پرنسس';
    }
    
    public function getEmoji() {
        return '👸🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو پرنسس 👸🏻 هستی! تک‌دختر لوس پادشاه. بعد از گذشت ۳ شب می‌تونی دستور بدی هر شب یک نفر توسط سربازهای سلطنتی دستگیر بشه و به سیاهچال بیفته و قابلیت استفاده از نقشش رو از دست بده. (قاتل و شوالیه ۵۰٪ امکان فرار دارن)";
    }
    
    public function hasNightAction() {
        $this->currentNight = $this->getCurrentNight();
        return $this->currentNight > $this->cooldown;
    }
    
    public function performNightAction($target = null) {
        if ($this->currentNight <= $this->cooldown) {
            return [
                'success' => false,
                'message' => '⏳ هنوز ۳ شب نگذشته! صبر کن...'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ مهر پادشاه رو برمی‌داری تا حکم دستگیری یک نفر رو صادر کنی، می‌خوای کیو به سیاهچال بندازی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی فرار برای قاتل و شوالیه
        if (in_array($targetPlayer['role'], ['killer', 'knight'])) {
            $escapeChance = rand(1, 100);
            if ($escapeChance <= 50) {
                $this->sendMessageToPlayer($target, "🏃‍♂️ دیشب سربازهای سلطنتی به خونت حمله کردن اما تونستی فرار کنی!");
                return [
                    'success' => false,
                    'message' => "😤 {$targetPlayer['name']} تونست از دست سربازها فرار کنه! (۵۰٪ شانس فرار)"
                ];
            }
        }
        
        // زندانی کردن
        $this->prisoners[] = $target;
        $this->disableRole($target);
        
        // پیام به هدف
        $this->sendMessageToPlayer($target, "⛓️ صدای پای افرادی رو می‌شنوی و متوجه حضور سربازها می‌شی. با اون‌ها می‌جنگی اما جسم سختی به سرت برخورد می‌کنه و روی زمین می‌فی. چشم‌هات رو باز می‌کنی و تصویر تار پرنسس 👸🏻 رو می‌بینی که از پشت میله‌های سیاهچال بهت نیشخند می‌زنه!");
        
        return [
            'success' => true,
            'message' => "✅ با موفقیت {$targetPlayer['name']} رو به سیاهچاله قصر بندازی!",
            'imprisoned' => $target
        ];
    }
    
    public function onDeath() {
        // آزاد کردن زندانی‌ها
        foreach ($this->prisoners as $prisonerId) {
            $this->enableRole($prisonerId);
            $this->sendMessageToPlayer($prisonerId, "🔓 حالا که پرنسس مرده تو می‌تونی از توانایی‌هات استفاده کنی و از سیاهچال آزاد شدی!");
        }
        $this->prisoners = [];
    }
    
    private function disableRole($playerId) {
        // منطق غیرفعال کردن نقش
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = true;
        }
    }
    
    private function enableRole($playerId) {
        // منطق فعال کردن نقش
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = false;
        }
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->currentNight <= $this->cooldown) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه زندانی شده رو دوباره زندانی کنه
            if (in_array($p['id'], $this->prisoners)) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'princess_' . $p['id']
            ];
        }
        return $targets;
    }
}