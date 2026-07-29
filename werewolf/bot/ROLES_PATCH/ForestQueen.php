<?php
/**
 * 🧝🏻‍♀️🐺 ملکه جنگل (ForestQueen)
 * تیم: گرگ‌نما
 */

require_once __DIR__ . '/base.php';

class ForestQueen extends Role {
    
    public function getName() {
        return 'ملکه جنگل';
    }
    
    public function getEmoji() {
        return '🧝🏻‍♀️🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        $isLeader = $this->getData('is_leader') ?? false;
        $alphaId = $this->getData('alpha_id');
        $alphaName = $alphaId ? ($this->getPlayerById($alphaId)['name'] ?? 'نامشخص') : 'نامشخص';
        
        if ($isLeader) {
            return "🧝🏻‍♀️🐺 تو ملکه جنگل و رهبر گرگ‌ها هستی! ۱۰٪ قدرت تبدیل داری!";
        }
        return "🧝🏻‍♀️🐺 تو ملکه جنگل هستی! معشوقه گرگ آلفا ({$alphaName}). اگه آلفا بمیره، تو رهبر می‌شی و ۱۰٪ قدرت تبدیل می‌گیری!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $isLeader = $this->getData('is_leader') ?? false;
        
        if ($isLeader && rand(1, 100) <= 10) {
            return [
                'success' => true,
                'message' => "🧝🏻‍♀️ {$targetPlayer['name']} رو آلوده کردی! فردا تبدیل به گرگ می‌شه!",
                'infected' => $target
            ];
        }
        
        $this->logAction('forest_queen_vote', $target);
        
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onAlphaDeath() {
        $this->setData('is_leader', true);
        $this->sendMessage("💔 گرگ آلفا مرد! تو رهبر جدید هستی!");
        $this->notifyWolfTeam("👑 ملکه جنگل رهبر جدید گرگ‌هاست!");
    }
    
    public function setAlphaId($id) {
        $this->setData('alpha_id', $id);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'forestqueen_' . $p['id']
            ];
        }
        return $targets;
    }
}