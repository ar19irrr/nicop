<?php
/**
 * 🧨 دینامیت (Dinamit)
 * تیم: مستقل
 */

require_once __DIR__ . '/base.php';

class Dinamit extends Role {
    
    public function getName() {
        return 'دینامیت';
    }
    
    public function getEmoji() {
        return '🧨';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        $elements = $this->getData('elements') ?? [];
        $allElements = ['timer' => 'تایمر', 'gunpowder' => 'باروت', 'chassis' => 'شاسی', 'wicks' => 'فیتیله'];
        $found = !empty($elements) ? implode(', ', array_map(function($e) use ($allElements) {
            return $allElements[$e] ?? $e;
        }, $elements)) : 'هیچ‌کدام';
        $remaining = array_diff(array_keys($allElements), $elements);
        
        return "تو دینامیت 🧨 هستی! باید ۴ عنصر پیدا کنی.\n\n✅ پیدا شده: {$found}\n❌ باقی‌مانده: " . (count($remaining) > 0 ? implode(', ', array_map(function($e) use ($allElements) {
            return $allElements[$e] ?? $e;
        }, $remaining)) : 'همه پیدا شد!');
    }
    
    public function hasNightAction() {
        $elements = $this->getData('elements') ?? [];
        return count($elements) < 4;
    }
    
    public function hasDayAction() {
        $elements = $this->getData('elements') ?? [];
        return count($elements) < 4;
    }
    
    public function performNightAction($target = null) {
        return $this->searchForElement($target);
    }
    
    public function performDayAction($target = null) {
        return $this->searchForElement($target);
    }
    
    private function searchForElement($target) {
        $elements = $this->getData('elements') ?? [];
        
        if (count($elements) >= 4) {
            return [
                'success' => false,
                'message' => '✅ همه عناصر رو پیدا کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ خونه کی رو می‌خوای بگردی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $searched = $this->getData('searched_houses') ?? [];
        if (in_array($target, $searched)) {
            return [
                'success' => false,
                'message' => "⚠️ قبلاً خونه {$targetPlayer['name']} رو گشتی!"
            ];
        }
        
        $searched[] = $target;
        $this->setData('searched_houses', $searched);
        
        $allElements = ['timer', 'gunpowder', 'chassis', 'wicks'];
        $remaining = array_diff($allElements, $elements);
        
        // ۳۰٪ شانس پیدا کردن
        if (rand(1, 100) <= 30 && !empty($remaining)) {
            $found = $remaining[array_rand($remaining)];
            $elements[] = $found;
            $this->setData('elements', $elements);
            
            $names = ['timer' => 'تایمر', 'gunpowder' => 'باروت', 'chassis' => 'شاسی', 'wicks' => 'فیتیله'];
            
            if (count($elements) >= 4) {
                $this->detonate();
                return [
                    'success' => true,
                    'message' => "🎉 عنصر {$names[$found]} رو پیدا کردی! همه عناصر کامل شد! 💥"
                ];
            }
            
            return [
                'success' => true,
                'message' => "🎉 عنصر {$names[$found]} رو پیدا کردی! (" . count($elements) . "/4)"
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 توی خونه {$targetPlayer['name']} چیزی پیدا نکردی!"
        ];
    }
    
    private function detonate() {
        $this->sendMessageToGroup("💥 دینامیت 🧨 عناصر رو پیدا کرد و روستا رو منفجر کرد!");
        
        foreach ($this->getAllPlayers() as $player) {
            if ($player['id'] != $this->playerId && isset($player['alive']) && $player['alive'] === true) {
                $this->killPlayer($player['id'], 'dinamit_bomb');
            }
        }
        
        $this->declareWinners(['independent']);
    }
    
    public function getValidTargets($phase = 'night') {
        $elements = $this->getData('elements') ?? [];
        if (count($elements) >= 4) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'dinamit_' . $p['id']
            ];
        }
        return $targets;
    }
}