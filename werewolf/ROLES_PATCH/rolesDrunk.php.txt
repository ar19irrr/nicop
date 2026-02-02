<?php
/**
 * 🍻 مست
 */

require_once __DIR__ . '/base.php';

class Drunk extends Role {
    
    public function getName() {
        return 'مست';
    }
    
    public function getEmoji() {
        return '🍻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو مست🍻 هستی. یه دائم‌الخمر که هرشب مست می‌کنه. مثل یه روستایی ساده هستی ولی اگه گرگا بخورنت، مسموم میشن و شب بعدی نمی‌تونن به روستا حمله کنن!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        // گرگ‌ها مسموم میشن
        $this->poisonWolves();
        
        return [
            'died' => true,
            'poisoned_wolves' => true,
            'message' => "🍻 گرگ‌ها خوردنت و مسموم شدن! فردا شب نمی‌تونن حمله کنن!"
        ];
    }
    
    private function poisonWolves() {
        // ثبت مسمومیت برای شب بعد
        $this->game['poisoned_night'] = ($this->game['night_count'] ?? 1) + 1;
        saveGame($this->game);
        
        // اطلاع به گرگ‌ها
        foreach ($this->game['players'] as $p) {
            if ($this->isWerewolf($p)) {
                sendPrivateMessage($p['id'], 
                    "🤢 اوه اوه... مست رو خوردیم و مسموم شدیم! فردا شب نمی‌تونیم حمله کنیم!"
                );
            }
        }
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}