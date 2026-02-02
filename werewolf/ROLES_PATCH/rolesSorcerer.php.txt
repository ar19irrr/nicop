<?php
/**
 * 🔮 جادوگر (ساحره)
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
        return "تو جادوگر🔮 هستی. با تیم گرگ‌ها هستی ولی نمی‌دونی اونا کیان! هر شب با گوی جادویی می‌تونی ببینی یه نفر پیشگو، گرگ، افسونگر یا شبگرد هست یا نه!";
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
        
        // فقط این نقش‌ها رو می‌بینه
        $visibleRoles = ['seer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'harlot'];
        
        if (in_array($targetRole, $visibleRoles)) {
            $roleName = $this->getRoleDisplayName($targetRole);
            return [
                'success' => true,
                'message' => "🔮 توی گوی دیدی که {$targetPlayer['name']} یه {$roleName} هست!",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔮 توی گوی گشتی ولی چیزی درباره {$targetPlayer['name']} ندیدی!",
            'found' => false
        ];
    }
    
    private function getRoleDisplayName($role) {
        $names = [
            'seer' => '👳🏻‍♂️ پیشگو',
            'werewolf' => '🐺 گرگینه',
            'alpha_wolf' => '⚡️🐺 گرگ آلفا',
            'wolf_cub' => '🐶 توله گرگ',
            'lycan' => '🌝🐺 گرگ ایکس',
            'forest_queen' => '🧝🏻‍♀️🐺 ملکه جنگل',
            'white_wolf' => '🌩🐺 گرگ سفید',
            'beta_wolf' => '💤🐺 گرگ خوابالو',
            'ice_wolf' => '☃️🐺 گرگ برفی',
            'enchanter' => '🧙🏻‍♂️ افسونگر',
            'harlot' => '💋 ناتاشا'
        ];
        return $names[$role] ?? $role;
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