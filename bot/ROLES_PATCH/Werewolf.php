<?php
/**
 * 🐺 گرگ ساده (Werewolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Werewolf extends Role {
    
    public function getName() {
        return 'گرگینه';
    }
    
    public function getEmoji() {
        return '🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        $teamList = $this->getTeamList();
        return "🐺 تو گرگینه هستی! هر شب می‌تونی به یه نفر حمله کنی و بخوریش. {$teamList}";
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
        
        $this->logAction('werewolf_vote', $target);
        
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    private function getTeamList() {
        $wolves = $this->getWolfTeam();
        if (empty($wolves)) return '';
        
        $names = [];
        foreach ($wolves as $wolf) {
            if ($wolf['id'] != $this->getId()) {
                $names[] = $wolf['name'];
            }
        }
        
        return empty($names) ? '' : "\n👥 بقیه گرگ‌ها: " . implode(', ', $names);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'werewolf_' . $p['id']
            ];
        }
        return $targets;
    }
}