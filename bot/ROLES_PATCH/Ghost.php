<?php
/**
 * 👻 روح (Ghost)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Ghost extends Role {
    
    public function getName() {
        return 'روح';
    }
    
    public function getEmoji() {
        return '👻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $isHidden = $this->getData('is_hidden') ?? true;
        
        if ($isHidden) {
            return "👻 تو روح هستی! می‌تونی شب‌ها نقش بقیه رو ببینی. ۱۰٪ شانس پیدا شدن داری!";
        }
        return "👻 پیدا شدی! دیگه قابلیتی نداری!";
    }
    
    public function hasNightAction() {
        return $this->getData('is_hidden') ?? true;
    }
    
    public function performNightAction($target = null) {
        $isHidden = $this->getData('is_hidden') ?? true;
        
        if (!$isHidden) {
            return [
                'success' => false,
                'message' => '❌ دیگه پیدا شدی و قابلیتی نداری!'
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
        
        $roleName = getRoleDisplayName($targetPlayer['role']);
        
        // ۱۰٪ شانس پیدا شدن
        if (rand(1, 100) <= 10) {
            $this->discover();
        }
        
        return [
            'success' => true,
            'message' => "👻 {$targetPlayer['name']} یک {$roleName} هست!",
            'seen_role' => $targetPlayer['role']
        ];
    }
    
    private function discover() {
        $this->setData('is_hidden', false);
        $this->sendMessage("😱 پیدات کردن! دیگه روح نیستی!");
        $this->sendMessageToGroup("👻 {$this->getPlayerName()} دیگه روح نیست و پیدا شد!");
    }
    
    public function getValidTargets($phase = 'night') {
        $isHidden = $this->getData('is_hidden') ?? true;
        
        if (!$isHidden) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'ghost_' . $p['id']
            ];
        }
        return $targets;
    }
}