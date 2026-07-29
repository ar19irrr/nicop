<?php
/**
 * 🔥🤴🏻 پادشاه آتش (FireKing)
 * تیم: آتش و یخ
 */

require_once __DIR__ . '/base.php';

class FireKing extends Role {
    
    public function getName() {
        return 'پادشاه آتش';
    }
    
    public function getEmoji() {
        return '🔥🤴🏻';
    }
    
    public function getTeam() {
        return 'fire_ice';
    }
    
    public function getDescription() {
        $iceQueen = $this->getIceQueenName();
        $oiledHouses = $this->getData('oiled_houses') ?? [];
        
        return "🔥🤴🏻 تو پادشاه آتش هستی! هر شب به خونه‌ی یک نفر نفت می‌پاشی. هر وقت خواستی می‌تونی همه خونه‌های نفتی رو به آتش بکشی! (قربانیان: " . count($oiledHouses) . " خونه نفتی)" . $iceQueen;
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null, $action = 'oil') {
        if ($action == 'detonate') {
            return $this->detonate();
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ خونه کی رو نفت بپاشم؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $oiledHouses = $this->getData('oiled_houses') ?? [];
        
        if (in_array($target, $oiledHouses)) {
            return [
                'success' => false,
                'message' => '⛔ قبلاً به این خونه نفت پاشیدی!'
            ];
        }
        
        $oiledHouses[] = $target;
        $this->setData('oiled_houses', $oiledHouses);
        
        return [
            'success' => true,
            'message' => "🔥 خونه {$targetPlayer['name']} نفت پاشی شد! (" . count($oiledHouses) . " خونه)",
            'oiled_count' => count($oiledHouses)
        ];
    }
    
    private function detonate() {
        $oiledHouses = $this->getData('oiled_houses') ?? [];
        
        if (empty($oiledHouses)) {
            return [
                'success' => false,
                'message' => '❌ هیچ خونه نفتی وجود نداره!'
            ];
        }
        
        $this->setData('detonated', true);
        $killed = [];
        
        foreach ($oiledHouses as $houseId) {
            $player = $this->getPlayerById($houseId);
            if ($player && $player['alive']) {
                $this->killPlayer($houseId, 'fire');
                $killed[] = $player['name'];
            }
        }
        
        $this->sendMessageToGroup("💥 پادشاه آتش همه خونه‌های نفتی رو آتش زد! قربانیان: " . implode(', ', $killed));
        
        return [
            'success' => true,
            'message' => "💥 همه خونه‌ها آتش گرفتن!",
            'killed' => $killed
        ];
    }
    
    private function getIceQueenName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'ice_queen' && isset($p['alive']) && $p['alive'] === true) {
                return "\n👸🏻 ملکه یخی: {$p['name']} (هم‌تیمی)";
            }
        }
        return '';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        $oiledHouses = $this->getData('oiled_houses') ?? [];
        $detonated = $this->getData('detonated') ?? false;
        
        if (!empty($oiledHouses) && !$detonated) {
            $targets[] = [
                'id' => 'detonate',
                'name' => '💥 آتش زدن همه',
                'callback' => 'fireking_detonate'
            ];
        }
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (!in_array($p['id'], $oiledHouses)) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => '🔥 ' . $p['name'],
                    'callback' => 'fireking_oil_' . $p['id']
                ];
            }
        }
        
        return $targets;
    }
}