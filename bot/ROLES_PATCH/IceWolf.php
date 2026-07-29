<?php
/**
 * ☃️🐺 گرگ برفی (IceWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class IceWolf extends Role {
    
    public function getName() {
        return 'گرگ برفی';
    }
    
    public function getEmoji() {
        return '☃️🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "☃️🐺 تو گرگ برفی هستی! هر شب می‌تونی یک نفر رو منجمد کنی. کسی که منجمد شده نمی‌تونه فعالیتی داشته باشه! شب بعد نمی‌تونی دوباره منجمدش کنی!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی رو منجمد کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $frozenLastNight = $this->getData('frozen_last_night') ?? [];
        
        if (in_array($target, $frozenLastNight)) {
            return [
                'success' => false,
                'message' => "⚠️ دیشب {$targetPlayer['name']} رو منجمد کردی!"
            ];
        }
        
        $frozenPlayers = $this->getData('frozen_players') ?? [];
        $frozenPlayers[$target] = $this->getCurrentNight();
        $this->setData('frozen_players', $frozenPlayers);
        
        $frozenLastNight[] = $target;
        $this->setData('frozen_last_night', $frozenLastNight);
        
        $this->sendMessageToPlayer($target, "❄️ توسط گرگ برفی منجمد شدی!");
        
        return [
            'success' => true,
            'message' => "❄️ {$targetPlayer['name']} رو منجمد کردی!",
            'frozen' => $target
        ];
    }
    
    public function onNightEnd() {
        $this->setData('frozen_last_night', []);
    }
    
    public function isFrozen($playerId) {
        $frozenPlayers = $this->getData('frozen_players') ?? [];
        return isset($frozenPlayers[$playerId]) && $frozenPlayers[$playerId] == $this->getCurrentNight();
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        $frozenLastNight = $this->getData('frozen_last_night') ?? [];
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) continue;
            if (in_array($p['id'], $frozenLastNight)) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'icewolf_' . $p['id']
            ];
        }
        return $targets;
    }
}