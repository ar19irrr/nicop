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
        $team = $this->getCultTeam();
        return "تو فرقه‌گرا👤 هستی. هر شب یک نفر رو به فرقه خودت دعوت می‌کنی. وقتی برنده می‌شی که تعداد اعضای فرقه، بیشتر از بقیه‌ی نقش‌ها باشه! $team";
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
        if ($targetRole == 'hunter') {
            $this->game = killPlayer($this->game, $this->player['id'], 'hunter');
            saveGame($this->game);
            
            return [
                'success' => true,
                'message' => "💂🏻‍♂️ رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون شکارچی بود! شمشیرش رو توی قلبت فرو کرد!",
                'died' => true
            ];
        }
        
        // قاتل = مرگ فرقه‌گرا
        if ($targetRole == 'serial_killer') {
            $this->game = killPlayer($this->game, $this->player['id'], 'serial_killer');
            saveGame($this->game);
            
            return [
                'success' => true,
                'message' => "🔪 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون قاتل بود! چاقوش رو توی قلبت فرو کرد!",
                'died' => true
            ];
        }
        
        // گرگ = مرگ فرقه‌گرا
        if ($this->isWerewolf($targetPlayer)) {
            $this->game = killPlayer($this->game, $this->player['id'], 'werewolf');
            saveGame($this->game);
            
            return [
                'success' => true,
                'message' => "🐺 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون گرگ بود! خوردت!",
                'died' => true
            ];
        }
        
        // ومپایر = تبدیل یا مرگ
        if ($this->isVampire($targetPlayer)) {
            if (rand(1, 100) <= 50) {
                // تبدیل به ومپایر
                $this->convertToVampire($targetPlayer);
                return [
                    'success' => true,
                    'message' => "🧛 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون ومپایر بود! خونت رو خورد و داری تبدیل میشی!",
                    'converting' => true
                ];
            } else {
                $this->game = killPlayer($this->game, $this->player['id'], 'vampire');
                saveGame($this->game);
                return [
                    'success' => true,
                    'message' => "🧛 رفتی {$targetPlayer['name']} رو دعوت کنی ولی اون ومپایر بود! خونت رو مکید و کشتی!",
                    'died' => true
                ];
            }
        }
        
        // منافق = برنده نمیشه دیگه
        if ($targetRole == 'tanner') {
            $this->convertToCult($targetPlayer, false);
            return [
                'success' => true,
                'message' => "👺 {$targetPlayer['name']} رو دعوت کردی! ولی چون منافق بود، دیگه نمی‌تونه با اعدام برنده بشه!",
                'converted' => true
            ];
        }
        
        // تبدیل موفق
        $this->convertToCult($targetPlayer, true);
        
        return [
            'success' => true,
            'message' => "👤 {$targetPlayer['name']} دعوت رو پذیرفت و به فرقه پیوست!",
            'converted' => true
        ];
    }
    
    private function convertToCult($targetPlayer, $canWin) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $targetPlayer['id']) {
                $p['role'] = 'cultist';
                $p['team'] = 'cult';
                $p['can_win_as_tanner'] = $canWin;
                break;
            }
        }
        saveGame($this->game);
        
        sendPrivateMessage($targetPlayer['id'], 
            "👤 به فرقه دعوت شدی! الان عضو فرقه‌ای و می‌تونی دعوت کنی!"
        );
    }
    
    private function convertToVampire($vampire) {
        // تبدیل فرقه‌گرا به ومپایر
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->player['id']) {
                $p['role_data']['converting_to_vampire'] = ($this->game['night_count'] ?? 1) + 1;
                break;
            }
        }
        saveGame($this->game);
    }
    
    private function getCultTeam() {
        $cult = [];
        foreach ($this->game['players'] as $p) {
            if (($p['role'] == 'cultist' || $p['role'] == 'cult_leader') && 
                $p['id'] != $this->player['id'] && 
                ($p['alive'] ?? false)) {
                $cult[] = $p['name'];
            }
        }
        return empty($cult) ? '' : "اعضای فرقه: " . implode(', ', $cult);
    }
    
    private function isWerewolf($player) {
        $werewolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    private function isVampire($player) {
        return in_array($player['role'] ?? '', ['vampire', 'bloodthirsty', 'count_vampire']);
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // نمی‌تونه فرقه‌گرای دیگه رو دعوت کنه
            if ($p['role'] != 'cultist' && $p['role'] != 'cult_leader') {
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