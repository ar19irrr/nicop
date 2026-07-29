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
        return "🐶 تو توله گرگ هستی! اگر بمیری، گرگ‌ها برای انتقام شب بعد می‌تونن ۲ نفر رو بخورن!";
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
        
        $this->logAction('wolf_cub_vote', $target);
        
        return [
            'success' => true,
            'message' => "🐶 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onDeath() {
        $this->setGameState('wolf_double_kill', true);
        $this->notifyWolfTeam("💔 توله گرگ مرد! شب بعد ۲ نفر رو می‌تونید بخورید!");
        
        return ['message' => "🐶 توله گرگ مرد! گرگ‌ها ۲ نفر رو می‌خورن!"];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'wolfcub_' . $p['id']
            ];
        }
        return $targets;
    }
}