<?php
/**
 * 🙇🏻‍♂️ شاگرد پیشگو
 */

require_once __DIR__ . '/base.php';

class ApprenticeSeer extends Role {
    
    private $becameSeer = false;
    
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
        return "تو شاگرد پیشگو🙇🏻‍♂️ هستی. در هنگام شب یا روز کار خاصی نمی‌تونی انجام بدی اما اگر پیشگوی اصلی بمیره، تو پیشگو میشی و می‌تونی پیشگویی کنی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onPlayerDeath($deadPlayer) {
        // اگه پیشگو مرد، شاگرد جایگزین میشه
        if ($deadPlayer['role'] == 'seer' && !$this->becameSeer) {
            $this->becomeSeer();
        }
    }
    
    private function becomeSeer() {
        $this->becameSeer = true;
        
        // تغییر نقش
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role'] = 'seer';
                $p['role_data']['apprentice'] = true; // نشونگر اینکه شاگرد بوده
                break;
            }
        }
        
        saveGame($this->game);
        
        $this->sendMessage(
            "📿 دیشب یه اتفاقی برای پیشگو افتاد، و تو به عنوان شاگرد پیشگو، الان تو پیشگو👳🏻‍♂️ هستی! هر شب می‌تونی نقش یک نفر رو ببینی!"
        );
        
        // اطلاع به شاهد
        $this->notifyBeholder();
    }
    
    private function notifyBeholder() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'beholder' && ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], 
                    "👁️ حاجی {$this->player['name']} پیشگوی رزرو بود و الان به جای پیشگوی قبلی پیشگویی می‌کنه!"
                );
            }
        }
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}