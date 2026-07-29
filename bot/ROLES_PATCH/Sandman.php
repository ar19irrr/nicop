<?php
/**
 * 💤 خوابگزار (Sandman)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Sandman extends Role {
    
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
        $used = $this->getData('used') ?? false;
        return "💤 تو خوابگزار هستی! یک بار می‌تونی همه رو بخوابونی و هیچ قدرتی شب کار نکنه!" . ($used ? "\n❌ استفاده شده." : "\n✅ موجود است.");
    }
    
    public function hasNightAction() {
        return !($this->getData('used') ?? false);
    }
    
    public function performNightAction($use = false) {
        $used = $this->getData('used') ?? false;
        
        if ($used) {
            return [
                'success' => false,
                'message' => '❌ قبلاً استفاده کردی!'
            ];
        }
        
        if (!$use) {
            return [
                'success' => false,
                'message' => 'امشب همه رو خواب نکردی.'
            ];
        }
        
        $this->setData('used', true);
        $this->setGameState('sleep_night', $this->getCurrentNight());
        $this->sendMessageToGroup("💤 خوابگزار همه رو به خواب فرو برد! امشب هیچ قدرتی کار نمی‌کنه!");
        
        return [
            'success' => true,
            'message' => "💤 همه رو به خواب فرو بردی!"
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $used = $this->getData('used') ?? false;
        
        if (!$used) {
            return [
                [
                    'id' => 'use',
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