<?php
/**
 * 🌝🐺 گرگ ایکس (Lycan)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Lycan extends Role {
    
    public function getName() {
        return 'گرگ ایکس';
    }
    
    public function getEmoji() {
        return '🌝🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ ایکس 🌝🐺 هستی! شب‌ها تبدیل به گرگینه می‌شی ولی اگر پیشگو نقشت رو چک کنه، تو رو یه شاهزاده 🤴🏻 تشخیص می‌ده!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
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
        
        return [
            'success' => true,
            'message' => "🌝 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onSeerCheck() {
        // وقتی پیشگو چکش می‌کنه، شاهزاده نشون داده می‌شه (به جای روستایی)
        return [
            'fake_role' => 'prince',
            'display_name' => '🤴🏻 شاهزاده'
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolfTeam($p['role'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'lycan_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}