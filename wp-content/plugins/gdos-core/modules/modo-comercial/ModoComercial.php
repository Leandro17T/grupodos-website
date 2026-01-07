<?php
// REFACTORIZADO: 2025-05-21
// /modules/modo-comercial/ModoComercial.php

namespace GDOS\Modules\ModoComercial;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) exit;

/**
 * Modo Comercial — Reemplaza la Home por una página específica durante un rango de fechas.
 */
class ModoComercial implements ModuleInterface
{
    /** Metadatos del Módulo */
    public const NAME        = 'Modo Comercial';
    public const SLUG        = 'modo-comercial';
    public const VERSION     = '1.0.0';
    public const DESCRIPTION = 'Reemplaza la Home por una página específica durante un rango de fechas.';

    public const MENU_PARENT = 'grupodos-main';
    public const OPT_KEY     = 'gdos_modo_comercial_options';
    public const TEXTDOMAIN  = 'gdos-core';

    private static bool $booted = false;

    public function boot(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        $this->load_dependencies();
    }

    /**
     * Carga e inicializa las dependencias (Admin y Frontend).
     */
    private function load_dependencies(): void
    {
        // Rutas absolutas seguras usando __DIR__
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Frontend.php';

        // Instanciación condicional
        if (\class_exists(__NAMESPACE__ . '\includes\Admin')) {
            new includes\Admin();
        }

        if (\class_exists(__NAMESPACE__ . '\includes\Frontend')) {
            new includes\Frontend();
        }
    }

    /**
     * Información para el Core de GDOS.
     */
    public static function info(): array
    {
        return [
            'name'        => self::NAME,
            'slug'        => self::SLUG,
            'version'     => self::VERSION,
            'description' => self::DESCRIPTION,
            'menu_parent' => self::MENU_PARENT,
            'class'       => __CLASS__,
        ];
    }
}
