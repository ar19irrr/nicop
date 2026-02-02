<?php
/**
 * 🧨 دینامیت (Dinamit)
 * تیم: مستقل (Independent)
 */

require_once __DIR__ . '/base.php';

class Dinamit extends Role {
    
    protected $elements = [];         // عناصر پیدا شده
    protected $elementsNeeded = [      // عناصر مورد نیاز
        'timer' => 'تایمر',
        'gunpowder' => 'باروت',
        'chassis' => 'شاسی',
        'wicks' => 'فیتیله'
    ];
    
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
        $found = implode(', ', $this->elements);
        $needed = implode(', ', array_diff(array_keys($this->elementsNeeded), $this->elements));
        
        return "تو دینامیت 🧨 هستی! باید ۴ عنصر پیدا کنی: باروت، تایمر، شاسی، فیتیله. عناصر پیدا شده: {$found} | باقی‌مانده: {$needed}";
    }
    
    public function hasNightAction() {
        return count($this->elements) < 4;
    }
    
    public function hasDayAction() {
        return count($this->elements) < 4;
    }
    
    public function performNightAction($target = null) {
        return $this->searchForElement($target, 'night');
    }
    
    public function performDayAction($target = null) {
        return $this->searchForElement($target, 'day');
    }
    
    private function searchForElement($target, $time) {
        if (count($this->elements) >= 4) {
            return [
                'success' => false,
                'message' => '✅ همه عناصر رو پیدا کردی!'
            ];
        }
        
        if (!$target) {
            $timeText = $time == 'night' ? 'امشب' : 'امروز';
            $foundList = empty($this->elements) ? 'هنوز چیزی پیدا نکردی!' : 'پیدا شده: ' . implode(', ', $this->elements);
            return [
                'success' => false,
                'message' => "❌ {$timeText} می‌خوای خونه کی رو برای ساخت بمب بگردی؟\n\n{$foundList}"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی آیا قبلاً از این خونه گشته
        if (in_array($target, $this->searchedHouses)) {
            return [
                'success' => false,
                'message' => "⚠️ قبلاً از خونه {$targetPlayer['name']} گشتی و یه عنصر پیدا کردی! دیگه اینجا چیزی نیست."
            ];
        }
        
        $this->searchedHouses[] = $target;
        
        // پیدا کردن عنصر
        $remaining = array_diff(array_keys($this->elementsNeeded), $this->elements);
        if (empty($remaining)) {
            return [
                'success' => true,
                'message' => "✅ همه عناصر رو داری!"
            ];
        }
        
        // ۲۵٪ شانس پیدا کردن عنصر
        $rand = rand(1, 100);
        if ($rand <= 25) {
            $found = array_rand(array_flip($remaining));
            $this->elements[] = $found;
            
            // بررسی آیا همه عناصر پیدا شده
            if (count($this->elements) >= 4) {
                $this->detonate();
            }
            
            return [
                'success' => true,
                'message' => "🎉 خوب! تونستی عنصر {$this->elementsNeeded[$found]} رو از خونه {$targetPlayer['name']} پیدا کنی! تبریک می‌گم :)",
                'element' => $found
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 خب ظاهراً چیزی اینجا نبود! پس نگرد دیگه اینجا رو.",
            'found' => false
        ];
    }
    
    private function detonate() {
        $this->sendMessageToGroup("💥 خب باید بگم که دینامیت 🧨 عناصرش رو برای ساخت بمب پیدا کرد و روستا رفت روی هوا!");
        
        // کشتن همه
        $players = $this->getAllPlayers();
        foreach ($players as $player) {
            if ($player['id'] != $this->getId() && $player['alive']) {
                $this->killPlayer($player['id'], 'dinamit_bomb');
            }
        }
        
        $this->declareWinners(['independent']);
    }
    
    protected $searchedHouses = [];
    
    public function getValidTargets($phase = 'night') {
        if (count($this->elements) >= 4) {
            return [];
        }
        
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