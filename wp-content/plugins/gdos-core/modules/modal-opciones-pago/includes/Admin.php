<?php
// REFACTORIZADO: 2025-05-21 (Versión Final: Assets separados)
// /modules/modal-opciones-pago/includes/Admin.php

namespace GDOS\Modules\ModalOpcionesPago\includes;

use GDOS\Modules\ModalOpcionesPago\ModalOpcionesPago as Config;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class Admin
{
    private const OPT_KEY       = 'gdos_modal_pagos_options';
    private const NONCE_ACTION  = 'gdos_mp_save_action';
    private const NONCE_FIELD   = 'gdos_mp_nonce';
    private const ADMINPOST_ACT = 'gdos_mp_save';

    private string $page_hook = '';

    public function __construct()
    {
        \add_action('admin_menu', [$this, 'add_submenu_page']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_action('admin_post_' . self::ADMINPOST_ACT, [$this, 'handle_save']);
    }

    public function add_submenu_page(): void
    {
        $this->page_hook = \add_submenu_page(
            'grupodos-main',
            'Opciones de Pago',
            'Opciones de Pago',
            'manage_woocommerce',
            'gdos-modal-opciones-pago',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets($hook): void
    {
        if (empty($this->page_hook) || $hook !== $this->page_hook) return;

        \wp_enqueue_media();
        \wp_enqueue_script('jquery-ui-sortable');

        // Usamos Assets::get desde la raíz del módulo (subimos un nivel desde includes)
        $module_root = \dirname(__DIR__) . '/ModalOpcionesPago.php';

        $css = Assets::get('assets/css/admin.css', $module_root);
        \wp_enqueue_style('gdos-modal-pago-admin', $css['url'], [], $css['ver']);

        $js = Assets::get('assets/js/admin.js', $module_root);
        \wp_enqueue_script('gdos-modal-pago-admin', $js['url'], ['jquery', 'jquery-ui-sortable'], $js['ver'], true);
    }

    public static function get_options(): array
    {
        $defaults = [
            ['cuotas' => 12, 'desc' => '12 cuotas sin interés con Visa', 'img_id' => 0],
            ['cuotas' => 12, 'desc' => '12 cuotas sin interés con MasterCard', 'img_id' => 0],
        ];
        return \get_option(self::OPT_KEY, $defaults);
    }

    public function handle_save(): void
    {
        if (! \current_user_can('manage_woocommerce')) \wp_die('Sin permisos.');

        $nonce = $_POST[self::NONCE_FIELD] ?? '';
        if (! \wp_verify_nonce($nonce, self::NONCE_ACTION)) \wp_die('Nonce inválido.');

        $cards = [];
        if (!empty($_POST['cards']) && \is_array($_POST['cards'])) {
            foreach ($_POST['cards'] as $card) {
                if (!\is_array($card)) continue;
                $cards[] = [
                    'desc'   => \sanitize_text_field($card['desc'] ?? ''),
                    'cuotas' => \absint($card['cuotas'] ?? 1),
                    'img_id' => \absint($card['img_id'] ?? 0),
                ];
            }
        }

        \update_option(self::OPT_KEY, $cards);

        \wp_safe_redirect(\add_query_arg(['page' => 'gdos-modal-opciones-pago', 'updated' => 1], \admin_url('admin.php')));
        exit;
    }

    public function render_page(): void
    {
        if (! \current_user_can('manage_woocommerce')) return;
        $cards = self::get_options();
?>
        <div class="wrap gdos-mp-wrap">
            <h1>💳 Opciones de Pago (Modal)</h1>
            <p>Configura las tarjetas y cuotas que se muestran en el modal de producto.</p>

            <?php if (isset($_GET['updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Cambios guardados correctamente.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
                <?php \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                <input type="hidden" name="action" value="<?php echo \esc_attr(self::ADMINPOST_ACT); ?>">

                <div id="gdos-cards-list">
                    <?php
                    if (!empty($cards)) :
                        foreach ($cards as $i => $c) :
                            $this->render_row($c, $i);
                        endforeach;
                    endif;
                    ?>
                </div>

                <div class="gdos-mp-actions">
                    <button type="button" class="button button-secondary" id="gdos-add-card">
                        <span class="dashicons dashicons-plus-alt2" style="vertical-align:text-bottom;"></span> Añadir Tarjeta
                    </button>
                </div>

                <hr>
                <?php \submit_button('Guardar Cambios'); ?>
            </form>

            <script type="text/html" id="tmpl-gdos-card-row">
                <?php $this->render_row(['cuotas' => 12, 'desc' => '', 'img_id' => 0], '__INDEX__'); ?>
            </script>
        </div>
    <?php
    }

    private function render_row($data, $index): void
    {
        $img_html = '';
        if (!empty($data['img_id'])) {
            $img_url = \wp_get_attachment_url($data['img_id']);
            if ($img_url) $img_html = '<img src="' . \esc_url($img_url) . '">';
        }
    ?>
        <div class="gdos-card-row">
            <span class="gdos-card-handle" title="Arrastrar para ordenar"></span>

            <div class="gdos-card-media">
                <div class="gdos-card-img-preview">
                    <?php echo $img_html ?: '<span class="dashicons dashicons-format-image" style="color:#ccc;"></span>'; ?>
                </div>
                <input type="hidden" name="cards[<?php echo \esc_attr($index); ?>][img_id]" class="img-id-input" value="<?php echo \esc_attr($data['img_id']); ?>">
                <button type="button" class="button button-small gdos-upload-img">Elegir Logo</button>
            </div>

            <div class="gdos-card-inputs">
                <div class="gdos-input-group">
                    <label>Descripción</label>
                    <input type="text" name="cards[<?php echo \esc_attr($index); ?>][desc]" value="<?php echo \esc_attr($data['desc']); ?>" placeholder="Ej: 12 cuotas sin recargo con Visa">
                </div>
                <div class="gdos-input-group small">
                    <label>Cuotas</label>
                    <input type="number" name="cards[<?php echo \esc_attr($index); ?>][cuotas]" value="<?php echo \esc_attr($data['cuotas']); ?>" min="1" max="60">
                </div>
            </div>

            <button type="button" class="gdos-remove-row" title="Eliminar tarjeta">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
<?php
    }
}
