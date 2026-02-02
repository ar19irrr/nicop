<?php
/**
 * ❄️👸🏻 ملکه یخی (IceQueen)
 * تیم: آتش و یخ (Fire & Ice)
 */

require_once __DIR__ . '/base.php';

class IceQueen extends Role {
    
    protected $firefighterId = null;  // آیدی پادشاه آتش
    protected $frozenPlayers = [];    // بازیکنان منجمد شده [id => night_count]
    
    public function getName() {
        return 'ملکه یخی';
    }
    
    public function getEmoji() {
        return '❄️👸🏻';
    }
    
    public function getTeam() {
        return 'fire_ice';
    }
    
    public function getDescription() {
        $fireName = $this->firefighterId ? $this->getPlayerById($this->firefighterId)['name'] : 'نامشخص';
        return "تو ملکه یخی ❄️👸🏻 هستی! با پادشاه آتش 🔥🤴🏻 ({$fireName}) هم‌تیمی هستی. هر شب می‌تونی یه نفر رو منجمد کنی. اگر همون فرد رو شب بعد دوباره منجمد کنی، به دلیل سرمای بیش از حد کشته می‌شه!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❄️ امشب می‌خوای چه کسی رو منجمد کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی فرشته نگهبان
        if ($this->isGuardedByAngel($target)) {
            return [
                'success' => false,
                'message' => "🛡️ می‌خواستی {$targetPlayer['name']} رو منجمد کنی اما فرشته از اون مراقبت می‌کرد و نتونستی!"
            ];
        }
        
        // بررسی آیا قبلاً منجمد شده
        if (isset($this->frozenPlayers[$target])) {
            // اگر دیشب منجمد شده بود، الان می‌میره
            if ($this->frozenPlayers[$target] == $this->getCurrentNight() - 1) {
                $this->killPlayer($target, 'ice_queen');
                $this->sendMessageToPlayer($target, "❄️💀 ملکه یخی ❄️👸🏻 برای دو شب متوالی منجمدت کرد. بدنت تحمل این همه سرما رو نداشت و خون توی رگ‌هات یخ زد!");
                
                return [
                    'success' => true,
                    'message' => "❄️💀 {$targetPlayer['name']} رو برای دومین شب متوالی منجمد کردی و کشتیش!",
                    'killed' => $target
                ];
            }
        }
        
        // منجمد کردن
        $this->frozenPlayers[$target] = $this->getCurrentNight();
        
        // غیرفعال کردن نقش
        $this->disableRole($target);
        
        $this->sendMessageToPlayer($target, "❄️ دیشب ملکه یخی ❄️👸🏻 منجمدت کرد! نمی‌تونی تا فردا فعالیتی داشته باشی!");
        
        return [
            'success' => true,
            'message' => "❄️ با موفقیت {$targetPlayer['name']} رو منجمد کردی! فردا شب نمی‌تونه از نقشش استفاده کنه!",
            'frozen' => $target
        ];
    }
    
    public function onNightEnd() {
        // آزاد کردن کسانی که دیشب منجمد شدن
        $currentNight = $this->getCurrentNight();
        foreach ($this->frozenPlayers as $playerId => $night) {
            if ($night == $currentNight - 1) {
                // این شخص دیشب منجمد بود، الان آزاد می‌شه
                $this->enableRole($playerId);
            }
        }
    }
    
    private function disableRole($playerId) {
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = true;
        }
    }
    
    private function enableRole($playerId) {
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = false;
        }
    }
    
    private function isGuardedByAngel($playerId) {
        return false; // بررسی توسط گیم انجین
    }
    
    public function setFirefighterId($id) {
        $this->firefighterId = $id;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه پادشاه آتش رو منجمد کنه (هم‌تیمی)
            if ($p['id'] == $this->firefighterId) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'ice_queen_' . $p['id']
            ];
        }
        return $targets;
    }
}