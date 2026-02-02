<?php
/**
 * 👺 منافق
 */

require_once __DIR__ . '/base.php';

class Tanner extends Role {
    
    public function getName() {
        return 'منافق';
    }
    
    public function getEmoji() {
        return '👺';
    }
    
    public function getTeam() {
        return 'neutral';
    }
    
    public function getDescription() {
        return "تو منافق👺 هستی! نه با تیم روستاییا هستی و نه با تیم‌های دیگر. باید وانمود کنی که یه نقش منفی داری که موقع رای‌گیری بیشتر بازیکن‌ها بهت رای بدن و اعدام بشی. تنها در صورتی که اعدام بشی برنده‌ی بازی می‌شی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function onLynched() {
        // منافق برنده شد!
        $this->game['winner'] = 'tanner';
        saveGame($this->game);
        
        return [
            'win' => true,
            'message' => "👺 ای بابا! منافق رو اعدام کردین و اون برنده شد!"
        ];
    }
    
    // اگه به فرقه دعوت بشه، شرط بردش از بین میره
    public function onConvertedToCult() {
        $this->sendMessage("👤 به فرقه دعوت شدی! دیگه نمی‌تونی با اعدام برنده بشی!");
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}