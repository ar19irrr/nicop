<?php
/**
 * 💍🧛🏻 کنت ومپایر (KentVampire)
 * تیم: ومپایر (Vampire)
 */

require_once __DIR__ . '/base.php';

class KentVampire extends Role {
    
    protected $observedPlayers = [];  // بازیکنانی که زیر نظر گرفته
    protected $allVampiresDead = false; // آیا همه ومپایرها مردن؟
    protected $canKillDaily = false;   // آیا می‌تونه هر روز بکشه؟
    
    public function getName() {
        return 'کنت ومپایر';
    }
    
    public function getEmoji() {
        return '💍🧛🏻';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        if (!$this->allVampiresDead) {
            return "تو کنت ومپایر 💍🧛🏻 هستی! از خاندان اصیل‌ها. هر شب می‌تونی یکی از اهالی رو زیر نظر بگیری و اگر قابلیت شبانه داشته باشن از نقششون باخبر بشی! در اول بازی بقیه ومپایرها رو می‌شناسی!";
        }
        return "تو کنت ومپایر 💍🧛🏻 هستی! همه‌ی ومپایرها مردن! با کمک انگشتر روشنایی هر روز می‌تونی یک نفر رو بکشی!";
    }
    
    public function hasNightAction() {
        return !$this->allVampiresDead;
    }
    
    public function hasDayAction() {
        return $this->allVampiresDead;
    }
    
    public function performNightAction($target = null) {
        if ($this->allVampiresDead) {
            return [
                'success' => false,
                'message' => '❌ الان باید از قابلیت روزانه استفاده کنی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی رو می‌خوای تعقیب کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->observedPlayers[$target] = true;
        
        // بررسی آیا قابلیت شبانه داره
        $hasNightRole = $this->hasNightAbility($targetPlayer['role']);
        
        if ($hasNightRole) {
            $roleName = $this->getRoleDisplayName($targetPlayer['role']);
            return [
                'success' => true,
                'message' => "👁️ دیشب {$targetPlayer['name']} رو زیر نظر گرفتی و متوجه شدی یه {$roleName} هست!",
                'found_role' => $targetPlayer['role']
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 دیشب {$targetPlayer['name']} رو زیر نظر گرفتی، اما از خونه بیرون نیومد و نتونستی از هویتش باخبر بشی!",
            'found_role' => null
        ];
    }
    
    public function performDayAction($target = null) {
        if (!$this->allVampiresDead) {
            return [
                'success' => false,
                'message' => '❌ هنوز نمی‌تونی از قابلیت روزانه استفاده کنی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امروز می‌خوای برای گرفتن انتقام کی رو بکشی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->killPlayer($target, 'kent_vampire');
        
        return [
            'success' => true,
            'message' => "💍 برای گرفتن انتقام {$targetPlayer['name']} رو کشتی!",
            'killed' => $target
        ];
    }
    
    public function onVampireTeamDeath() {
        // وقتی همه ومپایرها می‌میرن
        $this->allVampiresDead = true;
        $this->sendMessageToPlayer($this->getId(), "😠 خیلی خشمگین و عصبانی هستی! تمامی ومپایرها کشته شدن و برای انتقام مجبور می‌شی از انگشتر روشنایی خودت استفاده کنی و هر روز به یکی از اهالی حمله کنی!");
    }
    
    public function onAttackedByWolf() {
        // ۴۰٪ شانس کشتن یک گرگ
        $killChance = rand(1, 100);
        if ($killChance <= 40) {
            return [
                'survived' => true,
                'can_kill' => true,
                'message' => "⚔️ دیشب گرگ‌ها بهت حمله کردن ولی با خوش‌شانسی و شجاعت زیاد تونستی یکی از اونا رو بکشی و فرار کنی!"
            ];
        }
        return ['survived' => false];
    }
    
    private function hasNightAbility($role) {
        $nightRoles = ['seer', 'werewolf', 'alpha_wolf', 'guardian_angel', 'killer', 'vampire', 'bloodthirsty', 'enchanter', 'harlot', 'knight', 'archer'];
        return in_array($role, $nightRoles);
    }
    
    private function getRoleDisplayName($role) {
        $names = [
            'seer' => '👳🏻‍♂️ پیشگو',
            'werewolf' => '🐺 گرگینه',
            'guardian_angel' => '👼🏻 فرشته نگهبان',
            'killer' => '🔪 قاتل',
            'vampire' => '🧛🏻‍♂️ ومپایر'
        ];
        return $names[$role] ?? $role;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        
        if ($phase == 'night' && !$this->allVampiresDead) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                if ($p['role'] == 'vampire' || $p['role'] == 'bloodthirsty') {
                    continue;
                }
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'kent_vampire_' . $p['id']
                ];
            }
        } elseif ($phase == 'day' && $this->allVampiresDead) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'kent_vampire_day_' . $p['id']
                ];
            }
        }
        
        return $targets;
    }
}