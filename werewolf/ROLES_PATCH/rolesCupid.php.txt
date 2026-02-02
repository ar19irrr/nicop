<?php
/**
 * 💘 الهه عشق (Cupid)
 * اگه تایم‌اوت بشه، ربات خودش انتخاب می‌کنه!
 */

require_once __DIR__ . '/base.php';

class Cupid extends Role {
    
    protected $lover1 = null;
    protected $lover2 = null;
    protected $done = false;
    protected $timeoutHandled = false;  // آیا تایم‌اوت هندل شد؟
    
    public function getName() {
        return 'الهه عشق';
    }
    
    public function getEmoji() {
        return '💘';
    }
    
    public function getTeam() {
        return 'villager';
    }
    
    public function getDescription() {
        if (!$this->done) {
            return "💘 تو الهه عشق هستی! باید حتماً دو نفر رو عاشق هم کنی! اگه خودت انتخاب نکنی، ربات انتخاب می‌کنه (ولی بهت نمی‌گه کیا هستن!)";
        }
        return "💘 تو الهه عشق بودی و دو نفر رو عاشق هم کردی!";
    }
    
    public function hasNightAction() {
        return !$this->done;
    }
    
    public function canSkipNightAction() {
        return false;  // اجباری
    }
    
    public function performNightAction($target = null) {
        // تایم‌اوت یا اسکیپ غیرمجاز
        if ($target === null || $target === 'skip') {
            return $this->handleTimeout();
        }
        
        // انتخاب عادی...
        return $this->handleSelection($target);
    }
    
    /**
     * هندل کردن تایم‌اوت - ربات خودش انتخاب می‌کنه
     */
    private function handleTimeout() {
        if ($this->timeoutHandled) {
            return ['success' => true, 'message' => '💘 قبلاً انجام شده!'];
        }
        
        $this->timeoutHandled = true;
        
        // گرفتن لیست بازیکنان زنده
        $alivePlayers = $this->getOtherAlivePlayers();
        
        if (count($alivePlayers) < 2) {
            // بازیکن کافی نیست!
            $this->done = true;
            $this->setData('done', true);
            $this->setData('timeout_no_lovers', true);
            
            return [
                'success' => false,
                'message' => '💘 بازیکن کافی نیست! کسی عاشق نمی‌شه!',
                'done' => true
            ];
        }
        
        // انتخاب رندم دو نفر
        shuffle($alivePlayers);
        $this->lover1 = $alivePlayers[0]['id'];
        $this->lover2 = $alivePlayers[1]['id'];
        
        $this->setData('lover_1', $this->lover1);
        $this->setData('lover_2', $this->lover2);
        $this->setData('timeout_used', true);  // تایم‌اوت استفاده شد
        $this->done = true;
        $this->setData('done', true);
        
        // ست کردن عشق
        $this->setLovers();
        
        $p1 = $alivePlayers[0];
        $p2 = $alivePlayers[1];
        
        // پیام به الهه عشق (نمی‌گه کیا هستن!)
        $this->sendMessage("⏰ وقت تموم شد! ربات خودش دو نفر رو عاشق هم کرد! ولی بهت نمی‌گم کیا هستن! 😈");
        
        // پیام به لاورها (فقط خودشون می‌فهمن)
        $this->sendMessageToPlayer($this->lover1, "💘 الهه عشق تیرش رو به قلبت زد! الان عاشق یه نفر شدی! اگه اون بمیره، تو هم می‌میری! (ولی نمی‌دونی کیه!)");
        $this->sendMessageToPlayer($this->lover2, "💘 الهه عشق تیرش رو به قلبت زد! الان عاشق یه نفر شدی! اگه اون بمیره، تو هم می‌میری! (ولی نمی‌دونی کیه!)");
        
        return [
            'success' => true,
            'message' => '💘 ربات دو نفر رو عاشق هم کرد!',
            'timeout' => true,
            'hidden_lovers' => true,  // مخفی!
            'done' => true
        ];
    }
    
    /**
     * انتخاب دستی
     */
    private function handleSelection($target) {
        // انتخاب اول
        if (!$this->lover1) {
            $this->lover1 = $target;
            $this->setData('lover_1', $target);
            
            $player = $this->getPlayerById($target);
            return [
                'success' => true,
                'message' => "💘 {$player['name']} رو انتخاب کردی. حالا دومی رو انتخاب کن!",
                'need_second' => true
            ];
        }
        
        // انتخاب دوم
        if ($target == $this->lover1) {
            return [
                'success' => false,
                'message' => '❌ نمی‌تونی یه نفر رو دوبار انتخاب کنی!'
            ];
        }
        
        $this->lover2 = $target;
        $this->setData('lover_2', $target);
        $this->done = true;
        $this->setData('done', true);
        
        $this->setLovers();
        
        $p1 = $this->getPlayerById($this->lover1);
        $p2 = $this->getPlayerById($this->lover2);
        
        return [
            'success' => true,
            'message' => "💘 {$p1['name']} و {$p2['name']} رو عاشق هم کردی!",
            'done' => true
        ];
    }
    
    private function setLovers() {
        $p1Name = $this->getPlayerById($this->lover1)['name'];
        $p2Name = $this->getPlayerById($this->lover2)['name'];
        
        $this->setPlayerData($this->lover1, 'lover', $this->lover2);
        $this->setPlayerData($this->lover2, 'lover', $this->lover1);
        $this->setPlayerData($this->lover1, 'lover_name', $p2Name);
        $this->setPlayerData($this->lover2, 'lover_name', $p1Name);
        
        // اگه تایم‌اوت نبود (انتخاب دستی)، هر دو می‌فهمن
        if (!$this->getData('timeout_used')) {
            $this->sendMessageToPlayer($this->lover1, "💘 عاشق {$p2Name} شدی! اگه اون بمیره، تو هم می‌میری!");
            $this->sendMessageToPlayer($this->lover2, "💘 عاشق {$p1Name} شدی! اگه اون بمیره، تو هم می‌میری!");
        }
    }
    
    /**
     * هندل کردن تایم‌اوت از سمت سیستم (وقتی شب تموم می‌شه)
     */
    public function onNightEnd() {
        // اگه الهه عشق هنوز کارش تموم نشده
        if (!$this->done && !$this->timeoutHandled) {
            return $this->handleTimeout();
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        if ($phase != 'night' || $this->done) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['id'] == $this->lover1) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'cupid_' . $p['id']
            ];
        }
        return $targets;
    }
    
    public function onGameStart() {
        $this->setData('lover_1', null);
        $this->setData('lover_2', null);
        $this->setData('done', false);
        $this->setData('timeout_used', false);
        $this->setData('timeout_handled', false);
    }
    
    private function setPlayerData($playerId, $key, $value) {
        $this->game['players_data'][$playerId][$key] = $value;
    }
}