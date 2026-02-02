<?php
/**
 * 👮🏻‍♂️ کلانتر
 */

require_once __DIR__ . '/base.php';

class Hunter extends Role {
    
    private $hasRevenge = true;
    private $bloodthirstyLocation = null;
    
    public function getName() {
        return 'کلانتر';
    }
    
    public function getEmoji() {
        return '👮🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $bloodthirsty = $this->getBloodthirstyInfo();
        return "تو کلانتر👮🏻‍♂️ هستی. اگه کسی بخواد بکشتت، می‌تونی در لحظه‌ی مرگ به یه نفر دیگه شلیک کنی! اگه گرگا یا ومپایرا بهت حمله کردن، احتمال داره قبل از مرگ یه گرگ یا ومپایر رو بکشی! $bloodthirsty";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        // پیدا کردن ومپایر اصیل و زندانی کردن
        $this->imprisonBloodthirsty();
    }
    
    private function imprisonBloodthirsty() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'bloodthirsty') {
                $this->bloodthirstyLocation = $p['id'];
                $this->setData('imprisoned_bloodthirsty', $p['id']);
                
                sendPrivateMessage($p['id'], 
                    "🧛🏻‍♀️ توسط کلانتر {$this->player['name']} زندانی شدی! باید صبر کنی تا ومپایرا آزادت کنن یا کلانتر بمیره!"
                );
                
                $this->sendMessage("🧛🏻‍♀️ ومپایر اصیل رو زندانی کردی! توی زیرزمین خونه‌ته!");
                break;
            }
        }
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        // شلیک انتقام
        if ($this->hasRevenge) {
            return $this->performRevenge($attackerRole, $attackerId);
        }
        
        return ['died' => true];
    }
    
    private function performRevenge($attackerRole, $attackerId) {
        $attacker = $this->getPlayerById($attackerId);
        
        // محاسبه شانس کشتن گرگ
        if ($this->isWerewolf($attacker)) {
            $chance = $this->calculateWolfKillChance();
            
            if (rand(1, 100) <= $chance) {
                // یه گرگ رو می‌کشه
                $this->game = killPlayer($this->game, $attackerId, 'hunter');
                saveGame($this->game);
                
                return [
                    'died' => true,
                    'killed_attacker' => true,
                    'message' => "👮🏻‍♂️ گرگا حمله کردن ولی قبل از مرگ تفنگت رو درآوردی و {$attacker['name']} رو کشتی!"
                ];
            }
        }
        
        // شلیک به یه نفر دیگه (انتخاب کاربر)
        return [
            'died' => true,
            'can_shoot' => true,
            'message' => "👮🏻‍♂️ داری می‌میری! می‌تونی به یه نفر شلیک کنی!"
        ];
    }
    
    private function calculateWolfKillChance() {
        $wolfCount = 0;
        foreach ($this->game['players'] as $p) {
            if ($this->isWerewolf($p) && ($p['alive'] ?? false)) {
                $wolfCount++;
            }
        }
        
        // ۱ گرگ = ۳۰٪، ۲ گرگ = ۵۰٪، ۳ گرگ = ۷۰٪...
        return min(30 + (($wolfCount - 1) * 20), 90);
    }
    
    public function onLynched() {
        if ($this->hasRevenge) {
            return [
                'can_shoot' => true,
                'message' => "👮🏻‍♂️ دارن اعدامت می‌کنن! می‌تونی به یه نفر شلیک کنی!"
            ];
        }
        return ['died' => true];
    }
    
    public function performRevengeShot($target) {
        $targetPlayer = $this->getPlayerById($target);
        if ($targetPlayer && $targetPlayer['alive']) {
            $this->game = killPlayer($this->game, $target, 'hunter');
            saveGame($this->game);
            
            return [
                'success' => true,
                'message' => "💥 قبل از مرگ به {$targetPlayer['name']} شلیک کردی و کشتیش!",
                'killed' => $target
            ];
        }
        
        return [
            'success' => false,
            'message' => "❌ شلیک ناموفق بود!"
        ];
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    private function getBloodthirstyInfo() {
        if ($this->bloodthirstyLocation) {
            return "🧛🏻‍♀️ ومپایر اصیل توی زندانت هست!";
        }
        return "";
    }
    
    public function getValidTargets($phase = 'revenge') {
        if ($phase == 'revenge' && $this->hasRevenge) {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'hunter_revenge_' . $p['id']
                ];
            }
            return $targets;
        }
        return [];
    }
}