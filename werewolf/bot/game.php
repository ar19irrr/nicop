<?php
// game.php - نسخه ساده و خطاگیری شده

// ===== توابع اصلی =====

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function createGame($group_id, $creator_id, $creator_name) {
    try {
        $code = generateGameCode();
        
        $game = [
            'code' => $code,
            'group_id' => $group_id,
            'creator_id' => $creator_id,
            'creator_name' => $creator_name,
            'players' => [
                ['id' => $creator_id, 'name' => $creator_name, 'alive' => true]
            ],
            'status' => 'waiting',
            'created' => time()
        ];
        
        saveGame($game);
        
        return [
            'success' => true,
            'message' => "🐺 بازی ساخته شد!\n🎲 کد: <code>$code</code>",
            'code' => $code
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ خطا: ' . $e->getMessage()];
    }
}

function joinGame($code, $user_id, $user_name) {
    try {
        $game = getGame($code);
        if (!$game) {
            return ['success' => false, 'message' => '❌ بازی پیدا نشد!'];
        }
        if ($game['status'] != 'waiting') {
            return ['success' => false, 'message' => '⏳ بازی شروع شده!'];
        }
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) {
                return ['success' => false, 'message' => '❌ شما در بازی هستید!'];
            }
        }
        $game['players'][] = ['id' => $user_id, 'name' => $user_name, 'alive' => true];
        saveGame($game);
        return ['success' => true, 'message' => "✅ $user_name به بازی پیوست!"];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ خطا: ' . $e->getMessage()];
    }
}

function startGame($group_id, $user_id = null) {
    try {
        $game = getGroupActiveGame($group_id);
        if (!$game) {
            return ['success' => false, 'message' => '❌ بازی نیست!'];
        }
        if ($game['status'] != 'waiting') {
            return ['success' => false, 'message' => '⏳ شروع شده!'];
        }
        if (count($game['players']) < 4) {
            return ['success' => false, 'message' => '❌ حداقل ۴ نفر!'];
        }
        $game['status'] = 'started';
        saveGame($game);
        return ['success' => true, 'message' => "🎮 بازی با " . count($game['players']) . " نفر شروع شد!"];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ خطا: ' . $e->getMessage()];
    }
}

function cancelGame($group_id, $user_id) {
    try {
        $game = getGroupActiveGame($group_id);
        if (!$game) {
            return ['success' => false, 'message' => '❌ بازی نیست!'];
        }
        deleteGame($game['code']);
        return ['success' => true, 'message' => '❌ بازی لغو شد!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ خطا: ' . $e->getMessage()];
    }
}

function leaveGame($user_id, $chat_id) {
    try {
        $game = getPlayerActiveGame($user_id);
        if (!$game) {
            return ['success' => false, 'message' => '❌ شما در بازی نیستید!'];
        }
        foreach ($game['players'] as $key => $p) {
            if ($p['id'] == $user_id) {
                unset($game['players'][$key]);
                $game['players'] = array_values($game['players']);
                break;
            }
        }
        if (empty($game['players'])) {
            deleteGame($game['code']);
            return ['success' => true, 'message' => '✅ بازی لغو شد!'];
        }
        saveGame($game);
        return ['success' => true, 'message' => '✅ خارج شدید!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '❌ خطا: ' . $e->getMessage()];
    }
}

// ===== توابع دیتابیس =====

function getAllGames() {
    $file = __DIR__ . '/data/games.json';
    if (!file_exists($file)) {
        if (!is_dir(__DIR__ . '/data')) {
            mkdir(__DIR__ . '/data', 0777, true);
        }
        file_put_contents($file, '{}');
        return [];
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveAllGames($games) {
    $file = __DIR__ . '/data/games.json';
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }
    file_put_contents($file, json_encode($games, JSON_PRETTY_PRINT));
}

function getGame($code) {
    $games = getAllGames();
    return $games[$code] ?? null;
}

function saveGame($game) {
    if (!isset($game['code'])) return false;
    $games = getAllGames();
    $games[$game['code']] = $game;
    saveAllGames($games);
    return true;
}

function deleteGame($code) {
    $games = getAllGames();
    unset($games[$code]);
    saveAllGames($games);
    return true;
}

function getGroupActiveGame($group_id) {
    $games = getAllGames();
    foreach ($games as $game) {
        if ($game['group_id'] == $group_id && in_array($game['status'] ?? '', ['waiting', 'started'])) {
            return $game;
        }
    }
    return null;
}

function getPlayerActiveGame($user_id) {
    $games = getAllGames();
    foreach ($games as $game) {
        if (!in_array($game['status'] ?? '', ['waiting', 'started'])) continue;
        foreach ($game['players'] as $p) {
            if ($p['id'] == $user_id) return $game;
        }
    }
    return null;
}

function getAlivePlayers($game) {
    return array_filter($game['players'] ?? [], fn($p) => $p['alive'] ?? false);
}

function getPlayerById($game, $id) {
    foreach ($game['players'] ?? [] as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
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

function isAdmin($user_id, $group_id) {
    return true;
}

function extendWaitingTime($group_id, $user_id) {
    return ['success' => true, 'message' => '⏱ تمدید شد!'];
}

function setGameTiming($group_id, $user_id, $timing) {
    return ['success' => true, 'message' => '⚙️ تنظیم شد!'];
}

function getGameInfo($group_id) {
    $game = getGroupActiveGame($group_id);
    if (!$game) {
        return ['success' => false, 'message' => '❌ بازی نیست!'];
    }
    $msg = "🎮 اطلاعات بازی\n";
    $msg .= "🎲 کد: <code>" . $game['code'] . "</code>\n";
    $msg .= "👤 سازنده: " . $game['creator_name'] . "\n";
    $msg .= "👥 بازیکنان: " . count($game['players']) . " نفر";
    return ['success' => true, 'message' => $msg];
}

function getGameStats() {
    return ['total' => 0, 'waiting' => 0, 'started' => 0, 'ended' => 0, 'today' => 0];
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

function getGroupLinks() {
    return [];
}

function saveGroupLinks($links) {
    return true;
}
?>
