<?php

namespace GDOS\Modules\EnviosGdos\Methods\Flash;

if (! defined('ABSPATH')) exit;

class FlashMethod extends \WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'gdos_v2_flash';
        $this->instance_id = absint($instance_id);
        $this->method_title = 'Envío Flash en 2 horas (GDOS V2)';
        $this->method_description = 'Configurable en Envíos GDOS.';
        $this->supports = ['shipping-zones', 'instance-settings'];
        $this->enabled = 'yes';
        $this->title = get_option('gdos_flash_method_title', 'Envío Flash en 2 horas');
        $this->init();
    }
    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        $t = get_option('gdos_flash_method_title');
        if (!empty($t)) $this->title = $t;
    }
    public function init_form_fields()
    {
        $this->instance_form_fields = ['info' => ['title' => 'Gestión', 'type' => 'title', 'description' => 'Ir a <a href="' . admin_url('admin.php?page=gdos-envios-panel&tab=flash') . '">Envíos GDOS</a>.']];
    }

    public function calculate_shipping($package = [])
    {
        $qty = 0;
        foreach ($package['contents'] as $i) $qty += $i['quantity'];
        if ($qty > 5) return;
        foreach ($package['contents'] as $v) {
            $p = $v['data'];
            $pid = $p->is_type('variation') ? $p->get_parent_id() : $p->get_id();
            if (!has_term('flash', 'product_tag', $pid) && !has_term('Flash y Express', 'product_tag', $pid)) return;
        }

        $api = get_option('gdos_global_api_key', '');
        $zonas = json_decode(get_option('gdos_flash_zonas_json', '[]'), true);
        if (empty($api) || empty($zonas)) return;
        if (strtoupper($package['destination']['country'] ?? '') !== 'UY') return;

        $addr = $this->build_address($package);
        if (!$addr) return;
        $coords = $this->geo($addr, $api);
        if (!$coords) return;
        $zona = $this->match($coords, $zonas);
        if (!$zona) return;

        // --- INICIO LOGICA DE ELECTRODOMÉSTICOS Y TV ---
        $cost = floatval($zona['costo'] ?? 0);
        $special_cost_found = false;
        
        $special_rules = get_option('gdos_special_rules', []);
        
        if (!empty($special_rules) && is_array($special_rules)) {
            foreach ($package['contents'] as $item) {
                $product = $item['data'];
                $cat_ids = $product->get_category_ids();

                foreach ($special_rules as $rule) {
                    if (in_array($rule['cat_id'], $cat_ids)) {
                        $cost = floatval($rule['flash']);
                        $special_cost_found = true;
                        break 2; // Salir de ambos loops
                    }
                }
            }
        }
        // --- FIN LOGICA ELECTRODOMÉSTICOS ---

        $label = $this->title;
        $threshold = get_option('gdos_flash_free_threshold', '3000');
        $excluded = ['Electrodomesticos', 'Electrodomésticos', 'electrodomesticos', 'TV y Audio', 'tv-y-audio', 'TV & Audio'];

        // Solo evaluamos Gratis si NO es un envío especial
        if (!$special_cost_found && $threshold !== '' && $package['contents_cost'] > (float)$threshold) {
            $bad = false;
            foreach ($package['contents'] as $v) {
                $p = $v['data'];
                $pid = $p->is_type('variation') ? $p->get_parent_id() : $p->get_id();
                if (has_term($excluded, 'product_cat', $pid)) {
                    $bad = true;
                    break;
                }
            }
            if (!$bad) {
                // REGLAS DE RESTRICCIÓN:
                $has_coupons = !empty(\WC()->cart->get_applied_coupons());
                $is_iva_off  = $this->is_iva_off_restriction();

                if ($is_iva_off || $has_coupons) {
                    // Hay restricción: SE COBRA EL ENVÍO
                } else {
                    $cost = 0; // Aplicar gratis
                }
            }
        }

        $this->add_rate([
            'id' => $this->id . ':' . ($zona['id'] ?? 'z'),
            'label' => $label,
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
        $key = 'gdos_geo_v2_fla_' . md5($a);
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