<?php
/**
 * 👨‍🌾 روستایی ساده
 */

require_once __DIR__ . '/base.php';

class Villager extends Role {
    
    public function getName() {
        return 'روستایی ساده';
    }
    
    public function getEmoji() {
        return '👨‍🌾';
    }
    
    public function getTeam() {
        return 'villager'; // روستا
    }
    
    public function getDescription() {
        return "تو یه روستایی ساده 👨‍🌾 هستی. در هنگام شب یا روز، کار خاصی انجام نمیدی. سعی کن زنده بمونی چون ممکنه موقع رأی‌گیری؛ بتونی یه گرگ رو اعدام کنی.";
    }
    
    // روستایی ساده هیچ قابلیتی ندارد
}