<?php
/**
 * 🧛🏻‍♀️ ومپایر اصیل (Bloodthirsty)
 * تیم: ومپایر (Vampire)
 */

require_once __DIR__ . '/base.php';

class Bloodthirsty extends Role {
    
    protected $isFree = false;        // آیا آزاد شده؟
    protected $hunterId = null;       // آیدی کلانتر
    protected $convertChance = 40;    // درصد تبدیل
    
    public function getName() {
        return 'ومپایر اصیل';
    }
    
    public function getEmoji() {
        return '🧛🏻‍♀️';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        if (!$this->isFree) {
            $hunterName = $this->getHunterName();
            return "تو ومپایر اصیل 🧛🏻‍♀️ هستی. توسط کلانتر {$hunterName} زندانی شدی! باید صبر کنی تا ومپایرهای دیگه تو رو آزاد کنن یا کلانتر بمیره. بعدش رهبر ومپایرها می‌شی و ۴۰٪ قدرت تبدیل داری!";
        }
        return "تو ومپایر اصیل 🧛🏻‍♀️ هستی، رهبر ومپایرها! هر شب می‌تونی به یکی حمله کنی و ۴۰٪ احتمال داری اونو تبدیل به ومپایر کنی!";
    }
    
    public function hasNightAction() {
        return $this->isFree;
    }
    
    public function performNightAction($target = null) {
        if (!$this->isFree) {
            return [
                'success' => false,
                'message' => '⛓️ هنوز زندانی کلانتر هستی! نمی‌تونی از قابلیتت استفاده کنی.'
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
        
        // بررسی تبدیل یا کشتن
        $rand = rand(1, 100);
        if ($rand <= $this->convertChance) {
            // تبدیل به ومپایر
            $this->convertToVampire($target);
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ به {$targetPlayer['name']} حمله کردی و بعد از نوشیدن خونش، خودت رو کنترل کردی و ۴۰٪ شانس رو دادی! اون الان آلوده شده و فردا تبدیل به ومپایر می‌شه!",
                'converted' => $target
            ];
        } else {
            // کشتن
            $this->killPlayer($target, 'bloodthirsty');
            return [
                'success' => true,
                'message' => "🩸 به {$targetPlayer['name']} حمله کردی و خونش رو تا آخرین قطره نوشیدی! تبدیل نشد و مرد!",
                'killed' => $target
            ];
        }
    }
    
    public function freeFromPrison() {
        $this->isFree = true;
        $this->sendMessageToPlayer($this->getId(), "🎉 آزاد شدی! حالا رهبر ومپایرها هستی و می‌تونی هر شب حمله کنی!");
        
        // اطلاع به تیم ومپایر
        $this->notifyVampireTeam("🔓 ومپایر اصیل آزاد شد! حالا می‌تونه حمله کنه و ۴۰٪ تبدیل داره!");
    }
    
    public function setHunterId($id) {
        $this->hunterId = $id;
    }
    
    private function getHunterName() {
        if ($this->hunterId) {
            $hunter = $this->getPlayerById($this->hunterId);
            return $killer ? $killer['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    private function convertToVampire($playerId) {
        // منطق تبدیل به ومپایر
        $this->setPlayerRole($playerId, 'vampire');
        $this->sendMessageToPlayer($playerId, "🧛🏻‍♂️ دیشب یه ومپایر بهت حمله کرد و بعد از نوشیدن خونت ولت کرد. آلوده شدی و فردا تبدیل به یک ومپایر می‌شی!");
    }
    
    public function getValidTargets($phase = 'night') {
        if (!$this->isFree) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'bloodthirsty_' . $p['id']
            ];
        }
        return $targets;
    }
}