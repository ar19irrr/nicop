<?php
/**
 * 🗡 شوالیه (Knight)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Knight extends Role {
    
    protected $lastVisitNight = 0;    // شب آخرین بازدید
    
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
        return "تو شوالیه 🗡 هستی! هر شب به خونه یکی از روستاییان می‌ری. اگر منفی باشه (گرگ، قاتل، کماندار، ومپایر، پادشاه آتش، ملکه یخ) می‌کشی‌ش، اگر روستایی باشه کاری بهش نداری. فرقه استثنا هست!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای خونه کی بری؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->lastVisitNight = $this->getCurrentNight();
        
        // بررسی آیا منفی است
        if ($this->isEvil($targetPlayer['role'])) {
            // کشتن
            $this->killPlayer($target, 'knight');
            
            return [
                'success' => true,
                'message' => "⚔️ دیشب رفتی خونه {$targetPlayer['name']} و دیدی که نقش منفی داره! قبل از اینکه بتونه کاری کنه سرشو با شمشیرت قطع کردی.",
                'killed' => $target
            ];
        }
        
        // روستایی بود
        return [
            'success' => true,
            'message' => "🏠 دیشب رفتی خونه {$targetPlayer['name']}، ولی اون یه روستایی بود و کاری باهاش نداشتی.",
            'killed' => false
        ];
    }
    
    private function isEvil($role) {
        $evilRoles = [
            'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
            'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf',
            'killer', 'archer', 'vampire', 'bloodthirsty', 'kent_vampire',
            'firefighter', 'ice_queen'
        ];
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