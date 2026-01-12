<?php
// REFACTORIZADO: 2025-01-12
// /modules/banner-principal/BannerPrincipal.php

namespace GDOS\Modules\BannerPrincipal;

use GDOS\Core\BaseModule;

if (!\defined('ABSPATH')) {
    exit;
}

class BannerPrincipal extends BaseModule
{

    const CPT = 'gdos_slider_set_snip';
    const OPT_SINGLE_ID = 'gdos_single_slider_id';
    const CACHE_KEY = 'gdos_banner_main_data';

    public function boot(): void
    {
        // 1. Carga de dependencias
        // Nota: Podriamos mover estas clases a este mismo archivo si quisieramos reducir ficheros, 
        // pero por ahora las mantenemos para no hacer un cambio tan drastico.
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Frontend.php';

        // 2. Registro del CPT
        \add_action('init', [$this, 'register_cpt']);

        // 3. Lógica Administrativa
        if (\is_admin()) {
            \add_action('init', [$this, 'ensure_singleton'], 20);
            \add_action('save_post_' . self::CPT, [$this, 'clear_cache']);

            // Instanciamos Admin pasando $this para que pueda usar los helpers del módulo si fuera necesario
            new includes\Admin($this);
        } else {
            // Instanciamos Frontend pasando $this
            new includes\Frontend($this);
        }
    }

    /**
     * Registra el Custom Post Type
     */
    public function register_cpt(): void
    {
        \register_post_type(self::CPT, [
            'label' => 'Banner Principal',
            'labels' => [
                'name' => 'Banner Principal',
                'singular_name' => 'Banner Principal',
                'edit_item' => 'Gestionar Banner',
                'all_items' => 'Banner Principal',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => ['title'],
            'map_meta_cap' => true,
            'capabilities' => ['create_posts' => 'do_not_allow'],
        ]);
    }

    /**
     * Singleton Post Check
     */
    public function ensure_singleton(): void
    {
        $id = (int) \get_option(self::OPT_SINGLE_ID, 0);

        if ($id && \get_post($id)) {
            return;
        }

        $posts = \get_posts([
            'post_type' => self::CPT,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);

        if (!empty($posts)) {
            $id = $posts[0];
        } else {
            $id = \wp_insert_post([
                'post_type' => self::CPT,
                'post_title' => 'Banner Principal',
                'post_status' => 'publish'
            ]);
        }

        if ($id && !\is_wp_error($id)) {
            \update_option(self::OPT_SINGLE_ID, $id);
        }
    }

    public function clear_cache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }
}

