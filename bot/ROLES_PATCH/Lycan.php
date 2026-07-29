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
        return "🌝🐺 تو گرگ ایکس هستی! شب‌ها تبدیل به گرگینه می‌شی ولی اگر پیشگو نقشت رو چک کنه، تو رو یک شاهزاده 🤴🏻 تشخیص می‌ده!";
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
        
        $this->logAction('lycan_vote', $target);
        
        return [
            'success' => true,
            'message' => "🌝 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onSeerCheck() {
        return [
            'fake_role' => 'prince',
            'display_name' => '🤴🏻 شاهزاده'
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'lycan_' . $p['id']
            ];
        }
        return $targets;
    }
}