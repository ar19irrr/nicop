<?php
/**
 * 👑 حاکم (Ruler)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Ruler extends Role {
    
    protected $powerUsed = false;     // آیا قدرت استفاده شده؟
    
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
        return "تو حاکم 👑 هستی! به عنوان بخشی از خانواده سلطنتی، بر روستا تسلط داری. می‌تونی در یک روز بجای همه تصمیم بگیری که چه کسی اعدام شه!";
    }
    
    public function hasDayAction() {
        return !$this->powerUsed;
    }
    
    public function performDayAction($usePower = false) {
        if ($this->powerUsed) {
            return [
                'success' => false,
                'message' => '❌ قبلاً از قدرتت استفاده کردی!'
            ];
        }
        
        if (!$usePower) {
            return [
                'success' => false,
                'message' => '👑 حاکم عزیز، امروز می‌خوای حکمت رو نشون روستایی‌ها بدی و یکی رو به انتخاب خودت اعدام کنی؟'
            ];
        }
        
        $this->powerUsed = true;
        
        // اعلام در گروه
        $this->sendMessageToGroup("👑 زمانی که روستایی‌ها در حال تصمیم گرفتن اعدام بعدیشون بودن... حاکم قدمی به جلو برمی‌داره و تاجشو به مردم نشون می‌ده. {$this->getPlayerName()} از قدرتش استفاده می‌کنه و امروز می‌خواد فقط خودش حکم اعدام رو صادر کنه!");
        
        return [
            'success' => true,
            'message' => "✅ امروز قدرتت رو نشون دادی! الان می‌تونی یکی رو اعدام کنی.",
            'power_activated' => true
        ];
    }
    
    public function performExecution($target = null) {
        if (!$this->powerUsed || !$target) {
            return [
                'success' => false,
                'message' => '❌ باید اول قدرتت رو فعال کنی و بعد یکی رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // اعدام
        $this->killPlayer($target, 'ruler_execution');
        
        return [
            'success' => true,
            'message' => "⚔️ حاکم 👑 با کلی شک و تردید رای خودش رو صادر کرد و {$targetPlayer['name']} رو در وسط روستا جلوی چشم همه اعدامش کرد!",
            'executed' => $target
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        if (!$this->powerUsed || $phase != 'day') {
            return [];
        }
        
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