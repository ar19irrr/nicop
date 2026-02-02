<?php
/**
 * 🔪 قاتل (Serial Killer)
 * 
 * قوی‌ترین نقش بازی:
 * - همه رو می‌کشه (حتی با فرشته!)
 * - ارجعیت به گرگ
 * - فقط تله هانتسمن و اشک ققنوس می‌تونن جلوی قتل رو بگیرن
 */

require_once __DIR__ . '/base.php';

class SerialKiller extends Role {
    
    public function getName() {
        return 'قاتل';
    }
    
    public function getEmoji() {
        return '🔪';
    }
    
    public function getTeam() {
        return 'killer';
    }
    
    public function getDescription() {
        $archer = $this->getArcherName();
        return "تو قاتل روانی🔪 هستی. چند روزه که از تیمارستان فرار کردی و هدفت اینه که هرشب یکی از اهالی روستا رو به قطر برسونی و اعضای بدنشون رو به کُلکسیون خودت اضافه کنی. حتی فرشته نگهبان هم نمی‌تونه جلوت رو بگیره! فقط مراقب تله هانتسمن و اشک ققنوس باش...
$archer";
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
        if (!$targetPlayer) {
            return [
                'success' => false,
                'message' => '❌ بازیکن یافت نشد!'
            ];
        }
        
        if (!$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ این بازیکن مرده!'
            ];
        }
        
        $this->logAction('kill', $target);
        
        // ۱. بررسی اشک ققنوس (بالاترین اولویت)
        if ($this->hasPhoenixTear($target)) {
            return $this->blockedByPhoenix($targetPlayer);
        }
        
        // ۲. بررسی تله هانتسمن
        if ($this->hasHuntsmanTrap($target)) {
            return $this->caughtInTrap($targetPlayer);
        }
        
        // ۳. بررسی ارجعیت به گرگ
        if ($this->isWerewolf($targetPlayer)) {
            return $this->killWerewolf($targetPlayer);
        }
        
