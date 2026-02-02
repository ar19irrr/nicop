<?php
/**
 * 🐍👩🏻‍🦳 لیلیث (Lilis)
 * تیم: آتش و یخ (Fire & Ice)
 */

require_once __DIR__ . '/base.php';

class Lilis extends Role {
    
    protected $foundLucifer = false;  // آیا لوسیفر رو پیدا کرده؟
    protected $luciferId = null;      // آیدی لوسیفر
    protected $parentsDead = false;   // آیا پدر و مادر (پادشاه آتش و ملکه یخ) مردن؟
    protected $hasKillPower = false;  // آیا قدرت کشتن داره؟
    
    public function getName() {
        return 'لیلیث';
    }
    
    public function getEmoji() {
        return '🐍👩🏻‍🦳';
    }
    
    public function getTeam() {
        return 'fire_ice';
    }
    
    public function getDescription() {
        if (!$this->foundLucifer) {
            return "تو لیلیث 🐍👩🏻‍🦳 هستی، معشوقه سابق شیطان! زمانی که شیطان با همسر آدم بهت خیانت کرد از او متنفر شدی. هر شب می‌تونی به جستجوی شیطان به خونه‌ی یک نفر بری و اگر لوسیفر 👹 رو پیدا کنی با آب مقدس جونش رو برای همیشه می‌گیری!";
        }
        return "تو لیلیث 🐍👩🏻‍🦳 هستی! لوسیفر رو کشتی و الان با چشمان جادوییت هر شب یک نفر رو می‌تونی خشک کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            $msg = $this->foundLucifer ? "امشب کیو می‌خوای خشک کنی؟" : "امشب برای پیدا کردن شیطان؟";
            return [
                'success' => false,
                'message' => "❌ {$msg}"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // اگر لوسیفر رو پیدا نکرده
        if (!$this->foundLucifer) {
            if ($targetPlayer['role'] == 'lucifer') {
                // پیدا کردن لوسیفر و کشتن
                $this->foundLucifer = true;
                $this->luciferId = $target;
                $this->killPlayer($target, 'lilis');
                
                $this->sendMessageToGroup("😱 همه مات و مبهوت موندن! ظاهراً روستا از وجود لوسیفر 👹 یعنی {$targetPlayer['name']} پاک شده! معلوم نیست چی به سرش اومده!");
                
                return [
                    'success' => true,
                    'message' => "🗡️ رفتی خونه {$targetPlayer['name']} و فهمیدی که اون شیطان هست! از اونجایی که نسبت بهش تنفر داری، جونش رو واسه همیشه گرفتی!",
                    'killed' => $target
                ];
            }
            
            return [
                'success' => true,
                'message' => "🔍 دیشب به خونه {$targetPlayer['name']} رفتی و ظاهراً اون لوسیفر نبود!",
                'found' => false
            ];
        }
        
        // بعد از پیدا کردن لوسیفر، هر شب می‌تونه یکی رو خشک کنه
        $this->killPlayer($target, 'lilis');
        
        return [
            'success' => true,
            'message' => "🐍 با چشمان جادوییت {$targetPlayer['name']} رو خشک کردی!",
            'killed' => $target
        ];
    }
    
    public function onParentsDeath() {
        // وقتی پادشاه آتش و ملکه یخ می‌میرن
        $this->parentsDead = true;
        $this->hasKillPower = true;
        
        $this->sendMessageToPlayer($this->getId(), "🔥❄️ چون هم پادشاه آتش و ملکه یخ مردن، خیلی عصبانی شدی و الان چشمان جادویی داری! از امشب می‌تونی هر شب یک نفر رو خشک کنی!");
    }
    
    public function onAttacked($attackerId, $attackerRole) {
        // ۶۰٪ شانس معکوس کردن حمله
        $reverseChance = rand(1, 100);
        if ($reverseChance <= 60) {
            $attacker = $this->getPlayerById($attackerId);
            $this->killPlayer($attackerId, 'lilis_reverse');
            
            $this->sendMessageToPlayer($this->getId(), "🐍 دیشب {$attacker['name']} بهت حمله کرد، ولی تو زود برگشتی خونه و وقتی به چشماش خیره شدی خشکش زد و دار فانی رو وداع گفت!");
            
            return ['cancelled' => true, 'killed_attacker' => true];
        }
        
        return ['cancelled' => false];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'lilis_' . $p['id']
            ];
        }
        return $targets;
    }
}