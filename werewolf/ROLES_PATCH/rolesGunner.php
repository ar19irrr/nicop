<?php
/**
 * 🔫 تفنگدار
 */

require_once __DIR__ . '/base.php';

class Gunner extends Role {
    
    private $bullets = 2;
    private $revealed = false;
    
    public function getName() {
        return 'تفنگدار';
    }
    
    public function getEmoji() {
        return '🔫';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو تفنگدار🔫 روستا هستی. فقط ۲ تا گلوله داری. به اختیار خودت می‌تونی در هر روزی که خواستی به بازیکنی که بهش مشکوکی شلیک کنی. با اولین شلیک، همه متوجه میشن که تفنگدار چه کسی هست!";
    }
    
    public function hasDayAction() {
        return $this->bullets > 0;
    }
    
    public function performDayAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        if ($this->bullets <= 0) {
            return [
                'success' => false,
                'message' => '❌ گلوله‌ات تموم شده!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->bullets--;
        
        // اولین شلیک = افشای هویت
        if (!$this->revealed) {
            $this->revealed = true;
            $this->notifyAll("💥 صدای شلیک! {$this->player['name']} تفنگداره!");
        }
        
        // کشتن
        $this->game = killPlayer($this->game, $target, 'gunner');
        saveGame($this->game);
        
        // بررسی ریش سفید
        if ($targetPlayer['role'] == 'wise_elder') {
            $this->demoteToVillager();
            return [
                'success' => true,
                'message' => "💥 به {$targetPlayer['name']} شلیک کردی و کشتیش! ولی ریش سفید بود! از عذاب وجدان تفنگت رو انداختی و به روستایی ساده تبدیل شدی!",
                'killed' => true,
                'demoted' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "💥 به {$targetPlayer['name']} شلیک کردی و کشتیش! {$this->bullets} گلوله دیگه داری.",
            'killed' => true,
            'bullets_left' => $this->bullets
        ];
    }
    
    private function demoteToVillager() {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role'] = 'villager';
                break;
            }
        }
        saveGame($this->game);
    }
    
    private function notifyAll($message) {
        sendGroupMessage($this->game['group_id'], $message);
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase == 'day' && $this->bullets > 0) {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'] . " ({$this->bullets} گلوله)",
                    'callback' => 'gunner_' . $p['id']
                ];
            }
            return $targets;
        }
        return [];
    }
}