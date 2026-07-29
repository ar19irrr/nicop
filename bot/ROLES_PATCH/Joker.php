<?php
/**
 * 🤡 جوکر (Joker)
 * تیم: جوکر (Joker Team)
 */

require_once __DIR__ . '/base.php';

class Joker extends Role {
    
    public function getName() {
        return 'جوکر';
    }
    
    public function getEmoji() {
        return '🤡';
    }
    
    public function getTeam() {
        return 'joker';
    }
    
    public function getDescription() {
        $scrollsFound = $this->getData('scrolls_found') ?? 0;
        $scrollsNeeded = 3;
        $harlyName = $this->getHarlyName();
        
        return "🤡 تو جوکر هستی! برای ساخت بمب به ۳ کتیبه نیاز داری. ({$scrollsFound}/{$scrollsNeeded}) هارلی کویین ({$harlyName}) ازت محافظت می‌کنه!";
    }
    
    public function hasNightAction() {
        return ($this->getData('scrolls_found') ?? 0) < 3;
    }
    
    public function performNightAction($target = null) {
        $scrollsFound = $this->getData('scrolls_found') ?? 0;
        
        if ($scrollsFound >= 3) {
            return [
                'success' => false,
                'message' => '✅ همه کتیبه‌ها رو پیدا کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ خونه کی رو بگردی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $harlyDead = $this->getData('harly_dead') ?? false;
        $canKill = $this->getData('can_kill') ?? false;
        
        // اگر هارلی مرده، جوکر می‌تونه بکشه
        if ($harlyDead && $canKill && $this->hasKillAbility($targetPlayer['role'])) {
            $this->killPlayer($target, 'joker');
            $this->sendMessageToGroup("💥 جوکر {$targetPlayer['name']} رو کشت!");
            
            return [
                'success' => true,
                'message' => "🔫 {$targetPlayer['name']} رو کشتی!",
                'killed' => $target
            ];
        }
        
        // جستجوی کتیبه
        if (rand(1, 100) <= 33) {
            $scrollsFound++;
            $this->setData('scrolls_found', $scrollsFound);
            
            if ($scrollsFound >= 3) {
                $this->detonateBomb();
            }
            
            return [
                'success' => true,
                'message' => "📜 کتیبه پیدا کردی! ({$scrollsFound}/3)",
                'found' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 توی خونه {$targetPlayer['name']} چیزی پیدا نکردی!",
            'found' => false
        ];
    }
    
    private function detonateBomb() {
        $this->sendMessageToGroup("💣💥 جوکر بمب رو منفجر کرد!");
        
        foreach ($this->getAllPlayers() as $player) {
            if ($player['id'] != $this->playerId && isset($player['alive']) && $player['alive'] === true) {
                $this->killPlayer($player['id'], 'joker_bomb');
            }
        }
        
        $this->declareWinners(['joker']);
    }
    
    public function onHarlyDeath() {
        $this->setData('harly_dead', true);
        $this->setData('can_kill', true);
        $this->sendMessage("💔 هارلی مرد! از امشب می‌تونی بکشی!");
    }
    
    public function setHarlyId($id) {
        $this->setData('harly_id', $id);
    }
    
    private function getHarlyName() {
        $harlyId = $this->getData('harly_id');
        if ($harlyId) {
            $harly = $this->getPlayerById($harlyId);
            return $harly ? $harly['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    private function hasKillAbility($role) {
        $killers = ['werewolf', 'alpha_wolf', 'serial_killer', 'vampire', 'bloodthirsty', 'archer', 'knight'];
        return in_array($role, $killers);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'joker_' . $p['id']
            ];
        }
        return $targets;
    }
}