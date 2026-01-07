<?php
// REFACTORIZADO: 2025-05-21
// /modules/ofertas-flash/OfertasFlash.php

namespace GDOS\Modules\OfertasFlash;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) exit;

class OfertasFlash implements ModuleInterface
{
    // Constantes de estilo (Públicas para acceso desde el Shortcode)
    public const BORDER_START = '#FFEB3B';
    public const BORDER_END   = '#1e40af';
    public const BUY_COLOR    = '#1E40AF';

    public function boot(): void
    {
        // Fail-fast: Si no hay WooCommerce, no cargamos nada más.
        if (! \class_exists('WooCommerce')) return;

        $this->load_dependencies();

        // Inicializamos los sub-componentes
        // Nota: Asumimos que el namespace dentro de estos archivos es GDOS\Modules\OfertasFlash\includes
        new includes\Admin();

        // Pasamos $this al Shortcode por si necesita acceder a las constantes de configuración
        new includes\Shortcode($this);
    }

    /**
     * Carga los archivos necesarios del módulo.
     */
    private function load_dependencies(): void
    {
        // Uso de __DIR__ para rutas absolutas seguras
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Shortcode.php';
    }
}
