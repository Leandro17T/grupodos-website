<?php

namespace GDOS\Modules\WooDashboardWidget;

use GDOS\Core\ModuleInterface;

defined('ABSPATH') || exit;

/**
 * Módulo: WooDashboardWidget V2
 * Descripción: Centro de comando personalizado con KPIs en tiempo real.
 */
class WooDashboardWidget implements ModuleInterface
{

    private const CACHE_KEY_PREP  = 'gdos_woo_stat_prep';
    private const CACHE_KEY_TODAY = 'gdos_woo_stat_today';
    private const CACHE_TTL       = 300; // 5 minutos (Más corto para reflejar "Hoy" más rápido)

    public function boot(): void
    {
        if (! \class_exists('WooCommerce')) {
            return;
        }

        \add_action('wp_dashboard_setup', [$this, 'register_widget']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Hooks de limpieza de caché
        $this->register_cache_hooks();
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if ('index.php' !== $hook_suffix) {
            return;
        }

        // Usamos plugin_dir_url para evitar errores de tipo array/string
        $css_url = \plugin_dir_url(__FILE__) . 'widget.css';
        \wp_enqueue_style('gdos-woo-widget', $css_url, [], '2.0.0');
    }

    public function register_widget(): void
    {
        if (! \current_user_can('manage_woocommerce')) {
            return;
        }

        \wp_add_dashboard_widget(
            'gdos_command_center',
            \esc_html__('Resumen de Operaciones', 'gdos-core'),
            [$this, 'render']
        );
    }

    public function render(): void
    {
        // Datos
        $prep_count  = $this->get_preparation_count();
        $today_count = $this->get_today_count();
        $user_name   = \wp_get_current_user()->display_name;

        // URLs
        $url_prep  = \admin_url('edit.php?post_type=shop_order&post_status=wc-en-preparacion');
        $url_today = \admin_url('edit.php?post_type=shop_order'); // Lleva a todos, se podría filtrar por fecha con parámetros complejos pero 'todos' es seguro.

?>
        <div class="gdos-dashboard-wrapper">

            <div class="gdos-welcome-header">
                <div class="gdos-greeting">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php echo \esc_html($this->get_greeting() . ', ' . $user_name); ?>
                </div>
                <div class="gdos-date">
                    <?php echo \esc_html(\date_i18n('l j F')); ?>
                </div>
            </div>

            <div class="gdos-cards-grid">
                <div class="gdos-card card-alert">
                    <div class="gdos-card-icon"><span class="dashicons dashicons-hammer"></span></div>
                    <div class="gdos-card-data">
                        <span class="gdos-big-number"><?php echo \esc_html((string) $prep_count); ?></span>
                        <span class="gdos-label"><?php \esc_html_e('En Preparación', 'gdos-core'); ?></span>
                    </div>
                    <a href="<?php echo \esc_url($url_prep); ?>" target="_blank" class="gdos-card-link" aria-label="Ver pedidos en preparación"></a>
                </div>

                <div class="gdos-card card-info">
                    <div class="gdos-card-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="gdos-card-data">
                        <span class="gdos-big-number"><?php echo \esc_html((string) $today_count); ?></span>
                        <span class="gdos-label"><?php \esc_html_e('Pedidos Hoy', 'gdos-core'); ?></span>
                    </div>
                    <a href="<?php echo \esc_url($url_today); ?>" class="gdos-card-link" aria-label="Ver pedidos de hoy"></a>
                </div>
            </div>

            <a href="<?php echo \esc_url($url_prep); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary gdos-main-action">
                <?php \esc_html_e('Gestionar Preparación', 'gdos-core'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>

        </div>
<?php
    }

    /**
     * Retorna conteo de 'wc-en-preparacion'
     */
    private function get_preparation_count(): int
    {
        $cached = \get_transient(self::CACHE_KEY_PREP);
        if (false !== $cached) return (int) $cached;

        $counts = \wp_count_posts('shop_order');
        $slug   = 'wc-en-preparacion';
        $count  = isset($counts->$slug) ? (int) $counts->$slug : 0;

        \set_transient(self::CACHE_KEY_PREP, $count, self::CACHE_TTL);
        return $count;
    }

    /**
     * Retorna conteo de pedidos creados HOY.
     * Performance: Usa WP_Query con fields => ids para ser ligero.
     */
    private function get_today_count(): int
    {
        $cached = \get_transient(self::CACHE_KEY_TODAY);
        if (false !== $cached) return (int) $cached;

        // Consulta optimizada por fecha
        $args = [
            'post_type'      => 'shop_order',
            'post_status'    => ['wc-processing', 'wc-completed', 'wc-on-hold', 'wc-en-preparacion'], // Estados que cuentan como venta real
            'posts_per_page' => -1,
            'fields'         => 'ids', // Solo traer IDs, no objetos completos (Rápido)
            'date_query'     => [
                [
                    'year'  => \date('Y'),
                    'month' => \date('m'),
                    'day'   => \date('d'),
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $count = $query->found_posts;

        \set_transient(self::CACHE_KEY_TODAY, $count, self::CACHE_TTL);
        return $count;
    }

    /**
     * Genera un saludo basado en la hora.
     */
    private function get_greeting(): string
    {
        $hour = (int) \current_time('H');
        if ($hour < 12) return 'Buenos días';
        if ($hour < 20) return 'Buenas tardes';
        return 'Buenas noches';
    }

    private function register_cache_hooks(): void
    {
        $hooks = ['woocommerce_new_order', 'woocommerce_order_status_changed', 'woocommerce_order_delete'];
        foreach ($hooks as $hook) {
            \add_action($hook, function () {
                \delete_transient(self::CACHE_KEY_PREP);
                \delete_transient(self::CACHE_KEY_TODAY);
            });
        }
    }
}
