<?php
/**
 * 🏹 کماندار
 */

require_once __DIR__ . '/base.php';

class Archer extends Role {
    
    private $lastShotNight = 0;
    
    public function getName() {
        return 'کماندار';
    }
    
    public function getEmoji() {
        return '🏹';
    }
    
    public function getTeam() {
        return 'killer'; // تیم قاتل
    }
    
    public function getDescription() {
        $killer = $this->getKillerName();
        return "تو کماندار 🏹 هستی، یار قاتل هستی و در ابتدا بازی بهم دیگه معرفی میشید. توانایی اینو داری که هر دو شب یکبار از کمانت استفاده کنی و یک نفر رو با تیر مورد هدف قرار بدی و جانش رو بگیری. قاتل کسی نیست جز: $killer";
    }
    
    public function hasNightAction() {
        $night = $this->game['night_count'] ?? 1;
        return ($night - $this->lastShotNight) >= 2;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو برای شلیک انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer) {
            return [
                'success' => false,
                'message' => '❌ بازیکن یافت نشد!'
            ];
        }
        
        if (!$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ این بازیکن مرده!'
            ];
        }
        
        $this->lastShotNight = $this->game['night_count'] ?? 1;
        $this->logAction('shoot', $target);
        
        // کشتن هدف
        $this->game = killPlayer($this->game, $target, 'archer');
        saveGame($this->game);
        
        return [
            'success' => true,
            'message' => "🏹 تیرت رو به سمت {$targetPlayer['name']} پرتاب کردی و به قلبش اصابت کرد!",
            'killed' => true,
            'target' => $target
        ];
    }
    
    private function getKillerName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'serial_killer' && ($p['alive'] ?? false)) {
                return $p['name'];
            }
        }
        return '❓';
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