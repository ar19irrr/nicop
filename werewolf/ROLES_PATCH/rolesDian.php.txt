<?php
/**
 * 🧞‍♂️ دیان (Dian)
 * تیم: مستقل (Independent)
 */

require_once __DIR__ . '/base.php';

class Dian extends Role {
    
    protected $targetId = null;       // آیدی هدف انتخاب شده
    protected $targetSelected = false; // آیا هدف انتخاب شده؟
    protected $daysRemaining = 4;     // روزهای باقی‌مانده
    protected $daysPassed = 0;        // روزهای گذشته
    
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
        if (!$this->targetSelected) {
            return "تو دیان 🧞‍♂️ هستی! تنها در روز دوم می‌تونی ۱ نفر رو انتخاب کنی. روستایی‌ها ۴ روز فرصت دارن اون رو اعدام کنن. اگر اعدامش نکنن، تیم جنگل سیاه (تو) برنده می‌شه!";
        }
        return "تو دیان 🧞‍♂️ هستی! هدف تو {$this->getTargetName()} است. {$this->daysRemaining} روز دیگه فرصت دارن اون رو اعدام کنن!";
    }
    
    public function hasDayAction() {
        return !$this->targetSelected && $this->getCurrentDay() == 2;
    }
    
    public function performDayAction($target = null) {
        if ($this->targetSelected) {
            return [
                'success' => false,
                'message' => '❌ قبلاً هدف رو انتخاب کردی!'
            ];
        }
        
        if ($this->getCurrentDay() != 2) {
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
        
        $this->targetId = $target;
        $this->targetSelected = true;
        
        // اعلام در گروه
        $this->sendMessageToGroup("🔴 خب روستایی‌ها دیان 🧞‍♂️ تصمیمش رو گرفته! قراره ظرف مدت ۴ روز یک نفر قربانی بشه و اون کسی نیست جز {$targetPlayer['name']} :( اگر توی این ۴ روز دیان کشته بشه این حکم لغو می‌شه و اگر اعدامش نکنید تیم جنگل سیاه برنده بازی می‌شه!");
        
        return [
            'success' => true,
            'message' => "✅ {$targetPlayer['name']} رو به عنوان هدف انتخاب کردی! اگر ظرف ۴ روز اعدامش نکنن، تو برنده می‌شی!",
            'target' => $target
        ];
    }
    
    public function onDayEnd() {
        if (!$this->targetSelected) {
            return;
        }
        
        $this->daysPassed++;
        $this->daysRemaining--;
        
        // بررسی آیا هدف اعدام شده
        $target = $this->getPlayerById($this->targetId);
        if (!$target || !$target['alive']) {
            $this->sendMessageToGroup("✅ خب تبریک! از دست دیان 🧞‍♂️ خلاص شدید و شخص مورد نظرش رو اعدام کردید!");
            $this->targetSelected = false;
            return;
        }
        
        // بررسی ۴ روز گذشته
        if ($this->daysPassed >= 4) {
            $this->declareWinner();
        }
    }
    
    public function onDeath() {
        if ($this->targetSelected) {
            $this->sendMessageToGroup("🎉 چون دیان 🧞‍♂️ مرده، حکمش هم باطل شد و می‌تونید شخص اعلامی رو اعدام نکنید!");
        }
    }
    
    private function declareWinner() {
        $this->sendMessageToGroup("💀 متاسفانه بعد گذشت ۴ روز شما {$this->getTargetName()} رو که توسط دیان 🧞‍♂️ اعلام شده بود اعدام نکردید و تیم جنگل سیاه بازی رو برد!");
        $this->declareWinners(['independent']);
    }
    
    private function getTargetName() {
        if ($this->targetId) {
            $target = $this->getPlayerById($this->targetId);
            return $target ? $target['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase != 'day' || $this->targetSelected || $this->getCurrentDay() != 2) {
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