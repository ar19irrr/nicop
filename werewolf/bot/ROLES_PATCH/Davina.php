<?php
/**
 * 🍾 داوینا (Davina)
 * تیم: قاتل (Killer)
 */

require_once __DIR__ . '/base.php';

class Davina extends Role {
    
    public function getName() {
        return 'داوینا';
    }
    
    public function getEmoji() {
        return '🍾';
    }
    
    public function getTeam() {
        return 'killer';
    }
    
    public function getDescription() {
        $silenceUsed = $this->getData('silence_used') ?? false;
        return "تو داوینا 🍾 هستی، با تیم قاتل. می‌تونی یک روز رو سکوت کنی!" . ($silenceUsed ? "\n\n❌ سکوت استفاده شده." : "\n\n✅ سکوت موجود است.");
    }
    
    public function hasDayAction() {
        return !($this->getData('silence_used') ?? false);
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function performDayAction($useSilence = false) {
        $silenceUsed = $this->getData('silence_used') ?? false;
        
        if ($silenceUsed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً از قابلیت سکوت استفاده کردی!'
            ];
        }
        
        if (!$useSilence) {
            return [
                'success' => false,
                'message' => '⚠️ مطمئنی می‌خوای امروز سکوت کنی؟'
            ];
        }
        
        $this->setData('silence_used', true);
        $this->setGameState('silence_active', true);
        
        $this->sendMessageToGroup("🔇 داوینا 🍾 از قابلیتش استفاده کرد! امروز هیچ کس حق حرف زدن نداره!");
        
        return [
            'success' => true,
            'message' => "✅ امروز رو سکوت کردی!",
            'silence' => true
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        return [];
    }
}