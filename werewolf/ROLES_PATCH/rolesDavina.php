<?php
/**
 * 🍾 داوینا (Davina)
 * تیم: قاتل (Killer)
 */

require_once __DIR__ . '/base.php';

class Davina extends Role {
    
    protected $silenceUsed = false; // آیا سکوت استفاده شده؟
    
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
        return "تو داوینا 🍾 هستی، با تیم قاتل. می‌تونی یک روز رو سکوت کنی! وقتی سکوت کنی، نه کسی می‌تونه حرف بزنه توی گروه، نه نقش‌های روزکار می‌تونن از قابلیتشون استفاده کنن!";
    }
    
    public function hasDayAction() {
        return !$this->silenceUsed;
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function performDayAction($useSilence = false) {
        if ($this->silenceUsed) {
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
        
        $this->silenceUsed = true;
        
        // اعمال سکوت در گروه
        $this->sendMessageToGroup("🔇 خب خب بلاخره داوینا 🍾 از قابلیتش استفاده کرد! فردا نه کسی حق حرف زدن داره و نه نقشای روزکار می‌تونن از قابلیتشون استفاده کنن! فردا چه شود.");
        
        return [
            'success' => true,
            'message' => "✅ امروز رو سکوت کردی! همه چیز خاموشه...",
            'silence' => true
        ];
    }
    
    public function isSilenceActive() {
        return $this->silenceUsed;
    }
}