<?php
/**
 * 🏹 کماندار
 */

require_once __DIR__ . '/base.php';

class Archer extends Role {
    
    public function getName() {
        return 'کماندار';
    }
    
    public function getEmoji() {
        return '🏹';
    }
    
    public function getTeam() {
        return 'killer';
    }
    
    public function getDescription() {
        $killer = $this->getKillerName();
        return "تو کماندار 🏹 هستی، یار قاتل هستی و در ابتدا بازی بهم دیگه معرفی میشید. توانایی اینو داری که هر دو شب یکبار از کمانت استفاده کنی و یک نفر رو با تیر مورد هدف قرار بدی و جانش رو بگیری. قاتل کسی نیست جز: $killer";
    }
    
    public function hasNightAction() {
        $lastShot = $this->getData('last_shot_night') ?? 0;
        $night = $this->game['night_count'] ?? 1;
        return ($night - $lastShot) >= 2;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو برای شلیک انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('last_shot_night', $this->game['night_count'] ?? 1);
        $this->logAction('shoot', $target);
        
        $this->killPlayer($target, 'archer');
        
        return [
            'success' => true,
            'message' => "🏹 تیرت رو به سمت {$targetPlayer['name']} پرتاب کردی و به قلبش اصابت کرد!",
            'killed' => true,
            'target' => $target
        ];
    }
    
    private function getKillerName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'serial_killer' && isset($p['alive']) && $p['alive'] === true) {
                return $p['name'];
            }
        }
        return '❓ (مرده)';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'archer_' . $p['id']
            ];
        }
        return $targets;
    }
}