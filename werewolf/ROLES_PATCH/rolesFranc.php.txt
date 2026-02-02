<?php
/**
 * 🧟‍♂️🪖 فرانکشتاین (Franc)
 * تیم: فرقه (Cult)
 */

require_once __DIR__ . '/base.php';

class Franc extends Role {
    
    protected $guarding = null;       // کسی که در حال محافظت از اونه
    protected $isAlone = false;       // آیا تنهاست (فرقه مرده)؟
    
    public function getName() {
        return 'فرانکشتاین';
    }
    
    public function getEmoji() {
        return '🧟‍♂️🪖';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        if (!$this->isAlone) {
            return "تو فرانکشتاین 🧟‍♂️🪖 هستی! یه جنگجوی سابق که تبدیل به فرقه شدی. کله‌ی آهنی داری که باعث می‌شه نقش‌های کشنده راحت از پا درت نیارن. از اعضای فرقه محافظت می‌کنی!";
        }
        return "تو فرانکشتاین 🧟‍♂️🪖 هستی! همه‌ی فرقه مردن! الان می‌تونی به اهالی حمله کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null, $action = 'guard') {
        if ($this->isAlone) {
            // حمله کردن
            if (!$target) {
                return [
                    'success' => false,
                    'message' => '❌ امشب تصمیم داری به کی حمله کنی؟'
                ];
            }
            
            $targetPlayer = $this->getPlayerById($target);
            $this->killPlayer($target, 'franc');
            
            return [
                'success' => true,
                'message' => "⚔️ دیشب {$targetPlayer['name']} رو کتی! چیزی نبود جز یه {$this->getRoleDisplayName($targetPlayer['role'])}!",
                'killed' => $target
            ];
        }
        
        // محافظت
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای از کدوم فرقه محافظت کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // فقط از فرقه می‌تونه محافظت کنه
        if (!$this->isCultMember($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ فقط می‌تونی از اعضای فرقه محافظت کنی!'
            ];
        }
        
        $this->guarding = $target;
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت می‌کنی!",
            'guarding' => $target
        ];
    }
    
    public function onCultMemberAttacked($targetId, $attackerRole) {
        if ($this->guarding != $targetId) {
            return ['protected' => false];
        }
        
        // محافظت موفق
        $protectionMessages = [
            'werewolf' => "🐺 دیشب گرگ‌ها سعی کردن {name} رو بخورن اما تو باهاشون مبارزه کردی و اونا رو فراری دادی!",
            'killer' => "🔪 دیشب قاتل سعی کرد بدن {name} رو تکه‌تکه کنه اما تو یه مشت با دست راستت به صورتش زدی و اون به سختی تونست فرار کنه!",
            'archer' => "🏹 در حال نگهبانی بودی که دیدی تیر کماندار به سمت {name} پرتاب می‌شه. خودت رو جلوی بدنش انداختی و تیر به کله‌ی آهنیت اصابت کرد!",
            'vampire' => "🧛🏻‍♂️ ومپایرها دیشب سعی کردند {name} رو بکشن اما تو جلوشون رو گرفتی. اونا هرچی دندون‌هاشونو به گوشت فاسد بدنت فرو می‌کردن بیشتر حالشون بهم می‌خورد!",
            'firefighter' => "🔥 به سمت خونه‌ی {name} رفتی تا مراقبش باشی اما شعله‌های آتیش رو دیدی. به سرعت در رو شکستی و اونو نجات دادی!",
            'ice_queen' => "❄️ دیشب بدن {name} رو در حال انجماد پیدا کردی و سریع اون رو پیش دکتر بردی تا نجاتش بده!"
        ];
        
        $target = $this->getPlayerById($targetId);
        $msg = str_replace('{name}', $target['name'], $protectionMessages[$attackerRole] ?? "🛡️ از {name} محافظت کردی!");
        
        $this->sendMessageToPlayer($this->getId(), $msg);
        $this->sendMessageToPlayer($targetId, "🛡️ دیشب شانس آوردی! فرانکشتاین جونت رو نجات داد!");
        
        return ['protected' => true];
    }
    
    public function onCultHunterAttack($hunterId) {
        // ۱۰٪ شانس کشتن شکارچی
        $killChance = rand(1, 100);
        if ($killChance <= 10) {
            $this->killPlayer($hunterId, 'franc');
            $this->sendMessageToPlayer($this->getId(), "⚔️ شکارچی به دیدنت اومد و با شمشیرش به سرت ضربه زد اما اتفاقی برات نیفتاد چون کله‌ی آهنی داری! عوضش تو از مغزش تغذیه کردی و اونو از پا درآوردی!");
            return ['killed_hunter' => true, 'died' => false];
        }
        
        return ['killed_hunter' => false];
    }
    
    public function onCultDeath() {
        // وقتی همه فرقه می‌میرن
        $this->isAlone = true;
        $this->sendMessageToPlayer($this->getId(), "😠 دیشب خرامان خرامان به سمت پناهگاه رفتی ولی هیچ فرقه‌ی دیگه‌ای رو دور آتیش ندیدی. عصبی شدی و غرش کنان به سمت روستا برگشتی. از امشب می‌تونی به اهالی حمله کنی!");
    }
    
    private function isCultMember($role) {
        return in_array($role, ['cultist', 'royce', 'franc', 'mummy']);
    }
    
    private function getRoleDisplayName($role) {
        $names = [
            'villager' => '👨🏻 روستایی',
            'werewolf' => '🐺 گرگ',
            'seer' => '👳🏻‍♂️ پیشگو'
        ];
        return $names[$role] ?? $role;
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->isAlone) {
            // همه رو می‌تونه بکشه
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'franc_attack_' . $p['id']
                ];
            }
            return $targets;
        }
        
        // فقط اعضای فرقه
        $targets = [];
        foreach ($this->getAllPlayers() as $p) {
            if ($p['id'] != $this->getId() && $p['alive'] && $this->isCultMember($p['role'])) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'franc_guard_' . $p['id']
                ];
            }
        }
        return $targets;
    }
}