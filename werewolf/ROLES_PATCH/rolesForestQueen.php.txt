<?php
/**
 * 🧝🏻‍♀️🐺 ملکه جنگل (ForestQueen)
 * تیم: گرگ‌نما (Werewolf)
 */

require_once __DIR__ . '/base.php';

class ForestQueen extends Role {
    
    protected $isLeader = false;      // آیا رهبر شده؟
    protected $alphaId = null;        // آیدی گرگ آلفا
    protected $alphaDead = false;     // آیا آلفا مرده؟
    
    public function getName() {
        return 'ملکه جنگل';
    }
    
    public function getEmoji() {
        return '🧝🏻‍♀️🐺';
    }
    
    public function getTeam() {
        return 'werewolf';
    }
    
    public function getDescription() {
        if (!$this->isLeader) {
            $alphaName = $this->alphaId ? $this->getPlayerById($this->alphaId)['name'] : 'نامشخص';
            return "تو ملکه جنگل 🧝🏻‍♀️🐺 هستی! معشوقه گرگ آلفا ⚡️🐺 ({$alphaName}). اگر آلفا بمیره، تو رهبر جدید دسته‌ی گرگ‌ها می‌شی و ۱۰٪ قدرت تبدیل می‌گیری!";
        }
        return "تو ملکه جنگل 🧝🏻‍♀️🐺 هستی! رهبر جدید دسته‌ی گرگ‌ها. ۱۰٪ قدرت تبدیل داری!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
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
        
        // اگر رهبر شده، ۱۰٪ شانس تبدیل
        if ($this->isLeader) {
            $convertChance = rand(1, 100);
            if ($convertChance <= 10) {
                return [
                    'success' => true,
                    'message' => "🧝🏻‍♀️ به {$targetPlayer['name']} حمله کردی و آلودش کردی! فرداشب تبدیل به گرگ می‌شه!",
                    'infected' => $target
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => "🐺 نظرت اینه که {$targetPlayer['name']} رو بخوریم!",
            'vote' => $target
        ];
    }
    
    public function onAlphaDeath() {
        $this->alphaDead = true;
        $this->isLeader = true;
        
        $this->sendMessageToPlayer($this->getId(), "💔 از اونجایی که گرگ آلفا و معشوقه‌ات مرده، الان تو رهبر جدید دسته‌ی گرگ‌ها هستی و ۱۰٪ قدرت تبدیل داری!");
        $this->notifyWolfTeam("👑 چون گرگ آلفا مرده، ملکه جنگل الان رهبر جدید شماست و ۱۰٪ قدرت تبدیل داره!");
    }
    
    public function onDeath() {
        if (!$this->alphaDead && !$this->isLeader) {
            // اگر زودتر از آلفا بمیره، انتقام
            $this->setGameState('forest_queen_revenge', true);
            $this->notifyWolfTeam("🔥 ملکه جنگل مرد! شب بعد هر نقش شب‌کاری که از خونه بیرون بره کشته می‌شه!");
        }
    }
    
    public function setAlphaId($id) {
        $this->alphaId = $id;
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($this->isWolfTeam($p['role'])) {
                continue;
            }
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'forest_queen_' . $p['id']
            ];
        }
        return $targets;
    }
    
    private function isWolfTeam($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf', 'enchanter', 'honey', 'sorcerer'];
        return in_array($role, $wolfRoles);
    }
}