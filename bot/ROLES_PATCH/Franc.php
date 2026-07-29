<?php
/**
 * 🧟‍♂️🪖 فرانکشتاین (Franc)
 * تیم: فرقه (Cult)
 */

require_once __DIR__ . '/base.php';

class Franc extends Role {
    
    public function getName() {
        return 'فرانکشتاین';
    }
    
    public function getEmoji() {
        return '🧟‍♂️🪖';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        $isAlone = $this->getData('is_alone') ?? false;
        
        if (!$isAlone) {
            return "تو فرانکشتاین 🧟‍♂️🪖 هستی! کله‌ی آهنی داری که باعث می‌شه نقش‌های کشنده راحت از پا درت نیارن. از اعضای فرقه محافظت می‌کنی!";
        }
        return "تو فرانکشتاین 🧟‍♂️🪖 هستی! همه‌ی فرقه مردن! الان می‌تونی به اهالی حمله کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $isAlone = $this->getData('is_alone') ?? false;
        
        if ($isAlone) {
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
            
            $this->killPlayer($target, 'franc');
            
            return [
                'success' => true,
                'message' => "⚔️ {$targetPlayer['name']} رو کشتی!",
                'killed' => $target
            ];
        }
        
        // محافظت از فرقه
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب از کدوم فرقه محافظت کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if (!$this->isCultMember($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ فقط می‌تونی از اعضای فرقه محافظت کنی!'
            ];
        }
        
        $this->setData('guarding', $target);
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت می‌کنی!",
            'guarding' => $target
        ];
    }
    
    public function onCultMemberAttacked($targetId, $attackerRole) {
        $guarding = $this->getData('guarding');
        
        if ($guarding != $targetId) {
            return ['protected' => false];
        }
        
        $target = $this->getPlayerById($targetId);
        $this->sendMessageToPlayer($targetId, "🛡️ فرانکشتاین جونت رو نجات داد!");
        
        return ['protected' => true];
    }
    
    public function onCultHunterAttack($hunterId) {
        if (rand(1, 100) <= 10) {
            $this->killPlayer($hunterId, 'franc');
            return ['killed_hunter' => true, 'died' => false];
        }
        return ['killed_hunter' => false];
    }
    
    public function onCultDeath() {
        $this->setData('is_alone', true);
        $this->sendMessage("😠 همه فرقه مردن! از امشب می‌تونی به اهالی حمله کنی!");
    }
    
    private function isCultMember($role) {
        return in_array($role, ['cultist', 'royce', 'franc', 'monk_black']);
    }
    
    public function getValidTargets($phase = 'night') {
        $isAlone = $this->getData('is_alone') ?? false;
        
        if ($isAlone) {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'franc_attack_' . $p['id']
                ];
            }
            return $targets;
        }
        
        $targets = [];
        foreach ($this->getAllPlayers() as $p) {
            if ($p['id'] != $this->playerId && $p['alive'] && $this->isCultMember($p['role'])) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'franc_guard_' . $p['id']
                ];
            }
        }
        return $targets;
    }
}