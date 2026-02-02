<?php
/**
 * 🧱 حمال (Hamal)
 * تیم: روستا
 * 
 * هر شب یه نفر رو نگه می‌داره، اون شب نمی‌تونه کاری بکنه
 * اگه منفی بیاد خونش، نمی‌تونه کاری بکنه (حمال جلوشو می‌گیره)
 * اگه خونه کسی بره، اون متوجه می‌شه حماله و لو می‌ره
 * اگه خونه فرقه بره، تبدیل به فرقه می‌شه
 * نمی‌تونه دو شب پشت سر هم خونه یه نفر بره
 */

require_once __DIR__ . '/base.php';

class Hamal extends Role {
    
    protected $lastTarget = null;    // شب قبل کجا بود
    protected $isRevealed = false;   // لو رفته؟
    
    public function getName() {
        return 'حمال';
    }
    
    public function getEmoji() {
        return '🧱';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $revealedText = $this->isRevealed ? "\n⚠️ <b>لو رفتی!</b> یه نفر فهمیده حمالی!" : "";
        return "🧱 تو حمال هستی! هر شب می‌تونی بری خونه یه نفر و اونو نگه داری. اون شب نه می‌تونه از قابلیتش استفاده کنه، نه کسی می‌تونه بهش حمله کنه (حمال جلوشو می‌گیره). ولی اگه خونه کسی بری، اون متوجه می‌شه حمالی و لو می‌ری! اگه خونه فرقه بری، تبدیل به فرقه می‌شی. نمی‌تونی دو شب پشت سر هم خونه یه نفر بری. $revealedText";
    }
    
    public function hasNightAction() {
        return !$this->isRevealed;  // اگه لو رفته، دیگه نمی‌تونه بره
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ باید یه نفر رو انتخاب کنی که ببریش!'
            ];
        }
        
        // چک کردن دو شب پشت سر هم
        if ($this->lastTarget == $target) {
            return [
                'success' => false,
                'message' => '❌ دیشب هم خونه همین نفر بودی! نمی‌تونی دو شب پشت سر هم بری اونجا.'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !($targetPlayer['alive'] ?? false)) {
            return [
                'success' => false,
                'message' => '❌ این بازیکن مرده!'
            ];
        }
        
        $targetRole = $targetPlayer['role'];
        
        // ثبت هدف
        $this->lastTarget = $target;
        $this->setData('last_target', $target);
        
        // ====== اگه خونه فرقه بره ======
        if ($this->isCultRole($targetRole)) {
            // تبدیل به فرقه
            $this->convertToCult();
            
            return [
                'success' => true,
                'message' => "🧱 رفتی خونه {$targetPlayer['name']}... ولی دیدی داره یه خدای عجیب رو عبادت می‌کنه! تحت تأثیر قرار گرفتی و تبدیل به فرقه‌گرا شدی!",
                'converted_to_cult' => true,
                'blocked' => true
            ];
        }
        
        // ====== لو رفتن ======
        // به هدف می‌گه که حمال اومده (به جز فرقه که بالا هندل شد)
        $this->revealToTarget($targetPlayer);
        
        // ====== اگه خودش لو رفته باشه ======
        if ($this->isRevealed) {
            // اطلاع به همه که حمال لو رفته
            $this->notifyReveal($targetPlayer);
        }
        
        // ====== بلاک کردن هدف ======
        $this->blockTarget($target);
        
        return [
            'success' => true,
            'message' => "🧱 رفتی خونه {$targetPlayer['name']} و اونو نگه داشتی! امشب نه می‌تونه کاری بکنه، نه کسی می‌تونه بهش حمله کنه.",
            'blocked' => $target,
            'revealed' => $this->isRevealed
        ];
    }
    
    /**
     * لو دادن حمال به هدف
     */
    private function revealToTarget($targetPlayer) {
        $this->isRevealed = true;
        $this->setData('is_revealed', true);
        
        // به هدف می‌گه حمال کیه
        $this->sendMessageToPlayer($targetPlayer['id'], 
            "🧱 {$this->playerName} امشب اومد خونت! متوجه شدی حماله!"
        );
    }
    
    /**
     * اطلاع‌رسانی لو رفتن به گروه (اگه لازم باشه)
     */
    private function notifyReveal($targetPlayer) {
        // می‌تونه به کل گروه اطلاع بده که حمال لو رفته
        // یا فقط به هدف بگه
    }
    
    /**
     * بلاک کردن هدف
     */
    private function blockTarget($targetId) {
        // ثبت توی game که این نفر بلاک شده
        if (!isset($this->game['blocked_players'])) {
            $this->game['blocked_players'] = [];
        }
        $this->game['blocked_players'][$targetId] = [
            'by' => $this->playerId,
            'night' => $this->game['night_count'] ?? 1
        ];
    }
    
    /**
     * تبدیل به فرقه
     */
    private function convertToCult() {
        // آپدیت نقش
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $this->playerId) {
                $p['role'] = 'cultist';
                $p['role_data']['converted_from'] = 'hamal';
                $p['role_data']['converted_at'] = time();
                break;
            }
        }
        
        // اطلاع به فرقه‌گراها
        $this->notifyCultTeam();
    }
    
    /**
     * اطلاع به تیم فرقه
     */
    private function notifyCultTeam() {
        foreach ($this->game['players'] as $p) {
            if ($this->isCultRole($p['role']) && $p['id'] != $this->playerId) {
                $this->sendMessageToPlayer($p['id'], 
                    "👤 {$this->playerName} (حمال سابق) به فرقه پیوست!"
                );
            }
        }
    }
    
    /**
     * وقتی کسی به خونه هدف حمله می‌کنه
     */
    public function onAttackBlocked($targetId, $attackerRole, $attackerId) {
        // چک کنیم آیا این هدف توسط این حمال محافظت می‌شه
        if ($this->lastTarget != $targetId) {
            return null; // نه، این حمال اونجا نیست
        }
        
        $attacker = $this->getPlayerById($attackerId);
        $target = $this->getPlayerById($targetId);
        
        // جلوگیری از حمله
        $this->sendMessageToPlayer($attackerId, 
            "🧱 رفتی خونه {$target['name']} ولی دیدی {$this->playerName} اونجاست! حمال جلو تو رو گرفت و نتونستی کاری بکنی."
        );
        
        $this->sendMessage(
            "🧱 {$attacker['name']} اومد خونه {$target['name']} ولی جلوشو گرفتی! نتونست کاری بکنه."
        );
        
        return [
            'blocked' => true,
            'message' => 'حمال جلو حمله رو گرفت!'
        ];
    }
    
    /**
     * آیا نقش، فرقه‌گراست؟
     */
    private function isCultRole($role) {
        return in_array($role, ['cultist', 'cult', 'royce', 'frankenstein', 'mummy']);
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->isRevealed) {
            return []; // لو رفته، نمی‌تونه بره
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            // دو شب پشت سر هم نمی‌تونه بره یه جا
            if ($p['id'] == $this->lastTarget) {
                continue;
            }
            
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'hamal_' . $p['id']
            ];
        }
        
        return $targets;
    }
    
    public function onGameStart() {
        $this->setData('last_target', null);
        $this->setData('is_revealed', false);
    }
}