<?php
// game.php - نسخه فوق‌العاده ساده

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function createGame($group_id, $creator_id, $creator_name) {
    $code = generateGameCode();
    return [
        'success' => true,
        'message' => "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>",
        'code' => $code
    ];
}

function joinGame($code, $user_id, $user_name) {
    return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!"];
}

function leaveGame($user_id, $chat_id) {
    return ['success' => true, 'message' => "✅ خارج شدید!"];
}

function startGame($group_id, $user_id = null) {
    return ['success' => true, 'message' => "▶️ بازی شروع شد!"];
}

function cancelGame($group_id, $user_id) {
    return ['success' => true, 'message' => "❌ لغو شد!"];
}

function extendWaitingTime($group_id, $user_id) {
    return ['success' => true, 'message' => "⏱ تمدید شد!"];
}

function setGameTiming($group_id, $user_id, $timing) {
    return ['success' => true, 'message' => "⚙️ تنظیم شد!"];
}

function getGameInfo($group_id) {
    return ['success' => true, 'message' => "ℹ️ اطلاعات بازی"];
}

function getGameStats() {
    return ['total' => 0, 'waiting' => 0, 'started' => 0, 'ended' => 0];
}

function getDatabaseSize() {
    return "0 KB";
}

function handleTeamChat($user_id, $message, $gameCode) {
    return ['success' => true, 'message' => "✅ ارسال شد!"];
}

function killPlayer($game, $playerId, $cause) {
    return $game;
}

function checkWinCondition($game) {
    return ['ended' => false];
}

function endGame($game, $winCheck) {
    return true;
}

function cleanupOldGames() {
    return true;
}

function getAllGames() {
    return [];
}

function saveAllGames($games) {
    return true;
}

function getGame($code) {
    return null;
}

function saveGame($game) {
    return true;
}

function deleteGame($code) {
    return true;
}

function getGroupActiveGame($group_id) {
    return null;
}

function getPlayerActiveGame($user_id) {
    return null;
}

function getAlivePlayers($game) {
    return [];
}

function getPlayerById($game, $id) {
    return null;
}

function isAdmin($user_id, $group_id) {
    return true;
}

function getRoleDisplayName($role) {
    $names = [
        'villager' => '👨‍🌾 روستایی',
        'seer' => '👳🏻‍♂️ پیشگو',
        'werewolf' => '🐺 گرگینه',
        'guardian_angel' => '👼🏻 فرشته نگهبان',
        'hunter' => '👮🏻‍♂️ کلانتر',
        'serial_killer' => '🔪 قاتل',
        'vampire' => '🧛🏻‍♂️ ومپایر',
        'joker' => '🤡 جوکر',
        'tanner' => '👺 منافق'
    ];
    return $names[$role] ?? '❓ ' . $role;
}

function getGroupLinks() {
    return [];
}

function saveGroupLinks($links) {
    return true;
}
