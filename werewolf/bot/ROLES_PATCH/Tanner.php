<?php
/**
 * 👺 منافق (Tanner)
 * تیم: مستقل
 */

require_once __DIR__ . '/base.php';

class Tanner extends Role {
    
    public function getName() {
        return 'منافق';
    }
    
    public function getEmoji() {
        return '👺';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        return "👺 تو منافق هستی! باید اعدام بشی تا برنده بشی! اگر اعدام نشی، می‌بازی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onLynched() {
        return [
            'win' => true,
            'message' => "👺 منافق اعدام شد و برنده شد!"
        ];
    }
    
    public function onConvertedToCult() {
        $this->sendMessage("👤 به فرقه دعوت شدی! دیگه نمی‌تونی با اعدام برنده بشی!");
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}