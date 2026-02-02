<?php
/**
 * 📚 ریش سفید
 */

require_once __DIR__ . '/base.php';

class WiseElder extends Role {
    
    private $survivedAttack = false;
    private $demoted = false;
    
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
        return "تو ریش سفید📚 هستی. اگه گرگا بخورنت بار اول زنده می‌مونی، ولی دفعه دوم کشته میشی. اگه یه روستایی با قابلیت خاص (مثل تفنگدار یا کلانتر) بکشتت، از شدت عذاب وجدان نقشش رو از دست میده و به روستایی ساده تبدیل میشه!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onAttackedByWerewolf($werewolfId) {
        if (!$this->survivedAttack) {
            // بار اول زنده می‌مونه
            $this->survivedAttack = true;
            
            return [
                'died' => false,
                'survived' => true,
                'message' => "📚 گرگ‌ها حمله کردن ولی تونستی جلوی ورودشون رو بگیری! بار اول زنده موندی!"
            ];
        } else {
            // بار دوم می‌میره
            return [
                'died' => true,
                'message' => "📚 گرگ‌ها دوباره حمله کردن و اینبار تونستن بکشتنت!"
            ];
        }
    }
    
    public function onAttackedByGunner($gunnerId) {
        // تفنگدار رو تنزل بده
        $this->demoteGunner($gunnerId);
        
        return [
            'died' => true,
            'demoted_attacker' => true,
            'message' => "📚 تفنگدار کشتت ولی از عذاب وجدان تفنگش رو انداخت و به روستایی ساده تبدیل شد!"
        ];
    }
    
    public function onAttackedByHunter($hunterId) {
        // کلانتر رو تنزل بده
        $this->demoteHunter($hunterId);
        
        return [
            'died' => true,
            'demoted_attacker' => true,
            'message' => "📚 کلانتر کشتت ولی از عذاب وجدان تفنگش رو انداخت و به روستایی ساده تبدیل شد!"
        ];
    }
    
    private function demoteGunner($gunnerId) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $gunnerId) {
                $p['role'] = 'villager';
                $p['role_data']['demoted'] = true;
                sendPrivateMessage($gunnerId, 
                    "😰 ریش سفید رو کشتی و از عذاب وجدان تفنگت رو انداختی! الان یه روستایی ساده‌ای!"
                );
                break;
            }
        }
        saveGame($this->game);
    }
    
    private function demoteHunter($hunterId) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $hunterId) {
                $p['role'] = 'villager';
                $p['role_data']['demoted'] = true;
                sendPrivateMessage($hunterId, 
                    "😰 ریش سفید رو کشتی و از عذاب وجدان تفنگت رو انداختی! الان یه روستایی ساده‌ای!"
                );
                break;
            }
        }
        saveGame($this->game);
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}