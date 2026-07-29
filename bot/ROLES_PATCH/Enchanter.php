<?php
/**
 * 🧙🏻‍♂️ افسونگر (Enchanter)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Enchanter extends Role {
    
    public function getName() {
        return 'افسونگر';
    }
    
    public function getEmoji() {
        return '🧙🏻‍♂️';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو افسونگر 🧙🏻‍♂️ هستی، هم‌تیمی گرگ‌ها. هر شب می‌تونی یک نفر رو طلسم کنی. اگر گرگ‌ها بهش حمله کنن، ۳۰٪ احتمال داره به گرگ تبدیل بشه. طلسم‌ها فقط تا زمانی که تو زنده‌ای فعال هستن!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یک نفر رو انتخاب کنی تا طلسمش کنی!'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $enchantedPlayers = $this->getData('enchanted_players') ?? [];
        
        if (in_array($target, $enchantedPlayers)) {
            return [
                'success' => false,
                'message' => "⚠️ {$targetPlayer['name']} قبلاً طلسم شده!"
            ];
        }
        
        $enchantedPlayers[] = $target;
        $this->setData('enchanted_players', $enchantedPlayers);
        
        $this->sendMessageToPlayer($target, "🔮 احساس سوزش عجیبی روی بدنت داری... انگار که طلسم شدی!");
        
        return [
            'success' => true,
            'message' => "✅ {$targetPlayer['name']} رو طلسم کردی! ۳۰٪ شانس تبدیل به گرگ.",
            'enchanted' => $target
        ];
    }
    
    public function isEnchanted($playerId) {
        $enchantedPlayers = $this->getData('enchanted_players') ?? [];
        return in_array($playerId, $enchantedPlayers);
    }
    
    public function removeEnchantment($playerId) {
        $enchantedPlayers = $this->getData('enchanted_players') ?? [];
        $key = array_search($playerId, $enchantedPlayers);
        if ($key !== false) {
            unset($enchantedPlayers[$key]);
            $this->setData('enchanted_players', array_values($enchantedPlayers));
        }
    }
    
    public function onDeath() {
        $enchantedPlayers = $this->getData('enchanted_players') ?? [];
        
        foreach ($enchantedPlayers as $playerId) {
            $this->sendMessageToPlayer($playerId, "🌟 طلسم افسونگر شکسته شد!");
        }
        $this->setData('enchanted_players', []);
        
        return ['message' => "💀 افسونگر مرد و طلسم‌ها از بین رفتن!"];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolf($p['role'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'enchanter_' . $p['id']
            ];
        }
        return $targets;
    }
}