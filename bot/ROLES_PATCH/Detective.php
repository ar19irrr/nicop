<?php
/**
 * 🕵🏻‍♂️ کاراگاه (Detective)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Detective extends Role {
    
    public function getName() {
        return 'کاراگاه';
    }
    
    public function getEmoji() {
        return '🕵🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو کاراگاه 🕵🏻‍♂️ هستی! هر روز می‌تونی در مورد یک نفر تحقیق کنی. ۴۰٪ احتمال داره که گرگ‌ها متوجه تحقیقاتت بشن!";
    }
    
    public function hasDayAction() {
        return true;
    }
    
    public function performDayAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو می‌خوای تحقیق کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $targetRole = $targetPlayer['role'];
        $investigated = $this->getData('investigated') ?? [];
        $investigated[] = $target;
        $this->setData('investigated', $investigated);
        
        // ۴۰٪ احتمال شناسایی توسط گرگ‌ها
        $discovered = rand(1, 100) <= 40;
        if ($discovered) {
            $this->notifyWolfTeam("🐺 کاراگاه داره تحقیق می‌کنه! {$this->getPlayerName()} رو شناسایی کردیم!");
        }
        
        // بررسی طلسم عجوزه
        $displayRole = $targetRole;
        if (isset($targetPlayer['role_data']['cursed_by_honey']) && $targetPlayer['role_data']['cursed_by_honey']) {
            $displayRole = 'werewolf';
        } elseif ($targetRole == 'lycan') {
            $displayRole = 'prince';
        } elseif ($targetRole == 'wolf_man') {
            $displayRole = 'werewolf';
        }
        
        $roleName = getRoleDisplayName($displayRole);
        
        return [
            'success' => true,
            'message' => "🕵🏻‍♂️ {$targetPlayer['name']} یک {$roleName} هست!" . ($discovered ? "\n\n⚠️ گرگ‌ها متوجه تحقیقاتت شدن!" : ""),
            'investigated_role' => $displayRole,
            'discovered' => $discovered
        ];
    }
    
    public function getValidTargets($phase = 'day') {
        if ($phase != 'day') return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'detective_' . $p['id']
            ];
        }
        return $targets;
    }
}