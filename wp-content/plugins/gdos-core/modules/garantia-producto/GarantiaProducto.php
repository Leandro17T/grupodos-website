<?php
// REFACTORIZADO: 2025-12-06
// /modules/garantia-producto/GarantiaProducto.php

namespace GDOS\Modules\GarantiaProducto;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class GarantiaProducto implements ModuleInterface
{

    public function boot(): void
    {
        // Cargamos las dependencias solo al iniciar el módulo
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Frontend.php';

        // Instanciamos la lógica
        new includes\Admin();
        new includes\Frontend();
    }

    /**
     * Centraliza el mapeo de garantías por marca.
     * Datos estáticos: Máximo rendimiento, no requiere caché BD.
     * * @return array
     */
    public static function get_brand_warranty_map(): array
    {
        return [
            // slug_de_la_marca => valor_del_meta
            'punktal'  => '1_año',
            'enxuta'   => '1_año',
            'deko'     => '18_meses',
            'atlantic' => '1_año',
            'pacific'  => '1_año',
            'ariete'   => '1_año',
        ];
    }

    /**
     * Centraliza los mensajes de garantía manual.
     * * @return array
     */
    public static function get_manual_warranty_messages(): array
    {
        return [
            '1_año'    => \__('Este producto cuenta con 1 año de garantía oficial.', 'gdos-core'),
            '2_años'   => \__('Este producto cuenta con 2 años de garantía oficial.', 'gdos-core'),
            '18_meses' => \__('Este producto cuenta con 18 meses de garantía oficial.', 'gdos-core'),
            '5_años'   => \__('Este producto cuenta con 5 años de garantía oficial.', 'gdos-core'),
        ];
    }

    /**
     * Devuelve el mensaje de garantía por defecto.
     * * @return string
     */
    public static function get_default_warranty_message(): string
    {
        return \__('Este producto cuenta con 15 días de garantía para cambio o devolución.', 'gdos-core');
    }
}
