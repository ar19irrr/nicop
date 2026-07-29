<?php
/**
 * 💾 دیتابیس ساده (فایل JSON)
 */

require_once 'config.php';

// ==================== توابع اصلی ====================

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
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
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
 * 🎮 گرفتن یک بازی با کد
 */
function getGame($code) {
    $games = getAllGames();
    return isset($games[$code]) ? $games[$code] : null;
}

/**
 * 💾 ذخیره یک بازی
 */
function saveGame($game) {
    if (!isset($game['code'])) return false;
    
    $games = getAllGames();
    $games[$game['code']] = $game;
    saveAllGames($games);
    return true;
}

/**
 * 🗑️ حذف یک بازی
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

// ==================== توابع جستجو ====================

/**
 * 🔍 پیدا کردن بازی فعال یک گروه
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
 * 🔍 پیدا کردن بازی فعال یک کاربر
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
 * 🔍 پیدا کردن بازیکن در بازی
 */
function getPlayerById($game, $id) {
    foreach ($game['players'] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

/**
 * 🔍 گرفتن بازیکنان زنده
 */
function getAlivePlayers($game) {
    return array_filter($game['players'], function($p) {
        return isset($p['alive']) && $p['alive'] === true;
    });
}

/**
 * 🔍 گرفتن بازیکنان مرده
 */
function getDeadPlayers($game) {
    return array_filter($game['players'], function($p) {
        return !isset($p['alive']) || $p['alive'] === false;
    });
}

// ==================== توابع لینک گروه ====================

/**
 * 📂 گرفتن لینک‌های گروه
 */
function getGroupLinks() {
    $file = DATA_PATH . 'group_links.json';
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * 💾 ذخیره لینک‌های گروه
 */
function saveGroupLinks($links) {
    $file = DATA_PATH . 'group_links.json';
    ensureDirectoryExists(DATA_PATH);
    file_put_contents($file, json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ==================== توابع نگهداری ====================

/**
 * 🧹 پاک کردن بازی‌های قدیمی
 */
function cleanupOldGames() {
    $games = getAllGames();
    $now = time();
    $timeout = GAME_TIMEOUT * 2;
    $changed = false;

    foreach ($games as $code => $game) {
        // بازی‌های در انتظار قدیمی
        if ($game['status'] == 'waiting' && ($now - $game['created']) > $timeout) {
            unset($games[$code]);
            $changed = true;
            continue;
        }
        
        // بازی‌های تمام شده قدیمی (بیشتر از ۲۴ ساعت)
        if ($game['status'] == 'ended' && isset($game['ended']) && ($now - $game['ended']) > 86400) {
            unset($games[$code]);
            $changed = true;
        }
    }

    if ($changed) {
        saveAllGames($games);
    }
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
 * 📊 اندازه دیتابیس
 */
function getDatabaseSize() {
    $file = DATA_PATH . 'games.json';
    if (!file_exists($file)) return '0 KB';
    $size = filesize($file);
    if ($size < 1024) return $size . ' B';
    if ($size < 1024 * 1024) return round($size / 1024, 2) . ' KB';
    return round($size / (1024 * 1024), 2) . ' MB';
}

/**
 * 🗃️ بکاپ گرفتن از دیتابیس
 */
function backupDatabase() {
    $file = DATA_PATH . 'games.json';
    if (!file_exists($file)) return false;
    
    $backupDir = DATA_PATH . 'backups/';
    ensureDirectoryExists($backupDir);
    
    $backupFile = $backupDir . 'games_' . date('Y-m-d_H-i-s') . '.json';
    copy($file, $backupFile);
    
    // حذف بکاپ‌های قدیمی (فقط ۱۰ تا آخرین)
    $files = glob($backupDir . 'games_*.json');
    if (count($files) > 10) {
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        $toDelete = array_slice($files, 0, count($files) - 10);
        foreach ($toDelete as $f) {
            unlink($f);
        }
    }
    
    return true;
}