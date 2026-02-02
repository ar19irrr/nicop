<?php
/**
 * ⚒ آهنگر (Blacksmith) - آپدیت شده
 * 
 * تیم: روستا
 * نقره‌پاشی (یک بار) + ساخت شمشیر (یک بار در روز، کشتن در شب)
 */

require_once __DIR__ . '/base.php';

class Blacksmith extends Role {
    
    protected $silverUsed = false;      // آیا نقره پاشیده؟
    protected $swordMade = false;       // آیا شمشیر ساخته؟
    protected $swordReady = false;      // شمشیر آماده استفاده؟
    protected $swordTarget = null;      // هدف شمشیر
    
    public function getName() {
        return 'آهنگر';
    }
    
    public function getEmoji() {
        return '⚒';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو آهنگر⚒ هستی! خدمتکار وفادار سلطنتی. می‌تونی یک بار در کل بازی نقره بپاشی (شب بدون حمله گرگ/ومپایر). همچنین هر روز می‌تونی شمشیر بسازی و شب بعد با اون یکی رو بکشی!";
    }
    
    public function hasNightAction() {
        // اگه شمشیر آماده باشه، شب می‌تونه بزنه
        return $this->swordReady && $this->swordTarget;
    }
    
    public function hasDayAction() {
        // می‌تونه نقره بپاشه یا شمشیر بسازه
        return !$this->silverUsed || !$this->swordMade;
    }
    
    public function performDayAction($action = null, $target = null) {
        // نقره‌پاشی
        if ($action == 'silver') {
            if ($this->silverUsed) {
                return [
                    'success' => false,
                    'message' => '❌ قبلاً نقره پاشیدی!'
                ];
            }
            
            $this->silverUsed = true;
            $this->setData('silver_used', true);
            $this->setGameState('silver_night', $this->getCurrentNight() + 1);
            
            $this->sendMessageToGroup("⚒ آهنگر نقره پاشید! امشب گرگ‌ها و ومپایرها نمی‌تونن حمله کنن!");
            
            return [
                'success' => true,
                'message' => "✅ نقره پاشیدی! امشب (شب " . ($this->getCurrentNight() + 1) . ") گرگ‌ها و ومپایرها قدرت حمله ندارن.",
                'silver_used' => true
            ];
        }
        
        // ساخت شمشیر
        if ($action == 'sword') {
            if ($this->swordMade) {
                return [
                    'success' => false,
                    'message' => '❌ امروز قبلاً شمشیر ساختی!'
                ];
            }
            
            if (!$target) {
                return [
                    'success' => false,
                    'message' => '❌ اول باید هدف شمشیر رو انتخاب کنی!'
                ];
            }
            
            $targetPlayer = $this->getPlayerById($target);
            if (!$targetPlayer || !$targetPlayer['alive']) {
                return [
                    'success' => false,
                    'message' => '❌ هدف نامعتبر!'
                ];
            }
            
            $this->swordMade = true;
            $this->swordReady = true;
            $this->swordTarget = $target;
            
            $this->setData('sword_made', true);
            $this->setData('sword_target', $target);
            
            return [
                'success' => true,
                'message' => "⚔️ شمشیر رو ساختی و آماده کردی! امشب {$targetPlayer['name']} رو با شمشیرت می‌کشی!",
                'sword_ready' => true
            ];
        }
        
        return [
            'success' => false,
            'message' => '❌ باید انتخاب کنی: نقره‌پاشی یا ساخت شمشیر؟'
        ];
    }
    
    public function performNightAction($target = null) {
        if (!$this->swordReady || !$this->swordTarget) {
            return [
                'success' => false,
                'message' => '❌ شمشیری برای استفاده نداری!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($this->swordTarget);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            $this->swordReady = false;
            $this->swordTarget = null;
            return [
                'success' => false,
                'message' => '❌ هدف شمشیر مرده یا نیست!'
            ];
        }
        
        // کشتن با شمشیر
        $this->killPlayer($this->swordTarget, 'blacksmith_sword');
        $this->swordReady = false;
        $this->swordTarget = null;
        $this->swordMade = false; // فردا می‌تونه دوباره بسازه
        
        $this->setData('sword_ready', false);
        $this->setData('sword_target', null);
        
        return [
            'success' => true,
            'message' => "⚔️ با شمشیرت به {$targetPlayer['name']} حمله کردی و کشتیش!",
            'killed' => $this->swordTarget
        ];
    }
    
    /**
     * بررسی نقره‌پاشی - جلوگیری از حمله گرگ/ومپایر
     */
    public static function isSilverNight($game) {
        return ($game['state']['silver_night'] ?? 0) == ($game['night_count'] ?? 0);
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase == 'day') {
            $options = [];
            
            if (!$this->silverUsed) {
                $options[] = [
                    'id' => 'silver',
                    'name' => '⚪ نقره‌پاشی (یک بار)',
                    'callback' => 'blacksmith_silver'
                ];
            }
            
            if (!$this->swordMade) {
                // برای ساخت شمشیر باید هدف انتخاب کنه
                $targets = [];
                foreach ($this->getOtherAlivePlayers() as $p) {
                    $targets[] = [
                        'id' => $p['id'],
                        'name' => '⚔️ شمشیر برای: ' . $p['name'],
                        'callback' => 'blacksmith_sword_' . $p['id']
                    ];
                }
                return array_merge($options, $targets);
            }
            
            return $options;
        }
        
        // شب - اگه شمشیر آماده باشه
        if ($this->swordReady && $this->swordTarget) {
            return [[
                'id' => 'use_sword',
                'name' => '⚔️ استفاده از شمشیر',
                'callback' => 'blacksmith_use_sword'
            ]];
        }
        
        return [];
    }
}