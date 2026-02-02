<?php
/**
 * 🐶 توله گرگ (WolfCub)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class WolfCub extends Role {
    
    public function getName() {
        return 'توله گرگ';
    }
    
    public function getEmoji() {
        return '🐶';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو توله گرگ 🐶 هستی! عزیز دوره‌ی گرگ‌ها. اگر به هر نحوی بمیری، گرگ‌ها برای انتقام شب بعد می‌تونن ۲ نفر رو بخورن!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        // مثل گرگ ساده
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
            'message' => "🐶 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onDeath() {
        // فعال کردن قدرت انتقام برای گرگ‌ها
        $this->notifyWolfTeam("💔 توله گرگ مرد! برای انتقام، شب بعد می‌تونید ۲ نفر رو بخورید!");
        $this->setGameState('wolf_double_kill', true);
        
        return [
            'message' => "🐶 توله گرگ مرد! گرگ‌ها برای انتقام شب بعد ۲ نفر رو می‌خورن!"
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
                'callback' => 'wolf_cub_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}