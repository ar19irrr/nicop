<?php
/**
 * 💤🐺 گرگ خوابالو (BetaWolf)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class BetaWolf extends Role {
    
    public function getName() {
        return 'گرگ خوابالو';
    }
    
    public function getEmoji() {
        return '💤🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        return "تو گرگ خوابالو 💤🐺 هستی! بخاطر تنبلی و شکمو بودنت همیشه خواب می‌مونی و جایی در دسته‌ی گرگ‌ها نداری و اون‌ها رو نمی‌شناسی. اما هر دو شب خواب یکی از اهالی رو می‌بینی و متوجه نقشش می‌شی. اگر توسط شوالیه یا تفنگدار مورد هدف قرار بگیری، قبل از مردن می‌خوریشون!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        $sleepCounter = $this->getData('sleep_counter') ?? 0;
        $sleepCounter++;
        $this->setData('sleep_counter', $sleepCounter);
        
        // شب‌های فرد: خواب و رویا
        if ($sleepCounter % 2 == 1) {
            if (!$target) {
                return [
                    'success' => false,
                    'message' => '💤 کی رو می‌خوای در خواب ببینی؟'
                ];
            }
            
            $targetPlayer = $this->getPlayerById($target);
            if (!$targetPlayer || !$targetPlayer['alive']) {
                return [
                    'success' => false,
                    'message' => '❌ بازیکن نامعتبر!'
                ];
            }
            
            $realRole = $targetPlayer['role'];
            $dreams = $this->getData('dreams') ?? [];
            $dreams[$target] = $realRole;
            $this->setData('dreams', $dreams);
            
            $roleName = getRoleDisplayName($realRole);
            
            return [
                'success' => true,
                'message' => "💭 در خواب دیدی که {$targetPlayer['name']} یک {$roleName} هست!",
                'dream' => true
            ];
        }
        
        // شب‌های زوج: حمله عادی
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ به کی می‌خوای حمله کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->logAction('beta_wolf_attack', $target);
        
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onAttacked($attackerId, $attackerRole) {
        if (in_array($attackerRole, ['knight', 'gunner'])) {
            $attacker = $this->getPlayerById($attackerId);
            if ($attacker && isset($attacker['alive']) && $attacker['alive'] === true) {
                $this->killPlayer($attackerId, 'beta_wolf_revenge');
                $this->sendMessageToGroup("💥 گرگ خوابالو قبل از مردن، {$attacker['name']} رو به عنوان آخرین شام خورد!");
                return ['killed_attacker' => true, 'died' => true];
            }
        }
        return ['killed_attacker' => false, 'died' => true];
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        $sleepCounter = $this->getData('sleep_counter') ?? 0;
        
        foreach ($this->getOtherAlivePlayers() as $p) {
            // شب‌های فرد: فقط خواب (می‌تونه هر کسی رو ببینه)
            if ($sleepCounter % 2 == 1) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => '💭 ' . $p['name'],
                    'callback' => 'beta_wolf_' . $p['id']
                ];
            } else {
                // شب‌های زوج: حمله (فقط غیر گرگ)
                if (!$this->isWolf($p['role'])) {
                    $targets[] = [
                        'id' => $p['id'],
                        'name' => $p['name'],
                        'callback' => 'beta_wolf_' . $p['id']
                    ];
                }
            }
        }
        return $targets;
    }
}