<?php
/**
 * 🤕 پسر گیج (Clumsy)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Clumsy extends Role {
    
    public function getName() {
        return 'پسر گیج';
    }
    
    public function getEmoji() {
        return '🤕';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو پسر گیج 🤕 هستی! در زمان رأی‌گیری ۵۰٪ احتمال داره که رأیت تغییر کنه!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onVote($targetId) {
        // ۵۰٪ شانس تغییر رأی
        if (rand(1, 100) <= 50) {
            $otherPlayers = $this->getOtherAlivePlayers();
            $otherIds = array_column($otherPlayers, 'id');
            $otherIds = array_diff($otherIds, [$targetId, $this->getId()]);
            
            if (!empty($otherIds)) {
                $newTarget = $otherIds[array_rand($otherIds)];
                $newTargetPlayer = $this->getPlayerById($newTarget);
                
                return [
                    'changed' => true,
                    'new_target' => $newTarget,
                    'message' => "🤕 گیج شدی و به جای هدف اصلی، به {$newTargetPlayer['name']} رأی دادی!"
                ];
            }
        }
        
        return [
            'changed' => false,
            'target' => $targetId
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}