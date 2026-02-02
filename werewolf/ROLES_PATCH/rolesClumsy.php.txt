<?php
/**
 * 🤕 پسر گیج (Clumsy)
 * تیم: روستا (Villager)
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
        return "تو پسر گیج 🤕 هستی! چون مواد مصرف می‌کنی و هوش و حواست سر جاش نیست، در زمان رای‌گیری ۵۰٪ احتمال داره که رایت تغییر کنه!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onVote($targetId) {
        // ۵۰٪ شانس تغییر رای
        $rand = rand(1, 100);
        if ($rand <= 50) {
            // انتخاب تصادفی دیگر
            $otherPlayers = $this->getOtherAlivePlayers();
            $otherIds = array_column($otherPlayers, 'id');
            $otherIds = array_diff($otherIds, [$targetId, $this->getId()]);
            
            if (!empty($otherIds)) {
                $newTarget = $otherIds[array_rand($otherIds)];
                $newTargetPlayer = $this->getPlayerById($newTarget);
                
                return [
                    'changed' => true,
                    'original' => $targetId,
                    'new_target' => $newTarget,
                    'message' => "🤕 چون پسر گیج هستی، هوش و حواست نبود و به جای هدف اصلی، به {$newTargetPlayer['name']} رای دادی!"
                ];
            }
        }
        
        return [
            'changed' => false,
            'target' => $targetId
        ];
    }
}