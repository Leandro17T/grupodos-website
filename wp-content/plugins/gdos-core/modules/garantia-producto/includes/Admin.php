<?php
// REFACTORIZADO: 2025-12-06
// /modules/garantia-producto/includes/Admin.php

namespace GDOS\Modules\GarantiaProducto\includes;

use GDOS\Modules\GarantiaProducto\GarantiaProducto;

if (! \defined('ABSPATH')) {
    exit;
}

class Admin
{

    public function __construct()
    {
        // Campo personalizado en la página del producto
        \add_action('woocommerce_product_options_general_product_data', [$this, 'add_warranty_field']);
        \add_action('woocommerce_process_product_meta', [$this, 'save_warranty_field']);

        // Filtros en el listado de productos
        \add_action('restrict_manage_posts', [$this, 'add_admin_filters'], 20);
        \add_filter('parse_query', [$this, 'apply_admin_filters']);

        // Acciones masivas
        \add_filter('bulk_actions-edit-product', [$this, 'add_bulk_actions']);
        \add_filter('handle_bulk_actions-edit-product', [$this, 'handle_bulk_actions'], 10, 3);

        // Botón de aplicar por marca
        \add_action('restrict_manage_posts', [$this, 'add_apply_by_brand_button'], 30);
        \add_action('load-edit.php', [$this, 'handle_apply_by_brand']);

        // Notificaciones de resultado
        \add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    // --- Campo Personalizado (Meta Box) ---

    public function add_warranty_field(): void
    {
        $options = ['' => \__('Por defecto (15 días)', 'gdos-core')];

        // Generamos etiquetas cortas dinámicamente basadas en las claves
        foreach (GarantiaProducto::get_manual_warranty_messages() as $key => $message) {
            // Ejemplo: '1_año' -> '1 Año'
            $label = \ucwords(\str_replace('_', ' ', $key));
            $options[$key] = $label;
        }

        \woocommerce_wp_select([
            'id'          => '_garantia_producto',
            'label'       => \__('Garantía del producto', 'gdos-core'),
            'description' => \__('Seleccioná la garantía aplicable.', 'gdos-core'),
            'desc_tip'    => true,
            'options'     => $options
        ]);

        // SEGURIDAD: Nonce para proteger el guardado
        \wp_nonce_field('gdos_save_warranty_meta', 'gdos_warranty_nonce');
    }

    public function save_warranty_field($post_id): void
    {
        // Verificación de seguridad
        if (! isset($_POST['gdos_warranty_nonce']) || ! \wp_verify_nonce($_POST['gdos_warranty_nonce'], 'gdos_save_warranty_meta')) {
            return;
        }

        if (isset($_POST['_garantia_producto'])) {
            \update_post_meta($post_id, '_garantia_producto', \sanitize_text_field($_POST['_garantia_producto']));
        }
    }

    // --- Filtros de Admin (Tabla de Productos) ---

    public function add_admin_filters(): void
    {
        if (\get_current_screen()->id !== 'edit-product') {
            return;
        }

        // Filtro por Marca (Taxonomía)
        \wp_dropdown_categories([
            'show_option_all' => \__('Todas las marcas', 'gdos-core'),
            'taxonomy'        => 'pa_marca',
            'name'            => 'pa_marca',
            'orderby'         => 'name',
            'selected'        => $_GET['pa_marca'] ?? '',
            'show_count'      => true,
            'hide_empty'      => false,
            'value_field'     => 'slug', // Importante para que coincida con la query
        ]);

        // Filtro por Garantía (Meta)
        $garantia_filter = $_GET['_garantia_producto_estado'] ?? '';
        $options         = GarantiaProducto::get_manual_warranty_messages();

        echo '<select name="_garantia_producto_estado">';
        echo '<option value="">' . \esc_html__('Todas las garantías', 'gdos-core') . '</option>';
        echo '<option value="vacia" ' . \selected($garantia_filter, 'vacia', false) . '>' . \esc_html__('Sin selección (defecto)', 'gdos-core') . '</option>';

        foreach ($options as $key => $msg) {
            $label = \ucwords(\str_replace('_', ' ', $key));
            echo '<option value="' . \esc_attr($key) . '" ' . \selected($garantia_filter, $key, false) . '>' . \esc_html('Con selección: ' . $label) . '</option>';
        }
        echo '</select>';
    }

    public function apply_admin_filters($query): void
    {
        if (! \is_admin() || ! $query->is_main_query() || \get_current_screen()->id !== 'edit-product') {
            return;
        }

        // Aplicar filtro Marca
        if (! empty($_GET['pa_marca'])) {
            $query->set('tax_query', [
                [
                    'taxonomy' => 'pa_marca',
                    'field'    => 'slug',
                    'terms'    => \sanitize_text_field($_GET['pa_marca'])
                ]
            ]);
        }

        // Aplicar filtro Garantía
        if (! empty($_GET['_garantia_producto_estado'])) {
            $estado     = \sanitize_text_field($_GET['_garantia_producto_estado']);
            $meta_query = $query->get('meta_query') ?: [];

            if ($estado === 'vacia') {
                $meta_query[] = [
                    'relation' => 'OR',
                    ['key' => '_garantia_producto', 'compare' => 'NOT EXISTS'],
                    ['key' => '_garantia_producto', 'value' => '', 'compare' => '=']
                ];
            } else {
                $meta_query[] = [
                    'key'   => '_garantia_producto',
                    'value' => $estado
                ];
            }
            $query->set('meta_query', $meta_query);
        }
    }

    // --- Acciones Masivas y Avanzadas ---

    public function add_bulk_actions($bulk_actions): array
    {
        foreach (GarantiaProducto::get_manual_warranty_messages() as $key => $msg) {
            $label = \ucwords(\str_replace('_', ' ', $key));
            $bulk_actions['gdos_set_garantia_' . $key] = \__('Asignar garantía: ', 'gdos-core') . $label;
        }
        $bulk_actions['gdos_clear_garantia'] = \__('Quitar selección de garantía', 'gdos-core');

        return $bulk_actions;
    }

    public function handle_bulk_actions($redirect_to, $action, $post_ids): string
    {
        if (! \current_user_can('edit_products')) {
            return $redirect_to;
        }

        $value_to_set = null;
        if (\strpos($action, 'gdos_set_garantia_') === 0) {
            $value_to_set = \str_replace('gdos_set_garantia_', '', $action);
        }

        if ($value_to_set) {
            foreach ($post_ids as $post_id) {
                \update_post_meta($post_id, '_garantia_producto', $value_to_set);
            }
            return \add_query_arg('gdos_bulk_applied', \count($post_ids), $redirect_to);
        } elseif ($action === 'gdos_clear_garantia') {
            foreach ($post_ids as $post_id) {
                \delete_post_meta($post_id, '_garantia_producto');
            }
            return \add_query_arg('gdos_bulk_applied', \count($post_ids), $redirect_to);
        }

        return $redirect_to;
    }

    public function add_apply_by_brand_button(): void
    {
        if (\get_current_screen()->id !== 'edit-product') {
            return;
        }

        $url = \add_query_arg([
            'post_type'                 => 'product',
            'gdos_apply_brand_warranty' => 1,
            'pa_marca'                  => $_GET['pa_marca'] ?? '',
            '_wpnonce'                  => \wp_create_nonce('gdos_apply_brand_warranty'),
        ], \admin_url('edit.php'));

        echo '<a href="' . \esc_url($url) . '" class="button" style="margin-left:6px;">' . \esc_html__('Aplicar garantías por marca', 'gdos-core') . '</a>';
    }

    public function handle_apply_by_brand(): void
    {
        // Validaciones de seguridad y contexto
        if (
            \get_current_screen()->id !== 'edit-product'
            || ! isset($_GET['gdos_apply_brand_warranty'])
            || ! \current_user_can('edit_products')
            || ! \wp_verify_nonce(\sanitize_key($_GET['_wpnonce'] ?? ''), 'gdos_apply_brand_warranty')
        ) {
            return;
        }

        $marca = \sanitize_text_field($_GET['pa_marca'] ?? '');
        $brand_map = GarantiaProducto::get_brand_warranty_map();

        if (! $marca || ! isset($brand_map[$marca])) {
            // Podríamos agregar un aviso de error aquí si la marca no es válida
            return;
        }

        // WPO: Evitamos TimeOut en operaciones masivas
        \set_time_limit(0);

        $paged   = 1;
        $updated = 0;
        $value   = $brand_map[$marca];

        do {
            // Query optimizada: Solo IDs, sin conteo de filas total
            $q = new \WP_Query([
                'post_type'      => 'product',
                'posts_per_page' => 200,
                'paged'          => $paged,
                'tax_query'      => [
                    [
                        'taxonomy' => 'pa_marca',
                        'field'    => 'slug',
                        'terms'    => $marca
                    ]
                ],
                'fields'         => 'ids',
                'no_found_rows'  => true
            ]);

            if ($q->have_posts()) {
                foreach ($q->posts as $pid) {
                    \update_post_meta($pid, '_garantia_producto', $value);
                    $updated++;
                }
            }

            $paged++;

            // Limpiamos memoria en bucles grandes
            if (\function_exists('wp_cache_flush')) {
                \wp_cache_flush();
            }
        } while ($q->have_posts());

        $redirect = \remove_query_arg(['gdos_apply_brand_warranty', '_wpnonce'], \wp_get_referer() ?: \admin_url('edit.php?post_type=product'));
        \wp_safe_redirect(\add_query_arg('gdos_brand_applied', $updated, $redirect));
        exit;
    }

    // --- Notificaciones ---

    public function display_admin_notices(): void
    {
        if (! empty($_REQUEST['gdos_bulk_applied'])) {
            $count = \intval($_REQUEST['gdos_bulk_applied']);
            echo '<div class="notice notice-success is-dismissible"><p>' . \sprintf(\esc_html__('Garantía actualizada en %d producto(s).', 'gdos-core'), $count) . '</p></div>';
        }

        if (! empty($_GET['gdos_brand_applied'])) {
            $count = \intval($_GET['gdos_brand_applied']);
            echo '<div class="notice notice-success is-dismissible"><p>' . \sprintf(\esc_html__('Garantías por marca aplicadas en %d producto(s).', 'gdos-core'), $count) . '</p></div>';
        }
    }
}
