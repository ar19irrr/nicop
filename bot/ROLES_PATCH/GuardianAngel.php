<?php
/**
 * 👼🏻 فرشته نگهبان (GuardianAngel)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class GuardianAngel extends Role {
    
    public function getName() {
        return 'فرشته نگهبان';
    }
    
    public function getEmoji() {
        return '👼🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $converted = $this->getData('converted_to_villager') ?? false;
        
        if ($converted) {
            return "👼🏻 تو با لوسیفر برخورد کردی و الان روستایی ساده هستی!";
        }
        return "👼🏻 تو فرشته نگهبان هستی! هر شب از یک نفر محافظت می‌کنی. اگه اون گرگ باشه، ۵۰٪ می‌میری! اگه لوسیفر باشه، جفتتون روستایی ساده می‌شید!";
    }
    
    public function hasNightAction() {
        return !($this->getData('converted_to_villager') ?? false);
    }
    
    public function performNightAction($target = null) {
        $converted = $this->getData('converted_to_villager') ?? false;
        
        if ($converted) {
            return [
                'success' => false,
                'message' => '❌ دیگه فرشته نیستی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی!'
            ];
        }
        
        // نمی‌تونه دو شب پیاپی از یکی محافظت کنه
        $lastGuarded = $this->getData('last_guarded');
        if ($target == $lastGuarded) {
            return [
                'success' => false,
                'message' => '⚠️ نمی‌تونی دو شب پیاپی از یک نفر محافظت کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->setData('last_guarded', $target);
        $this->setData('guarding', $target);
        
        // اگه لوسیفر باشه، جفتشون روستایی ساده می‌شن
        if ($targetPlayer['role'] == 'lucifer') {
            return $this->convertBothToVillager($targetPlayer);
        }
        
        // اگه گرگ باشه، ۵۰٪ مرگ
        if ($this->isWolf($targetPlayer['role'])) {
            if (rand(1, 100) <= 50) {
                $this->killPlayer($this->getId(), 'guardian_wolf');
                return [
                    'success' => false,
                    'message' => "😇 {$targetPlayer['name']} گرگ بود و تورو خورد!",
                    'died' => true
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => "🛡️ امشب از {$targetPlayer['name']} محافظت کردی!",
            'guarding' => $target
        ];
    }
    
    private function convertBothToVillager($luciferPlayer) {
        $this->setData('converted_to_villager', true);
        
        $this->setPlayerRole($this->getId(), 'villager');
        $this->setPlayerRole($luciferPlayer['id'], 'villager');
        
        $this->sendMessage("😇 با لوسیفر برخورد کردی! جفتتون روستایی ساده شدید!");
        $this->sendMessageToPlayer($luciferPlayer['id'], "👹 فرشته با تو برخورد کرد! جفتتون روستایی ساده شدید!");
        $this->sendMessageToGroup("✨ فرشته و لوسیفر با هم برخورد کردن! هر دوتاشون روستایی ساده شدند!");
        
        return [
            'success' => true,
            'message' => "✨ جفتتون روستایی ساده شدید!",
            'converted' => true
        ];
    }
    
    public function onAttackTarget($targetId, $attackerRole = null) {
        $guarding = $this->getData('guarding');
        $converted = $this->getData('converted_to_villager') ?? false;
        
        if ($converted || $guarding != $targetId) {
            return ['protected' => false];
        }
        
        $target = $this->getPlayerById($targetId);
        
        // قاتل - فرشته کاری نمی‌تونه بکنه
        if ($attackerRole == 'serial_killer') {
            return ['protected' => false, 'killer_dominance' => true];
        }
        
        $this->sendMessageToPlayer($targetId, "🛡️ فرشته نگهبان جونت رو نجات داد!");
        
        return ['protected' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $converted = $this->getData('converted_to_villager') ?? false;
        if ($converted) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'guardian_' . $p['id']
            ];
        }
        return $targets;
    }
}