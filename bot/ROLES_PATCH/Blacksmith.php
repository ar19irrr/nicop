<?php
/**
 * ⚒ آهنگر (Blacksmith)
 */

require_once __DIR__ . '/base.php';

class Blacksmith extends Role {
    
    public function getName() {
        return 'آهنگر';
    }
    
    public function getEmoji() {
        return '⚒';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $silverUsed = $this->getData('silver_used') ? 'استفاده شده' : 'موجود';
        $swordReady = $this->getData('sword_ready') ? '⚔️ آماده' : '❌ ساخته نشده';
        return "تو آهنگر ⚒ هستی! می‌تونی یک بار در کل بازی نقره بپاشی (شب بدون حمله گرگ/ومپایر). همچنین می‌تونی شمشیر بسازی و با اون یکی رو بکشی!\n\n⚪ نقره: $silverUsed\n⚔️ شمشیر: $swordReady";
    }
    
    public function hasNightAction() {
        $swordReady = $this->getData('sword_ready') ?? false;
        $swordTarget = $this->getData('sword_target');
        return $swordReady && $swordTarget;
    }
    
    public function hasDayAction() {
        $silverUsed = $this->getData('silver_used') ?? false;
        $swordMade = $this->getData('sword_made') ?? false;
        return !$silverUsed || !$swordMade;
    }
    
    public function performDayAction($action = null, $target = null) {
        // نقره‌پاشی
        if ($action == 'silver') {
            $silverUsed = $this->getData('silver_used') ?? false;
            
            if ($silverUsed) {
                return [
                    'success' => false,
                    'message' => '❌ قبلاً نقره پاشیدی!'
                ];
            }
            
            $this->setData('silver_used', true);
            $this->setData('silver_night', ($this->game['night_count'] ?? 1) + 1);
            
            $this->sendMessageToGroup("⚒ آهنگر نقره پاشید! امشب گرگ‌ها و ومپایرها نمی‌تونن حمله کنن!");
            
            return [
                'success' => true,
                'message' => "✅ نقره پاشیدی! شب بعد گرگ‌ها و ومپایرها نمی‌تونن حمله کنن.",
                'silver_used' => true
            ];
        }
        
        // ساخت شمشیر
        if ($action == 'sword') {
            $swordMade = $this->getData('sword_made') ?? false;
            
            if ($swordMade) {
                return [
                    'success' => false,
                    'message' => '❌ امروز قبلاً شمشیر ساختی!'
                ];
            }
            
            if (!$target) {
                return [
                    'success' => false,
                    'message' => '❌ باید هدف شمشیر رو انتخاب کنی!'
                ];
            }
            
            $targetPlayer = $this->getPlayerById($target);
            if (!$targetPlayer || !$targetPlayer['alive']) {
                return [
                    'success' => false,
                    'message' => '❌ هدف نامعتبر!'
                ];
            }
            
            $this->setData('sword_made', true);
            $this->setData('sword_ready', true);
            $this->setData('sword_target', $target);
            
            return [
                'success' => true,
                'message' => "⚔️ شمشیر رو ساختی! امشب {$targetPlayer['name']} رو می‌کشی!",
                'sword_ready' => true
            ];
        }
        
        return [
            'success' => false,
            'message' => '❌ انتخاب نامعتبر!'
        ];
    }
    
    public function performNightAction($target = null) {
        $swordReady = $this->getData('sword_ready') ?? false;
        $swordTarget = $this->getData('sword_target');
        
        if (!$swordReady || !$swordTarget) {
            return [
                'success' => false,
                'message' => '❌ شمشیری برای استفاده نداری!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($swordTarget);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            $this->setData('sword_ready', false);
            $this->setData('sword_target', null);
            return [
                'success' => false,
                'message' => '❌ هدف شمشیر مرده!'
            ];
        }
        
        $this->killPlayer($swordTarget, 'blacksmith_sword');
        $this->setData('sword_ready', false);
        $this->setData('sword_target', null);
        $this->setData('sword_made', false);
        
        return [
            'success' => true,
            'message' => "⚔️ با شمشیرت {$targetPlayer['name']} رو کشتی!",
            'killed' => $swordTarget
        ];
    }
    
    public static function isSilverNight($game) {
        $silverNight = $game['state']['silver_night'] ?? 0;
        $currentNight = $game['night_count'] ?? 1;
        return $silverNight == $currentNight;
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase == 'day') {
            $options = [];
            $silverUsed = $this->getData('silver_used') ?? false;
            $swordMade = $this->getData('sword_made') ?? false;
            
            if (!$silverUsed) {
                $options[] = [
                    'id' => 'silver',
                    'name' => '⚪ نقره‌پاشی',
                    'callback' => 'blacksmith_silver'
                ];
            }
            
            if (!$swordMade) {
                foreach ($this->getOtherAlivePlayers() as $p) {
                    $options[] = [
                        'id' => $p['id'],
                        'name' => '⚔️ شمشیر: ' . $p['name'],
                        'callback' => 'blacksmith_sword_' . $p['id']
                    ];
                }
            }
            
            return $options;
        }
        
        $swordReady = $this->getData('sword_ready') ?? false;
        if ($swordReady) {
            return [[
                'id' => 'use',
                'name' => '⚔️ استفاده از شمشیر',
                'callback' => 'blacksmith_use'
            ]];
        }
        
        return [];
    }
}