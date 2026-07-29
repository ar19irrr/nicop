<?php
/**
 * 👰🏻 دلبر (Sweetheart)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Sweetheart extends Role {
    
    public function getName() {
        return 'دلبر';
    }
    
    public function getEmoji() {
        return '👰🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $isLover = $this->getData('is_lover') ?? false;
        
        if ($isLover) {
            $loverName = $this->getLoverName();
            return "👰🏻 تو دلبر هستی و عاشق {$loverName} شدی! اگر اون بمیره، تو هم می‌میری!";
        }
        return "👰🏻 تو دلبر هستی! هر کسی شب به خونت بیاد، عاشقت می‌شه و نمی‌کشتت!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onVisitor($visitorId, $visitorRole) {
        $isLover = $this->getData('is_lover') ?? false;
        
        if ($isLover) return null;
        
        $visitor = $this->getPlayerById($visitorId);
        if (!$visitor) return null;
        
        $this->setData('is_lover', true);
        $this->setData('lover_id', $visitorId);
        
        $this->sendMessage("💖 عاشق {$visitor['name']} شدی!");
        $this->sendMessageToPlayer($visitorId, "💖 عاشق {$this->getPlayerName()} شدی!");
        
        if ($this->isWolf($visitorRole)) {
            $this->notifyWolfTeam("🐺❤️ یکی از گرگ‌ها عاشق دلبر شد!");
        }
        
        return ['cancel_action' => true];
    }
    
    public function onLoverDeath() {
        $loverId = $this->getData('lover_id');
        $isLover = $this->getData('is_lover') ?? false;
        
        if (!$isLover || !$loverId) return null;
        
        $lover = $this->getPlayerById($loverId);
        if ($lover && isset($lover['alive']) && $lover['alive'] === false) {
            $this->killPlayer($this->getId(), 'sweetheart_suicide');
            $this->sendMessageToGroup("💔 {$this->getPlayerName()} از غم مرگ معشوقش خودکشی کرد!");
        }
    }
    
    private function getLoverName() {
        $loverId = $this->getData('lover_id');
        if ($loverId) {
            $lover = $this->getPlayerById($loverId);
            return $lover ? $lover['name'] : 'نامشخص';
        }
        return 'نامشخص';
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}