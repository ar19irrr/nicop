<?php
/**
 * 👹 لوسیفر (Lucifer)
 * تیم: متغیر (بستگی به انتخاب اول بازی)
 */

require_once __DIR__ . '/base.php';

class Lucifer extends Role {
    
    public function getName() {
        return 'لوسیفر';
    }
    
    public function getEmoji() {
        return '👹';
    }
    
    public function getTeam() {
        $selectedTeam = $this->getData('selected_team');
        return $selectedTeam ?? 'independent';
    }
    
    public function getDescription() {
        $selectedTeam = $this->getData('selected_team');
        $converted = $this->getData('converted_to_villager') ?? false;
        
        if ($converted) {
            return "👹 تبدیل به روستایی ساده شدی!";
        }
        
        if (!$selectedTeam) {
            return "👹 تیم خودت رو انتخاب کن! (روستا، گرگ، ومپایر، فرقه، قاتل)";
        }
        
        $teamNames = [
            'villager' => 'روستایی‌ها',
            'werewolf' => 'گرگ‌ها',
            'vampire' => 'ومپایرها',
            'cult' => 'فرقه',
            'killer' => 'قاتل'
        ];
        
        return "👹 با تیم {$teamNames[$selectedTeam]} هم‌تیمی هستی! می‌تونی وارد ذهن افراد بشی و جای اون‌ها تصمیم بگیری!";
    }
    
    public function onGameStart() {
        $this->sendMessage("👹 تیم خودت رو انتخاب کن: /select [villager|werewolf|vampire|cult|killer]");
    }
    
    public function selectTeam($team) {
        $validTeams = ['villager', 'werewolf', 'vampire', 'cult', 'killer'];
        
        if (!in_array($team, $validTeams)) {
            return ['success' => false, 'message' => '❌ تیم نامعتبر!'];
        }
        
        $this->setData('selected_team', $team);
        
        $teamNames = [
            'villager' => '👨🏻 تیم روستایی',
            'werewolf' => '🐺 تیم گرگ',
            'vampire' => '🧛🏻‍♂️ تیم ومپایر',
            'cult' => '👤 تیم فرقه',
            'killer' => '🔪 تیم قاتل'
        ];
        
        return [
            'success' => true,
            'message' => "✅ تیم شما به {$teamNames[$team]} تغییر کرد."
        ];
    }
    
    public function hasNightAction() {
        return !($this->getData('converted_to_villager') ?? false);
    }
    
    public function performNightAction($target = null) {
        $converted = $this->getData('converted_to_villager') ?? false;
        $selectedTeam = $this->getData('selected_team');
        
        if ($converted) {
            return [
                'success' => false,
                'message' => '❌ روستایی ساده شدی!'
            ];
        }
        
        if (!$selectedTeam) {
            return [
                'success' => false,
                'message' => '❌ اول تیمت رو انتخاب کن!'
            ];
        }
        
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ کی رو گول بزنی؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // فرشته نگهبان - جفتشون روستایی ساده می‌شن
        if ($targetPlayer['role'] == 'guardian_angel') {
            return $this->convertBothToVillager($targetPlayer);
        }
        
        // شکارچی - نمی‌تونه گولش بزنه
        if ($targetPlayer['role'] == 'cult_hunter') {
            return [
                'success' => false,
                'message' => "🛡️ نتونستی {$targetPlayer['name']} رو گول بزنی!"
            ];
        }
        
        // قاتل - ۳۵٪ شانس مرگ
        if ($targetPlayer['role'] == 'serial_killer') {
            if (rand(1, 100) <= 35) {
                $this->killPlayer($this->getId(), 'lucifer_killer');
                return [
                    'success' => false,
                    'message' => "🔪 قاتل کشتت!",
                    'died' => true
                ];
            }
        }
        
        // گرگ - ۳۵٪ شانس مرگ
        if ($this->isWolf($targetPlayer['role'])) {
            if (rand(1, 100) <= 35) {
                $this->killPlayer($this->getId(), 'lucifer_wolf');
                return [
                    'success' => false,
                    'message' => "🐺 گرگ خوردت!",
                    'died' => true
                ];
            }
        }
        
        // کنترل موفق
        $controlledPlayers = $this->getData('controlled_players') ?? [];
        $controlledPlayers[] = $target;
        $this->setData('controlled_players', $controlledPlayers);
        
        return [
            'success' => true,
            'message' => "✅ {$targetPlayer['name']} رو گول زدی!",
            'controlled' => $target
        ];
    }
    
    private function convertBothToVillager($angelPlayer) {
        $this->setData('converted_to_villager', true);
        
        $this->setPlayerRole($this->getId(), 'villager');
        $this->setPlayerRole($angelPlayer['id'], 'villager');
        
        $this->sendMessage("😇 با فرشته برخورد کردی! روستایی ساده شدی!");
        $this->sendMessageToPlayer($angelPlayer['id'], "👼 با لوسیفر برخورد کردی! روستایی ساده شدی!");
        $this->sendMessageToGroup("✨ لوسیفر و فرشته روستایی ساده شدند!");
        
        return [
            'success' => true,
            'message' => "✅ جفتتون روستایی ساده شدید!",
            'converted' => true
        ];
    }
    
    public function getValidTargets($phase = 'night') {
        $converted = $this->getData('converted_to_villager') ?? false;
        if ($converted) return [];
        
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