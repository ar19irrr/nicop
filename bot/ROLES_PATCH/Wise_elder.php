<?php
/**
 * 📚 ریش سفید (WiseElder)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class WiseElder extends Role {
    
    public function getName() {
        return 'ریش سفید';
    }
    
    public function getEmoji() {
        return '📚';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $survivedAttack = $this->getData('survived_attack') ?? false;
        
        if ($survivedAttack) {
            return "📚 تو ریش سفید هستی! یک بار از حمله گرگ‌ها جان سالم به در بردی، ولی دفعه دوم می‌میری!";
        }
        return "📚 تو ریش سفید هستی! یک بار می‌تونی از حمله گرگ‌ها جان سالم به در ببری!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        $survivedAttack = $this->getData('survived_attack') ?? false;
        
        if (!$survivedAttack) {
            $this->setData('survived_attack', true);
            $this->sendMessageToGroup("📚 ریش سفید از حمله گرگ‌ها جان سالم به در برد!");
            
            return [
                'died' => false,
                'survived' => true,
                'message' => "📚 بار اول زنده موندی!"
            ];
        }
        
        return [
            'died' => true,
            'message' => "📚 بار دوم گرگ‌ها کشتنت!"
        ];
    }
    
    public function onAttackedByGunner($gunnerId) {
        $this->demotePlayer($gunnerId, 'تفنگدار');
        return [
            'died' => true,
            'demoted_attacker' => true,
            'message' => "📚 تفنگدار کشتت و از عذاب وجدان به روستایی ساده تبدیل شد!"
        ];
    }
    
    public function onAttackedByHunter($hunterId) {
        $this->demotePlayer($hunterId, 'کلانتر');
        return [
            'died' => true,
            'demoted_attacker' => true,
            'message' => "📚 کلانتر کشتت و از عذاب وجدان به روستایی ساده تبدیل شد!"
        ];
    }
    
    private function demotePlayer($playerId, $roleName) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role'] = 'villager';
                $p['role_data']['demoted'] = true;
                $this->sendMessageToPlayer($playerId, "😰 ریش سفید رو کشتی! از عذاب وجدان به روستایی ساده تبدیل شدی!");
                break;
            }
        }
        $this->saveGame();
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}