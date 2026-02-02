<?php
/**
 * 💂🏻‍♂️ شکارچی (CultHunter) - آپدیت شده
 * 
 * تیم: روستا
 * شکار فرقه‌ها، 10% احتمال قربانی فرانکشتاین، 30% شکار گرگ/ومپایر
 * نقطه ضعف: قاتل زنجیره‌ای
 */

require_once __DIR__ . '/base.php';

class CultHunter extends Role {
    
    protected $huntedTonight = [];
    
    public function getName() {
        return 'شکارچی';
    }
    
    public function getEmoji() {
        return '💂🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو شکارچی💂🏻‍♂️ هستی! امید روستایی‌ها برای از بین بردن دشمن‌ها. توی شکار فرقه‌ها ماهری، ولی اگه به خونه فرانکشتاین🧟‍♂️🪖 بری، ۱۰٪ احتمال داره تو قربانی اون بشی! همچنین ۳۰٪ احتمال داره به گرگ‌ها🐺 یا ومپایرها🧛🏻 پی ببری و اونارو شکار کنی. ⚠️ حواست باشه، نقطه ضعف اصلیت قاتل زنجیره‌ای🔪 هست، هیچ شبی در خونش سبز نشو!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای به کدوم خونه بری برای شکار؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $targetRole = $targetPlayer['role'];
        
        // نقطه ضعف: قاتل زنجیره‌ای
        if ($targetRole == 'serial_killer') {
            $this->killPlayer($this->getId(), 'serial_killer');
            $this->sendMessageToPlayer($target, "🔪 شکارچی اومد خونت، ولی تو قاتل زنجیره‌ای بودی و قبل از اینکه بتونه کاری بکنه، کشتیش!");
            
            return [
                'success' => false,
                'message' => "💀 رفتی خونه قاتل زنجیره‌ای... این آخرین شکارت بود! (نقطه ضعف اصلیت)",
                'died' => true
            ];
        }
        
        // فرانکشتاین - 10% احتمال قربانی شدن
        if ($targetRole == 'frankenstein') {
            $rand = rand(1, 100);
            if ($rand <= 10) {
                $this->killPlayer($this->getId(), 'frankenstein');
                $this->sendMessageToPlayer($target, "🧟‍♂️ شکارچی اومد شکارت کنه، ولی تونستی ۱۰٪ شانستو استفاده کنی و اونو قربانی خودت کنی!");
                
                return [
                    'success' => false,
                    'message' => "🧟‍♂️ رفتی خونه فرانکشتاین... ولی اون ۱۰٪ شانستش اومد و تو قربانی اون شدی!",
                    'died' => true
                ];
            }
        }
        
        // گرگ یا ومپایر - 30% احتمال شکار
        if ($this->isWolf($targetRole) || $this->isVampireTeam($targetRole)) {
            $rand = rand(1, 100);
            if ($rand <= 30) {
                $this->killPlayer($target, 'cult_hunter');
                
                $teamName = $this->isWolf($targetRole) ? 'گرگ' : 'ومپایر';
                
                return [
                    'success' => true,
                    'message' => "🎯 رفتی خونه {$targetPlayer['name']} و با ۳۰٪ شانس تونستی پی به نقش {$teamName} بودنش ببری و شکارش کنی!",
                    'killed' => $target
                ];
            } else {
                return [
                    'success' => true,
                    'message' => "🌙 رفتی خونه {$targetPlayer['name']} ولی نتونستی چیزی بفهمی... ۷۰٪ شانس ناموفق بود.",
                    'killed' => false
                ];
            }
        }
        
        // فرقه‌گرا معمولی - شکار قطعی
        if ($this->isCultRole($targetRole)) {
            $this->killPlayer($target, 'cult_hunter');
            
            return [
                'success' => true,
                'message' => "🎯 رفتی خونه {$targetPlayer['name']} و با مهارتت تونستی شکارش کنی! یه فرقه‌گرا کمتر شد.",
                'killed' => $target
            ];
        }
        
        // بقیه - هیچی
        return [
            'success' => true,
            'message' => "🌙 رفتی خونه {$targetPlayer['name']} ولی هیچ چیز مشکوزی ندیدی... این شخص عادی به نظر میاد.",
            'killed' => false
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'cult_hunter_' . $p['id']
            ];
        }
        return $targets;
    }
}