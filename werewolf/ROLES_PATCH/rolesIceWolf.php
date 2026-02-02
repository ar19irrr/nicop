<?php
/**
 * ☃️🐺 گرگ برفی (IceWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class IceWolf extends Role {
    
    protected $frozenPlayers = [];    // بازیکنان منجمد شده
    protected $frozenLastNight = [];  // کسانی که دیشب منجمد شدن
    
    public function getName() {
        return 'گرگ برفی';
    }
    
    public function getEmoji() {
        return '☃️🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ برفی ☃️🐺 هستی! در کوهستانی نزدیک به روستا زندگی می‌کنی. می‌تونی هر شب یک نفر رو انتخاب کنی و منجمدش کنی. کسی که منجمد شده نمی‌تونه فعالیتی داشته باشه! ولی شب بعدش نمی‌تونی دوباره منجمدش کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای چه کسی رو منجمد کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی آیا دیشب منجمد شده
        if (in_array($target, $this->frozenLastNight)) {
            return [
                'success' => false,
                'message' => "⚠️ نتونستی {$targetPlayer['name']} رو منجمد کنی! چون دیشب منجمدش کردی!"
            ];
        }
        
        // بررسی فرشته نگهبان
        if ($this->isGuardedByAngel($target)) {
            return [
                'success' => false,
                'message' => "🛡️ ظاهراً نتونستی {$targetPlayer['name']} رو منجمد کنی! یه چیزی داشت ازش محافظت می‌کرد!"
            ];
        }
        
        $this->frozenPlayers[$target] = $this->getCurrentNight();
        $this->frozenLastNight[] = $target;
        
        // پیام به هدف
        $this->sendMessageToPlayer($target, "❄️ دیشب گرگ برفی ☃️🐺 منجمدت کرد! نمی‌تونی تا فردا فعالیتی داشته باشی!");
        
        return [
            'success' => true,
            'message' => "❄️ با موفقیت تونستی {$targetPlayer['name']} رو منجمد کنی!",
            'frozen' => $target
        ];
    }
    
    public function onNightEnd() {
        // پاک کردن لیست دیشب
        $this->frozenLastNight = [];
    }
    
    public function isFrozen($playerId) {
        return isset($this->frozenPlayers[$playerId]) && 
               $this->frozenPlayers[$playerId] == $this->getCurrentNight();
    }
    
    private function isGuardedByAngel($playerId) {
        // بررسی توسط گیم انجین
        return false;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolfTeam($p['role'])) {
                continue;
            }
            // نمی‌تونه کسی که الان منجمده رو دوباره منجمد کنه
            if (in_array($p['id'], $this->frozenLastNight)) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'ice_wolf_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}