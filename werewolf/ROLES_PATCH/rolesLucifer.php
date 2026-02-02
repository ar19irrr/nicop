<?php
/**
 * 👹 لوسیفر (Lucifer)
 * تیم: متغیر (بستگی به انتخاب اول بازی)
 */

require_once __DIR__ . '/base.php';

class Lucifer extends Role {
    
    protected $selectedTeam = null;   // تیم انتخاب شده (rosta, wolf, vampire, ferqe, qatel)
    protected $controlledPlayers = []; // بازیکنان تحت کنترل
    protected $convertedToVillager = false; // آیا تبدیل به روستایی شده؟
    
    public function getName() {
        return 'لوسیفر';
    }
    
    public function getEmoji() {
        return '👹';
    }
    
    public function getTeam() {
        return $this->selectedTeam ?? 'independent';
    }
    
    public function getDescription() {
        if (!$this->selectedTeam) {
            return "تو لوسیفر 👹 هستی، شیطان فرشته‌ای که به زمین تبعید شده! در ابتدای بازی باید انتخاب کنی می‌خوای به کدوم تیم کمک کنی. فقط با اون تیم برنده می‌شی!";
        }
        
        $teamNames = [
            'rosta' => 'روستایی‌ها',
            'werewolf' => 'گرگ‌ها',
            'vampire' => 'ومپایرها',
            'ferqe' => 'فرقه',
            'qatel' => 'قاتل'
        ];
        
        return "تو لوسیفر 👹 هستی و با تیم {$teamNames[$this->selectedTeam]} هم‌تیمی! می‌تونی وارد ذهن افراد بشی و جای اون‌ها تصمیم بگیری!";
    }
    
    public function onGameStart() {
        // انتخاب تیم در اول بازی
        return [
            'action' => 'select_team',
            'message' => '👹 تیم خودت رو انتخاب کن می‌خوای با کی ببری؟',
            'options' => [
                'rosta' => '👨🏻 تیم روستایی',
                'werewolf' => '🐺 تیم گرگ',
                'vampire' => '🧛🏻‍♂️ تیم ومپایر',
                'ferqe' => '👤 تیم فرقه',
                'qatel' => '🔪 تیم قاتل'
            ]
        ];
    }
    
    public function selectTeam($team) {
        $this->selectedTeam = $team;
        
        $teamNames = [
            'rosta' => '👨🏻 تیم روستایی',
            'werewolf' => '🐺 تیم گرگ',
            'vampire' => '🧛🏻‍♂️ تیم ومپایر',
            'ferqe' => '👤 تیم فرقه',
            'qatel' => '🔪 تیم قاتل'
        ];
        
        return [
            'success' => true,
            'message' => "✅ تیم شما به {$teamNames[$team]} با موفقیت تغییر کرد.",
            'team' => $team
        ];
    }
    
    public function hasNightAction() {
        return !$this->convertedToVillager;
    }
    
