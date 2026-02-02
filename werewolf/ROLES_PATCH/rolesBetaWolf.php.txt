<?php
/**
 * 💤🐺 گرگ خوابالو (BetaWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class BetaWolf extends Role {
    
    protected $sleepCounter = 0;      // شمارنده خواب
    protected $dreams = [];           // رویاهای دیده شده
    
    public function getName() {
        return 'گرگ خوابالو';
    }
    
    public function getEmoji() {
        return '💤🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ خوابالو 💤🐺 هستی! بخاطر تنبلی و شکمو بودنت همیشه خواب می‌مونی و جایی در دسته‌ی گرگ‌ها نداری و اون‌ها رو نمی‌شناسی. اما هر دو شب خواب یکی از اهالی رو می‌بینی و متوجه نقشش می‌شی. اگر توسط شوالیه یا تفنگدار مورد هدف قرار بگیری، قبل از مردن می‌خوریشون!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $this->sleepCounter++;
        
        // شب‌های فرد: خواب و رویا
        if ($this->sleepCounter % 2 == 1) {
            if (!$target) {
                return [
                    'success' => false,
                    'message' => '💤 امشب در حالی که گرگ‌ها برای طعمه به بیرون رفتن، تو در خواب ناز به سر می‌بری... کی رو می‌خوای ببینی؟'
                ];
            }
            
            $targetPlayer = $this->getPlayerById($target);
            if (!$targetPlayer || !$targetPlayer['alive']) {
                return [
                    'success' => false,
                    'message' => '❌ بازیکن نامعتبر!'
                ];
            }
            
            // دیدن نقش در رویا
            $realRole = $targetPlayer['role'];
            $this->dreams[$target] = $realRole;
            
            $roleName = $this->getRoleDisplayName($realRole);
            
            return [
                'success' => true,
                'message' => "💭 دیشب با قار و قور شکمت از خواب پریدی و رویایی که داشتی رو مرور کردی! اسم {$targetPlayer['name']} رو یادت میاد که توی خوابت متوجه شدی اون یه {$roleName} هست!",
                'dream' => true
            ];
        }
        
        // شب‌های زوج: حمله عادی
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onAttacked($attackerId, $attackerRole) {
        // اگر توسط شوالیه یا تفنگدار حمله شه، قبل از مردن می‌کشه
        if (in_array($attackerRole, ['knight', 'gunner'])) {
            $attacker = $this->getPlayerById($attackerId);
            
            // کشتن حمله‌کننده
            $this->killPlayer($attackerId, 'beta_wolf_revenge');
            
            $this->sendMessageToGroup("💥 دیشب شوالیه/تفنگدار به گرگ خوابالو حمله کرد ولی قبل از مردن، گرگ خوابالو {$attacker['name']} رو به عنوان آخرین شام خورد!");
            
            return ['killed_attacker' => true, 'died' => true];
        }
        
        return ['killed_attacker' => false];
    }
    
    private function getRoleDisplayName($role) {
        $names = [
            'seer' => '👳🏻‍♂️ پیشگو',
            'werewolf' => '🐺 گرگینه',
            'guardian_angel' => '👼🏻 فرشته نگهبان',
            'knight' => '🗡 شوالیه',
            'killer' => '🔪 قاتل'
        ];
        return $names[$role] ?? $role;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolfTeam($p['role'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'beta_wolf_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}