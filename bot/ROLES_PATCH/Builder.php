<?php
/**
 * 👷🏻‍♂️ بنا (فراماسون)
 */

require_once __DIR__ . '/base.php';

class Builder extends Role {
    
    public function getName() {
        return 'بنا';
    }
    
    public function getEmoji() {
        return '👷🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $masons = $this->getMasonTeamList();
        if (!empty($masons)) {
            return "تو بنا 👷🏻‍♂️ هستی. بناهای دیگه رو می‌شناسی:\n" . implode(', ', $masons);
        }
        return "تو بنا 👷🏻‍♂️ هستی. متاسفانه بناهای دیگه‌ای در این بازی نیستی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        $masons = $this->getMasonTeamList();
        if (!empty($masons)) {
            $this->sendMessage("👷🏻‍♂️ <b>بناهای روستا:</b>\n" . implode("\n", $masons));
        } else {
            $this->sendMessage("👷🏻‍♂️ متاسفانه بناهای دیگه‌ای در این بازی نیستی!");
        }
    }
    
    public function onPlayerDeath($deadPlayer) {
        // اگر بنا بمیره، به بناهای دیگه اطلاع بده
        if ($deadPlayer['role'] == 'builder' || $deadPlayer['role'] == 'mason') {
            $this->notifyOtherMasons("⚠️ بنا {$deadPlayer['name']} مرد!");
        }
    }
    
    private function getMasonTeamList() {
        $masons = [];
        foreach ($this->game['players'] as $p) {
            if ($p['id'] != $this->playerId && 
                in_array($p['role'], ['builder', 'mason']) && 
                isset($p['alive']) && $p['alive'] === true) {
                $masons[] = $p['name'];
            }
        }
        return $masons;
    }
    
    private function notifyOtherMasons($message) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] != $this->playerId && 
                in_array($p['role'], ['builder', 'mason']) && 
                isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], "👷🏻‍♂️ $message");
            }
        }
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}