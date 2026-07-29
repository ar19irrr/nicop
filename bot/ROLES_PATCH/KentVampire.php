<?php
/**
 * 💍🧛🏻 کنت ومپایر (KentVampire)
 * تیم: ومپایر (Vampire)
 */

require_once __DIR__ . '/base.php';

class KentVampire extends Role {
    
    public function getName() {
        return 'کنت ومپایر';
    }
    
    public function getEmoji() {
        return '💍🧛🏻';
    }
    
    public function getTeam() {
        return 'vampire';
    }
    
    public function getDescription() {
        $allVampiresDead = $this->getData('all_vampires_dead') ?? false;
        
        if (!$allVampiresDead) {
            return "💍🧛🏻 تو کنت ومپایر هستی! هر شب می‌تونی یکی رو زیر نظر بگیری و اگر قابلیت شبانه داشته باشه، نقشش رو بفهمی!";
        }
        return "💍🧛🏻 همه ومپایرها مردن! هر روز می‌تونی یک نفر رو بکشی!";
    }
    
    public function hasNightAction() {
        return !($this->getData('all_vampires_dead') ?? false);
    }
    
    public function hasDayAction() {
        return $this->getData('all_vampires_dead') ?? false;
    }
    
    public function performNightAction($target = null) {
        $allVampiresDead = $this->getData('all_vampires_dead') ?? false;
        
        if ($allVampiresDead) {
            return [
                'success' => false,
                'message' => '❌ الان باید از قابلیت روزانه استفاده کنی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی رو می‌خوای تعقیب کنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $observedPlayers = $this->getData('observed_players') ?? [];
        $observedPlayers[] = $target;
        $this->setData('observed_players', $observedPlayers);
        
        $hasNightRole = $this->hasNightAbility($targetPlayer['role']);
        
        if ($hasNightRole) {
            $roleName = getRoleDisplayName($targetPlayer['role']);
            return [
                'success' => true,
                'message' => "👁️ {$targetPlayer['name']} یک {$roleName} هست!",
                'found_role' => $targetPlayer['role']
            ];
        }
        
        return [
            'success' => true,
            'message' => "🔍 {$targetPlayer['name']} رو زیر نظر گرفتی ولی خونه نبود!",
            'found_role' => null
        ];
    }
    
    public function performDayAction($target = null) {
        $allVampiresDead = $this->getData('all_vampires_dead') ?? false;
        
        if (!$allVampiresDead) {
            return [
                'success' => false,
                'message' => '❌ هنوز نمی‌تونی از قابلیت روزانه استفاده کنی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امروز کی رو می‌خوای بکشی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        $this->killPlayer($target, 'kent_vampire');
        
        return [
            'success' => true,
            'message' => "💍 {$targetPlayer['name']} رو برای انتقام کشتی!",
            'killed' => $target
        ];
    }
    
    public function onVampireTeamDeath() {
        $this->setData('all_vampires_dead', true);
        $this->sendMessage("😠 همه ومپایرها مردن! هر روز می‌تونی یک نفر رو بکشی!");
    }
    
    private function hasNightAbility($role) {
        $nightRoles = ['seer', 'werewolf', 'alpha_wolf', 'guardian_angel', 'serial_killer', 
                       'vampire', 'bloodthirsty', 'enchanter', 'harlot', 'knight', 'archer'];
        return in_array($role, $nightRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        $allVampiresDead = $this->getData('all_vampires_dead') ?? false;
        $targets = [];
        
        if ($phase == 'night' && !$allVampiresDead) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                if (in_array($p['role'], ['vampire', 'bloodthirsty'])) continue;
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'kentvampire_' . $p['id']
                ];
            }
        } elseif ($phase == 'day' && $allVampiresDead) {
            foreach ($this->getOtherAlivePlayers() as $p) {
                $targets[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'callback' => 'kentvampire_day_' . $p['id']
                ];
            }
        }
        
        return $targets;
    }
}