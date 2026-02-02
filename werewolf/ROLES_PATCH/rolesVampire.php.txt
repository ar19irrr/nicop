<?php
/**
 * 🧛🏻‍♂️ ومپایر (Vampire)
 * تیم: ومپایر (Vampire)
 */

require_once __DIR__ . '/base.php';

class Vampire extends Role {
    
    protected $bloodthirstyId = null;    // آیدی ومپایر اصیل
    protected $bloodthirstyFound = false; // آیا اصیل پیدا شده؟
    protected $bloodthirstyFreed = false; // آیا اصیل آزاد شده؟
    protected $hunterId = null;           // آیدی کلانتر
    protected $convertChance = 30;        // درصد تبدیل
    
    public function getName() {
        return 'ومپایر';
    }
    
    public function getEmoji() {
        return '🧛🏻‍♂️';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        if (!$this->bloodthirstyFreed) {
            return "تو ومپایر 🧛🏻‍♂️ هستی! هر شب برای پیدا کردن کلانتر و آزاد کردن ومپایر اصیل به خونه‌ی یک نفر حمله می‌کنی. ۳۰٪ امکان داره طرف رو بکشی یا اینکه بعد خوردن خونش ولش کنی!";
        }
        return "تو ومپایر 🧛🏻‍♂️ هستی! ومپایر اصیل آزاد شده! هر شب می‌تونی به یکی حمله کنی و ۳۰٪ احتمال داری تبدیلش کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای برای پیدا کردن ومپایر اصیل کجا بری؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // اگر کلانتر رو پیدا کرده و هنوز آزاد نشده
        if ($targetPlayer['role'] == 'hunter' && !$this->bloodthirstyFreed) {
            $this->hunterId = $target;
            
            // کشتن کلانتر و آزاد کردن اصیل
            $this->killPlayer($target, 'vampire');
            $this->freeBloodthirsty();
            
            return [
                'success' => true,
                'message' => "🎉 ایول! کلانتر یعنی {$targetPlayer['name']} رو پیدا کردی و کشتی! تونستی ومپایر اصیل رو آزاد کنی!",
                'killed' => $target,
                'freed_bloodthirsty' => true
            ];
        }
        
        // بررسی شکارچی
        if ($targetPlayer['role'] == 'cult_hunter') {
            $this->killPlayer($this->getId(), 'cult_hunter');
            $this->sendMessageToGroup("🪵 روستاییان بیدار می‌شوند و جسد {$this->getPlayerName()} رو در نزدیک خانه شکارچی پیدا می‌کنن که چوب بلوط سفیدی توی قلبش فرو رفته!");
            
            return [
                'success' => false,
                'message' => "💀 دیشب به {$targetPlayer['name']} حمله کردی اما شکارچی بود و قبل از اینکه بتونی فرار کنی یه چوب بلوط سفید توی قلبت فرو کرد!",
                'died' => true
            ];
        }
        
        // بررسی گرگ
        if ($this->isWolf($targetPlayer['role'])) {
            $this->killPlayer($this->getId(), 'wolf');
            $this->sendMessageToGroup("🐺 روستاییان بیدار می‌شوند و جسد {$this->getPlayerName()} رو پیدا می‌کنن که دندون تیزی توی قلبش فرو رفته. مثل اینکه ومپایرها دیشب به دسته گرگ‌ها حمله کردن!");
            
            return [
                'success' => false,
                'message' => "🐺 دیشب به {$targetPlayer['name']} حمله کردی ولی اون یه گرگ بود و ترو تیکه‌پاره کرد!",
                'died' => true
            ];
        }
        
        // بررسی قاتل
        if ($targetPlayer['role'] == 'killer') {
            $this->killPlayer($this->getId(), 'killer');
            $this->sendMessageToGroup("🔪 روستایان بیدار می‌شوند و سر {$this->getPlayerName()} رو که از بدنش جدا شده بود تو مرکز روستا پیدا می‌کنن. مثل اینکه دیشب ومپایرها به قاتل حمله کردن!");
            
            return [
                'success' => false,
                'message' => "🔪 دیشب به {$targetPlayer['name']} حمله کردی اما قاتل بود و چاقوش رو فرو کرد تو چشم چپت!",
                'died' => true
            ];
        }
        
        // حمله عادی
        $rand = rand(1, 100);
        
        // ۳۰٪ کشتن
        if ($rand <= 30) {
            $this->killPlayer($target, 'vampire');
            return [
                'success' => true,
                'message' => "🩸 به {$targetPlayer['name']} حمله کردی و تمام خونش رو نوشیدی و کشتیش!",
                'killed' => $target
            ];
        }
        
        // ۳۰٪ تبدیل (فقط اگر اصیل آزاد شده)
        if ($rand <= 60 && $this->bloodthirstyFreed) {
            $this->convertToVampire($target);
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ به {$targetPlayer['name']} حمله کردی! بعد از نوشیدن مقداری از خونش ولش کردی و اون آلوده شد. فردا تبدیل به ومپایر می‌شه!",
                'converted' => $target
            ];
        }
        
        // ۴۰٪ ول کردن
        return [
            'success' => true,
            'message' => "🩸 دیشب رفتی خون {$targetPlayer['name']} بخوری ولی وسطاش بی‌خیالش شدی!",
            'spared' => $target
        ];
    }
    
    private function freeBloodthirsty() {
        $this->bloodthirstyFreed = true;
        if ($this->bloodthirstyId) {
            $this->sendMessageToPlayer($this->bloodthirstyId, "🔓 دیشب در حالی که توی زندان نشسته بودی، ومپایرها اومدن و کلانتر رو کشتن! حالا آزاد شدی و رهبر دسته‌ی ومپایرها هستی!");
        }
        $this->notifyVampireTeam("🎉 ومپایر اصیل آزاد شد! حالا می‌تونیم با هم حمله کنیم!");
    }
    
    private function convertToVampire($playerId) {
        $this->setPlayerRole($playerId, 'vampire');
        $this->sendMessageToPlayer($playerId, "🧛🏻‍♂️ دیشب یه ومپایر بهت حمله کرد و بعد از نوشیدن خونت ولت کرد. آلوده شدی و فردا تبدیل به یک ومپایر می‌شی!");
    }
    
    public function setBloodthirstyId($id) {
        $this->bloodthirstyId = $id;
    }
    
    public function onBloodthirstyDeath() {
        // اگر اصیل قبل از آزاد شدن بمیره
        if (!$this->bloodthirstyFreed) {
            $this->convertChance = 20; // ۲۰٪ قدرت تبدیل
            $this->sendMessageToPlayer($this->getId(), "⚠️ از اونجایی که ومپایر اصیل قبل از مرگ شما کشته شده، طلسم تبدیلی که در شما وجود داشت فعال شده و ۲۰٪ قدرت تبدیل داری!");
        }
    }
    
    private function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($role, $wolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['role'] == 'vampire' || $p['role'] == 'bloodthirsty') {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'vampire_' . $p['id']
            ];
        }
        return $targets;
    }
}