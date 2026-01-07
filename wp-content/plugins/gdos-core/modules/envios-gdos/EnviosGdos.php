<?php

namespace GDOS\Modules\EnviosGdos;

use GDOS\Core\ModuleInterface;

if (! defined('ABSPATH')) exit;

class EnviosGdos implements ModuleInterface
{

    public function boot(): void
    {
        add_filter('woocommerce_shipping_methods', [$this, 'register_methods']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_gdos_envios_save', [$this, 'save']);
        add_action('admin_post_gdos_envios_export', [$this, 'export']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_gdos_test_address', [$this, 'ajax_test_address']);
        add_action('admin_post_gdos_clear_geo_cache', [$this, 'clear_cache']);

        if (!is_admin() || wp_doing_ajax()) {
            if (file_exists(__DIR__ . '/includes/CheckoutFrontend.php')) {
                require_once __DIR__ . '/includes/CheckoutFrontend.php';
                new \GDOS\Modules\EnviosGdos\Includes\CheckoutFrontend();
            }
        }
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'gdos-envios-panel') === false) return;

        $key = get_option('gdos_global_api_key', '');
        if ($key) {
            wp_enqueue_script('gdos-gmaps-admin', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($key) . '&libraries=drawing,places', [], null, true);
        }

        wp_enqueue_script('gdos-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], time(), true);
        wp_localize_script('gdos-admin-js', 'gdosAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('gdos_test_address_nonce')
        ]);
    }

    public function ajax_test_address()
    {
        check_ajax_referer('gdos_test_address_nonce', 'nonce');

        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 0;
        $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;
        $tab = sanitize_text_field($_POST['tab'] ?? 'express');
        $prefix = 'gdos_' . $tab . '_';

        $zonas = json_decode(get_option($prefix . 'zonas_json', '[]'), true);
        if (!is_array($zonas)) $zonas = [];

        $match = null;
        $coords = ['lat' => $lat, 'lng' => $lng];

        foreach ($zonas as $z) {
            if (!empty($z['poligono']) && $this->pip($coords, $z['poligono'])) {
                $match = $z;
                break;
            }
        }

        if ($match) {
            wp_send_json_success([
                'found' => true,
                'zona' => $match['nombre'] ?? 'Sin Nombre',
                'costo' => $match['costo'] ?? 0,
            ]);
        } else {
            wp_send_json_success([
                'found' => false,
                'msg' => 'Fuera de zona de cobertura (Lat: ' . $lat . ', Lng: ' . $lng . ')'
            ]);
        }
    }

