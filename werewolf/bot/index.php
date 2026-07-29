<?php
/**
 * 🎯 نقطه ورود اصلی - Webhook Handler
 * 
 * این فایل توسط تلگرام صدا زده میشه و آپدیت‌ها رو پردازش میکنه
 */

// ==================== تنظیمات اولیه ====================

// ⏱️ پاسخ سریع به تلگرام (مهم! اگه دیر بشه تلگرام retry میکنه)
http_response_code(200);
echo '{"ok":true}';

// ==================== دریافت و اعتبارسنجی ورودی ====================

// گرفتن داده خام از تلگرام
$json = file_get_contents('php://input');

// اگه داده خالی بود، خارج شو
if (empty($json)) {
    exit;
}

// 🔄 تبدیل JSON به آرایه
$data = json_decode($json, true);

// اگه JSON نامعتبر بود
if (!$data || !is_array($data)) {
    error_log("Invalid JSON received: " . substr($json, 0, 500));
    exit;
}

// ==================== لود کردن فایل‌ها ====================

// تعریف مسیر پایه
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/');
}

// بارگذاری فایل‌های اصلی
require_once BASE_PATH . 'config.php';
require_once BASE_PATH . 'functions.php';
require_once BASE_PATH . 'database.php';
require_once BASE_PATH . 'game.php';
require_once BASE_PATH . 'commands.php';

// بارگذاری factory نقش‌ها (در صورت وجود)
$rolesPath = BASE_PATH . 'ROLES_PATCH/';
if (is_dir($rolesPath) && file_exists($rolesPath . 'factory.php')) {
    require_once $rolesPath . 'factory.php';
}

// ==================== پردازش آپدیت ====================

try {
    // تابع processUpdate در commands.php تعریف شده
    if (function_exists('processUpdate')) {
        processUpdate($data);
    } else {
        error_log("Function processUpdate not found!");
        if (defined('ADMIN_ID') && ADMIN_ID && function_exists('sendMessage')) {
            sendMessage(ADMIN_ID, "⚠️ خطا: تابع processUpdate پیدا نشد!");
        }
    }
} catch (Exception $e) {
    // خطا را لاگ کن
    error_log("Error processing update: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // به ادمین اطلاع بده
    if (defined('ADMIN_ID') && ADMIN_ID && function_exists('sendMessage')) {
        sendMessage(ADMIN_ID, 
            "❌ <b>خطا در پردازش:</b>\n\n" .
            "📝 " . htmlspecialchars($e->getMessage()) . "\n\n" .
            "📂 " . htmlspecialchars($e->getFile()) . ":" . $e->getLine()
        );
    }
}

// ✅ تمام
exit;