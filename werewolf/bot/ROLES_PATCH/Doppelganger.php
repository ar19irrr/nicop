<?php
/**
 * 🎭 همزاد (Doppelganger)
 * تیم: مستقل
 */

require_once __DIR__ . '/base.php';

class Doppelganger extends Role {
    
    public function getName() {
        return 'همزاد';
    }
    
    public function getEmoji() {
        return '🎭';
    }
    
    public function getTeam() {
        $transformed = $this->getData('transformed') ?? false;
        $target = $this->getData('doppelganger_target');
        
        if ($transformed && $target) {
            $targetPlayer = $this->getPlayerById($target);
            if ($targetPlayer) {
                return detectTeam($targetPlayer['role']);
            }
        }
        return 'independent';
    }
    
    public function getDescription() {
        $target = $this->getData('doppelganger_target');
        $transformed = $this->getData('transformed') ?? false;
        
        if ($transformed && $target) {
            $targetPlayer = $this->getPlayerById($target);
            $role = $targetPlayer ? $targetPlayer['role'] : 'نامشخص';
            return "🎭 تو همزاد هستی! به نقش {$role} تبدیل شدی!";
        }
        
        if ($target) {
            $targetPlayer = $this->getPlayerById($target);
            return "🎭 تو همزاد هستی! منتظر مرگ {$targetPlayer['name']} هستی تا نقشش رو بگیری!";
        }
        
        return "🎭 تو همزاد هستی! یک نفر رو انتخاب کن که وقتی مرد، نقشش رو بگیری! اگر تا آخر بازی نقش‌ت عوض نشه، بازنده می‌شی!";
    }
    
    public function hasNightAction() {
        $target = $this->getData('doppelganger_target');
        return $target === null;
    }
    
    public function performNightAction($target = null) {
        $targetId = $this->getData('doppelganger_target');
        
        if ($targetId !== null) {
            return [
                'success' => false,
                'message' => '❌ قبلاً هدف رو انتخاب کردی!'
            ];
        }
        
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
        
        $this->setData('doppelganger_target', $target);
        
        return [
            'success' => true,
            'message' => "🎭 {$targetPlayer['name']} رو انتخاب کردی! وقتی بمیره، نقشش رو می‌گیری!",
            'target' => $target
        ];
    }
    
    public function onPlayerDeath($deadPlayer) {
        $target = $this->getData('doppelganger_target');
        $transformed = $this->getData('transformed') ?? false;
        
        if ($deadPlayer['id'] == $target && !$transformed) {
            $this->transformToRole($deadPlayer['role']);
        }
    }
    
    private function transformToRole($newRole) {
        $this->setData('transformed', true);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $oldRole = $p['role'];
                $p['role'] = $newRole;
                $p['role_data']['was_doppelganger'] = true;
                $p['role_data']['original_role'] = $oldRole;
                break;
            }
        }
        $this->saveGame();
        
        $roleName = getRoleName($newRole);
        $targetPlayer = $this->getPlayerById($this->getData('doppelganger_target'));
        $targetName = $targetPlayer ? $targetPlayer['name'] : 'نامشخص';
        
        $this->sendMessage(
            "🎭 {$targetName} مرد و تو نقشش رو گرفتی! الان یک {$roleName} هستی!"
        );
        
        // اگه نقش جدید تیم داره، اطلاع بده
        $team = detectTeam($newRole);
        if ($team == 'werewolf') {
            $this->notifyWolfTeam("🎭 همزاد به تیم گرگ‌ها پیوست!");
        } elseif ($team == 'vampire') {
            $this->notifyVampireTeam("🎭 همزاد به تیم ومپایرها پیوست!");
        } elseif ($team == 'cult') {
            $this->notifyCultTeam("🎭 همزاد به تیم فرقه پیوست!");
            $this->introduceCultTeam($this->playerId);
        }
    }
    
    public function checkWinCondition() {
        $transformed = $this->getData('transformed') ?? false;
        
        if (!$transformed) {
            return [
                'won' => false,
                'message' => "🎭 همزاد تبدیل نشد و بازنده شد!"
            ];
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        $target = $this->getData('doppelganger_target');
        
        if ($target !== null) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'doppelganger_' . $p['id']
            ];
        }
        return $targets;
    }
}