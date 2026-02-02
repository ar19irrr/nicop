<?php
/**
 * 💾 دیتابیس ساده (فایل JSON)
 */

require_once 'config.php';

/**
 * 📂 گرفتن همه بازی‌ها
 */
function getAllGames() {
    $file = DATA_PATH . 'games.json';
    if (!file_exists($file)) {
        ensureDirectoryExists(DATA_PATH);
        file_put_contents($file, '{}');
        return [];
    }

    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * 💾 ذخیره همه بازی‌ها
 */
function saveAllGames($games) {
    $file = DATA_PATH . 'games.json';
    ensureDirectoryExists(DATA_PATH);
    file_put_contents($file, json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 🎮 گرفتن یه بازی با کد
 */
function getGame($code) {
    $games = getAllGames();
    return $games[$code] ?? null;
}

/**
 * 💾 ذخیره یه بازی
 */
function saveGame($game) {
    if (!isset($game['code'])) return false;
    
    $games = getAllGames();
    $games[$game['code']] = $game;
    saveAllGames($games);
    return true;
}

/**
 * 🗑️ حذف یه بازی
 */
function deleteGame($code) {
    $games = getAllGames();
    if (isset($games[$code])) {
        unset($games[$code]);
        saveAllGames($games);
        return true;
    }
    return false;
}

/**
 * 🔍 پیدا کردن بازی فعال یه گروه
 */
function getGroupActiveGame($group_id) {
    $games = getAllGames();

    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'], ['waiting', 'started'])) {
            return $game;
        }
    }

    return null;
}

/**
 * 🔍 پیدا کردن بازی فعال یه کاربر
 */
function getPlayerActiveGame($user_id) {
    $games = getAllGames();
    
    foreach ($games as $game) {
        if (!in_array($game['status'], ['waiting', 'started'])) continue;
        
        foreach ($game['players'] as $player) {
            if ($player['id'] == $user_id) {
                return $game;
            }
        }
    }
    
    return null;
}

/**
 * 🧹 پاک کردن بازی‌های قدیمی
 */
function cleanupOldGames() {
    $games = getAllGames();
    $now = time();
    $timeout = GAME_TIMEOUT * 2; // 10 دقیقه

    foreach ($games as $code => $game) {
        // بازی‌های در انتظار قدیمی
        if ($game['status'] == 'waiting' && ($now - $game['created']) > $timeout) {
            unset($games[$code]);
            continue;
        }
        
        // بازی‌های تمام شده قدیمی (بیشتر از ۲۴ ساعت)
        if ($game['status'] == 'ended' && isset($game['ended']) && ($now - $game['ended']) > 86400) {
            unset($games[$code]);
        }
    }

    saveAllGames($games);
}

/**
 * 📁 اطمینان از وجود پوشه
 */
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * 📊 آمار بازی‌ها
 */
function getGroupLinks() {
    $file = DATA_PATH . 'group_links.json';
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

/**
 * 💾 ذخیره لینک‌های گروه
 */
function saveGroupLinks($links) {
    $file = DATA_PATH . 'group_links.json';
    ensureDirectoryExists(DATA_PATH);
    file_put_contents($file, json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 📀 اندازه دیتابیس
 */
function getDatabaseSize() {
    $file = DATA_PATH . 'games.json';
    if (!file_exists($file)) return '0 KB';
    $size = filesize($file);
    if ($size < 1024) return $size . ' B';
    if ($size < 1024*1024) return round($size/1024, 2) . ' KB';
    return round($size/(1024*1024), 2) . ' MB';
}
