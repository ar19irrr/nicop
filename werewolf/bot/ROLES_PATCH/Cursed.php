<?php
/**
 * 😾 نفرین شده (Cursed)
 * تیم: روستا (تبدیل به گرگ)
 */

require_once __DIR__ . '/base.php';

class Cursed extends Role {
    
    public function getName() {
        return 'نفرین شده';
    }
    
    public function getEmoji() {
        return '😾';
    }
    
    public function getTeam() {
        $transformed = $this->getData('transformed') ?? false;
        return $transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        $transformed = $this->getData('transformed') ?? false;
        if ($transformed) {
            return "😾 تو نفرین‌شده بودی و الان تبدیل به گرگ شدی!";
        }
        return "😾 تو نفرین‌شده هستی! اگر گرگ‌ها بهت حمله کنن، نمیمیری و تبدیل به گرگ میشی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        $this->scheduleTransformation();
        
        return [
            'died' => false,
            'transformed' => true,
            'message' => "🐺 گرگ‌ها بهت حمله کردن ولی طلسمت فعال شد! فردا شب تبدیل به گرگ میشی!"
        ];
    }
    
    private function scheduleTransformation() {
        $this->setData('transform_night', ($this->game['night_count'] ?? 1) + 1);
        $this->notifyWolves();
    }
    
    public function onNightStart() {
        $transformNight = $this->getData('transform_night');
        $transformed = $this->getData('transformed') ?? false;
        
        if ($transformNight && ($this->game['night_count'] ?? 1) >= $transformNight && !$transformed) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->setData('transformed', true);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_cursed'] = true;
                break;
            }
        }
        $this->saveGame();
        
        $this->sendMessage("🐺 تبدیل به گرگینه شدی! به دسته گرگ‌ها بپیوند!");
        $this->introduceToWolves();
    }
    
    private function notifyWolves() {
        $wolves = $this->getWolfTeam();
        foreach ($wolves as $wolf) {
            if ($wolf['alive']) {
                sendPrivateMessage($wolf['id'], "😾 نفرین‌شده رو گاز زدیم! فردا شب بهمون می‌پیونه!");
            }
        }
    }
    
    private function introduceToWolves() {
        $wolves = $this->getWolfTeam();
        $wolfNames = array_column($wolves, 'name');
        if (!empty($wolfNames)) {
            $this->sendMessage("🐺 بقیه گرگ‌ها: " . implode(', ', $wolfNames));
        }
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}