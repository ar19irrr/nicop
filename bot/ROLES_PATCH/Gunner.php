<?php
/**
 * 🔫 تفنگدار (Gunner)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Gunner extends Role {
    
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
        $bullets = $this->getData('bullets') ?? 2;
        $revealed = $this->getData('revealed') ?? false;
        
        return "🔫 تو تفنگدار هستی! {$bullets} گلوله داری. با اولین شلیک، هویتت لو می‌ره!";
    }
    
    public function hasDayAction() {
        return ($this->getData('bullets') ?? 0) > 0;
    }
    
    public function performDayAction($target = null) {
        $bullets = $this->getData('bullets') ?? 0;
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        if ($bullets <= 0) {
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
        
        $bullets--;
        $this->setData('bullets', $bullets);
        
        $revealed = $this->getData('revealed') ?? false;
        if (!$revealed) {
            $this->setData('revealed', true);
            $this->sendMessageToGroup("💥 صدای شلیک! {$this->getPlayerName()} تفنگداره!");
        }
        
        $this->killPlayer($target, 'gunner');
        
        if ($targetPlayer['role'] == 'wise_elder') {
            $this->setPlayerRole($this->getId(), 'villager');
            return [
                'success' => true,
                'message' => "💥 {$targetPlayer['name']} رو کشتی! ولی ریش سفید بود! به روستایی ساده تبدیل شدی!",
                'killed' => true,
                'demoted' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "💥 {$targetPlayer['name']} رو کشتی! ({$bullets} گلوله مونده)",
            'killed' => true
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase != 'day' || ($this->getData('bullets') ?? 0) <= 0) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'gunner_' . $p['id']
            ];
        }
        return $targets;
    }
}