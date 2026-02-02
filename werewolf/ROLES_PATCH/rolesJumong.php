<?php
/**
 * 🏹⚔️ جومونگ (Jumong)
 * تیم: روستا (تا قبل از پیدا کردن ۳ نشان) / تیم قاتل (بعد از پیدا کردن ۳ نشان)
 */

require_once __DIR__ . '/base.php';

class Jumong extends Role {
    
    protected $badges = [];        // نشان‌های پیدا شده
    protected $foundAll = false;   // آیا هر ۳ نشان رو پیدا کرده؟
    protected $teamSwitched = false;
    
    const BADGE_JAVARANG = 'جاورنگ';
    const BADGE_ARANG = 'آرنگ';
    const BADGE_KAMAN = 'کمان دامول';
    const ALL_BADGES = [self::BADGE_JAVARANG, self::BADGE_ARANG, self::BADGE_KAMAN];
    
    public function getName() {
        return 'جومونگ';
    }
    
    public function getEmoji() {
        return '🏹⚔️';
    }
    
    public function getTeam() {
        return $this->foundAll ? 'killer' : 'villager';
    }
    
    public function getDescription() {
        if ($this->foundAll) {
            $team = $this->getKillerTeamInfo();
            return "تو جومونگ 🏹⚔️ هستی! هر سه نشان رو پیدا کردی و فهمیدی کمان کیه! حالا به تیم قاتل پیوستی! $team";
        }
        
        $badgesFound = implode('، ', $this->badges);
        $badgesText = empty($this->badges) ? 'هنوز هیچ نشانی پیدا نکردی' : "نشان‌های پیدا شده: $badgesFound";
        $remaining = 3 - count($this->badges);
        
        return "تو جومونگ 🏹⚔️ هستی! باید سه نشان گروه دامول (جاورنگ، آرنگ، کمان دامول) رو پیدا کنی. هر شب به خونه یکی می‌ری و دنبال نشان می‌گردی. اگه قبل از پیدا کردن سه نشان بمیری، جزو تیم روستا حساب می‌شی. $badgesText - $remaining نشان دیگه مونده!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی تا خونش رو بگردی!'
            ];
        }
        
        if ($this->foundAll) {
            return [
                'success' => false,
                'message' => '✅ تو هر سه نشان رو پیدا کردی! دیگه نیازی به گشتن نیست.'
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
        
        // بررسی اینکه آیا قبلاً این خونه رو گشته
        $searched = $this->getData('searched') ?? [];
        if (in_array($target, $searched)) {
            return [
                'success' => false,
                'message' => "⚠️ قبلاً خونه {$targetPlayer['name']} رو گشتی! برو یه جای دیگه."
            ];
        }
        
        // ثبت خونه گشته شده
        $searched[] = $target;
        $this->setData('searched', $searched);
        
        // شانس پیدا کردن نشان (۳۳٪ برای هر نشان)
        $foundBadge = $this->tryFindBadge();
        
        if ($foundBadge) {
            $this->badges[] = $foundBadge;
            $this->setData('badges', $this->badges);
            
            $badgeCount = count($this->badges);
            
            // بررسی آیا هر ۳ نشان پیدا شده
            if ($badgeCount >= 3) {
                $this->foundAll = true;
                $this->setData('found_all', true);
                $this->switchToKillerTeam();
                
                return [
                    'success' => true,
                    'message' => "🎉 تبریک! نشان آخر ($foundBadge) رو پیدا کردی! حالا هر سه نشان رو داری و فهمیدی کمان کیه! به تیم قاتل پیوستی!",
                    'found_badge' => $foundBadge,
                    'all_found' => true,
                    'team_switched' => true
                ];
            }
            
            $remaining = 3 - $badgeCount;
            return [
                'success' => true,
                'message' => "✨ نشان $foundBadge رو توی خونه {$targetPlayer['name']} پیدا کردی! $badgeCount تا از ۳ تا. $remaining نشان دیگه مونده.",
                'found_badge' => $foundBadge
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 خونه {$targetPlayer['name']} رو گشتی ولی هیچ نشانی پیدا نکردی. باید بیشتر بگردی!",
            'found_badge' => null
        ];
    }
    
    /**
     * تلاش برای پیدا کردن نشان
     */
    private function tryFindBadge() {
        // نشان‌هایی که هنوز پیدا نشدن
        $remainingBadges = array_diff(self::ALL_BADGES, $this->badges);
        
        if (empty($remainingBadges)) {
            return null;
        }
        
        // ۳۳٪ شانس پیدا کردن نشان
        if (rand(1, 100) > 33) {
            return null;
        }
        
        // انتخاب تصادفی یکی از نشان‌های باقیمانده
        $badge = array_values($remainingBadges)[array_rand($remainingBadges)];
        return $badge;
    }
    
    /**
     * تغییر تیم به قاتل
     */
    private function switchToKillerTeam() {
        $this->teamSwitched = true;
        
        // اطلاع به قاتل و کماندار و داوینا
        $this->notifyKillerTeam("🏹⚔️ جومونگ هر سه نشان رو پیدا کرد و به تیم ما پیوست!");
        
        // اطلاع به خود جومونگ
        $killerInfo = $this->getKillerTeamInfo();
        $this->sendMessage("🎉 به تیم قاتل خوش اومدی! $killerInfo");
    }
    
    /**
     * دریافت اطلاعات تیم قاتل
     */
    private function getKillerTeamInfo() {
        $killers = [];
        foreach ($this->game['players'] as $p) {
            if (in_array($p['role'], ['serial_killer', 'qatel', 'killer', 'archer', 'davina']) && ($p['alive'] ?? false)) {
                $roleName = $this->getRoleName($p['role']);
                $killers[] = "{$p['name']} ($roleName)";
            }
        }
        
        if (empty($killers)) {
            return "متأسفانه تیم قاتل خالیه!";
        }
        
        return "هم‌تیمی‌های تو: " . implode('، ', $killers);
    }
    
    /**
     * تبدیل نام نقش به فارسی
     */
    private function getRoleName($role) {
        $names = [
            'serial_killer' => 'قاتل',
            'qatel' => 'قاتل',
            'killer' => 'قاتل',
            'archer' => 'کماندار',
            'davina' => 'داوینا'
        ];
        return $names[$role] ?? $role;
    }
    
    /**
     * اطلاع‌رسانی به تیم قاتل
     */
    private function notifyKillerTeam($message) {
        foreach ($this->game['players'] as $p) {
            if (in_array($p['role'], ['serial_killer', 'qatel', 'killer', 'archer', 'davina']) && ($p['alive'] ?? false)) {
                $this->sendMessageToPlayer($p['id'], $message);
            }
        }
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        $attacker = $this->getPlayerById($attackerId);
        
        // اگه قاتل یا کماندار حمله کنن، متوجه میشن و نمی‌کشن
        if (in_array($attackerRole, ['serial_killer', 'qatel', 'killer', 'archer'])) {
            // اطلاع به حمله‌کننده
            $this->sendMessageToPlayer($attackerId, "🏹⚔️ رفتی خونه {$this->getPlayerName()} که بکشیش، ولی دیدی جومونگه! متوجه شدی طرف یه نقش مهمِ، نکشتش و برگشتی.");
            
            // اطلاع به جومونگ
            $this->sendMessage("⚠️ {$attacker['name']} اومد خونت ولی چون جومونگی، متوجه شد و نکشتت!");
            
            return [
                'died' => false,
                'spared' => true,
                'message' => 'قاتل/کماندار متوجه شد جومونگی و نکشتت!'
            ];
        }
        
        // بقیه حملات عادی
        return [
            'died' => true,
            'message' => 'کشته شدی!'
        ];
    }
    
    public function onDeath($killerRole = null) {
        // اگه قبل از پیدا کردن سه نشان بمیره، جزو تیم روستا حساب می‌شه
        if (!$this->foundAll) {
            return [
                'team' => 'villager',
                'message' => 'جومونگ قبل از پیدا کردن سه نشان مرد و جزو تیم روستا حساب شد.'
            ];
        }
        
        return [
            'team' => 'killer',
            'message' => 'جومونگ بعد از پیوستن به تیم قاتل مرد.'
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->foundAll) {
            return []; // دیگه نیازی به گشتن نیست
        }
        
        $targets = [];
        $searched = $this->getData('searched') ?? [];
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            // خونه‌های قبلاً گشته شده رو نشون نده
            if (in_array($p['id'], $searched)) {
                continue;
            }
            
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'jumong_' . $p['id']
            ];
        }
        
        return $targets;
    }
    
    public function onGameStart() {
        $this->setData('badges', []);
        $this->setData('searched', []);
        $this->setData('found_all', false);
    }
    
    private function getPlayerName() {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $this->playerId) {
                return $p['name'];
            }
        }
        return 'جومونگ';
    }
}