<?php

namespace GDOS\Modules\EnviosGdos\Methods\Express;

if (! defined('ABSPATH')) exit;

class ExpressMethod extends \WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'gdos_v2_express';
        $this->instance_id = absint($instance_id);
        $this->method_title = 'Envío Express en el día (GDOS V2)';
        $this->method_description = 'Configurable en Envíos GDOS.';
        $this->supports = ['shipping-zones', 'instance-settings'];
        $this->enabled = 'yes';
        $this->title = get_option('gdos_express_method_title', 'Envío Express en el día');
        $this->init();
    }
    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        $t = get_option('gdos_express_method_title');
        if (!empty($t)) $this->title = $t;
    }
    public function init_form_fields()
    {
        $this->instance_form_fields = ['info' => ['title' => 'Gestión', 'type' => 'title', 'description' => 'Ir a <a href="' . admin_url('admin.php?page=gdos-envios-panel&tab=express') . '">Envíos GDOS</a>.']];
    }

    public function calculate_shipping($package = [])
    {
        $api_key = get_option('gdos_global_api_key', '');
        $zonas = json_decode(get_option('gdos_express_zonas_json', '[]'), true);

        if (empty($api_key) || empty($zonas)) return;
        if (strtoupper($package['destination']['country'] ?? '') !== 'UY') return;

        $addr = $this->build_address($package);
        if (!$addr) return;
        $coords = $this->geo($addr, $api_key);
        if (!$coords) return;
        $zona = $this->match($coords, $zonas);
        if (!$zona) return;

        $cost = floatval($zona['costo'] ?? 0);
        $special_cost_found = false;

        // --- LÓGICA ESPECIAL ELECTRODOMÉSTICOS ---
        $special_rules = get_option('gdos_special_rules', []);
        
        if (!empty($special_rules) && is_array($special_rules)) {
            foreach ($package['contents'] as $item) {
                $product = $item['data'];
                $cat_ids = $product->get_category_ids();

                foreach ($special_rules as $rule) {
                    if (in_array($rule['cat_id'], $cat_ids)) {
                        $cost = floatval($rule['express']);
                        $special_cost_found = true;
                        break 2; 
                    }
                }
            }
        }
        // --- FIN LOGICA ESPECIAL ---

        // --- LÓGICA DE ENVÍO GRATIS (Solo si NO es especial) ---
        $threshold = get_option('gdos_express_free_threshold', '');

        if (!$special_cost_found && $threshold !== '' && $package['contents_cost'] > (float)$threshold) {

            // REGLAS DE RESTRICCIÓN:
            $has_coupons = !empty(\WC()->cart->get_applied_coupons());
            $is_iva_off  = $this->is_iva_off_restriction();

            if ($is_iva_off || $has_coupons) {
                // Hay restricción: SE COBRA EL ENVÍO
            } else {
                $cost = 0;
            }
        }

        $this->add_rate([
            'id' => $this->id . ':' . ($zona['id'] ?? 'z'),
            'label' => $this->title,
            'cost' => $cost,
            'meta_data' => ['gdos_zona_id' => $zona['id'] ?? '', 'gdos_zona_nombre' => $zona['nombre'] ?? '']
        ]);
    }

    private function is_iva_off_restriction()
    {
        if (class_exists('\GDOS\Modules\IvaOff\IvaOff')) {
            if (\GDOS\Modules\IvaOff\IvaOff::is_active_now()) {
                $chosen_payment = \WC()->session->get('chosen_payment_method');
                if ($chosen_payment === 'bacs') {
                    return true;
                }
            }
        }
        return false;
    }

    private function build_address($p)
    {
        $s = $p['destination'] ?? [];
        $x = [$s['address'] ?? '', $s['address_2'] ?? '', $s['city'] ?? '', $s['state'] ?? '', $s['postcode'] ?? '', $s['country'] ?? ''];
        return implode(', ', array_filter(array_map('trim', $x))) ?: null;
    }
    private function geo($a, $k)
    {
        $key = 'gdos_geo_v2_exp_' . md5($a);
        $c = get_transient($key);
        if (is_array($c)) return $c;
        $r = wp_remote_get(add_query_arg(['address' => rawurlencode($a), 'key' => $k], 'https://maps.googleapis.com/maps/api/geocode/json'), ['timeout' => 10]);
        if (is_wp_error($r)) return null;
        $b = json_decode(wp_remote_retrieve_body($r), true);
        $l = $b['results'][0]['geometry']['location'] ?? null;
        if ($l) {
            $res = ['lat' => floatval($l['lat']), 'lng' => floatval($l['lng'])];
            set_transient($key, $res, 43200);
            return $res;
        }
        return null;
    }
    private function match($c, $zs)
    {
        foreach ($zs as $z) {
            if (!empty($z['poligono']) && $this->pip($c, $z['poligono'])) return $z;
        }
        return null;
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
}