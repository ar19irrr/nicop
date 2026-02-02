<?php
/**
 * 🧲 مگنیتو (Magento)
 * تیم: مگنیتو (Magento Team)
 */

require_once __DIR__ . '/base.php';

class Magento extends Role {
    
    protected $teamMembers = [];      // اعضای تیم مگنیتو
    protected $attractedPlayers = []; // بازیکنان جذب شده
    
    public function getName() {
        return 'مگنیتو';
    }
    
    public function getEmoji() {
        return '🧲';
    }
    
    public function getTeam() {
        return 'magento';
    }
    
    public function getDescription() {
        return "تو مگنیتو 🧲 هستی! مثل آهنربا می‌تونی یکی رو انتخاب کنی و مثل خودت تبدیلش کنی یا بکشیش. با پادشاه آتش 🔥 و ملکه یخی ❄️ هم‌تیمی هستی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
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
        
        // اگر گرگ یا ومپایر باشه، فقط می‌تونه رای بده
        if ($this->isWolfOrVampire($targetPlayer['role'])) {
            // کشتن
            $this->killPlayer($target, 'magento');
            $this->losePower();
            
            return [
                'success' => true,
                'message' => "⚡ به {$targetPlayer['name']} حمله کردی ولی چون گرگ/ومپایر بود، قدرتت رو از دست دادی! فقط می‌تونی رای بدی.",
                'killed' => $target,
                'lost_power' => true
            ];
        }
        
        // تبدیل به مگنیتو
        $this->attractedPlayers[] = $target;
        $this->convertToMagento($target);
        
        return [
            'success' => true,
            'message' => "🧲 با موفقیت {$targetPlayer['name']} رو جذب کردی و تبدیل به مگنیتو کردی!",
            'converted' => $target
        ];
    }
    
    private function convertToMagento($playerId) {
        $this->setPlayerRole($playerId, 'magento');
        $this->sendMessageToPlayer($playerId, "🧲 دیشب احساس کردی داری جذب جایی می‌شی... بله مگنیتوها ت رو جذب کردن! الان دیگه یه مگنیتو هستی!");
    }
    
    private function losePower() {
        // از دست دادن قدرت شب
        $this->hasNightAction = false;
    }
    
    private function isWolfOrVampire($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        $vampireRoles = ['vampire', 'bloodthirsty', 'kent_vampire'];
        return in_array($role, array_merge($wolfRoles, $vampireRoles));
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'magento_' . $p['id']
            ];
        }
        return $targets;
    }
}