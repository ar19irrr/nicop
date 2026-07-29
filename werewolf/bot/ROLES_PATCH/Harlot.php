<?php
/**
 * 💋 ناتاشا (Harlot)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Harlot extends Role {
    
    public function getName() {
        return 'ناتاشا';
    }
    
    public function getEmoji() {
        return '💋';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "💋 تو ناتاشا هستی! هر شب می‌تونی بری خونه یکی برای جاسوسی. اگر گرگ یا قاتل بیاد خونه همون شخص، هر دو می‌میرید! اگر تو خونه نباشی، گرگ نمی‌تونه بکشتت!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $lastVisit = $this->getData('last_visit_id');
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب خونه کی می‌خوای بری؟'
            ];
        }
        
        if ($target == $lastVisit) {
            return [
                'success' => false,
                'message' => '⚠️ نمی‌تونی دو شب پیاپی به خونه یکی بری!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('last_visit_id', $target);
        $this->setData('is_away', true);
        
        $targetRole = $targetPlayer['role'];
        
        // گرگ - هر دو می‌میرن
        if ($this->isWolf($targetRole)) {
            $this->killPlayer($this->getId(), 'harlot_wolf');
            $this->sendMessageToGroup("💋 ناتاشا رفت پیش یه گرگ و کشته شد!");
            return [
                'success' => true,
                'message' => "💀 {$targetPlayer['name']} گرگ بود! کشته شدی!",
                'died' => true
            ];
        }
        
        // قاتل - هر دو می‌میرن
        if ($targetRole == 'serial_killer') {
            $this->killPlayer($this->getId(), 'harlot_killer');
            $this->killPlayer($target, 'harlot');
            $this->sendMessageToGroup("💋 ناتاشا و قاتل همدیگه رو کشتن!");
            return [
                'success' => true,
                'message' => "💀 {$targetPlayer['name']} قاتل بود! همدیگه رو کشتید!",
                'died' => true
            ];
        }
        
        // ومپایر اصیل - تبدیل
        if ($targetRole == 'bloodthirsty') {
            $this->setPlayerRole($this->getId(), 'vampire');
            $this->sendMessageToGroup("🧛🏻‍♂️ ناتاشا توسط ومپایر اصیل تبدیل شد!");
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ {$targetPlayer['name']} ومپایر اصیل بود! تبدیل شدی!",
                'converted' => true
            ];
        }
        
        $this->setData('is_away', false);
        
        return [
            'success' => true,
            'message' => "💋 با {$targetPlayer['name']} بودی... همه چیز خوب بود!",
            'safe' => true
        ];
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        $isAway = $this->getData('is_away') ?? false;
        
        if ($isAway) {
            return ['died' => false, 'not_home' => true];
        }
        
        if ($attackerRole == 'serial_killer') {
            $this->killPlayer($this->getId(), 'killer');
            $this->killPlayer($attackerId, 'harlot');
            $this->sendMessageToGroup("💋 قاتل و ناتاشا همدیگه رو کشتن!");
            return ['died' => true, 'killed_attacker' => true];
        }
        
        if ($this->isWolf($attackerRole)) {
            $this->killPlayer($this->getId(), 'werewolf');
            $this->sendMessageToGroup("💋 گرگا ناتاشا رو کشتن!");
            return ['died' => true];
        }
        
        return ['died' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'harlot_' . $p['id']
            ];
        }
        return $targets;
    }
}