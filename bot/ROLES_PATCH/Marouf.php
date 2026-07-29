<?php
/**
 * 🛡️🌿 معروف (Marouf)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Marouf extends Role {
    
    public function getName() {
        return 'معروف';
    }
    
    public function getEmoji() {
        return '🛡️🌿';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "🛡️🌿 تو معروف هستی، دوست صمیمی شکارچی! نمی‌دونی کیه ولی ازش محافظت می‌کنی. دو شب اول از حملات شبانه جلوگیری می‌کنی. در رای‌گیری هم جلوی اعدامش رو می‌گیری!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        $this->findHunter();
        $this->setData('protection_left', 2);
        $this->sendMessage("🛡️🌿 تو معروف هستی! یه دوست صمیمی داری که شکارچیه، ولی نمی‌دونی کیه!");
    }
    
    private function findHunter() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'cult_hunter' && isset($p['alive']) && $p['alive'] === true) {
                $this->setData('hunter_id', $p['id']);
                break;
            }
        }
    }
    
    public function onLynchVote($targetId) {
        $hunterId = $this->getData('hunter_id');
        
        if (!$hunterId || $targetId != $hunterId) return null;
        if (!$this->isAlive()) return null;
        
        return [
            'prevent_lynch' => true,
            'message' => "🛡️🌿 معروف جلوی اعدام شکارچی رو گرفت!"
        ];
    }
    
    public function onGunnerShot($targetId) {
        $hunterId = $this->getData('hunter_id');
        
        if (!$hunterId || $targetId != $hunterId) return null;
        if (!$this->isAlive()) return null;
        
        return [
            'prevented' => true,
            'message' => "🛡️🌿 معروف جلوی تیر تفنگدار رو گرفت!"
        ];
    }
    
    public function onNightAttack($targetId, $attackerRole) {
        $hunterId = $this->getData('hunter_id');
        $protectionLeft = $this->getData('protection_left') ?? 0;
        $night = $this->getCurrentNight();
        
        if (!$hunterId || $targetId != $hunterId) return null;
        if ($night > 2) return null;
        if ($protectionLeft <= 0) return null;
        if (!$this->isEvilRole($attackerRole)) return null;
        
        $protectionLeft--;
        $this->setData('protection_left', $protectionLeft);
        
        if ($protectionLeft <= 0) {
            $this->sendMessage("⚠️ دو شب محافظت تموم شد!");
        }
        
        return [
            'prevented' => true,
            'message' => "🛡️🌿 معروف از شکارچی محافظت کرد!"
        ];
    }
    
    private function isEvilRole($role) {
        $evilRoles = ['serial_killer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan',
                      'vampire', 'bloodthirsty', 'kent_vampire', 'cultist', 'royce',
                      'black_knight', 'archer', 'joker', 'harly'];
        return in_array($role, $evilRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}