<?php
/**
 * 🎭 کلاس پایه نقش‌ها (نسخه نهایی WEREWOLF_V2)
 */

abstract class Role {
    
    protected $player;
    protected $playerId;
    protected $game;
    protected $roleData = [];
    
    public function __construct($player, $game) {
        $this->player = $player;
        $this->playerId = $player['id'];
        $this->game = $game;
        $this->roleData = $player['role_data'] ?? [];
    }
    
    // ===== متدهای abstract =====
    
    abstract public function getName();
    abstract public function getEmoji();
    abstract public function getTeam();
    abstract public function getDescription();
    
    // ===== متدهای پیش‌فرض اکشن =====
    
    public function hasNightAction() {
        return false;
    }
    
    public function hasDayAction() {
        return false;
    }
    
    public function canVote() {
        return true;
    }
    
    public function getVoteValue() {
        return 1;
    }
    
    public function performNightAction($target = null) {
        return ['success' => false, 'message' => 'این نقش اکشن شب نداره!'];
    }
    
    public function performDayAction($target = null) {
        return ['success' => false, 'message' => 'این نقش اکشن روز نداره!'];
    }
    
    // ===== متدهای کمکی اصلی =====
    
    protected function getId() {
        return $this->playerId;
    }
    
    protected function getPlayerName() {
        return $this->player['name'];
    }
    
