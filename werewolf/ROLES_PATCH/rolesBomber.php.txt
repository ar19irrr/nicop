<?php
/**
 * 💣 بمب‌گذار (Bomber)
 * تیم: مستقل (Independent)
 */

require_once __DIR__ . '/base.php';

class Bomber extends Role {
    
    protected $bombsPlanted = 0;      // تعداد بمب‌های کار گذاشته
    protected $bombsNeeded = 5;       // تعداد بمب‌های مورد نیاز (متغیر)
    protected $teamMembers = [];      // اعضای تیم بمب‌گذار
    
    public function getName() {
        return 'بمب‌گذار';
    }
    
    public function getEmoji() {
        return '💣';
    }
    
    public function getTeam() {
        return 'bomber';
    }
    
    public function getDescription() {
        $teamList = empty($this->teamMembers) ? '' : "\nهم‌تیمی‌ها: " . implode(', ', $this->teamMembers);
        return "تو بمب‌گذار 💣 هستی! هر شب می‌تونی توی خونه ۱ نفر بمب بذاری. وقتی تعداد بمب‌ها به {$this->bombsNeeded} رسید، کل روستا میره رو هوا و تو برنده می‌شی!{$teamList}";
    }
    
    public function hasNightAction() {
        return $this->bombsPlanted < $this->bombsNeeded;
    }
    
    public function performNightAction($target = null) {
        if ($this->bombsPlanted >= $this->bombsNeeded) {
            return [
                'success' => false,
                'message' => '✅ همه بمب‌ها کار گذاشته شده!'
            ];
        }
        
        if (!$target) {
            $remaining = $this->bombsNeeded - $this->bombsPlanted;
            $teamInfo = empty($this->teamMembers) ? '' : "\n\nهم‌تیمی‌ها:\n" . $this->getTeamList();
            return [
                'success' => false,
                'message' => "❌ امشب می‌خوای خونه کی بمب رو کار بذاری؟!\nتعداد {$remaining} بمب مونده{$teamInfo}"
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->bombsPlanted++;
        
        // اطلاع به بازیکن هدف (بدون گفتن چه کسی بمب گذاشته)
        $this->sendMessageToPlayer($target, "⚠️ اوه اوه! متاسفانه بمب‌گذار 💣 خونت بمب گذاشته! یادت باشه اگه به کسی بگی بازیو بهم می‌زنم!");
        
        // بررسی آیا بمب کافی است
        if ($this->bombsPlanted >= $this->bombsNeeded) {
            $this->detonate();
        }
        
        return [
            'success' => true,
            'message' => "💣 با موفقیت یک بمب توی خونه {$targetPlayer['name']} فعال شد! ({$this->bombsPlanted}/{$this->bombsNeeded})",
            'planted' => true
        ];
    }
    
    private function detonate() {
        $this->sendMessageToGroup("💥💣 خب خب رفیق بردی! آره بمب‌گذار 💣ها برنده شدند و روستایی‌ها باختن!");
        
        // کشتن همه
        $players = $this->getAllPlayers();
        foreach ($players as $player) {
            if ($player['team'] != 'bomber' && $player['alive']) {
                $this->killPlayer($player['id'], 'bomber_explosion');
            }
        }
        
        $this->declareWinners(['bomber']);
    }
    
    public function addTeamMember($playerId, $playerName) {
        $this->teamMembers[$playerId] = $playerName;
    }
    
    private function getTeamList() {
        $list = '';
        foreach ($this->teamMembers as $id => $name) {
            $list .= "- {$name}\n";
        }
        return $list;
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->bombsPlanted >= $this->bombsNeeded) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'bomber_' . $p['id']
            ];
        }
        return $targets;
    }
}