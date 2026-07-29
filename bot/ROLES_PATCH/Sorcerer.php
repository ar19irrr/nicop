<?php
/**
 * 🔮 جادوگر (Sorcerer)
 * تیم: گرگ‌نما
 */

require_once __DIR__ . '/base.php';

class Sorcerer extends Role {
    
    public function getName() {
        return 'جادوگر';
    }
    
    public function getEmoji() {
        return '🔮';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "🔮 تو جادوگر هستی! با تیم گرگ‌ها هستی ولی نمی‌دونی اونا کیان! هر شب می‌تونی ببینی کسی پیشگو، گرگ، افسونگر یا ناتاشا هست یا نه!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
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
        
        $targetRole = $targetPlayer['role'];
        $visibleRoles = ['seer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan',
                         'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf',
                         'enchanter', 'harlot'];
        
        if (in_array($targetRole, $visibleRoles)) {
            $roleName = getRoleDisplayName($targetRole);
            return [
                'success' => true,
                'message' => "🔮 {$targetPlayer['name']} یک {$roleName} هست!",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔮 چیزی درباره {$targetPlayer['name']} ندیدی!",
            'found' => false
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'sorcerer_' . $p['id']
            ];
        }
        return $targets;
    }
}