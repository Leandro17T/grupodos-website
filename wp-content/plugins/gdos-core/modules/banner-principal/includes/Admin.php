<?php
// REFACTORIZADO: 2025-12-06
// /modules/banner-principal/includes/Admin.php

namespace GDOS\Modules\BannerPrincipal\includes;

use GDOS\Modules\BannerPrincipal\BannerPrincipal;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    private BannerPrincipal $module;

    public function __construct(BannerPrincipal $module)
    {
        $this->module = $module;

        add_action('admin_menu', [$this, 'admin_menu_cleanup'], 999);
        add_action('admin_bar_menu', [$this, 'admin_bar_cleanup'], 999);
        add_action('current_screen', [$this, 'redirect_to_singleton']);
        add_action('admin_head', [$this, 'admin_head_css']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    private function get_singleton_id(): int
    {
        return (int) get_option(BannerPrincipal::OPT_SINGLE_ID, 0);
    }

    public function redirect_to_singleton($screen): void
    {
        if (wp_doing_ajax() || !is_admin() || !$screen)
            return;

        if ($screen->base === 'edit' && $screen->post_type === BannerPrincipal::CPT) {
            $singleton_id = $this->get_singleton_id();
            if ($singleton_id) {
                wp_redirect(admin_url('post.php?post=' . $singleton_id . '&action=edit'));
                exit;
            }
        }
    }

    public function admin_menu_cleanup(): void
    {
        remove_submenu_page('edit.php?post_type=' . BannerPrincipal::CPT, 'post-new.php?post_type=' . BannerPrincipal::CPT);
    }

    public function admin_bar_cleanup($wp_admin_bar): void
    {
        $wp_admin_bar->remove_node('new-' . BannerPrincipal::CPT);
    }

    public function admin_head_css(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === BannerPrincipal::CPT) {
            echo '<style>.wrap .page-title-action{display:none!important}</style>';
        }
    }

    public function enqueue_assets(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== BannerPrincipal::CPT)
            return;

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css', [], '4.6.13');
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js', [], '4.6.13', true);

        // Usamos los helpers del módulo principal para cargar assets
        $css = $this->module->asset('assets/admin/css/admin.css');
        if ($css['url']) {
            wp_enqueue_style('gdos-banner-admin-css', $css['url'], [], $css['ver']);
        }

        $js = $this->module->asset('assets/admin/js/admin.js');
        if ($js['url']) {
            wp_enqueue_script('gdos-banner-admin-js', $js['url'], ['jquery', 'jquery-ui-sortable', 'flatpickr'], $js['ver'], true);
        }
    }

    public function add_meta_boxes(): void
    {
        add_meta_box('gdos_slides_tabs', 'Banners (Desktop / Tablet / Mobile)', [$this, 'render_metabox'], BannerPrincipal::CPT, 'normal', 'high');
        add_meta_box('gdos_shortcode_info', 'Shortcode', [$this, 'render_shortcode_metabox'], BannerPrincipal::CPT, 'side', 'default');
    }

    public function render_metabox($post): void
    {
        wp_nonce_field('gdos_save_slider_set', 'gdos_nonce_slider_set');

        $data = [
            'desktop' => get_post_meta($post->ID, '_gdos_slides_desktop', true) ?: [],
            'tablet' => get_post_meta($post->ID, '_gdos_slides_tablet', true) ?: [],
            'mobile' => get_post_meta($post->ID, '_gdos_slides_mobile', true) ?: [],
        ];

        // Usamos el sistema de vistas del módulo
        echo $this->module->view('views/admin/metabox', $data);
    }

    public function render_shortcode_metabox($post): void
    {
        echo '<p>' . esc_html__('Usa este shortcode para mostrar el banner:', 'gdos-core') . '</p>';
        echo '<code style="display:block; padding:8px; background:#f1f1f1; border-radius:4px; margin-bottom:10px;">[banner_principal]</code>';

        echo '<p><strong>' . esc_html__('Opciones disponibles:', 'gdos-core') . '</strong></p>';
        echo '<code style="display:block; padding:8px; background:#f1f1f1; border-radius:4px; font-size:11px;">[banner_principal autoplay="0" interval="4000" pause_on_hover="0"]</code>';

        echo '<ul style="list-style:disc; margin-left:20px; color:#666; font-size:12px; margin-top:8px;">';
        echo '<li><code>autoplay</code>: 1 / 0</li>';
        echo '<li><code>interval</code>: ms (ej: 5000)</li>';
        echo '<li><code>pause_on_hover</code>: 1 / 0</li>';
        echo '</ul>';
    }

    public function save_meta($post_id, $post): void
    {
        if (
            $post->post_type !== BannerPrincipal::CPT
            || !isset($_POST['gdos_nonce_slider_set'])
            || !wp_verify_nonce(sanitize_key($_POST['gdos_nonce_slider_set']), 'gdos_save_slider_set')
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $process_slides = function ($type) {
            $ids = $_POST["gdos_{$type}_id"] ?? [];
            $lnk = $_POST["gdos_{$type}_link"] ?? [];
            $df = $_POST["gdos_{$type}_date_from"] ?? [];
            $dt = $_POST["gdos_{$type}_date_to"] ?? [];
            $tf = $_POST["gdos_{$type}_time_from"] ?? [];
            $tt = $_POST["gdos_{$type}_time_to"] ?? [];
            $pr = $_POST["gdos_{$type}_priority"] ?? [];
            $dy = $_POST["gdos_{$type}_days"] ?? [];

            // NUEVO: Captura del label de texto
            $clbl = $_POST["gdos_{$type}_countdown_label"] ?? [];

            $cd_raw = $_POST["gdos_{$type}_countdown"] ?? [];
            $cd_flags = [];
            $ptr = 0;
            $total_slides = count($ids);

            for ($i = 0; $i < $total_slides; $i++) {
                $val = 0;
                if (isset($cd_raw[$ptr])) {
                    if ($cd_raw[$ptr] == '0') {
                        if (isset($cd_raw[$ptr + 1]) && $cd_raw[$ptr + 1] == '1') {
                            $val = 1;
                            $ptr += 2;
                        } else {
                            $val = 0;
                            $ptr += 1;
                        }
                    } else {
                        $val = 1;
                        $ptr += 1;
                    }
                }
                $cd_flags[$i] = $val;
            }

            $slides = [];
            for ($i = 0; $i < $total_slides; $i++) {
                if (empty($ids[$i]))
                    continue;

                $slides[] = [
                    'id' => intval($ids[$i]),
                    'link' => esc_url_raw($lnk[$i] ?? ''),
                    'date_from' => sanitize_text_field($df[$i] ?? ''),
                    'date_to' => sanitize_text_field($dt[$i] ?? ''),
                    'time_from' => sanitize_text_field($tf[$i] ?? ''),
                    'time_to' => sanitize_text_field($tt[$i] ?? ''),
                    'priority' => intval($pr[$i] ?? 10),
                    'days' => preg_replace('/[^0-9,]/', '', $dy[$i] ?? ''),
                    'countdown' => $cd_flags[$i] ?? 0,
                    // NUEVO: Guardar
                    'countdown_label' => sanitize_text_field($clbl[$i] ?? 'end'),
                ];
            }
            return $slides;
        };

        update_post_meta($post_id, '_gdos_slides_desktop', $process_slides('desktop'));
        update_post_meta($post_id, '_gdos_slides_tablet', $process_slides('tablet'));
        update_post_meta($post_id, '_gdos_slides_mobile', $process_slides('mobile'));
    }
}
