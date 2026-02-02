<?php
/**
 * 🎖 کدخدا
 */

require_once __DIR__ . '/base.php';

class Mayor extends Role {
    
    private $revealed = false;
    
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
        return "تو 🎖کدخدای روستا هستی. هر زمان که تصمیم بگیری نقش خودتو اعلام کنی، از رای‌گیری‌های بعدی رأی تو ۲ تا حساب میشه!";
    }
    
    public function hasDayAction() {
        return !$this->revealed;
    }
    
    public function performDayAction($reveal = false) {
        if (!$reveal) {
            return [
                'success' => false,
                'message' => 'امروز نقشت رو اعلام نکردی.'
            ];
        }
        
        if ($this->revealed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً نقشت رو اعلام کردی!'
            ];
        }
        
        $this->revealed = true;
        $this->setData('revealed', true);
        
        // اطلاع به همه
        $this->notifyAll("🎖 {$this->player['name']} کدخدای روستاست! از الان رأی‌ش ۲ تا حساب میشه!");
        
        return [
            'success' => true,
            'message' => "🎖 نقشت رو اعلام کردی! از الان رأی تو ۲ تا حساب میشه!",
            'revealed' => true
        ];
    }
    
    public function getVoteValue() {
        return $this->revealed ? 2 : 1;
    }
    
    private function notifyAll($message) {
        sendGroupMessage($this->game['group_id'], $message);
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase == 'day' && !$this->revealed) {
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