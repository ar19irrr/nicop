<?php
/**
 * 🎩 رئیس (Royce)
 * تیم: فرقه (Cult)
 */

require_once __DIR__ . '/base.php';

class Royce extends Role {
    
    public function getName() {
        return 'رئیس';
    }
    
    public function getEmoji() {
        return '🎩';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        return "🎩 تو رئیس هستی! یک فرقه‌گرای متعصب که در بین فرقه‌گراها شهرت بالایی داره. اگر کشته بشی، فرقه‌گراها شب بعد می‌تونن دو نفر رو به فرقه دعوت کنن!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        return [
            'success' => true,
            'message' => "🎩 امشب فرقه‌گراها فعالیت می‌کنن...",
            'action' => 'cult_invite'
        ];
    }
    
    public function onDeath() {
        $this->setGameState('cult_double_invite', true);
        $this->sendMessageToGroup("⚠️ رئیس فرقه 🎩 مرد! امشب فرقه‌گراها دو نفر رو دعوت می‌کنن!");
        
        return ['message' => "🎩 رئیس مرد! فرقه قوی‌تر می‌شه!"];
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}