<?php
/**
 * 💋 ناتاشا (Harlot)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Harlot extends Role {
    
    protected $lastVisitId = null;
    
    public function getName() {
        return 'ناتاشا';
    }
    
    public function getEmoji() {
        return '💋';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو ناتاشا 💋 هستی! هر شب می‌تونی بری به خونه یکی از اهالی که جاسوسی کنی. ولی مراقب باش، اگر گرگ یا قاتل بیاد خونه همون شخص، هردوتون کشته می‌شین! اگر تو رفته باشی خونه کسی، اون شب گرگ به خونه تو حمله کنه، نمی‌میری (چون خونه نیستی)!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب خونه کی می‌خوای بری؟'
            ];
        }
        
        if ($target == $this->lastVisitId) {
            return [
                'success' => false,
                'message' => '⚠️ نمی‌تونی دو شب پیاپی به خونه یکی بری!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->lastVisitId = $target;
        
        // بررسی آیا خونه خالیه
        if ($this->isNotHome($target)) {
            $this->sendMessageToPlayer($this->getId(), "🏠 به کاهدون زدی 😬 {$targetPlayer['name']} خونه نبود!");
            return [
                'success' => true,
                'message' => "🏠 {$targetPlayer['name']} خونه نبود!",
                'empty' => true
            ];
        }
        
        $targetRole = $targetPlayer['role'];
        
        // اگر گرگ باشه - هر دو می‌میرن!
        if ($this->isWolf($targetRole)) {
            $this->killPlayer($this->getId(), 'harlot_wolf');
            
            // پیام گروه - اسم گرگ لو نمیره!
            $this->sendMessageToGroup("💋 از قرار معلوم، ناتاشا دیشب رفت پیش یه گرگ... گرگه هم بهش رحم نکرد و تیکه‌پاره‌ش کرد. خدا بیامرزتت {$this->getPlayerName()}!");
            
            return [
                'success' => true,
                'message' => "💀 رفتی خونه {$targetPlayer['name']} که یه شب باحال رو باهم سپری کنین ولی {$targetPlayer['name']} یه گرگ بود و قاعدتاً با یه گرگ خوابیدن، عاقبتش مرگه!",
                'died' => true
            ];
        }
        
        // اگر قاتل باشه - هر دو می‌میرن!
        if ($targetRole == 'killer' || $targetRole == 'serial_killer') {
            $this->killPlayer($this->getId(), 'harlot_killer');
            $this->killPlayer($targetPlayer['id'], 'harlot'); // قاتل هم می‌میره چون ناتاشا مزاحمش شده!
            
            // پیام گروه - اسم قاتل لو نمیره!
            $this->sendMessageToGroup("💋 ناتاشا دیشب رفت خونه‌ی یه نفر برای جاسوسی ولی قاتل هم اونجا بود و هر دوتاشون کشته شدن!");
            
            return [
                'success' => true,
                'message' => "💀 رفتی خونه {$targetPlayer['name']} و همه چیز روبه‌راه بود تا اینکه یهو یه چاقو درآورد و دیوانه‌وار شروع به خندیدن کرد. از شکمت پاره کرد و ولت کرد که فرار کنی... ولی این اصلاً خنده‌دار نیست که تو مردی. اون قاتل روانی بود!",
                'died' => true
            ];
        }
        
        // اگر ومپایر اصیل باشه - ناتاشا تبدیل می‌شه!
        if ($targetRole == 'bloodthirsty') {
            $this->convertToVampire($this->getId());
            
            $this->sendMessageToGroup("🧛🏻‍♂️ صبح روز بعد {$this->getPlayerName()} رو دیدن که رفتارش عوض شده... ومپایر اصیل تبدیلش کرده!");
            
            return [
                'success' => true,
                'message' => "🧛🏻‍♂️ {$targetPlayer['name']} ومپایر اصیل بود و خونت رو خورد و تبدیلت کرد!",
                'converted' => true
            ];
        }
        
        // اگر فرقه‌گرا باشه - ناتاشا متوجه می‌شه
        if ($this->isCultMember($targetRole)) {
            return [
                'success' => true,
                'message' => "👁️ دیشب رفتی خونه {$targetPlayer['name']} که یکم جاسوسی کنی. موقع برگشت، توی خونه‌ش اتاقی رو می‌بینی که شبیه به محل عبادت فرقه‌گرا هاست!",
                'found_cult' => true
            ];
        }
        
        // روستایی ساده
        $this->sendMessageToPlayer($target, "💋 دیشب خیلی خسته بودی و اصلاً نفهمیدی کی خوابت برد. نیمه‌های شب بیدار می‌شی و می‌بینی که انگار کسی وارد خونه شده... اون ناتاشا بود که اومده بود واسه جاسوسی. اما خیالت راحت، اون چیزی متوجه نشد!");
        
        return [
            'success' => true,
            'message' => "💋 یه شب طولانی رو با {$targetPlayer['name']} خوش گذروندی... همه چیز رو به راهه... پس اون گرگ نبود. برو خونه‌ت و به فکر این باش که فردا می‌خوای کجا بری!",
            'safe' => true
        ];
    }
    
    /**
     * وقتی کسی به ناتاشا حمله می‌کنه (قاتل، گرگ، و...)
     */
    public function onAttacked($attackerRole, $attackerId) {
        // اگه ناتاشا خونه نباشه (رفته خونه کس دیگه‌ای)
        if ($this->isAway()) {
            return [
                'died' => false,
                'not_home' => true,
                'message' => 'ناتاشا خونه نبود!'
            ];
        }
        
        // اگه قاتل بیاد خونه ناتاشا
        if ($attackerRole == 'killer' || $attackerRole == 'serial_killer') {
            // هر دو می‌میرن!
            $this->killPlayer($this->getId(), 'killer');
            $this->killPlayer($attackerId, 'harlot');
            
            $this->sendMessageToGroup("💋 قاتل رفت خونه ناتاشا ولی ناتاشا قبل از مردن تونست قاتل رو هم بکشه! هر دو مردن!");
            
            return [
                'died' => true,
                'killed_attacker' => true,
                'message' => 'قاتل اومد خونت ولی قبل از اینکه بکشتت، تونستی اونو هم بکشی!'
            ];
        }
        
        // اگه گرگ بیاد خونه ناتاشا
        if ($this->isWolf($attackerRole)) {
            $this->killPlayer($this->getId(), 'werewolf');
            
            $this->sendMessageToGroup("💋 گرگا دیشب به خونه ناتاشا حمله کردن و تیکه‌پاره‌ش کردن!");
            
            return [
                'died' => true,
                'message' => 'گرگا اومدن خونت و خوردنت!'
            ];
        }
        
        return [
            'died' => true,
            'message' => 'کشته شدی!'
        ];
    }
    
    /**
     * وقتی گرگ به خونه ناتاشا حمله می‌کنه (خالیه)
     */
    public function onWolfAttackHome() {
        if ($this->isAway()) {
            $this->sendMessageToPlayer($this->getId(), "🏃‍♀️ شانس آوردی! دیشب گرگ‌ها به خونه‌ت حمله کردن ولی تو خونه نبودی!");
            return ['died' => false, 'not_home' => true];
        }
        
        // اگه خونه باشه، می‌میره
        $this->killPlayer($this->getId(), 'werewolf');
        $this->sendMessageToGroup("💋 گرگا دیشب به خونه ناتاشا حمله کردن و تیکه‌پاره‌ش کردن!");
        
        return ['died' => true];
    }
    
    /**
     * بررسی آیا ناتاشا خونه رو ترک کرده
     */
    private function isAway() {
        // اگه lastVisitId ست شده باشه، یعنی رفته خونه کس دیگه‌ای
        return !is_null($this->lastVisitId);
    }
    
    /**
     * بررسی آیا خونه خالیه
     */
    private function isNotHome($playerId) {
        // بررسی آیا بازیکن خونه رو ترک کرده (مثل جومونگ)
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $playerId && ($p['role_data']['is_away'] ?? false)) {
                return true;
            }
        }
        return false;
    }
    
    private function convertToVampire($playerId) {
        $this->setPlayerRole($playerId, 'vampire');
    }
    
    private function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
    
    private function isCultMember($role) {
        return in_array($role, ['cultist', 'royce', 'franc', 'mummy']);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['id'] == $this->getId()) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'harlot_' . $p['id']
            ];
        }
        return $targets;
    }
}