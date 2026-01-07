<?php
// /modules/admin-menu-principal/AdminMenuPrincipal.php

namespace GDOS\Modules\AdminMenuPrincipal;
use GDOS\Core\ModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminMenuPrincipal implements ModuleInterface {

    const MENU_SLUG = 'grupodos-main';

    public function boot(): void {
        add_action( 'admin_menu', [ $this, 'create_main_menu' ] );
    }

    public function create_main_menu(): void {
        add_menu_page(
            'Configuración General - GRUPO DOS', // Título de la página
            'GRUPO DOS',                         // Título del menú
            'manage_options',                    // Capacidad requerida
            self::MENU_SLUG,                     // Slug del menú
            '',                                  // Sin página de contenido
            'dashicons-menu-alt3',               // <-- ICONO CORREGIDO
            25                                   // Posición
        );
    }
}