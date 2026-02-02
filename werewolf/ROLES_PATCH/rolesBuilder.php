<?php
/**
 * 👷🏻‍♂️ بنا (فراماسون)
 */

require_once __DIR__ . '/base.php';

class Builder extends Role {
    
    public function getName() {
        return 'بنا';
    }
    
    public function getEmoji() {
        return '👷🏻‍♂️';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        $team = $this->getMasonTeam();
        return "تو بنا 👷🏻‍♂️ هستی. در طول بازی کاری جز رای دادن نمیتونی انجام بدی، فقط اگر بناهای دیگه‌ای توی روستا باشن، همدیگه رو میشناسین. $team";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function performNightAction($target = null) {
        return [
            'success' => false,
            'message' => 'بنا در شب کاری انجام نمیدهد.'
        ];
    }
    
    public function onGameStart() {
        $masons = $this->getMasonTeamList();
        if (!empty($masons)) {
            $this->sendMessage("بناهای روستا: " . implode(', ', $masons));
        }
    }
    
    public function onConvert($newRole, $newTeam) {
        // اگر بنا به گرگ یا فرقه تبدیل شد، به بناهای دیگه اطلاع بده
        if (in_array($newTeam, ['werewolf', 'cult'])) {
            $this->notifyOtherMasons();
        }
    }
    
    private function getMasonTeam() {
        $masons = $this->getMasonTeamList();
        if (empty($masons)) {
            return '';
        }
        return "بناهای روستا: " . implode(', ', $masons);
    }
    
    private function getMasonTeamList() {
        $masons = [];
        foreach ($this->game['players'] as $p) {
            if ($p['id'] != $this->player['id'] && 
                ($p['role'] == 'builder' || $p['role'] == 'mason') && 
                ($p['alive'] ?? false)) {
                $masons[] = $p['name'];
            }
        }
        return $masons;
    }
    
    private function notifyOtherMasons() {
        foreach ($this->game['players'] as $p) {
            if ($p['id'] != $this->player['id'] && 
                ($p['role'] == 'builder' || $p['role'] == 'mason') && 
                ($p['alive'] ?? false)) {
                sendPrivateMessage($p['id'], 
                    "عجیبه {$this->player['name']} امروز نیومده سرکار.. چی به سر بنّای خوبمون اومده؟ 🤔"
                );
            }
        }
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}