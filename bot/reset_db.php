<?php
// reset_db.php - پاک کردن دیتابیس

$data_path = __DIR__ . '/data/';
file_put_contents($data_path . 'games.json', '{}');
file_put_contents($data_path . 'coins.json', '{}');
file_put_contents($data_path . 'ranks.json', '{}');
file_put_contents($data_path . 'reports.json', '[]');
file_put_contents($data_path . 'group_settings.json', '{}');
file_put_contents($data_path . 'group_links.json', '{}');
file_put_contents($data_path . 'scores.json', '{}');

echo "✅ همه دیتابیس‌ها پاک شدند!";
