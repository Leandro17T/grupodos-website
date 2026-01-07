<?php
// /modules/gestion-proveedores/GestionProveedores.php
namespace GDOS\Modules\GestionProveedores;
use GDOS\Core\ModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/includes/Admin.php';

class GestionProveedores implements ModuleInterface {
    
    public function boot(): void {
        if (!class_exists('WooCommerce')) return;
        new includes\Admin();
    }

    public static function get_provider_list(): array {
        return [
            'DYD - KASSEL', 'GELBRING S.A - ENXUTA', 'ICONIK S.A.S.',
            'IMPOREX SA', 'JUAN GOLDFARB SA', 'MALAQUIO ANGONA GABRIEL - GM IMPORTACIONES',
            'MARCAL IMPORTACIONES SRL', 'PALERMO IMPORTACIONES SA', 'PUNKTAL SA',
            'TIBURIN SA', 'DRIVA SA'
        ];
    }

    public static function get_provider_colors(): array {
        return [
            'DYD - KASSEL' => '#DAB769',
            'GELBRING S.A - ENXUTA' => '#FFF100',
            'ICONIK S.A.S.' => '#EC90D9',
            'IMPOREX SA' => '#81B8F1',
            'JUAN GOLDFARB SA' => '#2657A2',
            'MALAQUIO ANGONA GABRIEL - GM IMPORTACIONES' => '#BFE876',
            'MARCAL IMPORTACIONES SRL' => '#E87686',
            'PALERMO IMPORTACIONES SA' => '#C293F3',
            'PUNKTAL SA' => '#686669',
            'TIBURIN SA' => '#B6F3F3',
            'DRIVA SA' => '#EF9A7F'
        ];
    }

    public static function is_light_color($hex): bool {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return ((0.299 * $r + 0.587 * $g + 0.114 * $b) > 186);
    }
}