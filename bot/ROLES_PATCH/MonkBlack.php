<?php
/**
 * 🦇 راهب سیاه (MonkBlack)
 * تیم: فرقه (Cult)
 */

require_once __DIR__ . '/base.php';

class MonkBlack extends Role {
    
    public function getName() {
        return 'راهب سیاه';
    }
    
    public function getEmoji() {
        return '🦇';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        $inviteUsed = $this->getData('invite_used') ?? 0;
        $maxInvites = 3;
        return "🦇 تو راهب سیاه هستی! هر ۲ شب یکبار می‌تونی یکی رو به فرقه دعوت کنی ({$inviteUsed}/{$maxInvites}). ⚠️ مراقب شکارچی باش!";
    }
    
    public function onGameStart() {
        $this->introduceCultTeam($this->getId());
        $this->sendMessage("🦇 تو راهب سیاه هستی! هر ۲ شب یکبار دعوت کن (۳ بار)");
    }
    
    public function hasNightAction() {
        $inviteUsed = $this->getData('invite_used') ?? 0;
        $lastInviteNight = $this->getData('last_invite_night') ?? 0;
        $currentNight = $this->getCurrentNight();
        
        return ($currentNight - $lastInviteNight) >= 2 && $inviteUsed < 3;
    }
    
    public function performNightAction($target = null) {
        $inviteUsed = $this->getData('invite_used') ?? 0;
        
        if ($inviteUsed >= 3) {
            return [
                'success' => false,
                'message' => '❌ ۳ بار استفاده کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو دعوت می‌کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if ($this->isCultRole($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ قبلاً فرقه‌ست!'
            ];
        }
        
        // شکارچی - ۳۰٪ شانس مرگ
        if ($targetPlayer['role'] == 'cult_hunter') {
            if (rand(1, 100) <= 30) {
                $this->killPlayer($this->getId(), 'cult_hunter');
                $this->notifyCultTeam("💀 راهب سیاه توسط شکارچی کشته شد!");
                return [
                    'success' => false,
                    'message' => "💀 شکارچی کشتت!",
                    'died' => true
                ];
            }
        }
        
        $inviteUsed++;
        $this->setData('invite_used', $inviteUsed);
        $this->setData('last_invite_night', $this->getCurrentNight());
        
        $hardRoles = ['serial_killer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan',
                      'vampire', 'bloodthirsty', 'joker', 'harly'];
        $isHard = in_array($targetPlayer['role'], $hardRoles);
        $successChance = $isHard ? 20 : 100;
        
        if (rand(1, 100) <= $successChance) {
            $this->convertToCult($target);
            $this->notifyCultTeam("🦇 راهب سیاه {$targetPlayer['name']} رو به فرقه دعوت کرد!");
            
            return [
                'success' => true,
                'message' => "🦇 {$targetPlayer['name']} رو به فرقه دعوت کردی! ({$inviteUsed}/3)",
                'converted' => $target
            ];
        }
        
        return [
            'success' => false,
            'message' => "🦇 نتونستی {$targetPlayer['name']} رو متقاعد کنی!",
            'invites_left' => 3 - $inviteUsed
        ];
    }
    
    private function convertToCult($playerId) {
        $this->setPlayerRole($playerId, 'cultist');
        $this->introduceCultTeam($playerId);
        $this->sendMessageToPlayer($playerId, "🦇 به فرقه پیوستی!");
    }
    
    public function getValidTargets($phase = 'night') {
        $inviteUsed = $this->getData('invite_used') ?? 0;
        $lastInviteNight = $this->getData('last_invite_night') ?? 0;
        $currentNight = $this->getCurrentNight();
        
        if (($currentNight - $lastInviteNight) < 2 || $inviteUsed >= 3) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isCultRole($p['role'])) continue;
            $isHard = in_array($p['role'], ['serial_killer', 'werewolf', 'alpha_wolf', 
                'wolf_cub', 'lycan', 'vampire', 'bloodthirsty', 'joker', 'harly']);
            $hint = $isHard ? ' (۲۰٪)' : ' (۱۰۰٪)';
            if ($p['role'] == 'cult_hunter') $hint = ' 💀⚠️';
            
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'] . $hint,
                'callback' => 'monkblack_' . $p['id']
            ];
        }
        return $targets;
    }
}