<?php
/**
 * 🎭 کلاس پایه نقش‌ها
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../database.php';

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
    abstract public function getValidTargets($phase = 'night');
    
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
        return ['success' => false, 'message' => 'این نقش اکشن شب ندارد!'];
    }
    
    public function performDayAction($target = null) {
        return ['success' => false, 'message' => 'این نقش اکشن روز ندارد!'];
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
        return array_filter($this->game['players'], function($p) {
            return isset($p['alive']) && $p['alive'] === true;
        });
    }
    
    protected function getOtherAlivePlayers() {
        return array_filter($this->game['players'], function($p) {
            return (isset($p['alive']) && $p['alive'] === true) && $p['id'] != $this->playerId;
        });
    }
    
    protected function isAlive() {
        return isset($this->player['alive']) && $this->player['alive'] === true;
    }
    
    protected function isPlayerAlive($playerId) {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $playerId) {
                return isset($p['alive']) && $p['alive'] === true;
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
        $this->saveGame();
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
        $this->saveGame();
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
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role_disabled'] = true;
                break;
            }
        }
        $this->saveGame();
    }
    
    protected function enableRole($playerId) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role_disabled'] = false;
                break;
            }
        }
        $this->saveGame();
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
    
    protected function notifyWolfTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyVampireTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isVampireTeam($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyCultTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isCultRole($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyKillerTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isKillerRole($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyFireIceTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isFireIceTeam($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyBlackKnightTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isBlackKnightTeam($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyJokerTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isJokerTeam($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], $message);
            }
        }
    }
    
    protected function notifyBeholder() {
        foreach ($this->game['players'] as $p) {
            if ($p['role'] == 'beholder' && isset($p['alive']) && $p['alive'] === true) {
                sendPrivateMessage($p['id'], 
                    "👁️ اطلاعات: " . $this->getPlayerName() . " جای پیشگو را گرفته است!"
                );
            }
        }
    }
    
    protected function introduceCultTeam($newMemberId) {
        $cultMembers = [];
        
        foreach ($this->game['players'] as $p) {
            if ($this->isCultRole($p['role']) && isset($p['alive']) && $p['alive'] === true && $p['id'] != $newMemberId) {
                $cultMembers[] = $p['name'];
            }
        }
        
        if (!empty($cultMembers)) {
            $msg = "👥 <b>بقیه اعضای فرقه:</b>\n";
            foreach ($cultMembers as $name) {
                $msg .= "• " . $name . "\n";
            }
            sendPrivateMessage($newMemberId, $msg);
        }
    }
    
    // ===== متدهای چت تیمی =====
    
    protected function sendTeamChat($message) {
        if (!empty($this->player['imprisoned'])) {
            $this->sendMessage("🔒 <b>شما زندانی کلانتر هستید!</b>\n\n❌ نمی‌توانید با تیم خود چت کنید.");
            return;
        }
        
        if (!empty($this->player['silenced'])) {
            $this->sendMessage("🤐 <b>شما ساکت شده‌اید!</b>\nنمی‌توانید چت کنید.");
            return;
        }
        
        $currentTeam = $this->getTeam();
        $teamMates = $this->getCurrentTeamMates();
        
        if (empty($teamMates)) {
            $this->sendMessage("❌ هم‌تیمی فعالی ندارید!");
            return;
        }
        
        $senderName = $this->getPlayerName();
        $teamIcon = $this->getTeamIcon($currentTeam);
        $formattedMsg = "$teamIcon <b>[$senderName]:</b>\n$message";
        
        foreach ($teamMates as $mate) {
            if (!empty($mate['imprisoned'])) continue;
            sendPrivateMessage($mate['id'], $formattedMsg);
        }
        
        $this->sendMessage("✅ پیام به " . count($teamMates) . " هم‌تیمی ارسال شد!");
    }
    
    protected function getCurrentTeamMates() {
        $currentTeam = $this->getTeam();
        $mates = [];
        
        foreach ($this->game['players'] as $p) {
            if ($p['id'] == $this->playerId) continue;
            if (!isset($p['alive']) || $p['alive'] !== true) continue;
            
            $mateTeam = detectTeam($p['role']);
            
            if (!empty($p['converted_to'])) {
                $mateTeam = $p['converted_to'];
            }
            
            if ($mateTeam == $currentTeam) {
                $mates[] = $p;
            }
        }
        
        return $mates;
    }
    
    protected function getTeamIcon($team) {
        $icons = [
            'villager' => '🏘️',
            'werewolf' => '🐺',
            'vampire' => '🧛',
            'cult' => '👤',
            'killer' => '🔪',
            'fire_ice' => '🔥❄️',
            'black_knight' => '🥷',
            'joker' => '🤡',
            'independent' => '⚡'
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
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                $wolves[] = $p;
            }
        }
        return $wolves;
    }
    
    // ===== متدهای بررسی نقش =====
    
    protected function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
    
    protected function isWolfTeam($role) {
        return $this->isWolf($role);
    }
    
    protected function isVampireTeam($role) {
        $vampireRoles = ['vampire', 'bloodthirsty', 'kent_vampire', 'chiang'];
        return in_array($role, $vampireRoles);
    }
    
    protected function isCultRole($role) {
        $cultRoles = ['cultist', 'royce', 'frankenstein', 'monk_black'];
        return in_array($role, $cultRoles);
    }
    
    protected function isKillerRole($role) {
        $killerRoles = ['serial_killer', 'archer', 'davina'];
        return in_array($role, $killerRoles);
    }
    
    protected function isFireIceTeam($role) {
        $fireIceRoles = ['fire_king', 'ice_queen', 'lilith', 'magento', 'lucifer'];
        return in_array($role, $fireIceRoles);
    }
    
    protected function isBlackKnightTeam($role) {
        $blackKnightRoles = ['black_knight', 'bride_dead'];
        return in_array($role, $blackKnightRoles);
    }
    
    protected function isJokerTeam($role) {
        $jokerRoles = ['joker', 'harly'];
        return in_array($role, $jokerRoles);
    }
    
    // ===== متدهای سیستمی =====
    
    protected function saveGame() {
        saveGame($this->game);
    }
    
    protected function setGameState($key, $value) {
        if (!isset($this->game['state'])) {
            $this->game['state'] = [];
        }
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
}