<?php
/**
 * ❄️👸🏻 ملکه یخی (IceQueen)
 * تیم: آتش و یخ (Fire & Ice)
 */

require_once __DIR__ . '/base.php';

class IceQueen extends Role {
    
    public function getName() {
        return 'ملکه یخی';
    }
    
    public function getEmoji() {
        return '❄️👸🏻';
    }
    
    public function getTeam() {
        return 'fire_ice';
    }
    
    public function getDescription() {
        $fireName = $this->getFireKingName();
        return "❄️👸🏻 تو ملکه یخی هستی! با پادشاه آتش ({$fireName}) هم‌تیمی هستی. هر شب می‌تونی یک نفر رو منجمد کنی. اگر دو شب متوالی منجمدش کنی، می‌میره!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❄️ امشب کی رو منجمد کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $frozenPlayers = $this->getData('frozen_players') ?? [];
        $currentNight = $this->getCurrentNight();
        
        // اگر دیشب منجمد شده بود، الان می‌میره
        if (isset($frozenPlayers[$target]) && $frozenPlayers[$target] == $currentNight - 1) {
            $this->killPlayer($target, 'ice_queen');
            unset($frozenPlayers[$target]);
            $this->setData('frozen_players', $frozenPlayers);
            
            $this->sendMessageToGroup("❄️💀 {$targetPlayer['name']} توسط ملکه یخی یخ زد و مرد!");
            
            return [
                'success' => true,
                'message' => "❄️💀 {$targetPlayer['name']} رو برای دومین شب متوالی منجمد کردی و کشتی!",
                'killed' => $target
            ];
        }
        
        // منجمد کردن
        $frozenPlayers[$target] = $currentNight;
        $this->setData('frozen_players', $frozenPlayers);
        $this->sendMessageToPlayer($target, "❄️ توسط ملکه یخی منجمد شدی!");
        
        return [
            'success' => true,
            'message' => "❄️ {$targetPlayer['name']} رو منجمد کردی!",
            'frozen' => $target
        ];
    }
    
    public function onNightEnd() {
        $frozenPlayers = $this->getData('frozen_players') ?? [];
        $currentNight = $this->getCurrentNight();
        
        foreach ($frozenPlayers as $playerId => $night) {
            if ($night == $currentNight - 1) {
                unset($frozenPlayers[$playerId]);
                $this->sendMessageToPlayer($playerId, "❄️ یخ‌هات آب شد! آزاد شدی!");
            }
        }
        $this->setData('frozen_players', $frozenPlayers);
    }
    
    private function getFireKingName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'fire_king' && isset($p['alive']) && $p['alive'] === true) {
                return $p['name'];
            }
        }
        return 'نامشخص';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        $fireKingId = $this->getData('fire_king_id');
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['id'] == $fireKingId) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'icequeen_' . $p['id']
            ];
        }
        return $targets;
    }
}