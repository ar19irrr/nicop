<?php
/**
 * 👮🏻‍♂️ کلانتر (Hunter)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Hunter extends Role {
    
    public function getName() {
        return 'کلانتر';
    }
    
    public function getEmoji() {
        return '👮🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $hasRevenge = $this->getData('has_revenge') ?? true;
        return "👮🏻‍♂️ تو کلانتر هستی! اگه کسی بکشتت، می‌تونی به یه نفر شلیک کنی! اگه گرگ‌ها حمله کنن، شانس کشتن یه گرگ رو داری!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        $this->imprisonBloodthirsty();
    }
    
    private function imprisonBloodthirsty() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'bloodthirsty') {
                $this->setData('imprisoned_bloodthirsty', $p['id']);
                
                $this->sendMessageToPlayer($p['id'], 
                    "🧛🏻‍♀️ توسط کلانتر {$this->getPlayerName()} زندانی شدی!"
                );
                
                $this->sendMessage("🧛🏻‍♀️ ومپایر اصیل رو زندانی کردی!");
                break;
            }
        }
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        $hasRevenge = $this->getData('has_revenge') ?? true;
        
        if ($hasRevenge) {
            return $this->performRevenge($attackerRole, $attackerId);
        }
        
        return ['died' => true];
    }
    
    private function performRevenge($attackerRole, $attackerId) {
        $attacker = $this->getPlayerById($attackerId);
        
        if ($this->isWolf($attacker['role'])) {
            $chance = $this->calculateWolfKillChance();
            if (rand(1, 100) <= $chance) {
                $this->killPlayer($attackerId, 'hunter');
                $this->setData('has_revenge', false);
                
                return [
                    'died' => true,
                    'killed_attacker' => true,
                    'message' => "👮🏻‍♂️ قبل از مرگ {$attacker['name']} رو کشتی!"
                ];
            }
        }
        
        $this->setData('has_revenge', false);
        
        return [
            'died' => true,
            'can_shoot' => true,
            'message' => "👮🏻‍♂️ داری می‌میری! می‌تونی به یه نفر شلیک کنی!"
        ];
    }
    
    private function calculateWolfKillChance() {
        $wolfCount = 0;
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                $wolfCount++;
            }
        }
        return min(30 + (($wolfCount - 1) * 20), 90);
    }
    
    public function onLynched() {
        $hasRevenge = $this->getData('has_revenge') ?? true;
        
        if ($hasRevenge) {
            return [
                'can_shoot' => true,
                'message' => "👮🏻‍♂️ دارن اعدامت می‌کنن! می‌تونی به یه نفر شلیک کنی!"
            ];
        }
        
        return ['died' => true];
    }
    
    public function performRevengeShot($target) {
        $targetPlayer = $this->getPlayerById($target);
        if ($targetPlayer && $targetPlayer['alive']) {
            $this->killPlayer($target, 'hunter');
            $this->setData('has_revenge', false);
            
            return [
                'success' => true,
                'message' => "💥 به {$targetPlayer['name']} شلیک کردی و کشتیش!"
            ];
        }
        return ['success' => false, 'message' => "❌ شلیک ناموفق!"];
    }
    
    public function getValidTargets($phase = 'revenge') {
        if ($phase == 'revenge') {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'hunter_revenge_' . $p['id']
                ];
            }
            return $targets;
        }
        return [];
    }
}