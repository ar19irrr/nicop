<?php
/**
 * ⚡️🐺 گرگ آلفا (AlphaWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class AlphaWolf extends Role {
    
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
            $bittenPlayers = $this->getData('bitten_players') ?? [];
            $bittenPlayers[$target] = $this->getCurrentNight();
            $this->setData('bitten_players', $bittenPlayers);
            
            $this->notifyWolfTeam("⚡️ گرگ آلفا به {$targetPlayer['name']} حمله کرد و آلودش کرد! فرداشب تبدیل به گرگ می‌شه!");
            
            return [
                'success' => true,
                'message' => "⚡️ به {$targetPlayer['name']} حمله کردی و آلودش کردی! فرداشب تبدیل به گرگ می‌شه!",
                'infected' => $target
            ];
        }
        
        // حمله عادی - ثبت رأی گرگ‌ها
        $this->logAction('alpha_wolf_vote', $target);
        
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onNightEnd() {
        $bittenPlayers = $this->getData('bitten_players') ?? [];
        $currentNight = $this->getCurrentNight();
        
        foreach ($bittenPlayers as $playerId => $night) {
            if ($currentNight == $night + 1) {
                $player = $this->getPlayerById($playerId);
                if ($player && isset($player['alive']) && $player['alive'] === true) {
                    $this->setPlayerRole($playerId, 'werewolf');
                    $this->sendMessageToPlayer($playerId, 
                        "🐺 تبدیل شدی! احساس درد و سوزش عجیبی تمام بدنت رو فرا گرفت... به یک گرگینه 🐺 تبدیل شدی!"
                    );
                }
                unset($bittenPlayers[$playerId]);
            }
        }
        $this->setData('bitten_players', $bittenPlayers);
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
                'callback' => 'alpha_' . $p['id']
            ];
        }
        return $targets;
    }
}