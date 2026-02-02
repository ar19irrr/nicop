<?php
/**
 * 🥷🗡 شوالیه تاریکی
 */

require_once __DIR__ . '/base.php';

class BlackKnight extends Role {
    
    private $lynchImmunity = 2;
    private $dayKillUsed = false;
    
    public function getName() {
        return 'شوالیه تاریکی';
    }
    
    public function getEmoji() {
        return '🥷🗡';
    }
    
    public function getTeam() {
        return 'black_knight'; // تیم مستقل شوالیه تاریکی
    }
    
    public function getDescription() {
        $bride = $this->getBrideName();
        return "تو شوالیه تاریکی 🥷🗡 هستی، فرزند ارشد پادشاه هستی. شب ها در جنگل سیاه به سر میبری. اگر نقش شب کار به دیدنت بیاد احتمال 50 درصد توی خونه باشی. اگر قاتل، گرگ، ومپایر یا شوالیه برای کشتن به خونت بیان احتمال 50 درصد اونا رو بکشی وگرنه کشته میشی. در هر روز قبل اعدام یک نفر رو میکشی. اگر بخواهن اعدامت کنن تا دو بار میتونی اعدام خودت رو کنسل کنی. $bride";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function hasDayAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        // 50% احتمال اینکه توی خونه باشه
        if (rand(1, 100) <= 50) {
            $this->setData('at_home', true);
            return [
                'success' => true,
                'message' => 'امشب تصمیم گرفتی توی خونه بمونی و کمین کنی.',
                'at_home' => true
            ];
        } else {
            $this->setData('at_home', false);
            return [
                'success' => true,
                'message' => 'امشب تصمیم گرفتی توی جنگل بچرخی.',
                'at_home' => false
            ];
        }
    }
    
    public function performDayAction($target = null) {
        if ($this->dayKillUsed) {
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
        
        $this->dayKillUsed = true;
        $this->game = killPlayer($this->game, $target, 'black_knight');
        saveGame($this->game);
        
        return [
            'success' => true,
            'message' => "🗡 شمشیر جادوییت رو بردی و {$targetPlayer['name']} رو کشتی!",
            'killed' => true
        ];
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        $atHome = $this->getData('at_home') ?? false;
        
        if (!$atHome) {
            return [
                'died' => true,
                'message' => 'توی خونه نبودی و نتونستی از خودت دفاع کنی!'
            ];
        }
        
        // 50% شانس دفاع و کشتن حمله‌کننده
        if (rand(1, 100) <= 50) {
            $this->game = killPlayer($this->game, $attackerId, 'black_knight');
            saveGame($this->game);
            
            return [
                'died' => false,
                'killed_attacker' => true,
                'message' => 'متوجه حضورش شدی و با شمشیر جادوییت کشتیش!'
            ];
        }
        
        return [
            'died' => true,
            'message' => 'متوجه حمله شدی ولی نتونستی دفاع کنی!'
        ];
    }
    
    public function onLynchAttempt() {
        if ($this->lynchImmunity > 0) {
            $this->lynchImmunity--;
            return [
                'lynched' => false,
                'message' => "شوالیه تاریکی هستی و انگار قبل فرار کردی! ($this->lynchImmunity بار دیگه میتونی فرار کنی)"
            ];
        }
        
        return [
            'lynched' => true,
            'message' => 'دیگه نتونستی فرار کنی و اعدام شدی!'
        ];
    }
    
    private function getBrideName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'bride_dead' && ($p['alive'] ?? false)) {
                return "عروس مردگان کسی نیست جز: {$p['name']}";
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
        
        // شب - انتخاب بموندن توی خونه یا رفتن
        return [
            [
                'id' => 'stay_home',
                'name' => '🏠 توی خونه بمون',
                'callback' => 'blackknight_stay'
            ],
            [
                'id' => 'go_out',
                'name' => '🌲 برو توی جنگل',
                'callback' => 'blackknight_go'
            ]
        ];
    }
}