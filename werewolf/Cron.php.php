<?php
/**
 * ⏰ مدیریت تایمرها و کرون جاب‌ها
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';
require_once 'game.php';

$action = $_GET['action'] ?? 'check';
$code = $_GET['code'] ?? null;
$group_id = $_GET['group_id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

switch ($action) {
    case 'check':
        checkAllGames();
        break;
        
    case 'extend':
        if ($code && $group_id && $user_id) {
            cronExtendGame($code, $group_id, $user_id);
        }
        break;
}

/**
 * ⏰ تمدید زمان از طریق کرون (برای استفاده داخلی)
 */
function cronExtendGame($code, $group_id, $user_id) {
    $game = getGame($code);
    
    if (!$game) {
        error_log("Extend failed: Game not found - $code");
        return;
    }
    
    if ($game['status'] != 'waiting') {
        error_log("Extend failed: Game not waiting - $code");
        return;
    }
    
    // چک کردن ادمین بودن
    if (!isAdmin($user_id, $group_id)) {
        error_log("Extend failed: User not admin - $user_id in $group_id");
        return;
    }
    
    // صدا زدن تابع اصلی
    $result = extendWaitingTime($group_id, $user_id);
    
    if ($result['success']) {
        sendMessage($group_id, $result['message']);
    }
}

/**
 * بررسی همه بازی‌ها
 */
function checkAllGames() {
    $games = getAllGames();
    $now = time();

    foreach ($games as $game) {
        if ($game['status'] == 'waiting') {
            checkWaitingGame($game, $now);
        } elseif ($game['status'] == 'started') {
            checkStartedGame($game, $now);
        }
    }

    cleanupOldGames();
}

/**
 * ✅ بررسی بازی در انتظار - شروع خودکار بعد از ۵ دقیقه
 */
function checkWaitingGame($game, $now) {
    if (!isset($game['wait_until']) || $now < $game['wait_until']) {
        return;
    }

    $playerCount = count($game['players']);

    if ($playerCount >= MIN_PLAYERS) {
        $result = startGame($game['group_id']);
        
        if ($result['success']) {
            sendMessage($game['group_id'], 
                "⏰ <b>زمان انتظار تمام شد!</b>\n" .
                "🎮 بازی با " . $playerCount . " نفر به صورت خودکار شروع شد!"
            );
        }
    } else {
        deleteGame($game['code']);
        sendMessage($game['group_id'], 
            "⏰ <b>زمان انتظار تمام شد!</b>\n" .
            "❌ تعداد بازیکنان کافی نبود (" . $playerCount . "/4)\n" .
            "بازی لغو شد."
        );
    }
}

/**
 * بررسی بازی در حال اجرا
 */
function checkStartedGame($game, $now) {
    if (isset($game['discussion_end']) && $game['phase'] == 'day') {
        if ($now >= $game['discussion_end']) {
            autoStartVoting($game['code']);
            return;
        }
    }

    if (isset($game['vote_end']) && $game['phase'] == 'vote') {
        if ($now >= $game['vote_end']) {
            autoEndVoting($game['code']);
            return;
        }
    }

    if (isset($game['night_end']) && $game['phase'] == 'night') {
        if ($now >= $game['night_end']) {
            autoEndNight($game['code']);
        }
    }
}

/**
 * پایان خودکار شب
 */
function autoEndNight($code) {
    $game = getGame($code);
    if (!$game || $game['phase'] != 'night') return;

    foreach ($game['players'] as $player) {
        if (!($player['alive'] ?? false)) continue;

        $hasAction = false;
        foreach ($game['night_actions'] as $action) {
            if ($action['player_id'] == $player['id']) {
                $hasAction = true;
                break;
            }
        }

        if (!$hasAction) {
            foreach ($game['players'] as &$p) {
                if ($p['id'] == $player['id']) {
                    $p['afk_count'] = ($p['afk_count'] ?? 0) + 1;
                    
                    if ($p['afk_count'] >= AFK_THRESHOLD) {
                        $game = killPlayer($game, $player['id'], 'afk');
                        sendMessage($game['group_id'], 
                            "😴 <b>{$player['name']}</b> به خاطر غیرفعالی در شب اخراج شد!"
                        );
                    }
                    break;
                }
            }
        }
    }

    saveGame($game);
    startDayPhase($game);
}

echo "✅ Cron job executed!\n";