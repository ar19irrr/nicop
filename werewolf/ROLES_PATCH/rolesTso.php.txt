<?php
/**
 * ⚔️ تسو (Tso)
 * انتقام‌جو از جومونگ
 */

require_once __DIR__ . '/base.php';

class Tso extends Role {
    
    protected $missionCompleted = false;  // آیا مأموریت تموم شده؟
    protected $canVote = false;           // تسو نمی‌تونه رای بده
    
    public function getName() {
        return 'تسو';
    }
    
    public function getEmoji() {
        return '⚔️🗡️';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        return "تو تسو ⚔️🗡️ هستی. به دلیل عصبانیت شدید از جومونگ، هر کس شب به خونه تو بیاد رو میکشی! تیمت مستقله و هدفت فقط پیدا کردن و کشتن جومونگه. نمی‌تونی رای بدی چون اصلاً اهل روستا نیستی. اگه جومونگ رو بکشی، تو برای خودت برنده‌ای ولی بازی ادامه داره!";
    }
    
    public function hasNightAction() {
        // اگه مأموریت تموم شده، دیگه نیازی به اکشن شبانه نداره
        return !$this->missionCompleted;
    }
    
    public function canVote() {
        return false; // تسو اصلاً اهل روستا نیست، نمی‌تونه رای بده
    }
    
    public function performNightAction($target = null) {
        if ($this->missionCompleted) {
            return [
                'success' => false,
                'message' => '✅ تو قبلاً جومونگ رو کشتی و مأموریتت تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی تا بری خونش و جومونگ رو پیدا کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی آیا تارگت جومونگ هست
        if ($targetPlayer['role'] === 'jumong') {
            // جومونگ رو پیدا کرد و کشت!
            $this->killPlayer($targetPlayer['id']);
            $this->missionCompleted = true;
            $this->setData('mission_completed', true);
            
            return [
                'success' => true,
                'message' => "⚔️ رفتی خونه {$targetPlayer['name']} و با دیدن جومونگ، از شدت عصبانیت شمشیرت رو کشیدی و سرش رو از بدنش جدا کردی! 🎉\n\n✅ تو به عنوان تسو، مأموریتت رو انجام دادی و برای خودت برنده شدی! ولی بازی برای بقیه ادامه داره...",
                'found_jumong' => true,
                'killed' => $targetPlayer['id'],
                'personal_win' => true,  // برد شخصی
                'end_game' => false      // بازی تموم نمیشه
            ];
        }
        
        return [
            'success' => true,
            'message' => "🗡️ رفتی خونه {$targetPlayer['name']} ولی جومونگ اونجا نبود. فردا شب دوباره بگرد!",
            'found_jumong' => false
        ];
    }
    
    /**
     * وقتی کسی شب میاد خونه تسو
     */
    public function onNightVisitor($visitor) {
        // هر کس بیاد خونه، کشته میشه!
        $this->killPlayer($visitor['id']);
        
        return [
            'success' => true,
            'message' => "⚔️ {$visitor['name']} شب اومد خونه تسو، ولی تسو از شدت عصبانیت از جومونگ، بدون فکر شمشیرش رو کشید و کشتیش!",
            'killed' => $visitor['id']
        ];
    }
    
    /**
     * چک کردن برد شخصی تسو
     */
    public function checkPersonalWin() {
        return $this->missionCompleted;
    }
    
    /**
     * وقتی تسو می‌میره
     */
    public function onDeath() {
        // بررسی آیا جومونگ هنوز زنده‌ست
        $jumong = $this->findJumong();
        
        if ($jumong && $jumong['alive']) {
            return [
                'message' => "💀 تسو مرد ولی جومونگ هنوز زنده‌ست! تسو بازنده شد...",
                'lost' => true
            ];
        }
        
        return [
            'message' => "💀 تسو مرد ولی حداقل جومونگ هم مرده بود!"
        ];
    }
    
    /**
     * پیدا کردن جومونگ در بازی
     */
    private function findJumong() {
        foreach ($this->game->getPlayers() as $player) {
            if ($player['role'] === 'jumong' && $player['alive']) {
                return $player;
            }
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        // اگه مأموریت تموم شده، تارگتی نداره
        if ($this->missionCompleted) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'tso_' . $p['id']
            ];
        }
        return $targets;
    }
    
    public function onGameStart() {
        $this->setData('mission_completed', false);
        $this->missionCompleted = false;
    }
}