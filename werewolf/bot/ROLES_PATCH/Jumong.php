<?php
/**
 * 🏹⚔️ جومونگ (Jumong)
 * تیم: روستا (قبل از پیدا کردن ۳ نشان) / قاتل (بعد از پیدا کردن ۳ نشان)
 */

require_once __DIR__ . '/base.php';

class Jumong extends Role {
    
    public function getName() {
        return 'جومونگ';
    }
    
    public function getEmoji() {
        return '🏹⚔️';
    }
    
    public function getTeam() {
        $foundAll = $this->getData('found_all') ?? false;
        return $foundAll ? 'killer' : 'villager';
    }
    
    public function getDescription() {
        $badges = $this->getData('badges') ?? [];
        $foundAll = $this->getData('found_all') ?? false;
        
        if ($foundAll) {
            return "🏹⚔️ تو جومونگ هستی! هر سه نشان رو پیدا کردی و به تیم قاتل پیوستی!";
        }
        
        $badgeNames = ['جاورنگ', 'آرنگ', 'کمان دامول'];
        $found = count($badges);
        $remaining = 3 - $found;
        
        return "🏹⚔️ تو جومونگ هستی! باید ۳ نشان پیدا کنی. ({$found}/3 - {$remaining} نشان مونده)";
    }
    
    public function hasNightAction() {
        return !($this->getData('found_all') ?? false);
    }
    
    public function performNightAction($target = null) {
        $foundAll = $this->getData('found_all') ?? false;
        
        if ($foundAll) {
            return [
                'success' => false,
                'message' => '✅ همه نشان‌ها رو پیدا کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ خونه کی رو بگردی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $searched = $this->getData('searched') ?? [];
        if (in_array($target, $searched)) {
            return [
                'success' => false,
                'message' => "⚠️ قبلاً خونه {$targetPlayer['name']} رو گشتی!"
            ];
        }
        
        $searched[] = $target;
        $this->setData('searched', $searched);
        
        $badges = $this->getData('badges') ?? [];
        $allBadges = ['جاورنگ', 'آرنگ', 'کمان دامول'];
        $remaining = array_diff($allBadges, $badges);
        
        // ۳۳٪ شانس پیدا کردن
        if (!empty($remaining) && rand(1, 100) <= 33) {
            $found = $remaining[array_rand($remaining)];
            $badges[] = $found;
            $this->setData('badges', $badges);
            
            if (count($badges) >= 3) {
                $this->setData('found_all', true);
                $this->switchToKillerTeam();
                
                return [
                    'success' => true,
                    'message' => "🎉 نشان آخر ($found) رو پیدا کردی! به تیم قاتل پیوستی!",
                    'all_found' => true
                ];
            }
            
            return [
                'success' => true,
                'message' => "✨ نشان $found رو پیدا کردی! (" . count($badges) . "/3)",
                'found_badge' => $found
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 توی خونه {$targetPlayer['name']} چیزی پیدا نکردی!",
            'found_badge' => null
        ];
    }
    
    private function switchToKillerTeam() {
        $this->sendMessage("🎉 به تیم قاتل پیوستی!");
        $this->notifyKillerTeam("🏹⚔️ جومونگ به تیم ما پیوست!");
    }
    
    private function notifyKillerTeam($message) {
        foreach ($this->game['players'] as $p) {
            if (in_array($p['role'], ['serial_killer', 'archer', 'davina']) && isset($p['alive']) && $p['alive'] === true) {
                $this->sendMessageToPlayer($p['id'], $message);
            }
        }
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        // قاتل و کماندار نمی‌تونن جومونگ رو بکشن
        if (in_array($attackerRole, ['serial_killer', 'archer'])) {
            $this->sendMessageToPlayer($attackerId, "🏹⚔️ جومونگه! نکشتیش!");
            return ['died' => false, 'spared' => true];
        }
        return ['died' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $foundAll = $this->getData('found_all') ?? false;
        if ($foundAll) return [];
        
        $searched = $this->getData('searched') ?? [];
        $targets = [];
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (in_array($p['id'], $searched)) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'jumong_' . $p['id']
            ];
        }
        return $targets;
    }
}