<?php
/**
 * ⚡️🐺 گرگ آلفا (AlphaWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class AlphaWolf extends Role {
    
    protected $bittenPlayers = [];    // بازیکنان آلوده شده
    
    public function getName() {
        return 'گرگ آلفا';
    }
    
    public function getEmoji() {
        return '⚡️🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ آلفا ⚡️🐺 هستی! سر دسته‌ی تیم گرگ‌ها. اگر به کسی حمله کنی، ۲۰٪ احتمال داره اون شخص آلوده بشه و شب بعد تبدیل به گرگینه بشه!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // ۲۰٪ شانس آلوده کردن
        $infectChance = rand(1, 100);
        if ($infectChance <= 20) {
            $this->bittenPlayers[$target] = $this->getCurrentNight();
            
            // اطلاع به گرگ‌ها
            $this->notifyWolfTeam("⚡️ گرگ آلفا به {$targetPlayer['name']} حمله کرد و آلودش کرد! فرداشب تبدیل به گرگ می‌شه (ولی هنوز ما رو نمی‌شناسه)!");
            
            return [
                'success' => true,
                'message' => "⚡️ به {$targetPlayer['name']} حمله کردی ولی نکشتیش، آلودش کردی! فرداشب تبدیل به گرگ می‌شه (ولی هنوز ترو نمی‌شناسه)!",
                'infected' => $target
            ];
        }
        
        // حمله عادی
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onNightEnd() {
        // تبدیل آلوده‌ها به گرگ
        foreach ($this->bittenPlayers as $playerId => $night) {
            if ($this->getCurrentNight() == $night + 1) {
                $player = $this->getPlayerById($playerId);
                if ($player && $player['alive']) {
                    $this->convertToWolf($playerId);
                    $this->sendMessageToPlayer($playerId, "🐺 شب شده و احساس درد و سوزش عجیبی تمام بدنت رو فرا می‌گیره... از شدت درد بیهوش می‌شی و وقتی بهوش می‌ای... می‌بینی که به یه گرگینه 🐺 تبدیل شدی!");
                }
                unset($this->bittenPlayers[$playerId]);
            }
        }
    }
    
    private function convertToWolf($playerId) {
        $this->setPlayerRole($playerId, 'werewolf');
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolfTeam($p['role'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'alpha_wolf_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}