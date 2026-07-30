<?php
// این فایل برای گرفتن لاگ واقعی خطاهاست
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
file_put_contents('debug_log.txt', "ربات بالا آمد\n", FILE_APPEND);
echo "فایل لاگ در پوشه ساخته شد.";
