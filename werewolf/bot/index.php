<?php
// index.php - ساده‌ترین نسخه ممکن برای تست

// ۱. لاگ می‌نویسیم تا ببینیم اصلاً فایل اجرا میشه یا نه
file_put_contents(__DIR__ . '/bot.log', date('Y-m-d H:i:s') . " - index.php is called!\n", FILE_APPEND);

// ۲. یک پاسخ ساده به هر درخواستی می‌دیم
http_response_code(200);
echo "OK";
