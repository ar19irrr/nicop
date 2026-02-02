<?php
/**
 * 👩🏻‍🌾 دختر دردسرساز (Trouble)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Trouble extends Role {
    
    protected $powerUsed = false;     // آیا قدرت استفاده شده؟
    
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
        return "تو دختر دردسرساز 👩🏻‍🌾 هستی! یه دختر زیبا ولی عصبی! یک روز می‌تونی با ایجاد سر و صدا باعث شی تا روستایی‌ها دوبار رای‌گیری کنند!";
    }
    
    public function hasDayAction() {
        return !$this->powerUsed;
    }
    
    public function performDayAction($usePower = false) {
        if ($this->powerUsed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً از قدرتت استفاده کردی!'
            ];
        }
        
        if (!$usePower) {
            return [
                'success' => false,
                'message' => '👩🏻‍🌾 می‌خوای امروز دردسر ایجاد کنی؟'
            ];
        }
        
        $this->powerUsed = true;
        
        $this->sendMessageToGroup("🔥 دختر دردسرساز👩🏻‍🌾 یعنی {$this->getPlayerName()} با ایجاد سر و صدا هنگام بحث برای اینکه امروز چه کسی اعدام شه باعث خشم روستاییان می‌شه و اهالی روستا تصمیم می‌گیرن برای اینکه اون آروم شه امروز دو نفر اعدام کنند!");
        
        return [
            'success' => true,
            'message' => "✅ امروز دردسر ایجاد کردی! امروز دو بار رای‌گیری می‌شه!",
            'double_vote' => true
        ];
    }
}