    public function performNightAction($target = null) {
        if ($this->convertedToVillager) {
            return [
                'success' => false,
                'message' => '❌ تو دیگه روستایی ساده‌ای و قدرت قبلی رو نداری!'
            ];
        }
        
        if (!$this->selectedTeam) {
            return [
                'success' => false,
                'message' => '❌ اول باید تیمت رو انتخاب کنی!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب می‌خوای به خونه کی بری و گولش بزنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // ⚠️ اگه فرشته نگهبان باشه، جفتشون روستایی ساده می‌شن!
        if ($targetPlayer['role'] == 'guardian_angel') {
            return $this->convertBothToVillager($targetPlayer);
        }
        
        // بررسی شکارچی (اعتقاد قوی)
        if ($targetPlayer['role'] == 'cult_hunter') {
            return [
                'success' => false,
                'message' => "🛡️ اعتقاد {$targetPlayer['name']} بیشتر از اون چیزی بود که فکر می‌کردی و نتونستی گولش بزنی!"
            ];
        }
        
        // بررسی قاتل (۳۵٪ شانس مرگ)
        if ($targetPlayer['role'] == 'serial_killer' || $targetPlayer['role'] == 'killer') {
            $deathChance = rand(1, 100);
            if ($deathChance <= 35) {
                $this->killPlayer($this->getId(), 'lucifer_qatel');
                return [
                    'success' => false,
                    'message' => "🔪 رفتی گول {$targetPlayer['name']} رو بزنی ولی قاتل اعصاب نداشت و چاقوش رو فرو کرد تو قلبت!",
                    'died' => true
                ];
            }
        }
        
        // بررسی گرگ (۳۵٪ شانس مرگ)
        if ($this->isWolf($targetPlayer['role'])) {
            $deathChance = rand(1, 100);
            if ($deathChance <= 35) {
                $this->killPlayer($this->getId(), 'lucifer_wolf');
                return [
                    'success' => false,
                    'message' => "🐺 رفتی گول {$targetPlayer['name']} رو بزنی ولی گرگه اعصابش خورد شد و گلوت رو جر داد!",
                    'died' => true
                ];
            }
        }
        
        // بررسی ومپایر اصیل (۵۰٪ شانس مرگ)
        if ($targetPlayer['role'] == 'bloodthirsty') {
            $deathChance = rand(1, 100);
            if ($deathChance <= 50) {
                $this->killPlayer($this->getId(), 'lucifer_blood');
                return [
                    'success' => false,
                    'message' => "🧛🏻‍♀️ رفتی گول {$targetPlayer['name']} رو بزنی ولی ومپایر اصیل خونت رو تا آخرین قطره نوشید!",
                    'died' => true
                ];
            }
        }
        
        // موفقیت در گول زدن
        $this->controlledPlayers[$target] = [
            'player_id' => $target,
            'night' => $this->getCurrentNight()
        ];
        
        return [
            'success' => true,
            'message' => "✅ تونستی {$targetPlayer['name']} رو گول بزنی! حالا می‌تونی جای اون تصمیم بگیری!",
            'controlled' => $target
        ];
    }
    
    /**
     * تبدیل جفتشون به روستایی ساده وقتی لوسیفر میره رو فرشته
     */
    private function convertBothToVillager($angelPlayer) {
        $this->convertedToVillager = true;
        
        // تبدیل لوسیفر به روستایی ساده
        $this->setPlayerRole($this->getId(), 'villager');
        
        // تبدیل فرشته به روستایی ساده
        $this->setPlayerRole($angelPlayer['id'], 'villager');
        
        // پیام به لوسیفر
        $this->sendMessageToPlayer($this->getId(), "😇 رفتی گول {$angelPlayer['name']} رو بزنی ولی اون فرشته نگهبان 👼🏻 بود! نور مقدس فرشته با تاریکی درونت برخورد کرد و هر دوتاتون تبدیل به روستایی ساده 👨🏻 شدین!");
        
        // پیام به فرشته
        $this->sendMessageToPlayer($angelPlayer['id'], "👼🏻 لوسیفر 👹 اومد خونه‌ت و سعی کرد گولت بزنه! نور مقدست با تاریکی شیطان برخورد کرد و هر دوتاتون تبدیل به روستایی ساده 👨🏻 شدین!");
        
        // اعلام در گروه
        $this->sendMessageToGroup("✨ یه معجزه رخ داد! لوسیفر و فرشته نگهبان با هم ملاقات کردن و نور، تاریکی رو شکست داد! هر دوتاشون الان روستایی ساده 👨🏻 هستن!");
        
        return [
            'success' => true,
            'message' => "✅ رفتی گول {$angelPlayer['name']} رو بزنی ولی اون فرشته بود! جفتتون تبدیل به روستایی ساده شدین!",
            'converted' => true,
            'both_converted' => true
        ];
    }
    
    /**
     * کنترل رای بازیکن در روز
     */
    public function controlVote($targetId) {
        if ($this->convertedToVillager) {
            return ['success' => false, 'converted' => true];
        }
        
        $player = $this->getPlayerById($targetId);
        if (!$player || !$player['alive']) {
            return ['success' => false];
        }
        
        return [
            'success' => true,
            'message' => "👹 لوسیفر جای {$player['name']} رای داد!",
            'vote' => $targetId
        ];
    }
    
    /**
     * کنترل اکشن شب بازیکن
     */
    public function controlNightAction($targetId, $action) {
        if ($this->convertedToVillager) {
            return ['success' => false, 'converted' => true];
        }
        
        $player = $this->getPlayerById($targetId);
        if (!$player || !$player['alive']) {
            return ['success' => false];
        }
        
        return [
            'success' => true,
            'message' => "👹 لوسیفر جای {$player['name']} تصمیم گرفت!",
            'action' => $action
        ];
    }
    
    private function isWolf($role) {
        $wolfRoles = ['werewolf', 'alpha_wolf', 'wolf_cub', 'lycan', 'forest_queen', 'white_wolf', 'beta_wolf', 'ice_wolf'];
        return in_array($role, $wolfRoles);
    }
    
    public function getValidTargets($phase = 'night') {
        if ($this->convertedToVillager) {
            return [];
        }
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'lucifer_' . $p['id']
            ];
        }
        return $targets;
    }
}