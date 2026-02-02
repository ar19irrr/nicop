<?php
/**
 * 😾 نفرین شده
 */

require_once __DIR__ . '/base.php';

class Cursed extends Role {
    
    private $transformed = false;
    
    public function getName() {
        return 'نفرین شده';
    }
    
    public function getEmoji() {
        return '😾';
    }
    
    public function getTeam() {
        return $this->transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        return "تو نفرین‌شده😾 هستی. در ابتدا جزء روستاییا هستی. ولی یه طلسم در وجودته که اگر گرگ‌ها بهت حمله کنن، با اولین گاز یه گرگ، طلسم فعال میشه و شب بعد تبدیل به یه گرگینه میشی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        // نفرین فعال میشه - نمی‌میره ولی تبدیل میشه
        $this->scheduleTransformation();
        
        return [
            'died' => false,
            'transformed' => true,
            'message' => "🐺 گرگ‌ها بهت حمله کردن ولی طلسمت فعال شد! فردا شب تبدیل به گرگ میشی!"
        ];
    }
    
    private function scheduleTransformation() {
        $this->setData('transform_night', ($this->game['night_count'] ?? 1) + 1);
        
        // اطلاع به گرگ‌ها
        $this->notifyWolves();
    }
    
    public function onNightStart() {
        $transformNight = $this->getData('transform_night');
        if ($transformNight && $this->game['night_count'] >= $transformNight && !$this->transformed) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->transformed = true;
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_cursed'] = true;
                break;
            }
        }
        
        saveGame($this->game);
        
        $this->sendMessage(
            "🐺 شب شده و احساس درد و سوزش عجیبی تمام بدنت رو فرا گرفت... وقتی بهوش اومدی دیدی تبدیل به یه گرگینه شدی! به دسته گرگ‌ها بپیوند!"
        );
        
        // معرفی به گرگ‌ها
        $this->introduceToWolves();
    }
    
    private function notifyWolves() {
        $wolfTeam = $this->getWolfTeam();
        foreach ($wolfTeam as $wolfId) {
            sendPrivateMessage($wolfId, 
                "😾 نفرین‌شده رو گاز زدیم! فردا شب تبدیل به گرگ میشه و بهمون می‌پیونه!"
            );
        }
    }
    
    private function introduceToWolves() {
        $wolfTeam = $this->getWolfTeam();
        $this->sendMessage("بقیه گرگ‌ها: " . implode(', ', $wolfTeam));
    }
    
    private function getWolfTeam() {
        $wolves = [];
        foreach ($this->game['players'] as $p) {
            if ($this->isWerewolf($p) && $p['id'] != $this->player['id']) {
                $wolves[] = $p['name'];
            }
        }
        return $wolves;
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}