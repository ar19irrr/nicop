<?php
/**
 * 🧞‍♂️ دیان (Dian)
 * تیم: مستقل
 */

require_once __DIR__ . '/base.php';

class Dian extends Role {
    
    public function getName() {
        return 'دیان';
    }
    
    public function getEmoji() {
        return '🧞‍♂️';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        $target = $this->getData('dian_target');
        $targetSelected = $this->getData('target_selected') ?? false;
        $daysPassed = $this->getData('days_passed') ?? 0;
        
        if (!$targetSelected) {
            return "تو دیان 🧞‍♂️ هستی! فقط روز دوم می‌تونی یک نفر رو انتخاب کنی. روستایی‌ها ۴ روز فرصت دارن اون رو اعدام کنن!";
        }
        
        $targetPlayer = $this->getPlayerById($target);
        $targetName = $targetPlayer ? $targetPlayer['name'] : 'نامشخص';
        $daysRemaining = 4 - $daysPassed;
        
        return "تو دیان 🧞‍♂️ هستی! هدف: {$targetName} | {$daysRemaining} روز فرصت دارن اعدامش کنن!";
    }
    
    public function hasDayAction() {
        $targetSelected = $this->getData('target_selected') ?? false;
        $currentDay = $this->getCurrentDay();
        return !$targetSelected && $currentDay == 2;
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function performDayAction($target = null) {
        $targetSelected = $this->getData('target_selected') ?? false;
        $currentDay = $this->getCurrentDay();
        
        if ($targetSelected) {
            return [
                'success' => false,
                'message' => '❌ قبلاً هدف رو انتخاب کردی!'
            ];
        }
        
        if ($currentDay != 2) {
            return [
                'success' => false,
                'message' => '⏳ فقط روز دوم می‌تونی هدف انتخاب کنی!'
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
        
        $this->setData('dian_target', $target);
        $this->setData('target_selected', true);
        $this->setData('days_passed', 0);
        
        $this->sendMessageToGroup("🔴 دیان 🧞‍♂️ هدفش رو انتخاب کرد: {$targetPlayer['name']}! اگر ۴ روز دیگه اعدامش نکنن، تیم جنگل سیاه برنده می‌شه!");
        
        return [
            'success' => true,
            'message' => "✅ {$targetPlayer['name']} رو انتخاب کردی!",
            'target' => $target
        ];
    }
    
    public function onDayEnd() {
        $targetSelected = $this->getData('target_selected') ?? false;
        
        if (!$targetSelected) return;
        
        $daysPassed = ($this->getData('days_passed') ?? 0) + 1;
        $this->setData('days_passed', $daysPassed);
        
        $target = $this->getData('dian_target');
        $targetPlayer = $this->getPlayerById($target);
        
        if (!$targetPlayer || !$targetPlayer['alive']) {
            $this->sendMessageToGroup("✅ هدف دیان اعدام شد! دیان 🧞‍♂️ باخت!");
            $this->setData('target_selected', false);
            return;
        }
        
        if ($daysPassed >= 4) {
            $this->sendMessageToGroup("💀 ۴ روز گذشت و هدف دیان اعدام نشد! تیم جنگل سیاه برنده شد!");
            $this->declareWinners(['independent']);
        }
    }
    
    public function onDeath() {
        $targetSelected = $this->getData('target_selected') ?? false;
        if ($targetSelected) {
            $this->sendMessageToGroup("🎉 دیان 🧞‍♂️ مرد! حکمش باطل شد!");
            $this->setData('target_selected', false);
        }
    }
    
    public function getValidTargets($phase = 'day') {
        $targetSelected = $this->getData('target_selected') ?? false;
        $currentDay = $this->getCurrentDay();
        
        if ($phase != 'day' || $targetSelected || $currentDay != 2) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'dian_' . $p['id']
            ];
        }
        return $targets;
    }
}