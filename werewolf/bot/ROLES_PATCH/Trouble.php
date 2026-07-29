<?php
/**
 * 👩🏻‍🌾 دختر دردسرساز (Trouble)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Trouble extends Role {
    
    public function getName() {
        return 'دختر دردسرساز';
    }
    
    public function getEmoji() {
        return '👩🏻‍🌾';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $powerUsed = $this->getData('power_used') ?? false;
        return "👩🏻‍🌾 تو دختر دردسرساز هستی! یک روز می‌تونی باعث شی دو بار رای‌گیری بشه!" . ($powerUsed ? "\n❌ استفاده شده." : "\n✅ موجود است.");
    }
    
    public function hasDayAction() {
        return !($this->getData('power_used') ?? false);
    }
    
    public function performDayAction($usePower = false) {
        $powerUsed = $this->getData('power_used') ?? false;
        
        if ($powerUsed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً استفاده کردی!'
            ];
        }
        
        if (!$usePower) {
            return [
                'success' => false,
                'message' => '👩🏻‍🌾 می‌خوای امروز دردسر ایجاد کنی؟'
            ];
        }
        
        $this->setData('power_used', true);
        $this->sendMessageToGroup("🔥 {$this->getPlayerName()} دردسر ایجاد کرد! امروز دو بار رای‌گیری می‌شه!");
        
        return [
            'success' => true,
            'message' => "✅ امروز دردسر ایجاد کردی! دو بار رای‌گیری می‌شه!",
            'double_vote' => true
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        return [];
    }
}