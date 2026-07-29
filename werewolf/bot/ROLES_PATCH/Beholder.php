<?php
/**
 * 👁️ شاهد
 */

require_once __DIR__ . '/base.php';

class Beholder extends Role {
    
    public function getName() {
        return 'شاهد';
    }
    
    public function getEmoji() {
        return '👁️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $seer = $this->getSeerName();
        if ($seer) {
            return "تو شاهد 👁️ هستی. در ابتدای بازی می‌دانی که پیشگوی واقعی چه کسی هست. پیشگو: $seer";
        }
        return "تو شاهد 👁️ هستی. متاسفانه در این بازی پیشگویی وجود ندارد!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        $seer = $this->getSeerName();
        if ($seer) {
            $this->sendMessage("👁️ <b>پیشگوی واقعی این بازی:</b> $seer");
        } else {
            $this->sendMessage("👁️ توی این بازی کسی پیشگو نیست!");
        }
    }
    
    private function getSeerName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'seer' && isset($p['alive']) && $p['alive'] === true) {
                return $p['name'];
            }
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}