<?php
/**
 * 👳🏻‍♂️ پیشگو (Seer)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Seer extends Role {
    
    public function getName() {
        return 'پیشگو';
    }
    
    public function getEmoji() {
        return '👳🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "👳🏻‍♂️ تو پیشگو هستی! هر شب می‌تونی نقش یک نفر رو ببینی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ نقش کی رو ببینی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $realRole = $targetPlayer['role'];
        $seenPlayers = $this->getData('seen_players') ?? [];
        $seenPlayers[$target] = $realRole;
        $this->setData('seen_players', $seenPlayers);
        
        // بررسی طلسم عجوزه
        $displayRole = $realRole;
        if ($this->isCursedByHoney($target)) {
            $displayRole = 'werewolf';
        } elseif ($realRole == 'lycan') {
            $displayRole = 'prince';
        } elseif ($realRole == 'wolf_man') {
            $displayRole = 'werewolf';
        }
        
        $roleName = getRoleDisplayName($displayRole);
        
        return [
            'success' => true,
            'message' => "👁️ {$targetPlayer['name']} یک {$roleName} هست!",
            'seen_role' => $displayRole
        ];
    }
    
    private function isCursedByHoney($player) {
        $honey = null;
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'honey' && isset($p['alive']) && $p['alive'] === true) {
                $honey = $p;
                break;
            }
        }
        if (!$honey) return false;
        
        $cursedPlayers = $honey['role_data']['cursed_players'] ?? [];
        return in_array($player['id'], $cursedPlayers);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'seer_' . $p['id']
            ];
        }
        return $targets;
    }
}