<?php
// REFACTORIZADO: 2025-05-21
// /modules/manual-producto/ManualProducto.php

namespace GDOS\Modules\ManualProducto;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class ManualProducto implements ModuleInterface
{
    private const META_KEY = '_gdos_manual_pdf_url';

    public function boot(): void
    {
        // --- Admin Hooks ---
        if (\is_admin()) {
            \add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tab']);
            \add_action('woocommerce_product_data_panels', [$this, 'render_product_data_panel']);
            \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
            \add_action('woocommerce_admin_process_product_object', [$this, 'save_manual_meta']);
        }

        // --- Frontend Hooks ---
        \add_shortcode('gdos_descargar_manual', [$this, 'render_shortcode']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
    }

    // 1. Agregar pestaña "Manual"
    public function add_product_data_tab($tabs)
    {
        $tabs['gdos_manual_tab'] = [
            'label'    => \__('Manual', 'woocommerce'),
            'target'   => 'gdos_manual_data',
            'class'    => ['show_if_simple', 'show_if_variable', 'show_if_external', 'show_if_grouped'],
            'priority' => 90,
        ];
        return $tabs;
    }

    // 2. Panel de contenido
    public function render_product_data_panel()
    {
        global $post;
        $url = \esc_url(\get_post_meta($post->ID, self::META_KEY, true));
?>
        <div id="gdos_manual_data" class="panel woocommerce_options_panel" style="padding: 16px;">
            <div class="options_group">
                <?php
                \woocommerce_wp_text_input([
                    'id'          => 'gdos_manual_pdf_url',
                    'label'       => \__('URL del manual (PDF)', 'woocommerce'),
                    'desc_tip'    => true,
                    'description' => \__('Introduce la URL del archivo PDF o súbelo.', 'woocommerce'),
                    'value'       => $url,
                    'placeholder' => 'https://...',
                    'type'        => 'url'
                ]);
                ?>
                <p class="form-field">
                    <label></label>
                    <button type="button" class="button" id="gdos_manual_pdf_select">
                        <?php \_e('Seleccionar / Subir PDF', 'woocommerce'); ?>
                    </button>
                    <button type="button" class="button" id="gdos_manual_pdf_clear"
                        style="<?php echo $url ? '' : 'display:none;'; ?>">
                        <?php \_e('Quitar', 'woocommerce'); ?>
                    </button>
                </p>
            </div>
        </div>
<?php
    }

    // 3. Scripts Admin (Solo en productos)
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;
        if ('product' === $post_type && \in_array($hook, ['post.php', 'post-new.php'])) {

            \wp_enqueue_media(); // Core Media Uploader

            $js = Assets::get('assets/js/admin.js', __FILE__);

            \wp_enqueue_script(
                'gdos-manual-admin-js',
                $js['url'],
                ['jquery'], // Dependencia básica para usar $(document).ready
                $js['ver'],
                true
            );
        }
    }

    // 4. Guardado seguro
    public function save_manual_meta($product)
    {
        // WooCommerce ya verifica nonces y permisos antes de llamar a este hook
        if (! isset($_POST['gdos_manual_pdf_url'])) return;

        $raw_url = \trim(\wp_unslash($_POST['gdos_manual_pdf_url']));

        if (empty($raw_url)) {
            $product->delete_meta_data(self::META_KEY);
            return;
        }

        $safe_url = \esc_url_raw($raw_url);
        $is_pdf   = false;

        // Validación 1: Extensión
        $path = \parse_url($safe_url, PHP_URL_PATH);
        if ($path && \preg_match('/\.pdf$/i', $path)) {
            $is_pdf = true;
        }
        // Validación 2: Si es un attachment local, verificar MIME real
        else {
            $att_id = \attachment_url_to_postid($safe_url);
            if ($att_id && \get_post_mime_type($att_id) === 'application/pdf') {
                $is_pdf = true;
            }
        }

        if ($is_pdf) {
            $product->update_meta_data(self::META_KEY, $safe_url);
        } else {
            // Opcional: Podrías guardar un log o aviso, pero por seguridad, no guardamos si no es PDF.
            $product->delete_meta_data(self::META_KEY);
        }
    }

    // 5. Shortcode
    public function render_shortcode($atts)
    {
        // Fail-fast
        if (! \function_exists('is_product') || ! \is_product()) return '';

        $product_id = \get_the_ID();
        if (! $product_id) return '';

        $pdf_url = \esc_url(\get_post_meta($product_id, self::META_KEY, true));

        if (! $pdf_url) return ''; // No hay manual, no renderizamos nada

        return \sprintf(
            '<p class="gdos-manual-descarga"><a href="%s" target="_blank" rel="noopener nofollow">📄 %s</a></p>',
            $pdf_url,
            \__('Descargar manual', 'woocommerce')
        );
    }

    // 6. Assets Frontend (Solo si hay shortcode)
    public function enqueue_frontend_styles()
    {
        global $post;
        if (\is_a($post, 'WP_Post') && \has_shortcode($post->post_content, 'gdos_descargar_manual')) {

            $css = Assets::get('assets/css/frontend.css', __FILE__);

            \wp_enqueue_style(
                'gdos-manual-frontend-css',
                $css['url'],
                [],
                $css['ver']
            );
        }
    }
}
