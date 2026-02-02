<?php
/**
 * 🤡 جوکر (Joker)
 * تیم: جوکر (Joker Team)
 */

require_once __DIR__ . '/base.php';

class Joker extends Role {
    
    protected $scrollsNeeded = 3;     // تعداد کتیبه‌های مورد نیاز
    protected $scrollsFound = 0;      // تعداد کتیبه‌های پیدا شده
    protected $harlyId = null;        // آیدی هارلی کویین
    protected $harlyDead = false;     // آیا هارلی مرده؟
    protected $canKill = false;       // آیا می‌تونه بکشه؟
    
    public function getName() {
        return 'جوکر';
    }
    
    public function getEmoji() {
        return '🤡';
    }
    
    public function getTeam() {
        return 'joker';
    }
    
    public function getDescription() {
        $harlyName = $this->getHarlyName();
        return "تو جوکر 🤡 هستی! برای ساخت بمب شیطانی به {$this->scrollsNeeded} کتیبه نیاز داری. هر شب برای پیدا کردن کتیبه به خونه‌ی یکی می‌ری. هارلی کویین 👩🏻‍🎤 ({$harlyName}) معشوقته و ازت محافظت می‌کنه!";
    }
    
    public function hasNightAction() {
        return $this->scrollsFound < $this->scrollsNeeded;
    }
    
    public function performNightAction($target = null) {
        if ($this->scrollsFound >= $this->scrollsNeeded) {
            return [
                'success' => false,
                'message' => '✅ همه کتیبه‌ها رو پیدا کردی! بمب آماده انفجاره!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی تا خونش رو بگردی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // اگر هارلی مرده و این شخص قابلیت کشتن داره، جوکر می‌کشه
        if ($this->harlyDead && $this->canKill && $this->hasKillAbility($targetPlayer['role'])) {
            $this->killPlayer($target, 'joker');
            $this->sendMessageToGroup("💥 دیشب جوکر خشمگین‌تر از همیشه به خونه‌ی {$targetPlayer['name']} رفت و با یه شلیک اونو کشت!");
            
            return [
                'success' => true,
                'message' => "🔫 به {$targetPlayer['name']} شلیک کردی و کشتیش!",
                'killed' => $target
            ];
        }
        
        // جستجو برای کتیبه
        $rand = rand(1, 100);
        if ($rand <= 33) { // ۳۳٪ شانس
            $this->scrollsFound++;
            
            // بررسی آیا بمب آماده است
            if ($this->scrollsFound >= $this->scrollsNeeded) {
                $this->detonateBomb();
            }
            
            return [
                'success' => true,
                'message' => "📜 دیشب به خونه‌ی {$targetPlayer['name']} رفتی و یکی از کتیبه‌ها رو پیدا کردی! ({$this->scrollsFound}/{$this->scrollsNeeded})",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 دیشب به خونه‌ی {$targetPlayer['name']} رفتی ولی آثاری از کتیبه پیدا نکردی!",
            'found' => false
        ];
    }
    
    public function onHarlyDeath() {
        $this->harlyDead = true;
        $this->canKill = true;
        $this->sendMessageToPlayer($this->getId(), "💔 مثل اینکه معشوقه‌ات رو برای همیشه از دست دادی. عصبانی‌تر از همیشه تفنگت رو از غلافش خارج می‌کنی؛ از امشب اگر به خونه‌ی کسی بری و اون رو بیرون خونه‌ش ببینی بهش شلیک می‌کنی!");
    }
    
    private function detonateBomb() {
        // انفجار بمب و کشتن همه
        $this->sendMessageToGroup("💣💥 یه بمــــب ساعــــتی 🕰💥 دیشب توی مرکز شهر منفجر شد و جــوکـر 🤡 بعد از شب‌ها بی‌خوابی بالاخره موفق شد با کمک هارلــی کویینِ👩‍🎤 خودش همه چیز رو با خاک یکسان کنه!");
        
        // کشتن همه روستایی‌ها
        $players = $this->getAllPlayers();
        foreach ($players as $player) {
            if ($player['team'] != 'joker' && $player['alive']) {
                $this->killPlayer($player['id'], 'joker_bomb');
            }
        }
        
        // اعلام برنده
        $this->declareWinners(['joker']);
    }
    
    public function setHarlyId($id) {
        $this->harlyId = $id;
    }
    
    private function getHarlyName() {
        if ($this->harlyId) {
            $harly = $this->getPlayerById($this->harlyId);
            return $harly ? $harly['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    private function hasKillAbility($role) {
        $killerRoles = ['werewolf', 'alpha_wolf', 'killer', 'vampire', 'bloodthirsty', 'archer', 'knight'];
        return in_array($role, $killerRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'joker_' . $p['id']
            ];
        }
        return $targets;
    }
}