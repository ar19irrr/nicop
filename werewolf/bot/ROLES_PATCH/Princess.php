<?php
/**
 * 👸🏻 پرنسس (Princess)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Princess extends Role {
    
    public function getName() {
        return 'پرنسس';
    }
    
    public function getEmoji() {
        return '👸🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $currentNight = $this->getCurrentNight();
        $prisoners = $this->getData('prisoners') ?? [];
        return "👸🏻 تو پرنسس هستی! بعد از ۳ شب می‌تونی هر شب یک نفر رو زندانی کنی. (زندانی‌ها: " . count($prisoners) . " نفر)";
    }
    
    public function hasNightAction() {
        return $this->getCurrentNight() > 3;
    }
    
    public function performNightAction($target = null) {
        $currentNight = $this->getCurrentNight();
        
        if ($currentNight <= 3) {
            return [
                'success' => false,
                'message' => '⏳ هنوز ۳ شب نگذشته!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو زندانی کنم؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $prisoners = $this->getData('prisoners') ?? [];
        if (in_array($target, $prisoners)) {
            return [
                'success' => false,
                'message' => '⚠️ قبلاً زندانی شده!'
            ];
        }
        
        // قاتل و شوالیه ۵۰٪ شانس فرار
        if (in_array($targetPlayer['role'], ['serial_killer', 'knight'])) {
            if (rand(1, 100) <= 50) {
                return [
                    'success' => false,
                    'message' => "🏃‍♂️ {$targetPlayer['name']} فرار کرد!"
                ];
            }
        }
        
        $prisoners[] = $target;
        $this->setData('prisoners', $prisoners);
        $this->setData('disabled_' . $target, true);
        
        $this->sendMessageToPlayer($target, "⛓️ توسط پرنسس زندانی شدی!");
        
        return [
            'success' => true,
            'message' => "👸🏻 {$targetPlayer['name']} رو زندانی کردی!",
            'imprisoned' => $target
        ];
    }
    
    public function onDeath() {
        $prisoners = $this->getData('prisoners') ?? [];
        
        foreach ($prisoners as $prisonerId) {
            $this->setData('disabled_' . $prisonerId, false);
            $this->sendMessageToPlayer($prisonerId, "🔓 پرنسس مرد! آزاد شدی!");
        }
        $this->setData('prisoners', []);
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->getCurrentNight() <= 3) return [];
        
        $prisoners = $this->getData('prisoners') ?? [];
        $targets = [];
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (in_array($p['id'], $prisoners)) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'princess_' . $p['id']
            ];
        }
        return $targets;
    }
}