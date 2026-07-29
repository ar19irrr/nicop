<?php
/**
 * 🃏 احمق (Fool)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Fool extends Role {
    
    public function getName() {
        return 'احمق';
    }
    
    public function getEmoji() {
        return '🃏';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "🃏 تو فکر می‌کنی پیشگو 👳🏻‍♂️ هستی! هر شب نقش یک نفر رو می‌بینی (اما نتیجه‌هات اشتباهه!)";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function onGameStart() {
        $this->sendMessage("👳🏻‍♂️ تو پیشگو هستی! هر شب می‌تونی نقش یک نفر رو ببینی.");
    }
    
    public function performNightAction($target = null) {
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
        
        $this->logAction('see', $target);
        
        // نتیجه تصادفی (اشتباه)
        $fakeResult = $this->getRandomFakeRole();
        $roleName = getRoleDisplayName($fakeResult);
        
        return [
            'success' => true,
            'message' => "👳🏻‍♂️ تو دیدی که {$targetPlayer['name']} یک {$roleName} هست!",
            'seen_role' => $fakeResult
        ];
    }
    
    private function getRandomFakeRole() {
        $fakeRoles = ['werewolf', 'villager', 'seer', 'cultist', 'serial_killer', 
                      'vampire', 'joker', 'tanner', 'hunter', 'guardian_angel'];
        return $fakeRoles[array_rand($fakeRoles)];
    }
    
    public function onDeath() {
        $this->sendMessage("💀 تو واقعاً 🃏 احمق بودی، نه پیشگو!");
        $this->sendMessageToGroup("💀 {$this->getPlayerName()} یه 🃏 احمق بود!");
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'seer_' . $p['id']
            ];
        }
        return $targets;
    }
}