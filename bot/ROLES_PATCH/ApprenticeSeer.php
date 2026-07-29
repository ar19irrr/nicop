<?php
/**
 * 🙇🏻‍♂️ شاگرد پیشگو
 */

require_once __DIR__ . '/base.php';

class ApprenticeSeer extends Role {
    
    public function getName() {
        return 'شاگرد پیشگو';
    }
    
    public function getEmoji() {
        return '🙇🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو شاگرد پیشگو 🙇🏻‍♂️ هستی. در هنگام شب یا روز کار خاصی نمی‌تونی انجام بدی اما اگر پیشگوی اصلی بمیره، تو پیشگو میشی و می‌تونی پیشگویی کنی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onPlayerDeath($deadPlayer) {
        if ($deadPlayer['role'] == 'seer' && !$this->getData('became_seer')) {
            $this->becomeSeer();
        }
    }
    
    private function becomeSeer() {
        $this->setData('became_seer', true);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role'] = 'seer';
                $p['role_data']['apprentice'] = true;
                break;
            }
        }
        $this->saveGame();
        
        $this->sendMessage(
            "📿 پیشگو مرد و تو به عنوان شاگرد پیشگو، الان پیشگو 👳🏻‍♂️ هستی! هر شب می‌تونی نقش یک نفر رو ببینی!"
        );
        
        $this->notifyBeholder();
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}