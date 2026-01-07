<?php
// REFACTORIZADO: 2025-05-21
// /modules/modal-widget/ModalWidget.php

namespace GDOS\Modules\ModalWidget;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class ModalWidget implements ModuleInterface
{
    // Requisito mínimo razonable (Elementor 3.5+ introdujo grandes cambios en la API)
    private const MIN_ELEMENTOR_VERSION = '3.5.0';

    public function boot(): void
    {
        // Registro de Assets (CSS/JS)
        \add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Elementor carga scripts en el editor de forma distinta
        \add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_assets']);

        // Registro de Hooks de Elementor
        \add_action('plugins_loaded', [$this, 'init_elementor'], 20);
    }

    public function register_assets(): void
    {
        // Usamos el helper Assets::get para versionado automático y rutas
        $css = Assets::get('assets/css/modal.css', __FILE__);
        $js  = Assets::get('assets/js/modal.js', __FILE__);

        \wp_register_style(
            'gdos-modal-widget-css',
            $css['url'],
            [],
            $css['ver']
        );

        \wp_register_script(
            'gdos-modal-widget-js',
            $js['url'],
            [], // Sin dependencias (Vanilla JS)
            $js['ver'],
            true // Footer
        );
    }

    public function init_elementor(): void
    {
        // Fail-fast: Si Elementor no está activo o es muy viejo, salir.
        if (! \did_action('elementor/loaded')) return;

        if (\defined('ELEMENTOR_VERSION') && \version_compare(\ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '<')) {
            return;
        }

        // 1. Registrar Categoría "Grupo Dos"
        \add_action('elementor/elements/categories_registered', [$this, 'register_category']);

        // 2. Registrar Widget
        \add_action('elementor/widgets/register', [$this, 'register_widget']);
    }

    public function register_category($elements_manager): void
    {
        $categories = $elements_manager->get_categories();

        if (empty($categories['grupodos'])) {
            $elements_manager->add_category('grupodos', [
                'title' => \__('Grupo Dos', 'gdos-core'),
                'icon'  => 'fa fa-plug',
            ], 1); // Prioridad alta
        }
    }

    public function register_widget($widgets_manager): void
    {
        // Carga bajo demanda del archivo del widget
        $widget_file = __DIR__ . '/includes/Widget_Modal.php';

        if (\file_exists($widget_file)) {
            require_once $widget_file;

            // Instanciamos el widget si la clase existe tras el require
            // Nota: Asumimos que el namespace en el archivo es GDOS\Modules\ModalWidget\includes
            if (\class_exists(__NAMESPACE__ . '\includes\Widget_Modal')) {
                $widgets_manager->register(new includes\Widget_Modal());
            }
        }
    }
}
