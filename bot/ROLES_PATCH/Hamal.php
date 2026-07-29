<?php
/**
 * 🧱 حمال (Hamal)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Hamal extends Role {
    
    public function getName() {
        return 'حمال';
    }
    
    public function getEmoji() {
        return '🧱';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $isRevealed = $this->getData('is_revealed') ?? false;
        $lastTarget = $this->getData('last_target');
        
        if ($isRevealed) {
            return "🧱 لو رفتی! دیگه نمی‌تونی بری خونه کسی.";
        }
        return "🧱 تو حمال هستی! هر شب می‌تونی بری خونه یه نفر و اونو نگه داری. اگر خونه فرقه بری، تبدیل به فرقه می‌شی!";
    }
    
    public function hasNightAction() {
        return !($this->getData('is_revealed') ?? false);
    }
    
    public function performNightAction($target = null) {
        $isRevealed = $this->getData('is_revealed') ?? false;
        $lastTarget = $this->getData('last_target');
        
        if ($isRevealed) {
            return [
                'success' => false,
                'message' => '❌ لو رفتی! دیگه نمی‌تونی بری.'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یه نفر رو انتخاب کنی!'
            ];
        }
        
        if ($target == $lastTarget) {
            return [
                'success' => false,
                'message' => '❌ نمی‌تونی دو شب پشت سر هم بری خونه یه نفر!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('last_target', $target);
        
        // فرقه - تبدیل
        if ($this->isCultRole($targetPlayer['role'])) {
            $this->convertToCult();
            return [
                'success' => true,
                'message' => "🧱 {$targetPlayer['name']} فرقه‌گرا بود! تبدیل به فرقه شدی!",
                'converted_to_cult' => true
            ];
        }
        
        // لو رفتن
        $this->setData('is_revealed', true);
        $this->sendMessageToPlayer($target, "🧱 {$this->getPlayerName()} اومد خونت! حماله!");
        
        // بلاک کردن هدف
        $this->blockTarget($target);
        
        return [
            'success' => true,
            'message' => "🧱 {$targetPlayer['name']} رو نگه داشتی!",
            'blocked' => $target,
            'revealed' => true
        ];
    }
    
    private function convertToCult() {
        $this->setPlayerRole($this->getId(), 'cultist');
        $this->notifyCultTeam();
    }
    
    private function notifyCultTeam() {
        foreach ($this->game['players'] as $p) {
            if ($this->isCultRole($p['role']) && $p['id'] != $this->playerId) {
                $this->sendMessageToPlayer($p['id'], "👤 {$this->getPlayerName()} (حمال سابق) به فرقه پیوست!");
            }
        }
    }
    
    private function blockTarget($targetId) {
        if (!isset($this->game['blocked_players'])) {
            $this->game['blocked_players'] = [];
        }
        $this->game['blocked_players'][$targetId] = [
            'by' => $this->playerId,
            'night' => $this->game['night_count'] ?? 1
        ];
        $this->saveGame();
    }
    
    public function onAttackBlocked($targetId, $attackerRole, $attackerId) {
        $lastTarget = $this->getData('last_target');
        
        if ($lastTarget != $targetId) return null;
        
        $target = $this->getPlayerById($targetId);
        $attacker = $this->getPlayerById($attackerId);
        
        $this->sendMessageToPlayer($attackerId, "🧱 حمال جلوت رو گرفت!");
        $this->sendMessage("🧱 از {$attacker['name']} جلوی حمله به {$target['name']} رو گرفتی!");
        
        return ['blocked' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->getData('is_revealed') ?? false) return [];
        
        $lastTarget = $this->getData('last_target');
        $targets = [];
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['id'] == $lastTarget) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'hamal_' . $p['id']
            ];
        }
        return $targets;
    }
}