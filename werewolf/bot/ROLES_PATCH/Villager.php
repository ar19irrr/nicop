<?php
/**
 * 👨‍🌾 روستایی ساده (Villager)
 * تیم: روستا
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
        return 'villager';
    }
    
    public function getDescription() {
        return "👨‍🌾 تو یک روستایی ساده هستی. در شب کاری نمی‌کنی، ولی در روز می‌تونی رأی بدی و گرگ‌ها رو پیدا کنی!";
    }
    
    public function hasNightAction() {
        return false;
    }
    
    public function getValidTargets($phase = 'night') {
        return [];
    }
}