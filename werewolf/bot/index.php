<?php
// index.php - نسخه خطایابی

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - START\n", FILE_APPEND);

$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - Input received\n", FILE_APPEND);

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - JSON decoded\n", FILE_APPEND);

// ===== تست فایل‌ها یکی یکی =====

// 1. config.php
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - config.php loaded\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - config.php NOT FOUND\n", FILE_APPEND);
    exit;
}

// 2. functions.php
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - functions.php loaded\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - functions.php NOT FOUND\n", FILE_APPEND);
    exit;
}

// 3. database.php
if (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - database.php loaded\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - database.php NOT FOUND\n", FILE_APPEND);
    exit;
}

// 4. game.php
if (file_exists(__DIR__ . '/game.php')) {
    require_once __DIR__ . '/game.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - game.php loaded\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - game.php NOT FOUND\n", FILE_APPEND);
    exit;
}

// 5. commands.php
if (file_exists(__DIR__ . '/commands.php')) {
    require_once __DIR__ . '/commands.php';
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - commands.php loaded\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - commands.php NOT FOUND\n", FILE_APPEND);
    exit;
}

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - All files loaded!\n", FILE_APPEND);

// ===== پردازش =====
if (function_exists('processUpdate')) {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - processUpdate exists!\n", FILE_APPEND);
    processUpdate($update);
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - processUpdate done\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - processUpdate NOT FOUND\n", FILE_APPEND);
}

http_response_code(200);
echo '{"ok":true}';
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - END\n", FILE_APPEND);
