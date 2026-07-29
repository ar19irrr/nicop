<?php
/**
 * 💂🏻‍♂️ شکارچی (CultHunter)
 * تیم: روستا
 */

require_once __DIR__ . '/base.php';

class CultHunter extends Role {
    
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
        return "تو شکارچی 💂🏻‍♂️ هستی! امید روستایی‌ها برای از بین بردن دشمن‌ها. توی شکار فرقه‌ها ماهری، ولی اگه به خونه فرانکشتاین 🧟‍♂️ بری، ۱۰٪ احتمال داره قربانی اون بشی! همچنین ۳۰٪ احتمال داره به گرگ‌ها 🐺 یا ومپایرها 🧛 پی ببری و اونارو شکار کنی. ⚠️ نقطه ضعف اصلیت قاتل زنجیره‌ای 🔪 هست!";
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
            $this->sendMessageToPlayer($target, "🔪 شکارچی اومد خونت، ولی تو قاتل زنجیره‌ای بودی و کشتیش!");
            
            return [
                'success' => false,
                'message' => "💀 رفتی خونه قاتل زنجیره‌ای... این آخرین شکارت بود!",
                'died' => true
            ];
        }
        
        // فرانکشتاین - 10% احتمال قربانی شدن
        if ($targetRole == 'frankenstein') {
            if (rand(1, 100) <= 10) {
                $this->killPlayer($this->getId(), 'frankenstein');
                $this->sendMessageToPlayer($target, "🧟‍♂️ شکارچی اومد شکارت کنه، ولی تونستی اونو قربانی خودت کنی!");
                
                return [
                    'success' => false,
                    'message' => "🧟‍♂️ رفتی خونه فرانکشتاین... ولی اون تو رو قربانی کرد!",
                    'died' => true
                ];
            }
        }
        
        // گرگ یا ومپایر - 30% احتمال شکار
        if ($this->isWolf($targetRole) || $this->isVampireTeam($targetRole)) {
            if (rand(1, 100) <= 30) {
                $this->killPlayer($target, 'cult_hunter');
                $teamName = $this->isWolf($targetRole) ? 'گرگ' : 'ومپایر';
                
                return [
                    'success' => true,
                    'message' => "🎯 رفتی خونه {$targetPlayer['name']} و با ۳۰٪ شانس تونستی {$teamName} بودنش رو بفهمی و شکارش کنی!",
                    'killed' => $target
                ];
            } else {
                return [
                    'success' => true,
                    'message' => "🌙 رفتی خونه {$targetPlayer['name']} ولی نتونستی چیزی بفهمی...",
                    'killed' => false
                ];
            }
        }
        
        // فرقه‌گرا - شکار قطعی
        if ($this->isCultRole($targetRole)) {
            $this->killPlayer($target, 'cult_hunter');
            
            return [
                'success' => true,
                'message' => "🎯 رفتی خونه {$targetPlayer['name']} و تونستی شکارش کنی! یه فرقه‌گرا کمتر شد.",
                'killed' => $target
            ];
        }
        
        return [
            'success' => true,
            'message' => "🌙 رفتی خونه {$targetPlayer['name']} ولی هیچ چیز مشکوکی ندیدی...",
            'killed' => false
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'culturunter_' . $p['id']
            ];
        }
        return $targets;
    }
}