<?php

namespace GDOS\Modules\EnviosGdos\Methods\Terminal;

if (! defined('ABSPATH')) exit;

class TerminalMethod extends \WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'gdos_v2_terminal';
        $this->instance_id = absint($instance_id);
        $this->method_title = 'Despacho Terminal (GDOS V2)';
        $this->method_description = 'Configurable en Envíos GDOS.';
        $this->supports = ['shipping-zones', 'instance-settings'];
        $this->enabled = 'yes';
        $this->title = get_option('gdos_terminal_method_title', 'Despacho en Terminal Tres Cruces');
        $this->init();
    }
    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        $t = get_option('gdos_terminal_method_title');
        if (!empty($t)) $this->title = $t;
    }
    public function init_form_fields()
    {
        $this->instance_form_fields = ['info' => ['title' => 'Gestión', 'type' => 'title', 'description' => 'Ir a <a href="' . admin_url('admin.php?page=gdos-envios-panel&tab=terminal') . '">Envíos GDOS</a>.']];
    }
    public function calculate_shipping($package = [])
    {
        $api_key = get_option('gdos_global_api_key', '');
        $zonas = json_decode(get_option('gdos_terminal_zonas_json', '[]'), true);

        if (empty($api_key) || empty($zonas)) return;
        if (strtoupper($package['destination']['country'] ?? '') !== 'UY') return;

        $addr = $this->build_address($package);
        if (!$addr) return;
        $coords = $this->geo($addr, $api_key);
        if (!$coords) return;
        $zona = $this->match($coords, $zonas);
        if (!$zona) return;

        // Costo base estándar: 0 (Pagas al recibir)
        $cost = 0;

        // --- LÓGICA ESPECIAL ELECTRODOMÉSTICOS ---
        $special_rules = get_option('gdos_special_rules', []);
        
        if (!empty($special_rules) && is_array($special_rules)) {
            foreach ($package['contents'] as $item) {
                $product = $item['data'];
                $cat_ids = $product->get_category_ids();

                foreach ($special_rules as $rule) {
                    if (in_array($rule['cat_id'], $cat_ids)) {
                        // Aquí asignamos el costo especial definido en el panel
                        $cost = floatval($rule['terminal']);
                        break 2; 
                    }
                }
            }
        }
        // --- FIN LOGICA ESPECIAL ---

        $this->add_rate([
            'id' => $this->id . ':' . ($zona['id'] ?? 'z'),
            'label' => $this->title,
            'cost' => $cost,
            'meta_data' => ['gdos_zona_id' => $zona['id'] ?? '', 'gdos_zona_nombre' => $zona['nombre'] ?? '']
        ]);
    }
    private function build_address($p)
    {
        $s = $p['destination'] ?? [];
        $x = [$s['address'] ?? '', $s['address_2'] ?? '', $s['city'] ?? '', $s['state'] ?? '', $s['postcode'] ?? '', $s['country'] ?? ''];
        return implode(', ', array_filter(array_map('trim', $x))) ?: null;
    }
    private function geo($a, $k)
    {
        $key = 'gdos_geo_v2_ter_' . md5($a);
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