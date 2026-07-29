<?php
/**
 * 🐍👩🏻‍🦳 لیلیث (Lilis)
 * تیم: آتش و یخ (Fire & Ice)
 */

require_once __DIR__ . '/base.php';

class Lilis extends Role {
    
    public function getName() {
        return 'لیلیث';
    }
    
    public function getEmoji() {
        return '🐍👩🏻‍🦳';
    }
    
    public function getTeam() {
        return 'fire_ice';
    }
    
    public function getDescription() {
        $foundLucifer = $this->getData('found_lucifer') ?? false;
        
        if (!$foundLucifer) {
            return "🐍👩🏻‍🦳 تو لیلیث هستی! هر شب به دنبال لوسیفر 👹 می‌گردی تا انتقام خیانتش رو بگیری!";
        }
        return "🐍👩🏻‍🦳 لوسیفر رو کشتی! حالا با چشمان جادوییت هر شب یک نفر رو خشک می‌کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $foundLucifer = $this->getData('found_lucifer') ?? false;
        
        if (!$target) {
            $msg = $foundLucifer ? "امشب کی رو خشک کنی؟" : "امشب به دنبال لوسیفر؟";
            return [
                'success' => false,
                'message' => "❌ $msg"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // جستجوی لوسیفر
        if (!$foundLucifer) {
            if ($targetPlayer['role'] == 'lucifer') {
                $this->setData('found_lucifer', true);
                $this->killPlayer($target, 'lilis');
                $this->sendMessageToGroup("😱 لوسیفر 👹 توسط لیلیث کشته شد!");
                
                return [
                    'success' => true,
                    'message' => "🗡️ لوسیفر رو پیدا کردی و کشتیش!",
                    'killed' => $target
                ];
            }
            
            return [
                'success' => true,
                'message' => "🔍 {$targetPlayer['name']} لوسیفر نبود!",
                'found' => false
            ];
        }
        
        // بعد از پیدا کردن لوسیفر - خشک کردن
        $this->killPlayer($target, 'lilis');
        
        return [
            'success' => true,
            'message' => "🐍 {$targetPlayer['name']} رو خشک کردی!",
            'killed' => $target
        ];
    }
    
    public function onParentsDeath() {
        $this->sendMessage("🔥❄️ پادشاه آتش و ملکه یخ مردن! از امشب می‌تونی هر شب یک نفر رو خشک کنی!");
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'lilis_' . $p['id']
            ];
        }
        return $targets;
    }
}