<?php
/**
 * 👶🏻 بچه وحشی
 */

require_once __DIR__ . '/base.php';

class WildChild extends Role {
    
    private $roleModel = null;
    private $transformed = false;
    
    public function getName() {
        return 'بچه وحشی';
    }
    
    public function getEmoji() {
        return '👶🏻';
    }
    
    public function getTeam() {
        return $this->transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        $model = $this->getRoleModelName();
        return "تو بچه‌ی وحشی👶🏻 هستی. در ابتدا جزء روستاییا هستی. یه نفر رو به عنوان الگوی خودت انتخاب می‌کنی که اگر اون بمیره، تبدیل به گرگینه می‌شی! $model";
    }
    
    public function hasNightAction() {
        return $this->roleModel === null;
    }
    
    public function onGameStart() {
        // اولین شب باید الگو رو انتخاب کنه
        if ($this->roleModel === null) {
            $this->sendMessage("الگوت رو انتخاب کن! اگه بمیره، تبدیل به گرگ می‌شی!");
        }
    }
    
    public function performNightAction($target = null) {
        if ($this->roleModel !== null) {
            return [
                'success' => false,
                'message' => '❌ قبلاً الگوت رو انتخاب کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید الگوت رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->roleModel = $target;
        $this->setData('role_model', $target);
        
        return [
            'success' => true,
            'message' => "👶🏻 {$targetPlayer['name']} رو به عنوان الگوت انتخاب کردی! اگه بمیره، تبدیل به گرگ می‌شی!",
            'role_model' => $target
        ];
    }
    
    public function onPlayerDeath($deadPlayer) {
        if ($deadPlayer['id'] == $this->roleModel && !$this->transformed) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->transformed = true;
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_wild_child'] = true;
                break;
            }
        }
        
        saveGame($this->game);
        
        $this->sendMessage(
            "🐺 الگوت مرد! الان تبدیل به گرگ شدی! به دسته گرگ‌ها بپیوند!"
        );
        
        // معرفی به گرگ‌ها
        $this->introduceToWolves();
    }
    
    private function introduceToWolves() {
        $wolves = [];
        foreach ($this->game['players'] as $p) {
            if ($this->isWerewolf($p) && $p['id'] != $this->player['id']) {
                $wolves[] = $p['name'];
                sendPrivateMessage($p['id'], 
                    "👶🏻 {$this->player['name']} (بچه وحشی) الگوش مرد و تبدیل به گرگ شد!"
                );
            }
        }
        
        if (!empty($wolves)) {
            $this->sendMessage("بقیه گرگ‌ها: " . implode(', ', $wolves));
        }
    }
    
    private function getRoleModelName() {
        if ($this->roleModel) {
            $player = $this->getPlayerById($this->roleModel);
            return "الگوت: " . ($player['name'] ?? '؟');
        }
        return '';
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->roleModel === null) {
            $targets = [];
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'wildchild_' . $p['id']
                ];
            }
            return $targets;
        }
        return [];
    }
}