        // ۴. کشتن عادی (حتی با فرشته!)
        return $this->normalKill($targetPlayer);
    }
    
    /**
     * ۱. اشک ققنوس - قاتل شکست میخوره
     */
    private function blockedByPhoenix($targetPlayer) {
        $this->consumePhoenixTear($targetPlayer['id']);
        $this->notifyPhoenixUsed($targetPlayer);
        
        // پیام گروه
        $this->sendMessageToGroup("🪶 صبح روز بعد اهالی دیدن {$targetPlayer['name']} زنده‌ست! ظاهراً اشک ققنوس جونش رو نجات داده!");
        
        return [
            'success' => true,
            'message' => "🔪 رفتی خونه {$targetPlayer['name']} که بکشیش ولی دیدی یه نور عجیب دورشه! اشک ققنوس نجاتش داد و زنده موند! تو دست خالی برگشتی!",
            'killed' => false,
            'blocked_by' => 'phoenix'
        ];
    }
    
    /**
     * ۲. تله هانتسمن - هر دو می‌میرن
     */
    private function caughtInTrap($targetPlayer) {
        $this->game = killPlayer($this->game, $targetPlayer['id'], 'serial_killer');
        $this->game = killPlayer($this->game, $this->player['id'], 'huntsman');
        saveGame($this->game);
        
        $this->notifyHuntsmanSuccess($targetPlayer);
        
        // پیام گروه
        $this->sendMessageToGroup("🪓 صبح روز بعد اهالی دو جنازه پیدا کردن: {$targetPlayer['name']} و قاتل! ظاهراً قاتل توی تله هانتسمن افتاده و قبل از مردن {$targetPlayer['name']} رو هم کشته!");
        
        return [
            'success' => true,
            'message' => "🔪 رفتی خونه {$targetPlayer['name']} ولی پات گیر کرد به تله هانتسمن! قبل از اینکه بمیری، {$targetPlayer['name']} رو هم کشتی! هانتسمن اومد و تیر خلاص رو زد!",
            'killed' => true,
            'target_killed' => true,
            'died' => true,
            'blocked_by' => 'huntsman'
        ];
    }
    
    /**
     * ۳. کشتن گرگ - ارجعیت قاتل
     */
    private function killWerewolf($targetPlayer) {
        $this->game = killPlayer($this->game, $targetPlayer['id'], 'serial_killer');
        saveGame($this->game);
        
        // پیام گروه
        $this->sendMessageToGroup("🔪 صبح روز بعد اهالی جنازه {$targetPlayer['name']} رو پیدا کردن که ۳۶ ضربه چاقو خورده! قاتل حتی گرگ‌ها رو هم رحم نمی‌کنه!");
        
        return [
            'success' => true,
            'message' => "🔪 رفتی خونه {$targetPlayer['name']} و دیدی یه گرگه! قبل از اینکه بتونه جلوتر بیاد، چاقوت رو ۳۶ بار فرو کردی تو بدنش! گرگ مرده!",
            'killed' => true,
            'dominance' => true
        ];
    }
    
    /**
     * ۴. کشتن عادی - حتی با فرشته!
     */
    private function normalKill($targetPlayer) {
        $this->game = killPlayer($this->game, $targetPlayer['id'], 'serial_killer');
        saveGame($this->game);
        
        $message = "🔪 رفتی خونه {$targetPlayer['name']} و ";
        
        if ($this->isProtectedByAngel($targetPlayer['id'])) {
            $message .= "فرشته نگهبان سعی کرد جلوت رو بگیره ولی تو ازش رد شدی و کشتیش! فرشته دست خالی برگشت!";
            $this->notifyAngelFailed($targetPlayer['id']);
        } else {
            $message .= "با چاقوت به طرز فجیعی کشتیش!";
        }
        
        // پیام گروه
        $this->sendMessageToGroup("🔪 صبح روز بعد اهالی روستا جنازه {$targetPlayer['name']} رو پیدا کردن که به طرز فجیعی کشته شده. قاتل روانی دوباره فعال بوده!");
        
        return [
            'success' => true,
            'message' => $message,
            'killed' => true
        ];
    }
    
    /**
     * بررسی اشک ققنوس
     */
    private function hasPhoenixTear($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $playerId && !empty($p['role_data']['phoenix_tear'])) {
                return true;
            }
        }
        return false;
    }
    
    private function consumePhoenixTear($playerId) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                unset($p['role_data']['phoenix_tear']);
                break;
            }
        }
        saveGame($this->game);
    }
    
    /**
     * بررسی تله هانتسمن
     */
    private function hasHuntsmanTrap($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'huntsman' && ($p['alive'] ?? false)) {
                $traps = $p['role_data']['traps'] ?? [];
                if (in_array($playerId, $traps)) {
                    return rand(1, 100) <= 50;
                }
            }
        }
        return false;
    }
    
    /**
     * بررسی محافظت فرشته
     */
    private function isProtectedByAngel($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'guardian_angel' && ($p['alive'] ?? false)) {
                if (($p['role_data']['protected'] ?? null) == $playerId) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * بررسی گرگ بودن
     */
    private function isWerewolf($player) {
        $werewolfRoles = [
            'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 
            'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'
        ];
        return in_array($player['role'] ?? '', $werewolfRoles);
    }
    
    /**
     * اطلاع‌رسانی‌ها
     */
    private function notifyPhoenixUsed($targetPlayer) {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'phoenix' && ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], "🪶 اشکت به {$targetPlayer['name']} کمک کرد از دست قاتل نجات پیدا کنه! اشک مصرف شد.");
            }
        }
    }
    
    private function notifyAngelFailed($protectedId) {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'guardian_angel' && ($p['alive'] ?? false)) {
                if (($p['role_data']['protected'] ?? null) == $protectedId) {
                    sendPrivateMessage($p['id'], "😰 سعی کردی از فلانی محافظت کنی ولی قاتل ازت رد شد و کشتش! دست خالی برگشتی!");
                }
            }
        }
    }
    
    private function notifyHuntsmanSuccess($targetPlayer) {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'huntsman' && ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], "🪓 تله‌ات گرفت! قاتل اومد بود {$targetPlayer['name']} رو بکشه ولی توی تله افتاد! قبل از مردنش {$targetPlayer['name']} رو هم کشته!");
            }
        }
    }
    
    /**
     * وقتی گرگ به قاتل حمله میکنه
     */
    public function onAttackedByWerewolf($werewolfId) {
        $werewolf = $this->getPlayerById($werewolfId);
        
        $this->game = killPlayer($this->game, $werewolfId, 'serial_killer');
        saveGame($this->game);
        
        // پیام گروه
        $this->sendMessageToGroup("🔪 صبح روز بعد اهالی جنازه {$werewolf['name']} رو پیدا کردن که با چاقو کشته شده! گرگ رفته بود خونه قاتل ولی قاتل خونه بود و ارجعیت داشت!");
        
        return [
            'died' => false,
            'message' => "🐺 گرگ اومد خونه‌ت که بخوریت، ولی تو خونه بودی و با چاقوت کشتش! ارجعیت قاتل!",
            'killed_attacker' => true
        ];
    }
    
    /**
     * گرفتن اسم کماندار
     */
    private function getArcherName() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'archer' && ($p['alive'] ?? false)) {
                return "کماندار کسی نیست جز: {$p['name']}";
            }
        }
        return '';
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'killer_' . $p['id']
            ];
        }
        return $targets;
    }
}