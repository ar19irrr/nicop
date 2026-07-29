<?php
/**
 * 🗡 شوالیه (Knight)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Knight extends Role {
    
    public function getName() {
        return 'شوالیه';
    }
    
    public function getEmoji() {
        return '🗡';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "🗡 تو شوالیه هستی! هر شب به خونه یکی می‌ری. اگر منفی باشه (گرگ، قاتل، ومپایر، ...) می‌کشی‌ش! اگر روستایی باشه کاری نداره. فرقه استثناست!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب خونه کی می‌خوای بری؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('last_visit_night', $this->getCurrentNight());
        
        if ($this->isEvil($targetPlayer['role'])) {
            $this->killPlayer($target, 'knight');
            $this->sendMessageToGroup("🗡 شوالیه {$targetPlayer['name']} رو به عنوان دشمن کشت!");
            
            return [
                'success' => true,
                'message' => "⚔️ {$targetPlayer['name']} منفی بود و کشتیش!",
                'killed' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🏠 {$targetPlayer['name']} روستایی بود و کاری نداشتی.",
            'killed' => false
        ];
    }
    
    private function isEvil($role) {
        $evilRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'serial_killer', 'archer',
                      'vampire', 'bloodthirsty', 'kent_vampire', 'fire_king', 'ice_queen'];
        return in_array($role, $evilRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'knight_' . $p['id']
            ];
        }
        return $targets;
    }
}