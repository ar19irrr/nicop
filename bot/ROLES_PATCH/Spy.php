<?php
/**
 * 🦹🏻‍♂️ جاسوس (Spy)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Spy extends Role {
    
    public function getName() {
        return 'جاسوس';
    }
    
    public function getEmoji() {
        return '🦹🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "🦹🏻‍♂️ تو جاسوس هستی! هر روز می‌تونی یک نفر رو زیر نظر بگیری و بفهمی توانایی کشتن داره یا نه. همچنین ۳۰٪ احتمال داره هر کسی بهت حمله کنه رو بکشی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function hasDayAction() {
        return true;
    }
    
    public function performDayAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو می‌خوای زیر نظر بگیری؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->logAction('spy', $target);
        $canKill = $this->canKill($targetPlayer);
        
        if ($canKill) {
            return [
                'success' => true,
                'message' => "🕵️ {$targetPlayer['name']} توانایی کشتن داره!",
                'can_kill' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🕵️ {$targetPlayer['name']} نمی‌تونه کسی رو بکشه.",
            'can_kill' => false
        ];
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        if (rand(1, 100) <= 30) {
            $attacker = $this->getPlayerById($attackerId);
            $this->killPlayer($attackerId, 'spy_mind_control');
            
            return [
                'died' => false,
                'killed_attacker' => true,
                'message' => "🦹🏻‍♂️ با کنترل ذهنی {$attacker['name']} رو کشتی!"
            ];
        }
        
        return ['died' => true];
    }
    
    private function canKill($player) {
        $killerRoles = ['serial_killer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan',
                        'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'archer',
                        'vampire', 'bloodthirsty', 'kent_vampire', 'hunter', 'knight',
                        'black_knight', 'bride_dead', 'blacksmith', 'bomber', 'dinamit',
                        'joker', 'harly', 'lilith', 'lucifer', 'fire_king', 'ice_queen',
                        'magento', 'dian', 'chiang', 'frankenstein', 'enchanter',
                        'huntsman', 'princess', 'ruler', 'phoenix', 'chemist', 'harlot'];
        
        return in_array($player['role'], $killerRoles);
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase != 'day') return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'spy_' . $p['id']
            ];
        }
        return $targets;
    }
}