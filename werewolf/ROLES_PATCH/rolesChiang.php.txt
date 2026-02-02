<?php
/**
 * 👩‍🦳 چیانگ (Chiang)
 * تیم: ومپایر (Vampire)
 */

require_once __DIR__ . '/base.php';

class Chiang extends Role {
    
    protected $bloodthirstyId = null;   // آیدی ومپایر اصیل
    protected $bloodthirstyDead = false; // آیا اصیل مرده؟
    protected $canAttack = false;        // آیا می‌تونه حمله کنه؟
    
    public function getName() {
        return 'چیانگ';
    }
    
    public function getEmoji() {
        return '👩‍🦳';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        if (!$this->bloodthirstyDead) {
            return "تو چیانگ 👩‍🦳 هستی! قبل از اینکه ومپایر اصیل بمیره، هر شب اسم یکی از نقشای منفی رو بهت می‌گم! بعد از مرگ اصیل، تو هم می‌تونی با بقیه ومپایرها به خوردن ادامه بدی!";
        }
        return "تو چیانگ 👩‍🦳 هستی! ومپایر اصیل مرده و الان می‌تونی با بقیه ومپایرها حمله کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        // اگر اصیل مرده، مثل ومپایر عادی عمل می‌کنه
        if ($this->bloodthirstyDead) {
            return $this->performVampireAttack($target);
        }
        
        // قبل از مرگ اصیل، فقط اطلاعات می‌گیره
        $negativeRoles = $this->findNegativeRoles();
        
        if (empty($negativeRoles)) {
            return [
                'success' => true,
                'message' => "🔍 متاسفانه امشب نتونستم منفی‌ها رو پیدا کنم!",
                'found' => false
            ];
        }
        
        $found = $negativeRoles[array_rand($negativeRoles)];
        
        return [
            'success' => true,
            'message' => "👁️ تو فهمیدی که {$found['name']} نقش منفی داره! اما نمی‌تونم بهت بگم دقیقاً نقشش چیه!",
            'found' => true,
            'player' => $found['id']
        ];
    }
    
    private function performVampireAttack($target) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای به کی حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // ۳۰٪ کشتن
        $rand = rand(1, 100);
        if ($rand <= 30) {
            $this->killPlayer($target, 'chiang');
            return [
                'success' => true,
                'message' => "🩸 به {$targetPlayer['name']} حمله کردی و کشتیش!",
                'killed' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🩸 به {$targetPlayer['name']} حمله کردی ولی بعد از نوشیدن خونش ولش کردی!",
            'spared' => $target
        ];
    }
    
    public function onBloodthirstyDeath() {
        $this->bloodthirstyDead = true;
        $this->canAttack = true;
        
        $team = $this->getVampireTeam();
        $this->sendMessageToPlayer($this->getId(), "🔓 خب مثل اینکه ومپایر اصیل مرده! حالا تو می‌تونی با بقیه ومپایرها به خوردن بری!\nهم‌تیمی‌هات: {$team}");
        $this->notifyVampireTeam("👩‍🦳 چون ومپایر اصیل مرده، الان چیانگ با ما می‌تونه حمله کنه!");
    }
    
    private function findNegativeRoles() {
        $negativeRoles = [];
        $allPlayers = $this->getAllPlayers();
        
        foreach ($allPlayers as $player) {
            if (!$player['alive']) continue;
            
            $role = $player['role'];
            // نقش‌های منفی (غیر از تیم ومپایر)
            if (in_array($role, ['werewolf', 'alpha_wolf', 'killer', 'firefighter', 'ice_queen', 'bomber', 'dinamit'])) {
                $negativeRoles[] = $player;
            }
        }
        
        return $negativeRoles;
    }
    
    private function getVampireTeam() {
        $team = [];
        $allPlayers = $this->getAllPlayers();
        foreach ($allPlayers as $p) {
            if (in_array($p['role'], ['vampire', 'chiang', 'kent_vampire']) && $p['alive']) {
                $team[] = $p['name'];
            }
        }
        return implode(', ', $team);
    }
    
    public function getValidTargets($phase = 'night') {
        if (!$this->bloodthirstyDead) {
            return []; // فقط اطلاعات می‌گیره، هدف انتخاب نمی‌کنه
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if (in_array($p['role'], ['vampire', 'bloodthirsty', 'chiang'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'chiang_' . $p['id']
            ];
        }
        return $targets;
    }
}