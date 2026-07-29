<?php
/**
 * 👩🏻‍🎤 هارلی کویین (Harly)
 * تیم: جوکر (Joker Team)
 */

require_once __DIR__ . '/base.php';

class Harly extends Role {
    
    public function getName() {
        return 'هارلی کویین';
    }
    
    public function getEmoji() {
        return '👩🏻‍🎤';
    }
    
    public function getTeam() {
        return 'joker';
    }
    
    public function getDescription() {
        $jokerDead = $this->getData('joker_dead') ?? false;
        $scrolls = $this->getData('scrolls_found') ?? 0;
        
        if ($jokerDead) {
            return "👩🏻‍🎤 جوکر مرد! تو باید کارش رو ادامه بدی. {$scrolls} کتیبه پیدا کردی.";
        }
        return "👩🏻‍🎤 تو هارلی کویین هستی! هر ۳ شب یه کتیبه می‌سازی. از جوکر محافظت می‌کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $jokerDead = $this->getData('joker_dead') ?? false;
        
        if ($jokerDead) {
            return $this->searchForScroll($target);
        }
        
        $currentNight = $this->getCurrentNight();
        $lastCraft = $this->getData('last_craft_night') ?? 0;
        
        // ساخت کتیبه هر ۳ شب
        if (($currentNight - $lastCraft) >= 3) {
            $this->setData('last_craft_night', $currentNight);
            $scrolls = ($this->getData('scrolls_found') ?? 0) + 1;
            $this->setData('scrolls_found', $scrolls);
            
            $jokerId = $this->getData('joker_id');
            if ($jokerId) {
                $this->sendMessageToPlayer($jokerId, "📜 هارلی یه کتیبه ساخت! ({$scrolls} کتیبه)");
            }
            
            return [
                'success' => true,
                'message' => "🔬 کتیبه جدید ساختی! ({$scrolls})",
                'action' => 'craft_scroll'
            ];
        }
        
        return [
            'success' => true,
            'message' => "🛡️ امشب مراقب جوکر بودی...",
            'action' => 'guard'
        ];
    }
    
    private function searchForScroll($target) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یه نفر رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if (rand(1, 100) <= 33) {
            $scrolls = ($this->getData('scrolls_found') ?? 0) + 1;
            $this->setData('scrolls_found', $scrolls);
            
            return [
                'success' => true,
                'message' => "📜 کتیبه‌ای توی خونه {$targetPlayer['name']} پیدا کردی! ({$scrolls})",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 توی خونه {$targetPlayer['name']} چیزی پیدا نکردی!",
            'found' => false
        ];
    }
    
    public function onJokerDeath() {
        $this->setData('joker_dead', true);
        $this->sendMessage("💔 جوکر مرد! باید کارش رو ادامه بدی!");
    }
    
    public function setJokerId($id) {
        $this->setData('joker_id', $id);
    }
    
    public function getValidTargets($phase = 'night') {
        $jokerDead = $this->getData('joker_dead') ?? false;
        
        if (!$jokerDead) {
            return []; // فقط محافظت می‌کنه، هدف انتخاب نمی‌کنه
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'harly_' . $p['id']
            ];
        }
        return $targets;
    }
}