    protected function getPlayerById($id) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $id) return $p;
        }
        return null;
    }
    
    protected function getAllPlayers() {
        return $this->game['players'];
    }
    
    protected function getAlivePlayers() {
        return array_filter($this->game['players'], fn($p) => $p['alive'] ?? false);
    }
    
    protected function getOtherAlivePlayers() {
        return array_filter($this->game['players'], function($p) {
            return ($p['alive'] ?? false) && $p['id'] != $this->playerId;
        });
    }
    
    protected function isAlive() {
        return $this->player['alive'] ?? false;
    }
    
    protected function isPlayerAlive($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $playerId) {
                return $p['alive'] ?? false;
            }
        }
        return false;
    }
    
    protected function setData($key, $value) {
        $this->roleData[$key] = $value;
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role_data'][$key] = $value;
                break;
            }
        }
    }
    
    protected function getData($key) {
        return $this->roleData[$key] ?? null;
    }
    
    protected function logAction($action, $target) {
        if (!isset($this->game['night_actions'])) {
            $this->game['night_actions'] = [];
        }
        $this->game['night_actions'][] = [
            'player_id' => $this->playerId,
            'action' => $action,
            'target' => $target,
            'night' => $this->game['night_count'] ?? 1
        ];
    }
    
    protected function killPlayer($playerId, $cause = 'unknown') {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['alive'] = false;
                $p['death_cause'] = $cause;
                $p['death_time'] = time();
                break;
            }
        }
        $this->saveGame();
    }
    
    protected function setPlayerRole($playerId, $newRole) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role'] = $newRole;
                break;
            }
        }
        $this->saveGame();
    }
    
    protected function disableRole($playerId) {
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = true;
        }
    }
    
    protected function enableRole($playerId) {
        $player = $this->getPlayerById($playerId);
        if ($player) {
            $player['role_disabled'] = false;
        }
    }
    
    // ===== متدهای ارتباطی =====
    
    protected function sendMessage($text) {
        sendPrivateMessage($this->playerId, $text);
    }
    
    protected function sendMessageToPlayer($playerId, $text) {
        sendPrivateMessage($playerId, $text);
    }
    
    protected function sendMessageToGroup($text) {
        sendMessage($this->game['group_id'], $text);
    }
    
    protected function notifyAll($message) {
        sendGroupMessage($this->game['group_id'], $message);
    }
    
    protected function notifyWolfTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && $p['alive']) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyVampireTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isVampireTeam($p['role']) && $p['alive']) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    /**
     * 🦇 اطلاع‌رسانی به تیم فرقه (شامل راهب سیاه)
     */
    protected function notifyCultTeam($message) {
        $cultRoles = ['cultist', 'royce', 'frankenstein', 'monk_black'];
        
        foreach ($this->game['players'] as $p) {
            if (in_array($p['role'], $cultRoles) && ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    /**
     * 👤 معرفی تیم فرقه به عضو جدید (شامل راهب سیاه)
     */
    protected function introduceCultTeam($newMemberId) {
        $cultMembers = [];
        $monkBlackName = null;
        
        foreach ($this->game['players'] as $p) {
            if (in_array($p['role'], ['cultist', 'royce', 'frankenstein', 'monk_black']) 
                && ($p['alive'] ?? false) 
                && $p['id'] != $newMemberId) {
                
                $roleIcon = $this->getCultRoleIcon($p['role']);
                $cultMembers[] = $roleIcon . ' ' . $p['name'];
                
                if ($p['role'] == 'monk_black') {
                    $monkBlackName = $p['name'];
                }
            }
        }
        
        if (!empty($cultMembers)) {
            $msg = "👥 <b>بقیه اعضای فرقه:</b>\n" . implode("\n", $cultMembers);
            sendPrivateMessage($newMemberId, $msg);
        }
    }
    
    /**
     * 🦇 گرفتن آیکون نقش فرقه
     */
    private function getCultRoleIcon($role) {
        $icons = [
            'cultist' => '👤',
            'royce' => '🎩',
            'frankenstein' => '🧟‍♂️',
            'monk_black' => '🦇'
        ];
        return $icons[$role] ?? '👤';
    }
    
    protected function notifyBeholder() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'beholder' && ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], 
                    "👁️ حاجی {$this->getPlayerName()} پیشگوی رزرو بود و الان به جای پیشگوی قبلی پیشگویی می‌کنه!"
                );
            }
        }
    }

    /**
     * 💬 ارسال پیام به چت تیم
     */
    protected function sendTeamChat($message) {
        // چک کردن زندانی کلانتر
        if (!empty($this->player['imprisoned'])) {
            $this->sendMessage("🔒 <b>شما زندانی کلانتر هستید!</b>\n\n❌ نمی‌توانید با تیم خود چت کنید.");
            return;
        }
        
        // چک کردن ساکت بودن
        if (!empty($this->player['silenced'])) {
            $this->sendMessage("🤐 <b>شما ساکت شده‌اید!</b>\nنمی‌توانید چت کنید.");
            return;
        }
        
        // گرفتن تیم فعلی
        $currentTeam = $this->getTeam();
        $teamMates = $this->getCurrentTeamMates();
        
        if (empty($teamMates)) {
            $this->sendMessage("❌ هم‌تیمی فعالی ندارید!");
            return;
        }
        
        // فرمت پیام
        $senderName = $this->getPlayerName();
        $teamIcon = $this->getTeamIcon($currentTeam);
        $formattedMsg = "$teamIcon <b>[$senderName]:</b>\n$message";
        
        // ارسال به هم‌تیمی‌ها
        foreach ($teamMates as $mate) {
            if (!empty($mate['imprisoned'])) continue;
            sendPrivateMessage($mate['id'], $formattedMsg);
        }
        
        // تایید به فرستنده
        $this->sendMessage("✅ پیام به " . count($teamMates) . " هم‌تیمی ارسال شد!");
    }
    
    /**
     * 👥 گرفتن هم‌تیمی‌های زنده (بر اساس تیم فعلی)
     */
    protected function getCurrentTeamMates() {
        $currentTeam = $this->getTeam();
        $currentRole = $this->player['role'];
        $mates = [];
        
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $this->playerId) continue;
            if (!($p['alive'] ?? false)) continue;
            
            $mateRole = RoleFactory::create($p['role'], $p, $this->game);
            $mateTeam = $mateRole->getTeam();
            
            // چک کردن تیم جدید بعد تبدیل
            if (!empty($p['converted_to'])) {
                $mateTeam = $p['converted_to'];
            }
            
            // فقط اگه هر دو تیم یکی باشن
            if ($mateTeam == $currentTeam) {
                $mates[] = $p;
            }
        }
        
        return $mates;
    }
    
    /**
     * 🏷️ آیکون تیم
     */
    protected function getTeamIcon($team) {
        $icons = [
            'werewolf' => '🐺',
            'vampire' => '🧛',
            'cult' => '👤',
            'killer' => '🔪',
            'fire_ice' => '🔥❄️',
            'black_knight' => '🥷',
            'joker' => '🤡',
        ];
        return $icons[$team] ?? '👥';
    }
    
    // ===== متدهای دریافت اطلاعات =====
    
    protected function getCurrentNight() {
        return $this->game['night_count'] ?? 1;
    }
    
    protected function getCurrentDay() {
        return $this->game['day_count'] ?? 1;
    }
    
    protected function getWolfTeam() {
        $wolves = [];
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && $p['alive']) {
                $wolves[] = $p;
            }
        }
        return $wolves;
    }
    
    // ===== متدهای بررسی نقش =====
    
    protected function isWolf($role) {
        return in_array($role, [
            'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
            'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'
        ]);
    }
    
    protected function isWolfTeam($role) {
        return $this->isWolf($role);
    }
    
    protected function isVampireTeam($role) {
        return in_array($role, ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang']);
    }
    
    protected function isCultRole($role) {
        return in_array($role, ['cultist', 'royce', 'frankenstein', 'monk_black']);
    }
    
    protected function isKillerRole($role) {
        return in_array($role, ['serial_killer', 'archer', 'davina']);
       }

        protected function isFireIceTeam($role) {
           return in_array($role, ['fire_king', 'ice_queen', 'lilith', 'lucifer']);
       }

        protected function isBlackKnightTeam($role) {
           return in_array($role, ['black_knight', 'bride_dead']);
       }

        protected function isJokerTeam($role) {
           return in_array($role, ['joker', 'harly']);
       }

// ✅ اضافه کردن notify برای تیم‌های جدید

        protected function notifyKillerTeam($message) {
           foreach ($this->game['players'] as $p) {
               if ($this->isKillerTeam($p['role']) && ($p['alive'] ?? false)) {
                   sendPrivateMessage($p['id'], $message);
        } 
    }
}

protected function notifyFireIceTeam($message) {
    foreach ($this->game['players'] as $p) {
        if ($this->isFireIceTeam($p['role']) && ($p['alive'] ?? false)) {
            sendPrivateMessage($p['id'], $message);
        }
    }
}

protected function notifyBlackKnightTeam($message) {
    foreach ($this->game['players'] as $p) {
        if ($this->isBlackKnightTeam($p['role']) && ($p['alive'] ?? false)) {
            sendPrivateMessage($p['id'], $message);
        }
    }
}

protected function notifyJokerTeam($message) {
    foreach ($this->game['players'] as $p) {
        if ($this->isJokerTeam($p['role']) && ($p['alive'] ?? false)) {
            sendPrivateMessage($p['id'], $message);
        }
    }
}
    
    // ===== متدهای سیستمی =====
    
    protected function saveGame() {
        saveGame($this->game);
    }
    
    protected function setGameState($key, $value) {
        $this->game['state'][$key] = $value;
        $this->saveGame();
    }
    
    protected function getGameState($key) {
        return $this->game['state'][$key] ?? null;
    }
    
    // ===== Event Handlers =====
    
    public function onGameStart() {}
    public function onNightStart() {}
    public function onNightEnd() {}
    public function onDayStart() {}
    public function onDayEnd() {}
    
    public function onDeath($killerRole = null) {
        return [
            'team' => $this->getTeam(),
            'message' => $this->getName() . ' ' . $this->getEmoji() . ' مرد.'
        ];
    }
    
    public function onAttacked($attackerRole, $attackerId) {
        return ['died' => true];
    }
    
    public function onPlayerDeath($deadPlayer) {}
    public function onLynched() {}
    public function onVisitor($visitorId, $visitorRole) {}
    public function onConvertedToCult() {}
    
    abstract public function getValidTargets($phase = 'night');
}