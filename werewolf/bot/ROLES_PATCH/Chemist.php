<?php
/**
 * 👨‍🔬 شیمیدان (Chemist)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Chemist extends Role {
    
    public function getName() {
        return 'شیمیدان';
    }
    
    public function getEmoji() {
        return '👨‍🔬';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو شیمیدان 👨‍🔬 هستی! هر شب می‌تونی یه نفر رو مجبور کنی بین دو معجون یکی رو انتخاب کنه. اگر سم رو بخوره می‌میره، اگر خنثی رو بخوره تو می‌میری!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی رو می‌خوای مهمون کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // قاتل = مرگ شیمیدان
        if ($targetPlayer['role'] == 'serial_killer') {
            $this->killPlayer($this->getId(), 'serial_killer');
            return [
                'success' => false,
                'message' => "💀 رفتی {$targetPlayer['name']} رو مهمون کنی ولی اون قاتل بود! کشتی!",
                'died' => true
            ];
        }
        
        // 50% شانس انتخاب سم یا خنثی
        if (rand(1, 100) <= 50) {
            $this->killPlayer($target, 'chemist');
            return [
                'success' => true,
                'message' => "☠️ {$targetPlayer['name']} سم رو انتخاب کرد و مرد!",
                'killed' => $target
            ];
        } else {
            $this->killPlayer($this->getId(), 'chemist_poison');
            $this->sendMessageToGroup("☠️ شیمیدان {$this->getPlayerName()} با سم خودش مرد!");
            return [
                'success' => false,
                'message' => "🧪 {$targetPlayer['name']} معجون خنثی رو انتخاب کرد و تو مردی!",
                'died' => true
            ];
        }
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'chemist_' . $p['id']
            ];
        }
        return $targets;
    }
}