<?php
/**
 * 🎭 کلاس پایه نقش‌ها - بدون وابستگی به فایل‌های خارجی
 */

abstract class Role {
    
    protected $player;
    protected $playerId;
    protected $game;
    protected $roleData = [];
    
    public function __construct($player, $game) {
        // اصلاح ارور Undefined array key "id"
        if (!is_array($player) || !isset($player['id'])) {
            // اگر پلیر خراب بود، یه آرایه امن بساز
            $player = ['id' => 0, 'name' => 'Unknown', 'alive' => false, 'role_data' => []];
        }
        
        $this->player = $player;
        $this->playerId = $player['id'];
        $this->game = $game;
        $this->roleData = $player['role_data'] ?? [];
    }
    
    abstract public function getName();
    abstract public function getEmoji();
    abstract public function getTeam();
    abstract public function getDescription();
    abstract public function getValidTargets($phase = 'night');
    
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
    }
    
    protected function setPlayerRole($playerId, $newRole) {
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $playerId) {
                $p['role'] = $newRole;
                break;
            }
        }
    }
    
    protected function sendMessage($text) {
        global $token;
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = ['chat_id' => $this->playerId, 'text' => $text, 'parse_mode' => 'HTML'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
    
    protected function sendMessageToPlayer($playerId, $text) {
        global $token;
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = ['chat_id' => $playerId, 'text' => $text, 'parse_mode' => 'HTML'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
    
    protected function sendMessageToGroup($text) {
        global $token;
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = ['chat_id' => $this->game['group_id'], 'text' => $text, 'parse_mode' => 'HTML'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
    
    protected function notifyWolfTeam($message) {
        foreach ($this->game['players'] as $p) {
            if ($this->isWolf($p['role']) && isset($p['alive']) && $p['alive'] === true) {
                $this->sendMessageToPlayer($p['id'], $message);
            }
        }
    }
    
    protected function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 
                      'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
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
        $fireIceRoles = ['fire_king', 'ice_queen', 'lilith', 'magento'];
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
    
    public function onGameStart() {}
    public function onNightStart() {}
    public function onNightEnd() {}
    public function onDayStart() {}
    public function onDayEnd() {}
    public function onDeath($killerRole = null) {}
    public function onAttacked($attackerRole, $attackerId) {}
    public function onPlayerDeath($deadPlayer) {}
}
