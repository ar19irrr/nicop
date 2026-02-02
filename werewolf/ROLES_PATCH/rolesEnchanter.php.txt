<?php
/**
 * 🧙🏻‍♂️ افسونگر (Enchanter)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class Enchanter extends Role {
    
    protected $enchantedPlayers = []; // لیست بازیکنان طلسم شده
    
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
        
        // بررسی آیا قبلاً طلسم شده
        if (in_array($target, $this->enchantedPlayers)) {
            return [
                'success' => false,
                'message' => "⚠️ {$targetPlayer['name']} قبلاً طلسم شده!"
            ];
        }
        
        // اضافه کردن به لیست طلسم شده‌ها
        $this->enchantedPlayers[] = $target;
        
        // ارسال پیام به بازیکن هدف
        $this->sendMessageToPlayer($target, "🔮 نیمه‌های شب با احساس سوزش از خواب بیدار می‌شی و لکه‌ی سیاهی رو روی بدنت می‌بینی. به نظر میاد افسونگر تو رو نفرین کرده و این لکه‌ی سیاه نشونه‌ی طلسم شدنته!");
        
        return [
            'success' => true,
            'message' => "✅ تو {$targetPlayer['name']} رو طلسم کردی! اگر گرگ‌ها بهش حمله کنن، ۳۰٪ احتمال داره آلوده شه و تبدیل به گرگ بشه.",
            'enchanted' => $target
        ];
    }
    
    public function isEnchanted($playerId) {
        return in_array($playerId, $this->enchantedPlayers);
    }
    
    public function removeEnchantment($playerId) {
        $key = array_search($playerId, $this->enchantedPlayers);
        if ($key !== false) {
            unset($this->enchantedPlayers[$key]);
            $this->enchantedPlayers = array_values($this->enchantedPlayers);
        }
    }
    
    public function onDeath() {
        // با مرگ افسونگر، همه طلسم‌ها از بین می‌رن
        foreach ($this->enchantedPlayers as $playerId) {
            $this->sendMessageToPlayer($playerId, "🌟 لکه‌های سیاهی که روی بدنت به وجود اومده بود از بین رفت. متوجه می‌شی که طلسمِ افسونگر شکسته شده. حالا دیگه نفرینش از روی تو برداشته شد!");
        }
        $this->enchantedPlayers = [];
        
        return [
            'message' => "💀 افسونگر مرد و همه طلسم‌ها از بین رفتن!"
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه گرگ‌ها رو طلسم کنه
            if ($this->isWolfTeam($p['role'])) {
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
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey'];
        return in_array($role, $wolfRoles);
    }
}