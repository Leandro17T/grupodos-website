<?php
namespace GDOS\Modules\IvaOff;
use GDOS\Core\ModuleInterface;
use GDOS\Modules\IvaOff\includes\Admin;
use GDOS\Modules\IvaOff\includes\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/includes/Admin.php';
require_once __DIR__ . '/includes/Frontend.php';

class IvaOff implements ModuleInterface {
    public function boot(): void {
        // Inicializar Admin si corresponde
        if (is_admin()) {
            new Admin();
        }
        
        // Inicializar Frontend siempre
        new Frontend();
    }
    
    public static function is_active_now(): bool {
        if (!class_exists('GDOS\Modules\IvaOff\includes\Admin')) return false;
        
        $o = wp_parse_args(get_option(Admin::OPT_KEY, []), Admin::get_defaults());
        
        if (empty($o['enabled'])) return false;
        
        $start = trim($o['start_date'] ?? '');
        $end = trim($o['end_date'] ?? '');
        
        if ($start === '' && $end === '') return true;
        
        try {
            $today_str = (new \DateTime('now', wp_timezone()))->format('Y-m-d');
            if ($start !== '' && $today_str < $start) return false;
            if ($end !== '' && $today_str > $end) return false;
        } catch (\Exception $e) { return false; }
        
        return true;
    }
}