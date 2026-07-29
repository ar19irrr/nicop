<?php
/**
 * 🧙🏻‍♀️ عجوزه (Honey)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Honey extends Role {
    
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
        return "🧙🏻‍♀️ تو عجوزه هستی! هر شب می‌تونی یک نفر رو طلسم کنی. اون شخص اگر توسط کاراگاه یا پیشگو استعلام بشه، گرگینه 🐺 دیده می‌شه! طلسم بعد ۲ شب باطل می‌شه.";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی رو طلسم کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // شکارچی - ۵۰٪ شانس شکست
        if ($targetPlayer['role'] == 'cult_hunter' && rand(1, 100) <= 50) {
            return [
                'success' => false,
                'message' => "😟 شکارچی رو نتونستی طلسم کنی!"
            ];
        }
        
        $cursedPlayers = $this->getData('cursed_players') ?? [];
        $cursedPlayers[$target] = $this->getCurrentNight() + 2;
        $this->setData('cursed_players', $cursedPlayers);
        
        return [
            'success' => true,
            'message' => "😈 {$targetPlayer['name']} رو طلسم کردی! اگه استعلام بشه، گرگ دیده می‌شه!",
            'cursed' => $target
        ];
    }
    
    public function isCursed($playerId) {
        $cursedPlayers = $this->getData('cursed_players') ?? [];
        
        if (isset($cursedPlayers[$playerId])) {
            if ($this->getCurrentNight() > $cursedPlayers[$playerId]) {
                unset($cursedPlayers[$playerId]);
                $this->setData('cursed_players', $cursedPlayers);
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