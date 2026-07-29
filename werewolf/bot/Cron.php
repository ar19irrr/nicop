<?php
/**
 * ⏰ مدیریت تایمرها و کرون جاب‌ها
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';
require_once 'game.php';

// ==================== ثابت‌ها ====================

if (!defined('WAITING_TIME')) define('WAITING_TIME', 300);
if (!defined('AFK_THRESHOLD')) define('AFK_THRESHOLD', 2);

// ==================== پردازش درخواست ====================

$action = $_GET['action'] ?? 'check';
$code = $_GET['code'] ?? null;

switch ($action) {
    case 'check':
        checkAllGames();
        break;
        
    case 'cleanup':
        cleanupOldGames();
        echo "✅ Cleanup completed!\n";
        break;
        
    case 'backup':
        backupDatabase();
        echo "✅ Backup completed!\n";
        break;
        
    case 'status':
        showStatus();
        break;
        
    default:
        echo "❌ Unknown action!\n";
        echo "Available actions: check, cleanup, backup, status\n";
}

// ==================== توابع اصلی ====================

/**
 * ✅ بررسی همه بازی‌ها
 */
function checkAllGames() {
    $games = getAllGames();
    $now = time();
    $checked = 0;

    foreach ($games as $game) {
        if ($game['status'] == 'waiting') {
            checkWaitingGame($game, $now);
            $checked++;
        } elseif ($game['status'] == 'started') {
            checkStartedGame($game, $now);
            $checked++;
        }
    }

    // پاکسازی خودکار
    cleanupOldGames();

    if ($checked > 0) {
        echo "✅ Checked $checked games at " . date('Y-m-d H:i:s') . "\n";
    } else {
        echo "✅ No active games at " . date('Y-m-d H:i:s') . "\n";
    }
}

/**
 * ✅ بررسی بازی در انتظار
 */
function checkWaitingGame($game, $now) {
    if (!isset($game['wait_until']) || $now < $game['wait_until']) {
        return;
    }

    $playerCount = count($game['players']);

    if ($playerCount >= MIN_PLAYERS) {
        $result = startGame($game['group_id']);
        
        if ($result['success']) {
            $msg = "⏰ <b>زمان انتظار تمام شد!</b>\n\n";
            $msg .= "🎮 بازی با " . $playerCount . " نفر به صورت خودکار شروع شد!";
            sendMessage($game['group_id'], $msg);
            
            echo "✅ Game {$game['code']} started automatically with $playerCount players\n";
        } else {
            echo "❌ Failed to start game {$game['code']}: " . $result['message'] . "\n";
        }
    } else {
        $msg = "⏰ <b>زمان انتظار تمام شد!</b>\n\n";
        $msg .= "❌ تعداد بازیکنان کافی نبود (" . $playerCount . "/" . MIN_PLAYERS . ")\n";
        $msg .= "بازی لغو شد.";
        
        sendMessage($game['group_id'], $msg);
        deleteGame($game['code']);
        
        echo "❌ Game {$game['code']} cancelled - not enough players ($playerCount)\n";
    }
}

/**
 * ✅ بررسی بازی در حال اجرا
 */
function checkStartedGame($game, $now) {
    // بررسی فاز روز - پایان بحث
    if (isset($game['discussion_end']) && $game['phase'] == 'day') {
        if ($now >= $game['discussion_end']) {
            autoStartVoting($game['code']);
            echo "⏰ Voting started for game {$game['code']}\n";
            return;
        }
    }

    // بررسی فاز رأی‌گیری - پایان رأی
    if (isset($game['vote_end']) && $game['phase'] == 'vote') {
        if ($now >= $game['vote_end']) {
            autoEndVoting($game['code']);
            echo "⏰ Voting ended for game {$game['code']}\n";
            return;
        }
    }

    // بررسی فاز شب - پایان شب
    if (isset($game['night_end']) && $game['phase'] == 'night') {
        if ($now >= $game['night_end']) {
            autoEndNight($game['code']);
            echo "⏰ Night ended for game {$game['code']}\n";
            return;
        }
    }
}

/**
 * 🌙 پایان خودکار شب
 */
function autoEndNight($code) {
    $game = getGame($code);
    if (!$game || $game['phase'] != 'night') return;

    // بررسی بازیکنان غیرفعال
    $afkPlayers = [];
    foreach ($game['players'] as &$player) {
        if (!($player['alive'] ?? false)) continue;

        // بررسی اینکه آیا بازیکن اکشن شب انجام داده
        $hasAction = false;
        foreach ($game['night_actions'] as $action) {
            if ($action['player_id'] == $player['id']) {
                $hasAction = true;
                break;
            }
        }

        if (!$hasAction) {
            $player['afk_count'] = ($player['afk_count'] ?? 0) + 1;
            
            if ($player['afk_count'] >= AFK_THRESHOLD) {
                $afkPlayers[] = $player;
            }
        }
    }
    saveGame($game);

    // حذف بازیکنان غیرفعال
    foreach ($afkPlayers as $afkPlayer) {
        $game = killPlayer($game, $afkPlayer['id'], 'afk');
        sendMessage($game['group_id'], 
            "😴 <b>" . $afkPlayer['name'] . "</b> به خاطر غیرفعالی در شب اخراج شد!"
        );
    }

    // شروع روز
    startDayPhase($game);
}

/**
 * 📊 نمایش وضعیت
 */
function showStatus() {
    $games = getAllGames();
    $total = count($games);
    $waiting = 0;
    $started = 0;
    $ended = 0;
    $totalPlayers = 0;

    foreach ($games as $game) {
        if ($game['status'] == 'waiting') $waiting++;
        elseif ($game['status'] == 'started') $started++;
        elseif ($game['status'] == 'ended') $ended++;
        $totalPlayers += count($game['players']);
    }

    echo "📊 <b>وضعیت ربات</b>\n";
    echo "============================\n";
    echo "⏰ زمان: " . date('Y-m-d H:i:s') . "\n";
    echo "🎮 کل بازی‌ها: $total\n";
    echo "   ⏳ در انتظار: $waiting\n";
    echo "   ▶️ در حال اجرا: $started\n";
    echo "   🏁 تمام شده: $ended\n";
    echo "👥 کل بازیکنان: $totalPlayers\n";
    echo "📊 اندازه دیتابیس: " . getDatabaseSize() . "\n";
    echo "============================\n";
}

// ==================== اجرای مستقیم از خط فرمان ====================

// اگه از خط فرمان اجرا شده، خروجی بده
if (php_sapi_name() === 'cli') {
    if ($action == 'check') {
        echo "⏰ Running cron check at " . date('Y-m-d H:i:s') . "\n";
        checkAllGames();
    } elseif ($action == 'status') {
        showStatus();
    }
}