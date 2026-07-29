<?php
/**
 * 👶🏻 بچه وحشی (WildChild)
 * تیم: روستا (تبدیل به گرگ)
 */

require_once __DIR__ . '/base.php';

class WildChild extends Role {
    
    public function getName() {
        return 'بچه وحشی';
    }
    
    public function getEmoji() {
        return '👶🏻';
    }
    
    public function getTeam() {
        $transformed = $this->getData('transformed') ?? false;
        return $transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        $transformed = $this->getData('transformed') ?? false;
        $roleModel = $this->getData('role_model');
        
        if ($transformed) {
            return "👶🏻 تو بچه وحشی بودی و تبدیل به گرگ شدی!";
        }
        
        if ($roleModel) {
            $model = $this->getPlayerById($roleModel);
            $modelName = $model ? $model['name'] : 'نامشخص';
            return "👶🏻 تو بچه وحشی هستی! الگویت {$modelName} است. اگر بمیره، تبدیل به گرگ می‌شی!";
        }
        
        return "👶🏻 تو بچه وحشی هستی! یک نفر رو به عنوان الگو انتخاب کن!";
    }
    
    public function hasNightAction() {
        return $this->getData('role_model') === null;
    }
    
    public function onGameStart() {
        if ($this->getData('role_model') === null) {
            $this->sendMessage("👶🏻 الگوت رو انتخاب کن! اگر بمیره، تبدیل به گرگ می‌شی!");
        }
    }
    
    public function performNightAction($target = null) {
        $roleModel = $this->getData('role_model');
        
        if ($roleModel !== null) {
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
        
        $this->setData('role_model', $target);
        
        return [
            'success' => true,
            'message' => "👶🏻 {$targetPlayer['name']} رو به عنوان الگوت انتخاب کردی!",
            'role_model' => $target
        ];
    }
    
    public function onPlayerDeath($deadPlayer) {
        $roleModel = $this->getData('role_model');
        $transformed = $this->getData('transformed') ?? false;
        
        if ($deadPlayer['id'] == $roleModel && !$transformed) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->setData('transformed', true);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_wild_child'] = true;
                break;
            }
        }
        $this->saveGame();
        
        $this->sendMessage("🐺 الگوت مرد! تبدیل به گرگ شدی!");
        $this->introduceToWolves();
    }
    
    private function introduceToWolves() {
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true && $p['id'] != $this->playerId) {
                $this->sendMessageToPlayer($p['id'], "👶🏻 بچه وحشی به تیم گرگ‌ها پیوست!");
            }
        }
    }
    
    public function getValidTargets($phase = 'night') {
        $roleModel = $this->getData('role_model');
        if ($roleModel !== null) return [];
        
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
}