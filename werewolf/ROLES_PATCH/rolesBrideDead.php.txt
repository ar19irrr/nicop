<?php
/**
 * 👰‍♀☠️ عروس مردگان (BrideDead)
 * تیم: شوالیه تاریکی (Black Knight Team)
 */

require_once __DIR__ . '/base.php';

class BrideDead extends Role {
    
    protected $blackKnightId = null;  // آیدی شوالیه تاریکی
    protected $isVisible = false;     // آیا قابل مشاهده شده؟
    
    public function getName() {
        return 'عروس مردگان';
    }
    
    public function getEmoji() {
        return '👰‍♀☠️';
    }
    
    public function getTeam() {
        return 'black_knight';
    }
    
    public function getDescription() {
        $knightName = $this->getBlackKnightName();
        return "تو عروس مردگان 👰‍♀☠️ هستی، عروس شوالیه تاریکی {$knightName}. از دید همگان مخفی هستی - نه می‌تونن بهت رای بدن، نه شلیکت کنن، نه بکشتنت! هر شب می‌تونی یکی رو بکشی. اگر شوالیه تاریکی بمیره، تو هم می‌میری!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی تا بکشی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // کشتن هدف
        $this->killPlayer($target, 'bride_dead');
        
        return [
            'success' => true,
            'message' => "💀 امشب به سراغ {$targetPlayer['name']} رفتی و به طرز فجیعی کشتش!",
            'killed' => $target
        ];
    }
    
    public function onBlackKnightDeath() {
        // وقتی شوالیه تاریکی می‌میره، عروس مردگان هم می‌میره
        $this->killPlayer($this->getId(), 'bride_dead_suicide');
        
        return [
            'message' => "💔 چون شوالیه تاریکی مرد، عروس مردگان هم از غمش مرد!"
        ];
    }
    
    public function isVisibleInList() {
        return false; // در لیست بازیکنان نمایش داده نمی‌شه
    }
    
    public function canBeVoted() {
        return false; // نمی‌شه بهش رای داد
    }
    
    public function canBeShot() {
        return false; // نمی‌شه بهش شلیک کرد
    }
    
    public function canBeKilled() {
        return false; // نمی‌شه کشتش (به جز با مرگ شوالیه)
    }
    
    public function setBlackKnightId($id) {
        $this->blackKnightId = $id;
    }
    
    private function getBlackKnightName() {
        if ($this->blackKnightId) {
            $knight = $this->getPlayerById($this->blackKnightId);
            return $knight ? $knight['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه شوالیه تاریکی رو بکشه
            if ($p['id'] == $this->blackKnightId) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'bride_dead_' . $p['id']
            ];
        }
        return $targets;
    }
}