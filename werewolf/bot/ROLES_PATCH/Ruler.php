<?php
/**
 * 👑 حاکم (Ruler)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Ruler extends Role {
    
    public function getName() {
        return 'حاکم';
    }
    
    public function getEmoji() {
        return '👑';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $powerUsed = $this->getData('power_used') ?? false;
        return "👑 تو حاکم هستی! می‌تونی یک روز بجای همه تصمیم بگیری چه کسی اعدام شه!" . ($powerUsed ? "\n❌ قدرتت استفاده شده." : "\n✅ قدرتت موجود است.");
    }
    
    public function hasDayAction() {
        return !($this->getData('power_used') ?? false);
    }
    
    public function performDayAction($usePower = false) {
        $powerUsed = $this->getData('power_used') ?? false;
        
        if ($powerUsed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً از قدرتت استفاده کردی!'
            ];
        }
        
        if (!$usePower) {
            return [
                'success' => false,
                'message' => '👑 امروز می‌خوای یکی رو اعدام کنی؟'
            ];
        }
        
        $this->setData('power_used', true);
        $this->setData('power_activated', true);
        $this->sendMessageToGroup("👑 حاکم از قدرت خود استفاده کرد! امروز خودش حکم اعدام رو صادر می‌کنه!");
        
        return [
            'success' => true,
            'message' => "✅ قدرتت رو فعال کردی! حالا یکی رو برای اعدام انتخاب کن.",
            'power_activated' => true
        ];
    }
    
    public function performExecution($target = null) {
        $powerActivated = $this->getData('power_activated') ?? false;
        
        if (!$powerActivated) {
            return [
                'success' => false,
                'message' => '❌ اول باید قدرتت رو فعال کنی!'
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
        
        $this->killPlayer($target, 'ruler_execution');
        $this->setData('power_activated', false);
        
        return [
            'success' => true,
            'message' => "⚔️ {$targetPlayer['name']} توسط حاکم اعدام شد!",
            'executed' => $target
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        $powerActivated = $this->getData('power_activated') ?? false;
        
        if (!$powerActivated || $phase != 'day') return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'ruler_exec_' . $p['id']
            ];
        }
        return $targets;
    }
}