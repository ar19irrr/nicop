<?php
/**
 * 🌩🐺 گرگ سفید (WhiteWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class WhiteWolf extends Role {
    
    public function getName() {
        return 'گرگ سفید';
    }
    
    public function getEmoji() {
        return '🌩🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "🌩🐺 تو گرگ سفید هستی! هر شب می‌تونی از یکی از گرگ‌ها محافظت کنی. اگر آخرین گرگ باشی، می‌تونی حمله کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $isLastWolf = $this->isLastWolf();
        
        // اگر آخرین گرگه، می‌تونه حمله کنه
        if ($isLastWolf) {
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
            
            $this->logAction('white_wolf_attack', $target);
            
            return [
                'success' => true,
                'message' => "🐺 (آخرین گرگ) نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
                'vote' => $target
            ];
        }
        
        // محافظت از گرگ‌ها
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ از کدوم گرگ می‌خوای محافظت کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if (!$this->isWolf($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ فقط از گرگ‌ها می‌تونی محافظت کنی!'
            ];
        }
        
        $this->setData('guarding', $target);
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت می‌کنی!",
            'guarding' => $target
        ];
    }
    
    public function onAttackTeammate($targetId, $attackerRole) {
        $guarding = $this->getData('guarding');
        
        if ($guarding != $targetId) return ['protected' => false];
        
        $threats = ['serial_killer', 'archer', 'knight', 'vampire', 'bloodthirsty', 'fire_king', 'ice_queen'];
        if (!in_array($attackerRole, $threats)) return ['protected' => false];
        
        $target = $this->getPlayerById($targetId);
        $this->sendMessageToPlayer($targetId, "🛡️ گرگ سفید نجاتت داد!");
        
        return [
            'protected' => true,
            'message' => "🌩 گرگ سفید از {$target['name']} محافظت کرد!"
        ];
    }
    
    private function isLastWolf() {
        $wolvesAlive = 0;
        foreach ($this->getAllPlayers() as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                $wolvesAlive++;
            }
        }
        return $wolvesAlive <= 1;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        $isLastWolf = $this->isLastWolf();
        
        if ($isLastWolf) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'whitewolf_' . $p['id']
                ];
            }
            return $targets;
        }
        
        foreach ($this->getAllPlayers() as $p) {
            if ($p['id'] != $this->playerId && isset($p['alive']) && $p['alive'] === true && $this->isWolf($p['role'])) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => '🛡️ ' . $p['name'],
                    'callback' => 'whitewolf_guard_' . $p['id']
                ];
            }
        }
        return $targets;
    }
}