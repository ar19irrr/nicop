<?php
// index.php - فوق‌العاده ساده

// دریافت داده از تلگرام
$json = file_get_contents('php://input');

// اگه داده اومد، توی یه فایل ذخیره کن
if (!empty($json)) {
    file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . "\n" . $json . "\n---\n", FILE_APPEND);
    
    // پاسخ ساده به تلگرام
    $update = json_decode($json, true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        
        // ارسال پاسخ ساده
        $token = '8520546535:AAGUOnE7GYqTKb3jvt49DO_RatT8bgcWSNA';
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = ['chat_id' => $chat_id, 'text' => "✅ پیام دریافت شد: $text"];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        
        file_put_contents('telegram_log.txt', "Response: " . $result . "\n", FILE_APPEND);
    }
}

// همیشه پاسخ 200 برگردون
http_response_code(200);
echo '{"ok":true}';
