<?php
/**
 * 🥷🗡 شوالیه تاریکی
 */

require_once __DIR__ . '/base.php';

class BlackKnight extends Role {
    
    public function getName() {
        return 'شوالیه تاریکی';
    }
    
    public function getEmoji() {
        return '🥷🗡';
    }
    
    public function getTeam() {
        return 'black_knight';
    }
    
    public function getDescription() {
        $bride = $this->getBrideName();
        $immunity = $this->getData('lynch_immunity') ?? 2;
        return "تو شوالیه تاریکی 🥷🗡 هستی. شب‌ها در جنگل سیاه به سر میبری. اگر کسی برای کشتن به خونت بیاد ۵۰٪ شانس دفاع داری. هر روز می‌تونی یک نفر رو بکشی. تا $immunity بار می‌تونی اعدام خودت رو کنسل کنی. $bride";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function hasDayAction() {
        $dayKillUsed = $this->getData('day_kill_used') ?? false;
        return !$dayKillUsed;
    }
    
    public function performNightAction($target = null) {
        if (rand(1, 100) <= 50) {
            $this->setData('at_home', true);
            return [
                'success' => true,
                'message' => '🏠 امشب توی خونه بمونی و کمین کردی.',
                'at_home' => true
            ];
        } else {
            $this->setData('at_home', false);
            return [
                'success' => true,
                'message' => '🌲 امشب توی جنگل گشتی.',
                'at_home' => false
            ];
        }
    }
    
    public function performDayAction($target = null) {
        $dayKillUsed = $this->getData('day_kill_used') ?? false;
        
        if ($dayKillUsed) {
            return [
                'success' => false,
                'message' => '❌ امروز قبلاً کسی رو کشتی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو برای کشتن انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('day_kill_used', true);
        $this->killPlayer($target, 'black_knight');
        
        return [
            'success' => true,
            'message' => "🗡 شمشیر جادوییت رو بردی و {$targetPlayer['name']} رو کشتی!",
            'killed' => true
        ];
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        $atHome = $this->getData('at_home') ?? false;
        
        if (!$atHome) {
            return ['died' => true];
        }
        
        if (rand(1, 100) <= 50) {
            $this->killPlayer($attackerId, 'black_knight');
            return [
                'died' => false,
                'killed_attacker' => true
            ];
        }
        
        return ['died' => true];
    }
    
    public function onLynchAttempt() {
        $immunity = $this->getData('lynch_immunity') ?? 2;
        
        if ($immunity > 0) {
            $immunity--;
            $this->setData('lynch_immunity', $immunity);
            return [
                'lynched' => false,
                'message' => "🛡️ فرار کردی! ($immunity بار دیگه)"
            ];
        }
        
        return ['lynched' => true];
    }
    
    public function onDayStart() {
        $this->setData('day_kill_used', false);
    }
    
    private function getBrideName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'bride_dead' && isset($p['alive']) && $p['alive'] === true) {
                return "\nعروس مردگان: {$p['name']}";
            }
        }
        return '';
    }
    
    public function getValidTargets($phase = 'night') {
        if ($phase == 'day') {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'blackknight_kill_' . $p['id']
                ];
            }
            return $targets;
        }
        
        return [
            [
                'id' => 'stay',
                'name' => '🏠 توی خونه بمون',
                'callback' => 'blackknight_stay'
            ],
            [
                'id' => 'go',
                'name' => '🌲 برو توی جنگل',
                'callback' => 'blackknight_go'
            ]
        ];
    }
}