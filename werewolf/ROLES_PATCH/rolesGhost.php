<?php
/**
 * 👻 روح (Ghost)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Ghost extends Role {
    
    protected $isHidden = true;       // آیا مخفی است؟
    protected $discovered = false;    // آیا پیدا شده؟
    
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
        return "تو روح 👻 هستی! مثل یه روح واقعی توی روستا هستی و می‌تونی از نقش بقیه با خبر بشی. تا وقتی پیدا نشدی قابلیت داری، وقتی پیدا شدی ظاهر می‌شی و دیگه قابلیتی نداری!";
    }
    
    public function hasNightAction() {
        return $this->isHidden;
    }
    
    public function performNightAction($target = null) {
        if (!$this->isHidden) {
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
        
        // دیدن نقش
        $roleName = $this->getRoleDisplayName($targetPlayer['role']);
        
        // احتمال پیدا شدن (مثلاً ۱۰٪)
        $rand = rand(1, 100);
        if ($rand <= 10) {
            $this->discover();
        }
        
        return [
            'success' => true,
            'message' => "👻 دیشب به عنوان روح به خونه {$targetPlayer['name']} رفتی و دیدی یه {$roleName} هست!",
            'seen_role' => $targetPlayer['role']
        ];
    }
    
    public function discover() {
        $this->isHidden = false;
        $this->discovered = true;
        $this->sendMessageToPlayer($this->getId(), "😱 اوه نه! پیدات کردن! دیگه روح 👻 نیستی و توی لیست پلیرها مشخصی!");
        $this->sendMessageToGroup("👻 خب باید بگم که {$this->getPlayerName()} دیگه روح 👻 نیست و پیداش کردن! از این به بعد قابلیتی نداره دیگه!");
    }
    
    public function isVisibleInList() {
        return !$this->isHidden;
    }
    
    private function getRoleDisplayName($role) {
        $names = [
            'seer' => '👳🏻‍♂️ پیشگو',
            'werewolf' => '🐺 گرگینه',
            'guardian_angel' => '👼🏻 فرشته نگهبان',
            'knight' => '🗡 شوالیه',
            'hunter' => '👮🏻‍♂️ کلانتر',
            'fool' => '🃏 احمق'
            // ... سایر نقش‌ها
        ];
        return $names[$role] ?? $role;
    }
    
    public function getValidTargets($phase = 'night') {
        if (!$this->isHidden) {
            return [];
        }
        
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