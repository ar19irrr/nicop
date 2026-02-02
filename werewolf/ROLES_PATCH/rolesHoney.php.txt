<?php
/**
 * 🧙🏻‍♀️ عجوزه (Honey)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Honey extends Role {
    
    protected $cursedPlayers = [];    // بازیکنان طلسم شده
    
    public function getName() {
        return 'عجوزه';
    }
    
    public function getEmoji() {
        return '🧙🏻‍♀️';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو عجوزه 🧙🏻‍♀️ هستی! هر شب می‌تونی یک نفر رو طلسم گرگینه کنی. اون شخص اگر توسط کارگاه یا پیشگو استعلام بشه، گرگینه 🐺 دیده می‌شه! طلسم بعد ۲ شب خود به خود باطل می‌شه.";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب نقش کیو می‌خوای تغییر بدی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی شکارچی (۵۰٪ شانس شکست)
        if ($targetPlayer['role'] == 'cult_hunter') {
            $failChance = rand(1, 100);
            if ($failChance <= 50) {
                return [
                    'success' => false,
                    'message' => "😟 متاسفیم! از اونجایی که نزدیک شدن به شکار سخته، نتونستی نقش {$targetPlayer['name']} رو تغییر بدی!"
                ];
            }
        }
        
        // اضافه کردن طلسم
        $this->cursedPlayers[$target] = $this->getCurrentNight() + 2; // باطل شدن بعد ۲ شب
        
        return [
            'success' => true,
            'message' => "😈 تو با موفقیت تونستی نقش {$targetPlayer['name']} رو تغییر بدی! اگه امشب یا فردا استعلام بشه می‌گه گرگه 🐺!",
            'cursed' => $target
        ];
    }
    
    public function isCursed($playerId) {
        if (isset($this->cursedPlayers[$playerId])) {
            // بررسی آیا طلسم منقضی شده
            if ($this->getCurrentNight() > $this->cursedPlayers[$playerId]) {
                unset($this->cursedPlayers[$playerId]);
                return false;
            }
            return true;
        }
        return false;
    }
    
    public function getFakeRole($playerId) {
        if ($this->isCursed($playerId)) {
            return 'werewolf';
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'honey_' . $p['id']
            ];
        }
        return $targets;
    }
}