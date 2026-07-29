<?php
// game.php - فوق‌العاده ساده برای تست

file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - GAME.PHP IS LOADED!\n", FILE_APPEND);

function test_game_function() {
    return "Game is working!";
}
