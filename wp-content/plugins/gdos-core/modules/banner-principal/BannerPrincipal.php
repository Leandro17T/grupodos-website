<?php
// REFACTORIZADO: 2025-12-06
// /modules/banner-principal/BannerPrincipal.php

namespace GDOS\Modules\BannerPrincipal;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class BannerPrincipal implements ModuleInterface
{

    const CPT           = 'gdos_slider_set_snip';
    const OPT_SINGLE_ID = 'gdos_single_slider_id';

    // WPO: Clave para el Transient que almacenará el HTML/Query del banner
    const CACHE_KEY     = 'gdos_banner_main_data';

    public function boot(): void
    {
        // 1. Carga de dependencias
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Frontend.php';

        // 2. Registro del CPT (Necesario en Front y Admin para que funcionen las queries)
        \add_action('init', [$this, 'register_cpt']);

        // 3. Lógica Administrativa
        if (\is_admin()) {
            // WPO: El chequeo de singleton solo es necesario en Admin
            \add_action('init', [$this, 'ensure_singleton'], 20);

            // WPO: Limpiar caché al guardar el banner
            \add_action('save_post_' . self::CPT, [$this, 'clear_cache']);
        }

        // 4. Instanciamos los sub-módulos
        new includes\Admin();
        new includes\Frontend();
    }

    /**
     * Registra el Custom Post Type que actuará como contenedor de datos.
     */
    public function register_cpt(): void
    {
        \register_post_type(self::CPT, [
            'label'        => 'Banner Principal',
            'labels'       => [
                'name'          => 'Banner Principal',
                'singular_name' => 'Banner Principal',
                'edit_item'     => 'Gestionar Banner',
                'all_items'     => 'Banner Principal',
            ],
            'public'       => false,  // No accesible públicamente por URL
            'show_ui'      => true,   // Visible en admin
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon'    => 'dashicons-images-alt2',
            'supports'     => ['title'], // Solo título, el resto son metaboxes
            'map_meta_cap' => true,
            // Bloqueamos la creación manual de nuevos posts desde la UI
            'capabilities' => ['create_posts' => 'do_not_allow'],
        ]);
    }

    /**
     * Garantiza que exista un único post de configuración.
     * Solo corre en Admin para no impactar el frontend.
     */
    public function ensure_singleton(): void
    {
        // Check rápido en memoria/opciones para evitar query pesada
        $id = (int) \get_option(self::OPT_SINGLE_ID, 0);

        if ($id && \get_post($id)) {
            return;
        }

        // Si no está en opciones, buscamos si existe físicamente
        $posts = \get_posts([
            'post_type'      => self::CPT,
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids' // WPO: Solo traemos ID
        ]);

        if (! empty($posts)) {
            $id = $posts[0];
        } else {
            // Si no existe, lo creamos
            $id = \wp_insert_post([
                'post_type'   => self::CPT,
                'post_title'  => 'Banner Principal',
                'post_status' => 'publish'
            ]);
        }

        if ($id && ! \is_wp_error($id)) {
            \update_option(self::OPT_SINGLE_ID, $id);
        }
    }

    /**
     * Borra la caché del banner cuando se actualiza el post.
     * Esto fuerza a regenerar el HTML en la próxima visita.
     */
    public function clear_cache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }
}
