<?php
/**
 * 👤 فرقه‌گرا
 */

require_once __DIR__ . '/base.php';

class Cultist extends Role {
    
    public function getName() {
        return 'فرقه‌گرا';
    }
    
    public function getEmoji() {
        return '👤';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        return "تو فرقه‌گرا 👤 هستی. هر شب یک نفر رو به فرقه دعوت می‌کنی. وقتی تعداد اعضای فرقه بیشتر از بقیه بشه، برنده می‌شید!";
    }
    
    public function hasNightAction() {
        return true;
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
        
        $targetRole = $targetPlayer['role'];
        
        // شکارچی = مرگ فرقه‌گرا
        if ($targetRole == 'hunter' || $targetRole == 'cult_hunter') {
            $this->killPlayer($this->getId(), 'hunter');
            return [
                'success' => false,
                'message' => "💂🏻‍♂️ رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون شکارچی بود! کشتی!",
                'died' => true
            ];
        }
        
        // قاتل = مرگ فرقه‌گرا
        if ($targetRole == 'serial_killer') {
            $this->killPlayer($this->getId(), 'serial_killer');
            return [
                'success' => false,
                'message' => "🔪 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون قاتل بود! کشتی!",
                'died' => true
            ];
        }
        
        // گرگ = مرگ فرقه‌گرا
        if ($this->isWolf($targetRole)) {
            $this->killPlayer($this->getId(), 'werewolf');
            return [
                'success' => false,
                'message' => "🐺 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون گرگ بود! خوردت!",
                'died' => true
            ];
        }
        
        // ومپایر = 50% تبدیل، 50% مرگ
        if ($this->isVampireTeam($targetRole)) {
            if (rand(1, 100) <= 50) {
                $this->setData('converting_to_vampire', ($this->game['night_count'] ?? 1) + 1);
                return [
                    'success' => true,
                    'message' => "🧛 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون ومپایر بود! داری تبدیل میشی!",
                    'converting' => true
                ];
            } else {
                $this->killPlayer($this->getId(), 'vampire');
                return [
                    'success' => false,
                    'message' => "🧛 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون ومپایر بود! کشتی!",
                    'died' => true
                ];
            }
        }
        
        // منافق = تبدیل میشه ولی دیگه نمی‌تونه با اعدام برنده بشه
        if ($targetRole == 'tanner') {
            $this->convertToCult($target, false);
            return [
                'success' => true,
                'message' => "👺 {$targetPlayer['name']} رو دعوت کردی! ولی چون منافق بود، دیگه نمی‌تونه با اعدام برنده بشه!",
                'converted' => true
            ];
        }
        
        // تبدیل موفق
        $this->convertToCult($target, true);
        
        return [
            'success' => true,
            'message' => "👤 {$targetPlayer['name']} دعوت رو پذیرفت و به فرقه پیوست!",
            'converted' => true
        ];
    }
    
    private function convertToCult($playerId, $canWin) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role'] = 'cultist';
                $p['role_data']['converted_to_cult'] = true;
                $p['role_data']['can_win_as_cult'] = $canWin;
                break;
            }
        }
        $this->saveGame();
        
        $this->sendMessageToPlayer($playerId, "👤 به فرقه دعوت شدی! الان عضو فرقه‌ای!");
    }
    
    public function onNightEnd() {
        $convertingNight = $this->getData('converting_to_vampire');
        if ($convertingNight && $this->getCurrentNight() >= $convertingNight) {
            $this->setPlayerRole($this->getId(), 'vampire');
            $this->sendMessage("🧛 تبدیل به ومپایر شدی!");
            $this->setData('converting_to_vampire', null);
        }
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['role'] != 'cultist') {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'cultist_' . $p['id']
                ];
            }
        }
        return $targets;
    }
}