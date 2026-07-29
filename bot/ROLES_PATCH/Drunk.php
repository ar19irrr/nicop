<?php
/**
 * 🍻 مست (Drunk)
 * تیم: روستا
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
        return "تو مست 🍻 هستی. مثل یه روستایی ساده هستی ولی اگه گرگ‌ها بخورنت، مسموم میشن و شب بعد نمی‌تونن حمله کنن!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        $this->poisonWolves();
        
        return [
            'died' => true,
            'poisoned_wolves' => true,
            'message' => "🍻 گرگ‌ها خوردنت و مسموم شدن! فردا شب نمی‌تونن حمله کنن!"
        ];
    }
    
    private function poisonWolves() {
        $this->setGameState('poisoned_night', ($this->game['night_count'] ?? 1) + 1);
        
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], "🤢 مست رو خوردیم و مسموم شدیم! فردا شب نمی‌تونیم حمله کنیم!");
            }
        }
    }
    
    public static function isPoisonedNight($game) {
        $poisonedNight = $game['state']['poisoned_night'] ?? 0;
        $currentNight = $game['night_count'] ?? 1;
        return $poisonedNight == $currentNight;
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}