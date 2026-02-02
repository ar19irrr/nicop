<?php
/**
 * 🦇 راهب سیاه (MonkBlack)
 * 
 * تیم: فرقه (Cult)
 * هر ۲ شب یکبار دعوت به فرقه، فقط ۳ بار
 */

require_once __DIR__ . '/base.php';

class MonkBlack extends Role {
    
    protected $inviteUsed = 0;
    protected $maxInvites = 3;
    protected $cooldown = 2;
    protected $lastInviteNight = 0;
    
    public function getName() {
        return 'راهب سیاه';
    }
    
    public function getEmoji() {
        return '🦇';
    }
    
    public function getTeam() {
        return 'cult';
    }
    
    public function getDescription() {
        return "تو راهب سیاه🦇 هستی! عضو فرقه. هر ۲ شب یکبار می‌تونی یکی رو به فرقه دعوت کنی (فقط ۳ بار). ⚠️ مراقب شکارچی💂🏻‍♂️ باش!";
    }
    
    public function hasNightAction() {
        $currentNight = $this->getCurrentNight();
        $canInvite = ($currentNight - $this->lastInviteNight) >= $this->cooldown;
        return $canInvite && $this->inviteUsed < $this->maxInvites;
    }
    
    /**
     * 🎮 وقتی بازی شروع می‌شه - معرفی تیم فرقه
     */
    public function onGameStart() {
        // راهب سیاه بقیه اعضای فرقه رو می‌بینه
        $this->introduceCultTeam($this->getId());
        
        $this->sendMessage(
            "🦇 <b>تو راهب سیاه هستی!</b>\n\n" .
            "👤 <b>تیم فرقه</b>\n" .
            "🎯 هدف: تبدیل همه روستایی‌ها به فرقه\n\n" .
            "⚡ قابلیت: هر ۲ شب یکبار دعوت (۳ بار)"
        );
    }
    
    public function performNightAction($target = null) {
        // ... (کد قبلی همونه)
        
        $currentNight = $this->getCurrentNight();
        
        if (($currentNight - $this->lastInviteNight) < $this->cooldown) {
            $remaining = $this->cooldown - ($currentNight - $this->lastInviteNight);
            return [
                'success' => false,
                'message' => "⏳ {$remaining} شب دیگر صبر کن."
            ];
        }
        
        if ($this->inviteUsed >= $this->maxInvites) {
            return [
                'success' => false,
                'message' => '❌ ۳ بار استفاده کردی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو دعوت می‌کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        if ($this->isCultRole($targetPlayer['role'])) {
            return [
                'success' => false,
                'message' => '❌ قبلاً فرقه‌ست!'
            ];
        }
        
        // شکارچی
        if ($targetPlayer['role'] == 'cult_hunter') {
            return $this->handleHunterEncounter($targetPlayer);
        }
        
        return $this->attemptConvert($targetPlayer);
    }
    
    private function handleHunterEncounter($hunter) {
        $rand = rand(1, 100);
        
        if ($rand <= 30) {
            $this->killPlayer($this->getId(), 'cult_hunter');
            
            $this->sendMessageToPlayer($hunter['id'], 
                "🦇 راهب سیاه اومد خونت، ولی با ۳۰٪ شانس تونستی اول حمله کنی و بکشتش!"
            );
            
            $this->notifyCultTeam("💀 راهب سیاه توسط شکارچی کشته شد!");
            
            return [
                'success' => false,
                'message' => "💀 رفتی خونه شکارچی💂🏻‍♂️ ولی اون ۳۰٪ شانسش اومد و ترو کشت!",
                'died' => true
            ];
        } else {
            return $this->attemptConvert($hunter, true);
        }
    }
    
    private function attemptConvert($targetPlayer, $fromHunter = false) {
        $targetRole = $targetPlayer['role'];
        
        $hardRoles = ['serial_killer', 'werewolf', 'alpha_wolf', 'wolf_cub', 'lycan',
                      'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf',
                      'vampire', 'bloodthirsty', 'kent_vampire', 'joker', 'harly',
                      'cult_hunter'];
        
        $isHard = in_array($targetRole, $hardRoles);
        $successChance = $isHard ? 20 : 100;
        $rand = rand(1, 100);
        
        $this->inviteUsed++;
        $this->lastInviteNight = $this->getCurrentNight();
        $this->setData('invite_used', $this->inviteUsed);
        $this->setData('last_invite_night', $this->lastInviteNight);
        
        if ($rand <= $successChance) {
            // موفق شد
            $this->convertToCult($targetPlayer['id']);
            
            // اطلاع به تیم فرقه که کی عضو جدیده
            $this->notifyCultTeam("🦇 راهب سیاه {$targetPlayer['name']} رو به فرقه دعوت کرد! 👤");
            
            $extraText = $fromHunter ? " (شکارچی ۷۰٪ زنده موند، ولی دعوت موفق بود!)" : "";
            $chanceText = $isHard ? " (۲۰٪ شانس اومد!)" : "";
            
            return [
                'success' => true,
                'message' => "🦇 تونستی {$targetPlayer['name']} رو به فرقه دعوت کنی!{$chanceText}{$extraText}",
                'converted' => $targetPlayer['id'],
                'invites_left' => $this->maxInvites - $this->inviteUsed
            ];
        } else {
            $this->sendMessageToPlayer($targetPlayer['id'], "🦇 کسی اومد خونت ولی قبول نکردی بپیوندی بهش!");
            
            $extraText = $fromHunter ? " (شکارچی ۷۰٪ زنده موند و ۲۰٪ دعوتت هم نیومد!)" : "";
            
            return [
                'success' => false,
                'message' => "🦇 نتونستی {$targetPlayer['name']} رو متقاعد کنی!{$extraText}",
                'invites_left' => $this->maxInvites - $this->inviteUsed
            ];
        }
    }
    
    private function convertToCult($playerId) {
        $this->setPlayerRole($playerId, 'cultist');
        
        // به عضو جدید معرفی کنه بقیه رو
        $this->introduceCultTeam($playerId);
        
        $this->sendMessageToPlayer($playerId, 
            "🦇 راهب سیاه اومد خونت...\n\n" .
            "👤 <b>تو الان عضو فرقه هستی!</b>\n" .
            "🎯 هدف: تبدیل همه روستایی‌ها به فرقه!"
        );
    }
    
    public function getValidTargets($phase = 'night') {
        $currentNight = $this->getCurrentNight();
        
        if (($currentNight - $this->lastInviteNight) < $this->cooldown) {
            return [];
        }
        
        if ($this->inviteUsed >= $this->maxInvites) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isCultRole($p['role'])) {
                continue;
            }
            
            $isHard = in_array($p['role'], ['serial_killer', 'werewolf', 'alpha_wolf', 
                'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf',
                'vampire', 'bloodthirsty', 'kent_vampire', 'joker', 'harly',
                'cult_hunter']);
            
            $isHunter = ($p['role'] == 'cult_hunter');
            $hint = $isHunter ? ' 💀⚠️' : ($isHard ? ' (۲۰٪)' : ' (۱۰۰٪)');
            
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'] . $hint,
                'callback' => 'monk_black_' . $p['id']
            ];
        }
        return $targets;
    }
}