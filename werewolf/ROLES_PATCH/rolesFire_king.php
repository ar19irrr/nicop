<?php
/**
 * 🔥🤴🏻 پادشاه آتش
 */

require_once __DIR__ . '/base.php';

class FireKing extends Role {
    
    private $oiledHouses = [];
    private $detonated = false;
    
    public function getName() {
        return 'پادشاه آتش';
    }
    
    public function getEmoji() {
        return '🔥🤴🏻';
    }
    
    public function getTeam() {
        return 'fire_ice'; // تیم آتش و یخ
    }
    
    public function getDescription() {
        $iceQueen = $this->getIceQueenName();
        return "تو پادشاه آتش 🔥🤴🏻 هستی. هر شب ممکنه به نوچه هات دستور بدی که برن به خونه‌ی یک نفر نفت بپاشن و هر زمان که دوس داشتی میتونی دستور بدی تمام خونه های نفتی رو به آتش بکشن (هر بازدید کننده ای هم که باشه میمیره). $iceQueen";
    }
    
    public function hasNightAction() {
        return !$this->detonated;
    }
    
    public function performNightAction($target = null, $action = 'oil') {
        if ($action == 'detonate') {
            return $this->detonate();
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک خونه رو برای نفت پاشی انتخاب کنی!'
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
        
        if (in_array($target, $this->oiledHouses)) {
            return [
                'success' => false,
                'message' => '⛔ قبلاً به این خونه نفت پاشیدی!'
            ];
        }
        
        $this->oiledHouses[] = $target;
        $this->setData('oiled_houses', $this->oiledHouses);
        
        return [
            'success' => true,
            'message' => "🔥 نوچه ها با موفقیت خونه {$targetPlayer['name']} رو نفت پاشی کردن!",
            'oiled_count' => count($this->oiledHouses)
        ];
    }
    
    private function detonate() {
        if (empty($this->oiledHouses)) {
            return [
                'success' => false,
                'message' => '❌ هیچ خونه نفتی برای آتش زدن وجود نداره!'
            ];
        }
        
        $this->detonated = true;
        $killed = [];
        
        foreach ($this->oiledHouses as $houseId) {
            $player = $this->getPlayerById($houseId);
            if ($player && $player['alive']) {
                // چک کردن محافظت فرشته
                if ($this->isProtectedByAngel($houseId)) {
                    $this->notifyAngelSaved($houseId);
                    continue;
                }
                
                $this->game = killPlayer($this->game, $houseId, 'fire');
                $killed[] = $player['name'];
                
                // کشتن بازدیدکننده‌ها هم (اگر کسی اون شب اونجا بوده)
                $this->killVisitors($houseId);
            }
        }
        
        saveGame($this->game);
        
        return [
            'success' => true,
            'message' => "💥 همه خونه های نفتی منفجر شدن! قربانیان: " . implode(', ', $killed),
            'killed' => $killed,
            'detonated' => true
        ];
    }
    
    private function killVisitors($houseId) {
        // این متد باید چک کنه چه کسایی اون شب رفتن اون خونه
        // و اونا رو هم بکشه
        $visitors = $this->getVisitorsToHouse($houseId);
        foreach ($visitors as $visitorId) {
            if ($visitorId != $this->player['id']) {
                $this->game = killPlayer($this->game, $visitorId, 'fire');
            }
        }
    }
    
    private function isProtectedByAngel($playerId) {
        // چک کردن آیا فرشته از این بازیکن محافظت میکنه
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'guardian_angel' && 
                ($p['role_data']['protected'] ?? null) == $playerId) {
                return true;
            }
        }
        return false;
    }
    
    private function notifyAngelSaved($playerId) {
        $player = $this->getPlayerById($playerId);
        sendPrivateMessage($playerId, 
            "با حس گرما و نبود اکسیژن از خواب میپری! همه جارو آتیش 🔥گرفته! ولی فرشته نگهبان نجاتت داد و سریع ترو از خونه برد بیرون."
        );
    }
    
    private function getIceQueenName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'ice_queen' && ($p['alive'] ?? false)) {
                return "ملکه یخی ❄️👸🏻 یعنی {$p['name']} هم تیمیته.";
            }
        }
        return '';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        
        // دکمه انفجار
        if (!empty($this->oiledHouses)) {
            $targets[] = [
                'id' => 'detonate',
                'name' => '💥 دستور آتش زدن',
                'callback' => 'fireking_detonate'
            ];
        }
        
        // لیست خونه‌ها برای نفت پاشی
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (!in_array($p['id'], $this->oiledHouses)) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'fireking_oil_' . $p['id']
                ];
            }
        }
        
        return $targets;
    }
}