<?php
// index.php - نسخه نهایی

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - START\n", FILE_APPEND);

$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

$update = json_decode($json, true);
if (!$update) {
    http_response_code(200);
    echo '{"ok":true}';
    exit;
}

require_once __DIR__ . '/game.php';
require_once __DIR__ . '/commands.php';

if (function_exists('processUpdate')) {
    processUpdate($update);
}

http_response_code(200);
echo '{"ok":true}';
