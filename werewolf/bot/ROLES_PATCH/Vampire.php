<?php
/**
 * 🧛🏻‍♂️ ومپایر (Vampire)
 * تیم: ومپایر
 */

require_once __DIR__ . '/base.php';

class Vampire extends Role {
    
    public function getName() {
        return 'ومپایر';
    }
    
    public function getEmoji() {
        return '🧛🏻‍♂️';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        $bloodthirstyFreed = $this->getData('bloodthirsty_freed') ?? false;
        
        if (!$bloodthirstyFreed) {
            return "🧛🏻‍♂️ تو ومپایر هستی! هر شب به دنبال کلانتر می‌گردی تا ومپایر اصیل رو آزاد کنی!";
        }
        return "🧛🏻‍♂️ ومپایر اصیل آزاد شد! هر شب می‌تونی حمله کنی و ۳۰٪ تبدیل کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $bloodthirstyFreed = $this->getData('bloodthirsty_freed') ?? false;
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب به کی حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // کلانتر - آزاد کردن اصیل
        if ($targetPlayer['role'] == 'hunter' && !$bloodthirstyFreed) {
            $this->killPlayer($target, 'vampire');
            $this->setData('bloodthirsty_freed', true);
            $this->freeBloodthirsty();
            
            return [
                'success' => true,
                'message' => "🎉 کلانتر رو کشتی و ومپایر اصیل رو آزاد کردی!",
                'killed' => $target,
                'freed_bloodthirsty' => true
            ];
        }
        
        // شکارچی - مرگ ومپایر
        if ($targetPlayer['role'] == 'cult_hunter') {
            $this->killPlayer($this->getId(), 'cult_hunter');
            $this->sendMessageToGroup("🪵 ومپایر توسط شکارچی کشته شد!");
            
            return [
                'success' => false,
                'message' => "💀 شکارچی کشتت!",
                'died' => true
            ];
        }
        
        // گرگ - مرگ ومپایر
        if ($this->isWolf($targetPlayer['role'])) {
            $this->killPlayer($this->getId(), 'wolf');
            $this->sendMessageToGroup("🐺 ومپایر توسط گرگ کشته شد!");
            
            return [
                'success' => false,
                'message' => "🐺 گرگ کشتت!",
                'died' => true
            ];
        }
        
        // حمله عادی
        $rand = rand(1, 100);
        
        if ($rand <= 30) {
            $this->killPlayer($target, 'vampire');
            return [
                'success' => true,
                'message' => "🩸 {$targetPlayer['name']} رو کشتی!",
                'killed' => $target
            ];
        }
        
        if ($rand <= 60 && $bloodthirstyFreed) {
            $this->convertToVampire($target);
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ {$targetPlayer['name']} آلوده شد! فردا تبدیل به ومپایر می‌شه!",
                'converted' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🩸 به {$targetPlayer['name']} حمله کردی ولی ولش کردی!",
            'spared' => $target
        ];
    }
    
    private function freeBloodthirsty() {
        $bloodthirstyId = $this->getData('bloodthirsty_id');
        if ($bloodthirstyId) {
            $this->sendMessageToPlayer($bloodthirstyId, "🔓 ومپایر اصیل آزاد شدی!");
        }
        $this->notifyVampireTeam("🎉 ومپایر اصیل آزاد شد!");
    }
    
    private function convertToVampire($playerId) {
        $this->setPlayerRole($playerId, 'vampire');
        $this->sendMessageToPlayer($playerId, "🧛🏻‍♂️ آلوده شدی! فردا ومپایر می‌شی!");
    }
    
    public function setBloodthirstyId($id) {
        $this->setData('bloodthirsty_id', $id);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (in_array($p['role'], ['vampire', 'bloodthirsty'])) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'vampire_' . $p['id']
            ];
        }
        return $targets;
    }
}