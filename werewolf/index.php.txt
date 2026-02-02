<?php
/**
 * 🎯 ورودی اصلی - Webhook
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'commands.php';

$input = file_get_contents('php://input');

if (empty($input)) {
    echo '🐺 ' . BOT_NAME . ' Bot is running!';
    exit;
}

$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    echo 'Invalid JSON';
    exit;
}

processUpdate($update);

http_response_code(200);
echo 'OK';