<?php
/**
 * 🌩🐺 گرگ سفید (WhiteWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class WhiteWolf extends Role {
    
    protected $guarding = null;       // کسی که در حال محافظت از اونه
    
    public function getName() {
        return 'گرگ سفید';
    }
    
    public function getEmoji() {
        return '🌩🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ سفید 🌩🐺 هستی! عاقل‌ترین و مسئولیت‌پذیرترین گرگ دسته. هر شب می‌تونی به جای حمله، از یکی از اعضای دسته محافظت کنی (از قاتل، کماندار، شوالیه، ومپایر، پادشاه آتش، ملکه یخ).";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null, $action = 'guard') {
        if ($action == 'attack') {
            // اگر دیگه گرگی نمونده، می‌تونه حمله کنه
            if ($this->isLastWolf()) {
                return $this->performAttack($target);
            }
            return [
                'success' => false,
                'message' => '❌ هنوز گرگ‌های دیگه هستن! باید از یکی محافظت کنی.'
            ];
        }
        
        // محافظت
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای از کدوم یکی از اعضای دسته محافظت کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // فقط از تیم خودش می‌تونه محافظت کنه
        if (!$this->isWolfTeam($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ فقط می‌تونی از اعضای دسته‌ی گرگ‌ها محافظت کنی!'
            ];
        }
        
        $this->guarding = $target;
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت می‌کنی!",
            'guarding' => $target
        ];
    }
    
    public function onAttackTeammate($targetId, $attackerRole) {
        if ($this->guarding != $targetId) {
            return ['protected' => false];
        }
        
        // محافظت موفق
        $threats = ['killer', 'archer', 'knight', 'vampire', 'bloodthirsty', 'firefighter', 'ice_queen'];
        
        if (!in_array($attackerRole, $threats)) {
            return ['protected' => false];
        }
        
        $target = $this->getPlayerById($targetId);
        $this->sendMessageToPlayer($targetId, "🛡️ دیشب گرگ سفید تونست تو رو از خطر مرگ نجات بده!");
        
        return [
            'protected' => true,
            'message' => "🌩 گرگ سفید از {$target['name']} محافظت کرد!"
        ];
    }
    
    private function isLastWolf() {
        $wolves = $this->getWolfTeam();
        $aliveWolves = 0;
        foreach ($wolves as $wolf) {
            if ($wolf['alive'] && $wolf['id'] != $this->getId()) {
                $aliveWolves++;
            }
        }
        return $aliveWolves == 0;
    }
    
    private function performAttack($target) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        return [
            'success' => true,
            'message' => "🐺 (آخرین گرگ) نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        
        // اگر آخرین گرگه، همه رو نشون بده
        if ($this->isLastWolf()) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'white_wolf_' . $p['id']
                ];
            }
            return $targets;
        }
        
        // فقط اعضای تیم
        foreach ($this->getAllPlayers() as $p) {
            if ($p['id'] != $this->getId() && $p['alive'] && $this->isWolfTeam($p['role'])) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'white_wolf_guard_' . $p['id']
                ];
            }
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}