<?php
/**
 * 💘 الهه عشق (Cupid)
 */

require_once __DIR__ . '/base.php';

class Cupid extends Role {
    
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
        $done = $this->getData('done') ?? false;
        if (!$done) {
            return "💘 تو الهه عشق هستی! باید دو نفر رو عاشق هم کنی! اگه خودت انتخاب نکنی، ربات انتخاب می‌کنه!";
        }
        return "💘 تو الهه عشق بودی و دو نفر رو عاشق هم کردی!";
    }
    
    public function hasNightAction() {
        return !($this->getData('done') ?? false);
    }
    
    public function performNightAction($target = null) {
        $done = $this->getData('done') ?? false;
        $lover1 = $this->getData('lover_1');
        $lover2 = $this->getData('lover_2');
        
        if ($done) {
            return [
                'success' => false,
                'message' => '💘 قبلاً عاشق‌ها رو انتخاب کردی!'
            ];
        }
        
        // اگر تایم‌اوت شده یا اسکیپ
        if ($target === null || $target === 'skip') {
            return $this->handleTimeout();
        }
        
        // انتخاب اول
        if (!$lover1) {
            $this->setData('lover_1', $target);
            $player = $this->getPlayerById($target);
            return [
                'success' => true,
                'message' => "💘 {$player['name']} رو انتخاب کردی. حالا دومی رو انتخاب کن!",
                'need_second' => true
            ];
        }
        
        // انتخاب دوم
        if ($target == $lover1) {
            return [
                'success' => false,
                'message' => '❌ نمی‌تونی یه نفر رو دوبار انتخاب کنی!'
            ];
        }
        
        $this->setData('lover_2', $target);
        $this->setData('done', true);
        
        $p1 = $this->getPlayerById($lover1);
        $p2 = $this->getPlayerById($target);
        
        $this->setLovers($lover1, $target);
        
        return [
            'success' => true,
            'message' => "💘 {$p1['name']} و {$p2['name']} رو عاشق هم کردی!",
            'done' => true
        ];
    }
    
    private function handleTimeout() {
        if ($this->getData('timeout_handled') ?? false) {
            return ['success' => true, 'message' => '💘 قبلاً انجام شده!'];
        }
        
        $this->setData('timeout_handled', true);
        
        $alivePlayers = $this->getOtherAlivePlayers();
        
        if (count($alivePlayers) < 2) {
            $this->setData('done', true);
            return [
                'success' => false,
                'message' => '💘 بازیکن کافی نیست! کسی عاشق نمی‌شه!'
            ];
        }
        
        shuffle($alivePlayers);
        $lover1 = $alivePlayers[0]['id'];
        $lover2 = $alivePlayers[1]['id'];
        
        $this->setData('lover_1', $lover1);
        $this->setData('lover_2', $lover2);
        $this->setData('done', true);
        $this->setData('timeout_used', true);
        
        $this->setLovers($lover1, $lover2);
        
        $this->sendMessage("⏰ وقت تموم شد! ربات خودش دو نفر رو عاشق هم کرد! ولی بهت نمی‌گم کیا هستن! 😈");
        
        $this->sendMessageToPlayer($lover1, "💘 عاشق شدی! اگه عشقت بمیره، تو هم می‌میری!");
        $this->sendMessageToPlayer($lover2, "💘 عاشق شدی! اگه عشقت بمیره، تو هم می‌میری!");
        
        return [
            'success' => true,
            'message' => '💘 ربات دو نفر رو عاشق هم کرد!',
            'timeout' => true
        ];
    }
    
    private function setLovers($id1, $id2) {
        $p1 = $this->getPlayerById($id1);
        $p2 = $this->getPlayerById($id2);
        
        foreach ($this->game['players'] as &$p) {
            if ($p['id'] == $id1) {
                $p['role_data']['lover'] = $id2;
                $p['role_data']['lover_name'] = $p2['name'];
            }
            if ($p['id'] == $id2) {
                $p['role_data']['lover'] = $id1;
                $p['role_data']['lover_name'] = $p1['name'];
            }
        }
        $this->saveGame();
        
        // اگر تایم‌اوت نبود، به عاشق‌ها بگو
        if (!$this->getData('timeout_used')) {
            $this->sendMessageToPlayer($id1, "💘 عاشق {$p2['name']} شدی!");
            $this->sendMessageToPlayer($id2, "💘 عاشق {$p1['name']} شدی!");
        }
    }
    
    public function onNightEnd() {
        $done = $this->getData('done') ?? false;
        $timeoutHandled = $this->getData('timeout_handled') ?? false;
        
        if (!$done && !$timeoutHandled) {
            return $this->handleTimeout();
        }
        return null;
    }
    
    public function getValidTargets($phase = 'night') {
        $done = $this->getData('done') ?? false;
        $lover1 = $this->getData('lover_1');
        
        if ($phase != 'night' || $done) return [];
        
        $targets = [];
        foreach ($this->getOtherAlivePlayers() as $p) {
            if ($p['id'] == $lover1) continue;
            $targets[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'callback' => 'cupid_' . $p['id']
            ];
        }
        return $targets;
    }
}