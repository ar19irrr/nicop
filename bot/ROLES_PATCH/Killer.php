<?php
/**
 * 🔪 قاتل (Serial Killer)
 * تیم: قاتل (Killer)
 */

require_once __DIR__ . '/base.php';

class SerialKiller extends Role {
    
    public function getName() {
        return 'قاتل';
    }
    
    public function getEmoji() {
        return '🔪';
    }
    
    public function getTeam() {
        return 'killer';
    }
    
    public function getDescription() {
        $archer = $this->getArcherName();
        return "🔪 تو قاتل روانی هستی! هر شب یکی رو می‌کشی. حتی فرشته نگهبان هم نمی‌تونه جلوت رو بگیره! فقط تله هانتسمن و اشک ققنوس می‌تونن جلوی قتل رو بگیرن. $archer";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->logAction('kill', $target);
        
        // ۱. اشک ققنوس
        if ($this->hasPhoenixTear($target)) {
            return $this->blockedByPhoenix($targetPlayer);
        }
        
        // ۲. تله هانتسمن
        if ($this->hasHuntsmanTrap($target)) {
            return $this->caughtInTrap($targetPlayer);
        }
        
        // ۳. ارجعیت به گرگ
        if ($this->isWerewolf($targetPlayer['role'])) {
            return $this->killWerewolf($targetPlayer);
        }
        
        // ۴. کشتن عادی (حتی با فرشته)
        return $this->normalKill($targetPlayer);
    }
    
    private function blockedByPhoenix($targetPlayer) {
        $this->consumePhoenixTear($targetPlayer['id']);
        $this->sendMessageToGroup("🪶 اشک ققنوس {$targetPlayer['name']} رو نجات داد!");
        
        return [
            'success' => true,
            'message' => "🔪 {$targetPlayer['name']} رو کشتی ولی اشک ققنوس نجاتش داد!",
            'killed' => false,
            'blocked_by' => 'phoenix'
        ];
    }
    
    private function caughtInTrap($targetPlayer) {
        $this->killPlayer($targetPlayer['id'], 'serial_killer');
        $this->killPlayer($this->getId(), 'huntsman');
        $this->sendMessageToGroup("🪓 قاتل توی تله هانتسمن افتاد و مرد!");
        
        return [
            'success' => true,
            'message' => "🔪 توی تله هانتسمن افتادی و مردی!",
            'killed' => true,
            'died' => true,
            'blocked_by' => 'huntsman'
        ];
    }
    
    private function killWerewolf($targetPlayer) {
        $this->killPlayer($targetPlayer['id'], 'serial_killer');
        $this->sendMessageToGroup("🔪 قاتل یک گرگ رو کشت!");
        
        return [
            'success' => true,
            'message' => "🔪 گرگ {$targetPlayer['name']} رو کشتی!",
            'killed' => true,
            'dominance' => true
        ];
    }
    
    private function normalKill($targetPlayer) {
        $this->killPlayer($targetPlayer['id'], 'serial_killer');
        $this->sendMessageToGroup("🔪 {$targetPlayer['name']} توسط قاتل کشته شد!");
        
        return [
            'success' => true,
            'message' => "🔪 {$targetPlayer['name']} رو کشتی!",
            'killed' => true
        ];
    }
    
    private function hasPhoenixTear($playerId) {
        $player = $this->getPlayerById($playerId);
        return $player && !empty($player['role_data']['phoenix_tear']);
    }
    
    private function consumePhoenixTear($playerId) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                unset($p['role_data']['phoenix_tear']);
                break;
            }
        }
        $this->saveGame();
    }
    
    private function hasHuntsmanTrap($playerId) {
        $traps = $this->getGameState('huntsman_traps') ?? [];
        return in_array($playerId, $traps) && rand(1, 100) <= 50;
    }
    
    private function isWerewolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
    
    private function getArcherName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'archer' && isset($p['alive']) && $p['alive'] === true) {
                return "\nکماندار: {$p['name']}";
            }
        }
        return '';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'killer_' . $p['id']
            ];
        }
        return $targets;
    }
}