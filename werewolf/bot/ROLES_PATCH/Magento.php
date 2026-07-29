<?php
/**
 * 🧲 مگنیتو (Magento)
 * تیم: مگنیتو (Magento Team)
 */

require_once __DIR__ . '/base.php';

class Magento extends Role {
    
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
        return "🧲 تو مگنیتو هستی! هر شب می‌تونی یک نفر رو جذب کنی و مثل خودت تبدیلش کنی. اگر گرگ یا ومپایر باشه، می‌کشی‌ش ولی قدرتت رو از دست می‌دی!";
    }
    
    public function hasNightAction() {
        return !($this->getData('lost_power') ?? false);
    }
    
    public function performNightAction($target = null) {
        $lostPower = $this->getData('lost_power') ?? false;
        
        if ($lostPower) {
            return [
                'success' => false,
                'message' => '❌ قدرتت رو از دست دادی! فقط می‌تونی رای بدی.'
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
        
        // اگر گرگ یا ومپایر باشه، کشته میشه ولی قدرت از دست می‌ره
        if ($this->isWolfOrVampire($targetPlayer['role'])) {
            $this->killPlayer($target, 'magento');
            $this->setData('lost_power', true);
            $this->sendMessage("💀 قدرتت رو از دست دادی!");
            
            return [
                'success' => true,
                'message' => "⚡ {$targetPlayer['name']} گرگ/ومپایر بود! کشتیش ولی قدرتت رو از دست دادی!",
                'killed' => $target,
                'lost_power' => true
            ];
        }
        
        // تبدیل به مگنیتو
        $this->convertToMagento($target);
        
        return [
            'success' => true,
            'message' => "🧲 {$targetPlayer['name']} رو جذب کردی و تبدیل به مگنیتو کردی!",
            'converted' => $target
        ];
    }
    
    private function convertToMagento($playerId) {
        $this->setPlayerRole($playerId, 'magento');
        $this->sendMessageToPlayer($playerId, "🧲 تبدیل به مگنیتو شدی!");
    }
    
    private function isWolfOrVampire($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        $vampireRoles = ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'];
        return in_array($role, array_merge($wolfRoles, $vampireRoles));
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->getData('lost_power') ?? false) return [];
        
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