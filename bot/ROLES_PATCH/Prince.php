<?php
/**
 * 🤴🏻 شاهزاده (Prince)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class Prince extends Role {
    
    public function getName() {
        return 'شاهزاده';
    }
    
    public function getEmoji() {
        return '🤴🏻';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $immunityUsed = $this->getData('immunity_used') ?? false;
        return "🤴🏻 تو شاهزاده هستی! یک بار می‌تونی جلوی اعدام خودت رو بگیری!" . ($immunityUsed ? "\n❌ استفاده شده." : "\n✅ موجود است.");
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onLynched() {
        $immunityUsed = $this->getData('immunity_used') ?? false;
        
        if (!$immunityUsed) {
            $this->setData('immunity_used', true);
            return [
                'lynched' => false,
                'message' => "🤴🏻 انگشتر سلطنتی رو نشون دادی! جلوی اعدام رو گرفتی!"
            ];
        }
        
        return [
            'lynched' => true,
            'message' => "🤴🏻 دیگه نتونستی جلوی اعدام رو بگیری!"
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}