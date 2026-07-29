<?php
/**
 * 🪶 ققنوس (Phoenix)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Phoenix extends Role {
    
    public function getName() {
        return 'ققنوس';
    }
    
    public function getEmoji() {
        return '🪶';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $tears = $this->getData('tears') ?? 2;
        return "🪶 تو ققنوس هستی! ۲ اشک مقدس داری که در شب‌های ۳ و ۵ می‌تونی به یکی بدی و جونش رو نجات بدی! ({$tears} اشک مونده)";
    }
    
    public function hasNightAction() {
        $currentNight = $this->getCurrentNight();
        $tears = $this->getData('tears') ?? 0;
        return in_array($currentNight, [3, 5]) && $tears > 0;
    }
    
    public function performNightAction($target = null) {
        $currentNight = $this->getCurrentNight();
        $tears = $this->getData('tears') ?? 0;
        
        if (!in_array($currentNight, [3, 5])) {
            return [
                'success' => false,
                'message' => '⏳ فقط شب‌های ۳ و ۵ می‌تونی!'
            ];
        }
        
        if ($tears <= 0) {
            return [
                'success' => false,
                'message' => '❌ اشک‌هات تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $tears--;
        $this->setData('tears', $tears);
        
        $tearTargets = $this->getData('tear_targets') ?? [];
        $tearTargets[] = $target;
        $this->setData('tear_targets', $tearTargets);
        
        $this->sendMessageToPlayer($target, "✨ اشک ققنوس به تو داده شد! از حمله شب نجات پیدا می‌کنی!");
        
        return [
            'success' => true,
            'message' => "🪶 اشک به {$targetPlayer['name']} داده شد! ({$tears} اشک مونده)",
            'tear_given' => $target
        ];
    }
    
    public function onAttack($targetId) {
        $tearTargets = $this->getData('tear_targets') ?? [];
        
        if (!in_array($targetId, $tearTargets)) {
            return ['protected' => false];
        }
        
        $key = array_search($targetId, $tearTargets);
        unset($tearTargets[$key]);
        $this->setData('tear_targets', array_values($tearTargets));
        
        $this->sendMessageToPlayer($targetId, "🛡️ اشک ققنوس نجاتت داد!");
        
        return ['protected' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $currentNight = $this->getCurrentNight();
        $tears = $this->getData('tears') ?? 0;
        
        if (!in_array($currentNight, [3, 5]) || $tears <= 0) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'phoenix_' . $p['id']
            ];
        }
        return $targets;
    }
}