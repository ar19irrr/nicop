<?php
/**
 * 🎖 کدخدا (Mayor)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Mayor extends Role {
    
    public function getName() {
        return 'کدخدا';
    }
    
    public function getEmoji() {
        return '🎖';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $revealed = $this->getData('revealed') ?? false;
        return "🎖 تو کدخدای روستا هستی!" . ($revealed ? "\n✅ نقش خودت رو اعلام کردی! رأی‌ت ۲ تا حساب می‌شه!" : "\n⚠️ هنوز نقشت رو اعلام نکردی!");
    }
    
    public function hasDayAction() {
        return !($this->getData('revealed') ?? false);
    }
    
    public function performDayAction($reveal = false) {
        $revealed = $this->getData('revealed') ?? false;
        
        if (!$reveal) {
            return [
                'success' => false,
                'message' => 'امروز نقشت رو اعلام نکردی.'
            ];
        }
        
        if ($revealed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً نقشت رو اعلام کردی!'
            ];
        }
        
        $this->setData('revealed', true);
        $this->sendMessageToGroup("🎖 {$this->getPlayerName()} کدخدای روستاست! از الان رأی‌ش ۲ تا حساب می‌شه!");
        
        return [
            'success' => true,
            'message' => "🎖 نقشت رو اعلام کردی! رأی تو ۲ تا حساب می‌شه!"
        ];
    }
    
    public function getVoteValue() {
        return ($this->getData('revealed') ?? false) ? 2 : 1;
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase == 'day' && !($this->getData('revealed') ?? false)) {
            return [
                [
                    'id' => 'reveal',
                    'name' => '🎖 اعلام کردن (رأی ۲ برابر)',
                    'callback' => 'mayor_reveal'
                ],
                [
                    'id' => 'skip',
                    'name' => '⏭️ فعلاً نه',
                    'callback' => 'mayor_skip'
                ]
            ];
        }
        return [];
    }
}