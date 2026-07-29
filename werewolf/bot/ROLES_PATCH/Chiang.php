<?php
/**
 * 👩‍🦳 چیانگ (Chiang)
 * تیم: ومپایر
 */

require_once __DIR__ . '/base.php';

class Chiang extends Role {
    
    public function getName() {
        return 'چیانگ';
    }
    
    public function getEmoji() {
        return '👩‍🦳';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        $bloodthirstyDead = $this->getData('bloodthirsty_dead') ?? false;
        
        if (!$bloodthirstyDead) {
            return "تو چیانگ 👩‍🦳 هستی! قبل از مرگ ومپایر اصیل، هر شب اسم یکی از نقش‌های منفی رو بهت می‌گم! بعد از مرگ اصیل، می‌تونی حمله کنی!";
        }
        return "تو چیانگ 👩‍🦳 هستی! ومپایر اصیل مرده و الان می‌تونی با بقیه ومپایرها حمله کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $bloodthirstyDead = $this->getData('bloodthirsty_dead') ?? false;
        
        // اگر اصیل مرده، مثل ومپایر عادی حمله می‌کنه
        if ($bloodthirstyDead) {
            return $this->performVampireAttack($target);
        }
        
        // قبل از مرگ اصیل: پیدا کردن نقش منفی
        $negativeRoles = $this->findNegativeRoles();
        
        if (empty($negativeRoles)) {
            return [
                'success' => true,
                'message' => "🔍 امشب نتونستم منفی‌ها رو پیدا کنم!"
            ];
        }
        
        $found = $negativeRoles[array_rand($negativeRoles)];
        
        return [
            'success' => true,
            'message' => "👁️ فهمیدی که {$found['name']} نقش منفی داره!",
            'found' => true,
            'player' => $found['id']
        ];
    }
    
    private function performVampireAttack($target) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // 30% شانس کشتن
        if (rand(1, 100) <= 30) {
            $this->killPlayer($target, 'chiang');
            return [
                'success' => true,
                'message' => "🩸 به {$targetPlayer['name']} حمله کردی و کشتیش!",
                'killed' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🩸 به {$targetPlayer['name']} حمله کردی ولی ولش کردی!",
            'spared' => $target
        ];
    }
    
    public function onBloodthirstyDeath() {
        $this->setData('bloodthirsty_dead', true);
        $this->sendMessage("🔓 ومپایر اصیل مرد! حالا می‌تونی حمله کنی!");
        $this->notifyVampireTeam("👩‍🦳 چیانگ می‌تونه حمله کنه!");
    }
    
    private function findNegativeRoles() {
        $negative = [];
        foreach ($this->getAllPlayers() as $p) {
            if (!isset($p['alive']) || $p['alive'] !== true) continue;
            if (in_array($p['role'], ['werewolf', 'alpha_wolf', 'serial_killer', 'cultist', 'black_knight', 'joker'])) {
                $negative[] = $p;
            }
        }
        return $negative;
    }
    
    public function getValidTargets($phase = 'night') {
        $bloodthirstyDead = $this->getData('bloodthirsty_dead') ?? false;
        
        if (!$bloodthirstyDead) {
            return []; // فقط اطلاعات می‌گیره
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (in_array($p['role'], ['vampire', 'bloodthirsty', 'chiang'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'chiang_' . $p['id']
            ];
        }
        return $targets;
    }
}