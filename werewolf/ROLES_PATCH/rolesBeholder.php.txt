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
        return "تو شاهد👁️ هستی. کارت شهادت دادنه. در ابتدای بازی فقط شاهد می‌دونه که پیشگوی واقعی چه کسی هست. $seer";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onGameStart() {
        $seer = $this->getSeerName();
        if ($seer) {
            $this->sendMessage("پیشگوی واقعی این بازی: $seer");
        } else {
            $this->sendMessage("توی این بازی کسی پیشگو نیست!");
        }
    }
    
    private function getSeerName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'seer' && ($p['alive'] ?? false)) {
                return $p['name'];
            }
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}