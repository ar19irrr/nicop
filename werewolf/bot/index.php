// پردازش
try {
    if (function_exists('processUpdate')) {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Calling processUpdate\n", FILE_APPEND);
        processUpdate($update);
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - processUpdate done\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - processUpdate NOT FOUND\n", FILE_APPEND);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - processUpdate ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}
