<?php
/**
 * 🖕🏿 خائن (Traitor)
 * تیم: روستا (تبدیل به گرگ)
 */

require_once __DIR__ . '/base.php';

class Traitor extends Role {
    
    public function getName() {
        return 'خائن';
    }
    
    public function getEmoji() {
        return '🖕🏿';
    }
    
    public function getTeam() {
        $transformed = $this->getData('transformed') ?? false;
        return $transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        $transformed = $this->getData('transformed') ?? false;
        
        if ($transformed) {
            return "🖕🏿 تو خائن هستی و تبدیل به گرگ شدی!";
        }
        return "🖕🏿 تو خائن هستی! اگر همه گرگ‌ها بمیرن، تبدیل به گرگ میشی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onPlayerDeath($deadPlayer) {
        if ($this->isWolf($deadPlayer['role'])) {
            $this->checkAllWolvesDead();
        }
    }
    
    private function checkAllWolvesDead() {
        $transformed = $this->getData('transformed') ?? false;
        
        if ($transformed) return;
        
        $wolvesAlive = false;
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                $wolvesAlive = true;
                break;
            }
        }
        
        if (!$wolvesAlive && isset($this->player['alive']) && $this->player['alive'] === true) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->setData('transformed', true);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_traitor'] = true;
                break;
            }
        }
        $this->saveGame();
        
        $this->sendMessage("🐺 همه گرگ‌ها مردن! تو تبدیل به گرگینه شدی!");
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}