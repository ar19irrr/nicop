<?php
/**
 * 💣 بمب‌گذار (Bomber)
 */

require_once __DIR__ . '/base.php';

class Bomber extends Role {
    
    public function getName() {
        return 'بمب‌گذار';
    }
    
    public function getEmoji() {
        return '💣';
    }
    
    public function getTeam() {
        return 'independent';
    }
    
    public function getDescription() {
        $bombsPlanted = $this->getData('bombs_planted') ?? 0;
        $bombsNeeded = $this->getData('bombs_needed') ?? 5;
        return "تو بمب‌گذار 💣 هستی! هر شب می‌تونی توی خونه ۱ نفر بمب بذاری. وقتی تعداد بمب‌ها به {$bombsNeeded} رسید، کل روستا می‌ره رو هوا و تو برنده می‌شی!\n\n💣 بمب‌ها: {$bombsPlanted}/{$bombsNeeded}";
    }
    
    public function hasNightAction() {
        $bombsPlanted = $this->getData('bombs_planted') ?? 0;
        $bombsNeeded = $this->getData('bombs_needed') ?? 5;
        return $bombsPlanted < $bombsNeeded;
    }
    
    public function performNightAction($target = null) {
        $bombsPlanted = $this->getData('bombs_planted') ?? 0;
        $bombsNeeded = $this->getData('bombs_needed') ?? 5;
        
        if ($bombsPlanted >= $bombsNeeded) {
            return [
                'success' => false,
                'message' => '✅ همه بمب‌ها کار گذاشته شده!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => "❌ خونه کی بمب بذاریم؟ ({$bombsPlanted}/{$bombsNeeded})"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $bombsPlanted++;
        $this->setData('bombs_planted', $bombsPlanted);
        
        $this->sendMessageToPlayer($target, "⚠️ خونه‌ات بمب گذاری شده! اگه به کسی بگی بازیو بهم می‌زنم!");
        
        if ($bombsPlanted >= $bombsNeeded) {
            $this->detonate();
        }
        
        return [
            'success' => true,
            'message' => "💣 بمب توی خونه {$targetPlayer['name']} کار گذاشته شد! ({$bombsPlanted}/{$bombsNeeded})",
            'planted' => true
        ];
    }
    
    private function detonate() {
        $this->sendMessageToGroup("💥💣 بمب‌گذار برنده شد!");
        
        foreach ($this->getAllPlayers() as $player) {
            if ($player['id'] != $this->playerId && isset($player['alive']) && $player['alive'] === true) {
                $this->killPlayer($player['id'], 'bomber_explosion');
            }
        }
    }
    
    public function getValidTargets($phase = 'night') {
        $bombsPlanted = $this->getData('bombs_planted') ?? 0;
        $bombsNeeded = $this->getData('bombs_needed') ?? 5;
        
        if ($bombsPlanted >= $bombsNeeded) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'bomber_' . $p['id']
            ];
        }
        return $targets;
    }
}