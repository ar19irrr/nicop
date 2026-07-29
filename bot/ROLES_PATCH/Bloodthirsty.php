<?php
/**
 * 🧛🏻‍♀️ ومپایر اصیل (Bloodthirsty)
 */

require_once __DIR__ . '/base.php';

class Bloodthirsty extends Role {
    
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
        $isFree = $this->getData('is_free') ?? false;
        $hunterName = $this->getHunterName();
        
        if (!$isFree) {
            return "تو ومپایر اصیل 🧛🏻‍♀️ هستی. توسط کلانتر {$hunterName} زندانی شدی! باید صبر کنی تا ومپایرهای دیگه تو رو آزاد کنن یا کلانتر بمیره. بعدش رهبر ومپایرها می‌شی و ۴۰٪ قدرت تبدیل داری!";
        }
        return "تو ومپایر اصیل 🧛🏻‍♀️ هستی، رهبر ومپایرها! هر شب می‌تونی به یکی حمله کنی و ۴۰٪ احتمال داری اونو تبدیل به ومپایر کنی!";
    }
    
    public function hasNightAction() {
        return $this->getData('is_free') ?? false;
    }
    
    public function performNightAction($target = null) {
        $isFree = $this->getData('is_free') ?? false;
        
        if (!$isFree) {
            return [
                'success' => false,
                'message' => '⛓️ هنوز زندانی کلانتر هستی!'
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
        
        $convertChance = $this->getData('convert_chance') ?? 40;
        $rand = rand(1, 100);
        
        if ($rand <= $convertChance) {
            $this->convertToVampire($target);
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ به {$targetPlayer['name']} حمله کردی! اون آلوده شد و فردا تبدیل به ومپایر می‌شه!",
                'converted' => $target
            ];
        } else {
            $this->killPlayer($target, 'bloodthirsty');
            return [
                'success' => true,
                'message' => "🩸 به {$targetPlayer['name']} حمله کردی و خونش رو نوشیدی!",
                'killed' => $target
            ];
        }
    }
    
    public function freeFromPrison() {
        $this->setData('is_free', true);
        $this->sendMessage("🎉 آزاد شدی! حالا رهبر ومپایرها هستی!");
        $this->notifyVampireTeam("🔓 ومپایر اصیل آزاد شد!");
    }
    
    public function setHunterId($id) {
        $this->setData('hunter_id', $id);
    }
    
    private function getHunterName() {
        $hunterId = $this->getData('hunter_id');
        if ($hunterId) {
            $hunter = $this->getPlayerById($hunterId);
            return $hunter ? $hunter['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    private function convertToVampire($playerId) {
        $this->setPlayerRole($playerId, 'vampire');
        $this->sendMessageToPlayer($playerId, "🧛🏻‍♂️ آلوده شدی! فردا تبدیل به ومپایر می‌شی!");
    }
    
    public function getValidTargets($phase = 'night') {
        $isFree = $this->getData('is_free') ?? false;
        
        if (!$isFree) {
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