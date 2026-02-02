<?php
/**
 * 🎭 همزاد
 */

require_once __DIR__ . '/base.php';

class Doppelganger extends Role {
    
    private $target = null;
    private $transformed = false;
    
    public function getName() {
        return 'همزاد';
    }
    
    public function getEmoji() {
        return '🎭';
    }
    
    public function getTeam() {
        if ($this->transformed && $this->target) {
            $targetPlayer = $this->getPlayerById($this->target);
            if ($targetPlayer) {
                // گرفتن تیم نقش جدید
                $roleObj = $this->getRoleObject($targetPlayer['role']);
                return $roleObj ? $roleObj->getTeam() : 'neutral';
            }
        }
        return 'neutral';
    }
    
    public function getDescription() {
        return "تو همزاد🎭 هستی. می‌تونی در ابتدای بازی یکی از بازیکنا رو انتخاب کنی که وقتی اون شخص بمیره، نقشش (هر نقشی که داشته باشه) به تو می‌رسه! اگر تا آخر بازی نقشت تغییر نکنه، بازنده می‌شی!";
    }
    
    public function hasNightAction() {
        return $this->target === null;
    }
    
    public function onGameStart() {
        if ($this->target === null) {
            $this->sendMessage("یکی رو انتخاب کن که وقتی مرد، نقشش رو بگیری!");
        }
    }
    
    public function performNightAction($target = null) {
        if ($this->target !== null) {
            return [
                'success' => false,
                'message' => '❌ قبلاً انتخاب کردی!'
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
        
        $this->target = $target;
        $this->setData('doppelganger_target', $target);
        
        return [
            'success' => true,
            'message' => "🎭 {$targetPlayer['name']} رو انتخاب کردی! وقتی مرد، نقشش رو می‌گیری!",
            'target' => $target
        ];
    }
    
    public function onPlayerDeath($deadPlayer) {
        if ($deadPlayer['id'] == $this->target && !$this->transformed) {
            $this->transformToRole($deadPlayer['role']);
        }
    }
    
    private function transformToRole($newRole) {
        $this->transformed = true;
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $oldRole = $p['role'];
                $p['role'] = $newRole;
                $p['role_data']['was_doppelganger'] = true;
                $p['role_data']['original_role'] = $oldRole;
                break;
            }
        }
        
        saveGame($this->game);
        
        $roleObj = $this->getRoleObject($newRole);
        $roleName = $roleObj ? $roleObj->getName() : $newRole;
        
        $this->sendMessage(
            "🎭 {$this->getPlayerById($this->target)['name']} مرد و الان تو نقشش رو گرفتی! الان تو یه {$roleName} هستی!"
        );
        
        // اگه بنا باشه، بناهای دیگه رو بشناسه
        if ($newRole == 'builder') {
            $this->notifyMasons();
        }
        
        // اگه فرقه‌گرا باشه، تیم فرقه رو بشناسه
        if ($newRole == 'cultist') {
            $this->notifyCult();
        }
    }
    
    private function notifyMasons() {
        $masons = [];
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'builder' && $p['id'] != $this->player['id'] && ($p['alive'] ?? false)) {
                $masons[] = $p['name'];
                sendPrivateMessage($p['id'], 
                    "👷🏻‍♂️ همزاد {$this->player['name']} تبدیل به بنا شد!"
                );
            }
        }
        
        if (!empty($masons)) {
            $this->sendMessage("بناهای دیگه: " . implode(', ', $masons));
        }
    }
    
    private function notifyCult() {
        $cult = [];
        foreach ($this->game['players'] as $p) {
            if (($p['role'] == 'cultist' || $p['role'] == 'cult_leader') && 
                $p['id'] != $this->player['id'] && 
                ($p['alive'] ?? false)) {
                $cult[] = $p['name'];
            }
        }
        
        if (!empty($cult)) {
            $this->sendMessage("اعضای فرقه: " . implode(', ', $cult));
        }
    }
    
    public function checkWinCondition() {
        // اگه تا آخر بازی تبدیل نشده، بازنده
        if (!$this->transformed) {
            return [
                'won' => false,
                'message' => "🎭 همزاد تبدیل نشد و بازنده شد!"
            ];
        }
        return null; // تیم جدید تصمیم می‌گیره
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->target === null) {
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
        return [];
    }
}