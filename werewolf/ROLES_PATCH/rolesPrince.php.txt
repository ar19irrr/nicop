<?php
/**
 * 🤴🏻 شاهزاده
 */

require_once __DIR__ . '/base.php';

class Prince extends Role {
    
    private $lynchImmunityUsed = false;
    
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
        return "تو 🤴🏻شاهزاده‌ای انگشتر سلطنتی در دست داری. اگر در زمان رای‌گیری بیشتر بازیکن‌ها بهت رای بدن، با نشون دادن انگشترت جلوی اعدام شدنت رو می‌گیری! (فقط یه بار)";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onLynched() {
        if (!$this->lynchImmunityUsed) {
            $this->lynchImmunityUsed = true;
            $this->setData('immunity_used', true);
            
            return [
                'lynched' => false,
                'message' => "🤴🏻 خواستن اعدامت کنن ولی انگشتر سلطنتی رو نشون دادی! جلوی اعدام رو گرفتی! (فقط همین یه بار)"
            ];
        }
        
        return [
            'lynched' => true,
            'message' => "🤴🏻 دوباره رای آوردی ولی دیگه نتونستی جلوی اعدام رو بگیری!"
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}