    private function pip($c, $p)
    {
        if ($p[0] !== end($p)) $p[] = $p[0];
        $x = $c['lng'];
        $y = $c['lat'];
        $in = false;
        $n = count($p);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $p[$i][0];
            $xi = $p[$i][1];
            $yj = $p[$j][0];
            $xj = $p[$j][1];
            if ((($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) $in = !$in;
        }
        return $in;
    }

    public function clear_cache()
    {
        if (!current_user_can('manage_options')) wp_die('Sin permisos');
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_gdos_geo_%' OR option_name LIKE '_transient_timeout_gdos_geo_%'");
        wp_safe_redirect(add_query_arg(['page' => 'gdos-envios-panel', 'tab' => 'general', 'msg' => 'cache_cleared'], admin_url('admin.php')));
        exit;
    }

    public function register_methods($methods)
    {
        $files = [
            __DIR__ . '/methods/express/ExpressMethod.php',
            __DIR__ . '/methods/flash/FlashMethod.php',
            __DIR__ . '/methods/pickup/PickupMethod.php',
            __DIR__ . '/methods/terminal/TerminalMethod.php'
        ];
        foreach ($files as $f) {
            if (file_exists($f)) require_once $f;
        }

        if (class_exists('GDOS\Modules\EnviosGdos\Methods\Express\ExpressMethod')) $methods['gdos_v2_express'] = 'GDOS\Modules\EnviosGdos\Methods\Express\ExpressMethod';
        if (class_exists('GDOS\Modules\EnviosGdos\Methods\Flash\FlashMethod')) $methods['gdos_v2_flash'] = 'GDOS\Modules\EnviosGdos\Methods\Flash\FlashMethod';
        if (class_exists('GDOS\Modules\EnviosGdos\Methods\Pickup\PickupMethod')) $methods['gdos_v2_pickup'] = 'GDOS\Modules\EnviosGdos\Methods\Pickup\PickupMethod';
        if (class_exists('GDOS\Modules\EnviosGdos\Methods\Terminal\TerminalMethod')) $methods['gdos_v2_terminal'] = 'GDOS\Modules\EnviosGdos\Methods\Terminal\TerminalMethod';

        return $methods;
    }

    public function add_menu()
    {
        add_submenu_page('grupodos-main', 'Envíos GDOS', 'Envíos GDOS', 'manage_options', 'gdos-envios-panel', [$this, 'render']);
    }

    public function render()
    {
        if (!current_user_can('manage_options')) return;

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        $data = [];

        // Recuperar categorías para el select de reglas especiales
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        
        if ($active_tab === 'general') {
            $data = [
                'api_key' => get_option('gdos_global_api_key', ''),
                'special_rules' => get_option('gdos_special_rules', []) // Reglas guardadas
            ];
        } else {
            $prefix = 'gdos_' . $active_tab . '_';
            $data = [
                'title' => get_option($prefix . 'method_title', ''),
                'desc' => get_option($prefix . 'frontend_desc', ''),
                'json' => get_option($prefix . 'zonas_json', '[]'),
                'free_threshold' => get_option($prefix . 'free_threshold', ''),
                'dynamic_enabled' => get_option($prefix . 'dynamic_enabled', 'no'),
                'cutoff_time' => get_option($prefix . 'cutoff_time', '16:00'),
                'desc_today' => get_option($prefix . 'desc_today', ''),
                'desc_tomorrow' => get_option($prefix . 'desc_tomorrow', ''),
                'cutoff_sat'    => get_option($prefix . 'cutoff_sat', '12:00'),
                'desc_sat_pm'   => get_option($prefix . 'desc_sat_pm', ''),
            ];
        }

        $zonas = isset($data['json']) ? json_decode($data['json'], true) : [];
        if (!is_array($zonas)) $zonas = [];

        if (file_exists(__DIR__ . '/views/admin-panel.php')) require_once __DIR__ . '/views/admin-panel.php';
    }

    public function save()
    {
        check_admin_referer('gdos_envios_save_nonce');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');

        $tab = sanitize_key($_POST['current_tab'] ?? 'general');

        if ($tab === 'general') {
            update_option('gdos_global_api_key', sanitize_text_field($_POST['gdos_global_api_key'] ?? ''));
            
            // --- GUARDAR REGLAS ESPECIALES (Electrodomésticos) ---
            $rules = [];
            if (!empty($_POST['special_cat_id']) && is_array($_POST['special_cat_id'])) {
                $ids = $_POST['special_cat_id'];
                $flash = $_POST['special_cost_flash'];
                $express = $_POST['special_cost_express'];
                $terminal = $_POST['special_cost_terminal'];

                for ($i = 0; $i < count($ids); $i++) {
                    if (!empty($ids[$i])) {
                        $rules[] = [
                            'cat_id' => intval($ids[$i]),
                            'flash' => floatval($flash[$i] ?? 0),
                            'express' => floatval($express[$i] ?? 0),
                            'terminal' => floatval($terminal[$i] ?? 0),
                        ];
                    }
                }
            }
            update_option('gdos_special_rules', $rules);

            wp_safe_redirect(add_query_arg(['page' => 'gdos-envios-panel', 'tab' => 'general', 'msg' => 'success'], admin_url('admin.php')));
            exit;
        }

        $prefix = 'gdos_' . $tab . '_';

        if (isset($_POST[$prefix . 'method_title'])) update_option($prefix . 'method_title', sanitize_text_field($_POST[$prefix . 'method_title']));
        if (isset($_POST[$prefix . 'frontend_desc'])) update_option($prefix . 'frontend_desc', wp_kses_post($_POST[$prefix . 'frontend_desc']));

        if (isset($_POST[$prefix . 'free_threshold'])) {
            $val = $_POST[$prefix . 'free_threshold'];
            update_option($prefix . 'free_threshold', ($val === '') ? '' : (float)$val);
        }

        $dynamic_enabled = isset($_POST[$prefix . 'dynamic_enabled']) ? 'yes' : 'no';
        update_option($prefix . 'dynamic_enabled', $dynamic_enabled);

        if (isset($_POST[$prefix . 'cutoff_time'])) update_option($prefix . 'cutoff_time', sanitize_text_field($_POST[$prefix . 'cutoff_time']));
        if (isset($_POST[$prefix . 'desc_today'])) update_option($prefix . 'desc_today', wp_kses_post($_POST[$prefix . 'desc_today']));
        if (isset($_POST[$prefix . 'desc_tomorrow'])) update_option($prefix . 'desc_tomorrow', wp_kses_post($_POST[$prefix . 'desc_tomorrow']));
        if (isset($_POST[$prefix . 'cutoff_sat'])) update_option($prefix . 'cutoff_sat', sanitize_text_field($_POST[$prefix . 'cutoff_sat']));
        if (isset($_POST[$prefix . 'desc_sat_pm'])) update_option($prefix . 'desc_sat_pm', wp_kses_post($_POST[$prefix . 'desc_sat_pm']));

        $json_raw = get_option($prefix . 'zonas_json', '[]');
        if (!empty($_FILES['gdos_zonas_file']['tmp_name'])) {
            $json_raw = file_get_contents($_FILES['gdos_zonas_file']['tmp_name']);
        } elseif (isset($_POST['gdos_zonas_json_text'])) {
            $json_raw = wp_unslash($_POST['gdos_zonas_json_text']);
        }
        $zonas_arr = json_decode($json_raw, true);

        if (is_array($zonas_arr) && !empty($_POST['gdos_cost']) && is_array($_POST['gdos_cost'])) {
            foreach ($zonas_arr as &$z) {
                $zid = (string)($z['id'] ?? '');
                if ($zid && isset($_POST['gdos_cost'][$zid])) $z['costo'] = floatval($_POST['gdos_cost'][$zid]);
            }
        }

        $msg = is_array($zonas_arr) ? 'success' : 'error_json';
        if (is_array($zonas_arr)) update_option($prefix . 'zonas_json', wp_json_encode($zonas_arr, JSON_UNESCAPED_UNICODE));

        wp_safe_redirect(add_query_arg(['page' => 'gdos-envios-panel', 'tab' => $tab, 'msg' => $msg], admin_url('admin.php')));
        exit;
    }

    public function export()
    {
        check_admin_referer('gdos_envios_export_nonce');
        if (!current_user_can('manage_options')) wp_die('Sin permisos');
        $tab = sanitize_key($_GET['tab'] ?? 'express');
        $prefix = 'gdos_' . $tab . '_';
        $json = get_option($prefix . 'zonas_json', '[]');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="gdos_' . $tab . '_zonas_' . date('Ymd_His') . '.json"');
        echo $json;
        exit;
    }
}