<?php
/**
 * 👨‍🔬 شیمیدان (Chemist)
 * تیم: روستا (Villager)
 */

require_once __DIR__ . '/base.php';

class Chemist extends Role {
    
    protected $hasBrewed = false;     // آیا معجون ساخته؟
    
    public function getName() {
        return 'شیمیدان';
    }
    
    public function getEmoji() {
        return '👨‍🔬';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        return "تو شیمیدان 👨‍🔬 هستی! یه فرد دیوانه که دوتا معجون درست می‌کنه: یکی کشنده، یکی خنثی. هر شب می‌تونی یه نفر رو مجبور کنی یکی رو انتخاب کنه. اگر سمی رو بخوره می‌میره، اگر خنثی رو بخوری تو می‌میری!";
    }
    
    public function hasNightAction() {
        return true;
    }
    
    public function performNightAction($target = null) {
        if (!$target) {
            return [
                'success' => false,
                'message' => '❌ امشب کی می‌تونه یه میزبان و شریک خوب توی شرط‌بندی باشه؟'
            ];
        }
        
        $targetPlayer = $this->getPlayerById($target);
        if (!$targetPlayer || !$targetPlayer['alive']) {
            return [
                'success' => false,
                'message' => '❌ بازیکن نامعتبر!'
            ];
        }
        
        // بررسی خالی بودن خونه
        if ($this->isNotHome($target)) {
            return [
                'success' => false,
                'message' => "🏠 رفتی که با {$targetPlayer['name']} نوشیدنی بخوری اما خونه نبود!"
            ];
        }
        
        // بررسی قاتل
        if ($targetPlayer['role'] == 'killer') {
            $this->killPlayer($this->getId(), 'killer');
            $this->sendMessageToGroup("🔪 شیمیدان 👨‍🔬 {$this->getPlayerName()} قاتل سریالی رو به انجام بازی مرگ اجبار کرد. به نظر می‌رسه قاتل حوصله انجام بازی رو نداشت... بدن {$this->getPlayerName()} کنار کیسه‌ای که داخلش دو تا معجون بود پیدا شد.");
            return [
                'success' => false,
                'message' => "💀 رفتی که {$targetPlayer['name']} رو مجبور به انتخاب کنی اما اون قاتل بود و چاقوش رو فرو کرد تو چشم چپت!",
                'died' => true
            ];
        }
        
        // انتخاب معجون توسط هدف (۵۰-۵۰)
        $targetChoice = rand(1, 2); // 1 = سمی، 2 = خنثی
        
        if ($targetChoice == 1) {
            // هدف سمی رو انتخاب کرد و می‌میره
            $this->killPlayer($target, 'chemist');
            
            // اگر ریش سفید باشه، شیمیدان تبدیل به روستایی ساده می‌شه
            if ($targetPlayer['role'] == 'wise_elder') {
                $this->sendMessageToPlayer($this->getId(), "📚 چون {$targetPlayer['name']} ریش سفید بود، الان تو تبدیل شدی به روستایی ساده :(");
                $this->setRole('villager');
            }
            
            return [
                'success' => true,
                'message' => "☠️ شما به دیدن {$targetPlayer['name']} رفتید و به او حق انتخاب دادید. خوشبختانه اون سم رو انتخاب کرد و مرد!",
                'killed' => $target
            ];
        } else {
            // هدف خنثی رو انتخاب کرد، شیمیدان می‌میره
            $this->killPlayer($this->getId(), 'chemist_poison');
            $this->sendMessageToGroup("☠️ شیمیدان {$this->getPlayerName()} 👨‍🔬 مرده پیدا شد. ظاهراً بدشانس بوده و شربت سمی رو نوشیده!");
            
            return [
                'success' => true,
                'message' => "🧪 شما به دیدن {$targetPlayer['name']} رفتید و به آن اجازه دادید یک معجون انتخاب کند. اما اون انتخاب خوبی داشت و شما شربت سمی رو نوشیدید و مردید!",
                'died' => true
            ];
        }
    }
    
    public function getValidTargets($phase = 'night') {
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'chemist_' . $p['id']
            ];
        }
        return $targets;
    }
}