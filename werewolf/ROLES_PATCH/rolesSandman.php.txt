<?php
/**
 * 💤 خوابگزار
 */

require_once __DIR__ . '/base.php';

class Sandman extends Role {
    
    private $used = false;
    
    public function getName() {
        return 'خوابگزار';
    }
    
    public function getEmoji() {
        return '💤';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو خوابگزار💤 هستی. یک بار در هر بازی می‌تونی از جادوت استفاده کنی که یک شب همه رو به خواب فرو ببری. جوری که هیچکس نمی‌تونه در اون شب از توانایی‌هاش استفاده کنه!";
    }
    
    public function hasNightAction() {
        return !$this->used;
    }
    
    public function performNightAction($use = false) {
        if (!$use) {
            return [
                'success' => false,
                'message' => 'امشب همه رو خواب نکردی.'
            ];
        }
        
        if ($this->used) {
            return [
                'success' => false,
                'message' => '❌ قبلاً از جادوت استفاده کردی!'
            ];
        }
        
        $this->used = true;
        $this->game['sleep_night'] = $this->game['night_count'] ?? 1;
        saveGame($this->game);
        
        // اطلاع به همه
        $this->notifyAll("خوابگزار💤 با ورد جادویی همه رو به خواب فرو برد! امشب هیچکس نمی‌تونه از قدرتش استفاده کنه!");
        
        return [
            'success' => true,
            'message' => "💤 همه رو به خواب فرو بردی! امشب هیچ قدرتی کار نمی‌کنه!"
        ];
    }
    
    private function notifyAll($message) {
        sendGroupMessage($this->game['group_id'], $message);
    }
    
    public function getValidTargets($phase = 'night') {
        if (!$this->used) {
            return [
                [
                    'id' => 'use_sleep',
                    'name' => '💤 همه رو بخوابون',
                    'callback' => 'sandman_use'
                ],
                [
                    'id' => 'skip',
                    'name' => '⏭️ فعلاً نه',
                    'callback' => 'sandman_skip'
                ]
            ];
        }
        return [];
    }
}