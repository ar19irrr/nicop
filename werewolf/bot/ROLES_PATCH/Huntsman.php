<?php
/**
 * 🪓 هانتسمن (Huntsman)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Huntsman extends Role {
    
    public function getName() {
        return 'هانتسمن';
    }
    
    public function getEmoji() {
        return '🪓';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $traps = $this->getData('traps') ?? 2;
        $isHunter = $this->getData('is_hunter') ?? false;
        
        if ($isHunter) {
            return "🪓 تو شکارچی جدید روستا هستی!";
        }
        return "🪓 تو هانتسمن هستی! هر شب می‌تونی جلوی خونه روستایی‌ها تله بزاری ({$traps} تا تله). اگر نقش شب‌کار بیاد، ۵۰٪ گیر می‌کنه و می‌میره!";
    }
    
    public function hasNightAction() {
        $isHunter = $this->getData('is_hunter') ?? false;
        $traps = $this->getData('traps') ?? 0;
        return !$isHunter && $traps > 0;
    }
    
    public function performNightAction($target = null) {
        $isHunter = $this->getData('is_hunter') ?? false;
        $traps = $this->getData('traps') ?? 0;
        
        if ($isHunter) {
            return [
                'success' => false,
                'message' => '❌ الان شکارچی شدی!'
            ];
        }
        
        if ($traps <= 0) {
            return [
                'success' => false,
                'message' => '❌ تله‌ات تموم شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => "❌ جلو خونه کی تله بذاریم؟ ({$traps} تا تله)"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $traps--;
        $this->setData('traps', $traps);
        
        $placedTraps = $this->getData('placed_traps') ?? [];
        $placedTraps[$target] = true;
        $this->setData('placed_traps', $placedTraps);
        
        return [
            'success' => true,
            'message' => "🕳️ جلوی خونه {$targetPlayer['name']} تله گذاشتی!",
            'trap_placed' => $target
        ];
    }
    
    public function onVisitor($visitorId, $visitorRole) {
        $placedTraps = $this->getData('placed_traps') ?? [];
        
        if (!isset($placedTraps[$visitorId])) {
            return null;
        }
        
        if (rand(1, 100) > 50) {
            return null; // فرار کرد
        }
        
        $visitor = $this->getPlayerById($visitorId);
        $this->killPlayer($visitorId, 'huntsman_trap');
        
        $this->sendMessageToGroup("🪓 {$visitor['name']} توی تله هانتسمن گیر کرد و کشته شد!");
        
        return ['cancelled' => true, 'killed' => true];
    }
    
    public function becomeHunter() {
        $this->setData('is_hunter', true);
        $this->sendMessage("🏹 شکارچی جدید روستا شدی!");
    }
    
    public function getValidTargets($phase = 'night') {
        $isHunter = $this->getData('is_hunter') ?? false;
        $traps = $this->getData('traps') ?? 0;
        
        if ($isHunter || $traps <= 0) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'huntsman_' . $p['id']
            ];
        }
        return $targets;
    }
}