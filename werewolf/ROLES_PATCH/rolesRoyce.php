<?php
/**
 * 🎩 رئیس (Royce)
 * تیم: فرقه (Cult)
 */

require_once __DIR__ . '/base.php';

class Royce extends Role {
    
    protected $doubleInviteNight = false; // آیا شب بعد دوبار دعوت می‌شه؟
    
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
        return "تو رئیس 🎩 هستی! یک فرقه‌گرای متعصب که در بین فرقه‌گراها شهرت بالایی داره. اگر کشته بشی، فرقه‌گراها شب بعد می‌تونن دو نفر رو به فرقه دعوت کنن!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        // رئیس فقط یک فرقه‌گرای معمولیه که قدرت ویژه داره
        // دعوت کردن توسط سیستم فرقه انجام می‌شه
        return [
            'success' => true,
            'message' => "🎩 امشب فرقه‌گراها فعالیت می‌کنن...",
            'action' => 'cult_invite'
        ];
    }
    
    public function onDeath() {
        // فعال کردن قدرت دوبل برای شب بعد
        $this->doubleInviteNight = true;
        $this->setGameState('cult_double_invite', true);
        
        $this->sendMessageToGroup("⚠️ از اونجایی که رئیس فرقه 🎩 {$this->getPlayerName()} کشته شده، امشب فرقه‌گراها می‌تونن دو نفر رو به فرقه دعوت کنن!");
        
        return [
            'message' => "🎩 رئیس مرد! فرقه قوی‌تر می‌شه!"
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        // رئیس مستقیماً هدف انتخاب نمی‌کنه
        return [];
    }
}