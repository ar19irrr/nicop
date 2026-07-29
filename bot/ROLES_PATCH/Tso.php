<?php
/**
 * ⚔️ تسو (Tso)
 * تیم: مستقل
 */

require_once __DIR__ . '/base.php';

class Tso extends Role {
    
    public function getName() {
        return 'تسو';
    }
    
    public function getEmoji() {
        return '⚔️';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        $missionCompleted = $this->getData('mission_completed') ?? false;
        
        if ($missionCompleted) {
            return "⚔️ مأموریتت تموم شد! جومونگ رو کشتی!";
        }
        return "⚔️ تو تسو هستی! باید جومونگ رو پیدا کنی و بکشی! نمی‌تونی رای بدی!";
    }
    
    public function hasNightAction() {
        return !($this->getData('mission_completed') ?? false);
    }
    
    public function canVote() {
        return false;
    }
    
    public function performNightAction($target = null) {
        $missionCompleted = $this->getData('mission_completed') ?? false;
        
        if ($missionCompleted) {
            return [
                'success' => false,
                'message' => '✅ مأموریتت تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if ($targetPlayer['role'] == 'jumong') {
            $this->killPlayer($target, 'tso');
            $this->setData('mission_completed', true);
            
            $this->sendMessageToGroup("⚔️ تسو جومونگ رو پیدا کرد و کشت!");
            
            return [
                'success' => true,
                'message' => "⚔️ جومونگ رو پیدا کردی و کشتی! مأموریتت تموم شد!",
                'found_jumong' => true,
                'killed' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🗡️ رفتی خونه {$targetPlayer['name']} ولی جومونگ اونجا نبود.",
            'found_jumong' => false
        ];
    }
    
    public function onNightVisitor($visitor) {
        $this->killPlayer($visitor['id'], 'tso');
        return ['killed' => $visitor['id']];
    }
    
    public function getValidTargets($phase = 'night') {
        $missionCompleted = $this->getData('mission_completed') ?? false;
        if ($missionCompleted) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'tso_' . $p['id']
            ];
        }
        return $targets;
    }
}