<?php
/**
 * 🖕🏿 خائن
 */

require_once __DIR__ . '/base.php';

class Traitor extends Role {
    
    private $transformed = false;
    
    public function getName() {
        return 'خائن';
    }
    
    public function getEmoji() {
        return '🖕🏿';
    }
    
    public function getTeam() {
        return $this->transformed ? 'werewolf' : 'villager';
    }
    
    public function getDescription() {
        return "تو خائن🖕🏿 هستی. در ابتدا جزء روستاییا هستی. ولی اگر همه گرگ‌ها بمیرن، تبدیل به یه گرگینه میشی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onPlayerDeath($deadPlayer) {
        // چک کردن اینکه آیا همه گرگ‌ها مردن
        if ($this->isWerewolf($deadPlayer)) {
            $this->checkAllWolvesDead();
        }
    }
    
    private function checkAllWolvesDead() {
        $wolvesAlive = false;
        foreach ($this->game['players'] as $p) {
            if ($this->isWerewolf($p) && ($p['alive'] ?? false)) {
                $wolvesAlive = true;
                break;
            }
        }
        
        // اگه همه گرگ‌ها مردن و هنوز تبدیل نشده
        if (!$wolvesAlive && !$this->transformed && ($this->player['alive'] ?? false)) {
            $this->transformToWerewolf();
        }
    }
    
    private function transformToWerewolf() {
        $this->transformed = true;
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role'] = 'werewolf';
                $p['role_data']['was_traitor'] = true;
                break;
            }
        }
        
        saveGame($this->game);
        
        $this->sendMessage(
            "🐺 ای خائن! چون همه گرگ‌ها مردن، الان تو تبدیل به یه گرگینه شدی!"
        );
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}