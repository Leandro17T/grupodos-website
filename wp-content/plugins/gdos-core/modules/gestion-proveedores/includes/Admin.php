<?php
// /modules/gestion-proveedores/includes/Admin.php
namespace GDOS\Modules\GestionProveedores\includes;
use GDOS\Modules\GestionProveedores\GestionProveedores as Config;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {
    public function __construct() {
        // Campo personalizado
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_provider_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_provider_field']);
        
        // Filtros del listado
        add_action('restrict_manage_posts', [$this, 'add_admin_filters']);
        add_filter('parse_query', [$this, 'apply_admin_filters']);
        add_action('pre_get_posts', [$this, 'apply_admin_filters']); // Doble hook para compatibilidad

        // Columna personalizada
        add_filter('manage_edit-product_columns', [$this, 'add_provider_column']);
        add_action('manage_product_posts_custom_column', [$this, 'render_provider_column'], 10, 2);
        add_filter('manage_edit-product_sortable_columns', [$this, 'make_provider_column_sortable']);
        
        // Acciones Masivas
        add_filter('bulk_actions-edit-product', [$this, 'add_bulk_actions']);
        add_action('admin_footer-edit.php', [$this, 'add_bulk_actions_script']);
        add_filter('handle_bulk_actions-edit-product', [$this, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [$this, 'display_bulk_actions_notices']);

        // Estilos y validación
        add_action('admin_head', [$this, 'add_admin_styles']);
        add_action('save_post_product', [$this, 'validate_imported_provider'], 20);
    }

    // --- Campo Personalizado ---
    public function add_provider_field() {
        global $post;
        $current_value = get_post_meta($post->ID, '_proveedor', true);
        $providers = Config::get_provider_list();
        $options = ['' => 'Seleccionar...'];
        foreach ($providers as $prov) $options[$prov] = $prov;
        
        woocommerce_wp_select(['id' => '_proveedor', 'label' => 'Proveedor', 'options' => $options, 'desc_tip' => true, 'description' => 'Seleccioná el proveedor del producto.']);
    }

    public function save_provider_field($post_id) {
        if (isset($_POST['_proveedor'])) {
            update_post_meta($post_id, '_proveedor', sanitize_text_field($_POST['_proveedor']));
        }
    }

    // --- Columna, Filtros y Ordenamiento ---
    public function add_admin_filters() {
        if (get_current_screen()->id !== 'edit-product') return;

        $providers = Config::get_provider_list();
        $selected = $_GET['filtro_proveedor'] ?? '';
        echo '<select name="filtro_proveedor"><option value="">Todos los proveedores</option>';
        foreach ($providers as $prov) {
            printf('<option value="%s"%s>%s</option>', esc_attr($prov), selected($selected, $prov, false), esc_html($prov));
        }
        echo '</select>';
    }

    public function apply_admin_filters($query) {
        if (!is_admin() || !$query->is_main_query() || get_current_screen()->id !== 'edit-product') return;

        if (!empty($_GET['filtro_proveedor'])) {
            $query->set('meta_key', '_proveedor');
            $query->set('meta_value', sanitize_text_field($_GET['filtro_proveedor']));
        }
        
        if ($query->get('orderby') === 'proveedor') {
            $query->set('meta_key', '_proveedor');
            $query->set('orderby', 'meta_value');
        }
    }

    public function add_provider_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') $new_columns['proveedor'] = 'Proveedor';
        }
        return $new_columns;
    }

    public function render_provider_column($column, $post_id) {
        if ($column !== 'proveedor') return;
        $proveedor = get_post_meta($post_id, '_proveedor', true);
        if ($proveedor) {
            $colors = Config::get_provider_colors();
            $bg_color = $colors[$proveedor] ?? '#999';
            $text_color = Config::is_light_color($bg_color) ? '#000' : '#fff';
            echo '<span class="gdos-proveedor-label" style="background-color:'.esc_attr($bg_color).';color:'.esc_attr($text_color).';">' . esc_html($proveedor) . '</span>';
        } else {
            echo '—';
        }
    }

    public function make_provider_column_sortable($columns) {
        $columns['proveedor'] = 'proveedor';
        return $columns;
    }

    // --- Acciones Masivas ---
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['asignar_proveedor'] = 'Asignar proveedor';
        return $bulk_actions;
    }
    
    public function add_bulk_actions_script() {
        if (get_current_screen()->id !== 'edit-product') return;
        $providers = Config::get_provider_list();
        ?>
        <script>
        jQuery(function($){
            const $select = $('<select id="gdos-proveedor-select" style="display:none; vertical-align:middle; margin-left:5px;"><option value="">Seleccionar...</option></select>');
            <?php foreach ($providers as $p): ?>
                $select.append('<option value="<?php echo esc_js($p); ?>"><?php echo esc_js($p); ?></option>');
            <?php endforeach; ?>
            $('#bulk-action-selector-top, #bulk-action-selector-bottom').after($select);
            $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function(){
                $(this).next('#gdos-proveedor-select').toggle($(this).val() === 'asignar_proveedor');
            }).trigger('change');
            
            $('#posts-filter').on('submit', function(e){
                const action = $(this).find('select[name^="action"]').val();
                if (action === 'asignar_proveedor') {
                    const provider = $(this).find('#gdos-proveedor-select:visible').val();
                    if (!provider) {
                        alert('Por favor, seleccioná un proveedor.');
                        e.preventDefault();
                        return;
                    }
                    $(this).append($('<input>').attr({type:'hidden', name:'gdos_proveedor', value:provider}));
                }
            });
        });
        </script>
        <?php
    }

    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'asignar_proveedor') return $redirect_to;
        if (empty($_REQUEST['gdos_proveedor'])) return add_query_arg('asignar_proveedor_error', 1, $redirect_to);

        $proveedor = sanitize_text_field($_REQUEST['gdos_proveedor']);
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_proveedor', $proveedor);
        }
        return add_query_arg('asignar_proveedor_success', count($post_ids), $redirect_to);
    }
    
    public function display_bulk_actions_notices() {
        if (isset($_REQUEST['asignar_proveedor_success'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Proveedor asignado a ' . intval($_REQUEST['asignar_proveedor_success']) . ' producto(s).</p></div>';
        }
        if (isset($_REQUEST['asignar_proveedor_error'])) {
            echo '<div class="error notice is-dismissible"><p><strong>Error:</strong> No seleccionaste un proveedor.</p></div>';
        }
    }
    
    // --- Estilos y Validación ---
    public function add_admin_styles() {
        if (get_current_screen()->id === 'edit-product') {
            // Obtenemos la ruta y la URL del directorio principal del módulo
            $module_dir_path = dirname(__DIR__); // Sube un nivel desde /includes/ hasta /gestion-proveedores/
            $module_url = plugin_dir_url($module_dir_path . '/module.php'); // URL base del módulo

            wp_enqueue_style(
                'gdos-proveedores-admin', 
                $module_url . 'assets/css/admin.css', // La URL correcta al archivo
                [], 
                filemtime($module_dir_path . '/assets/css/admin.css') // La ruta correcta al archivo
            );
        }
    }

    public function validate_imported_provider($post_id) {
        $valor = trim(get_post_meta($post_id, '_proveedor', true));
        if ($valor && !in_array($valor, Config::get_provider_list())) {
            update_post_meta($post_id, '_proveedor', '');
        }
    }
}