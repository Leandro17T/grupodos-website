<?php
// REFACTORIZADO: 2025-05-21 (Corregido: isset nativo)
// /modules/widget-pedidos-preparacion/WidgetPedidosPreparacion.php

namespace GDOS\Modules\WidgetPedidosPreparacion;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class WidgetPedidosPreparacion implements ModuleInterface
{
    /**
     * Nombre del transient para cachear el HTML del widget.
     */
    private const TRANSIENT_KEY = 'gdos_widget_pedidos_prep_html';

    public function boot(): void
    {
        if (! \class_exists('WooCommerce')) return;

        // Hook para guardar metadatos y limpiar caché al cambiar estado
        \add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 3);

        // Registrar el widget en el escritorio
        \add_action('wp_dashboard_setup', [$this, 'register_dashboard_widget']);
    }

    /**
     * Maneja el cambio de estado: guarda fecha si entra en preparación
     * y limpia la caché si el pedido entra o sale de ese estado.
     */
    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        // 1. Guardar fecha si entra en preparación
        if ($new_status === 'en-preparacion') {
            \update_post_meta($order_id, '_fecha_en_preparacion', \current_time('mysql'));
        }

        // 2. Limpiar caché si el cambio involucra el estado 'en-preparacion'
        // (Ya sea que entre nuevo o que salga a 'completado'/'enviado')
        if ($new_status === 'en-preparacion' || $old_status === 'en-preparacion') {
            \delete_transient(self::TRANSIENT_KEY);
        }
    }

    public function register_dashboard_widget()
    {
        // Solo usuarios con permisos de tienda deberían ver esto
        if (! \current_user_can('manage_woocommerce')) return;

        // Cargar assets solo en el dashboard
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        \wp_add_dashboard_widget(
            'gdos_pedidos_en_preparacion',
            '🛒 Pedidos en Preparación - Grupo Dos',
            [$this, 'render_widget_content']
        );
    }

    public function enqueue_assets($hook)
    {
        // Carga condicional estricta: Solo en el dashboard
        if ('index.php' !== $hook) return;

        $css = Assets::get('assets/css/admin.css', __FILE__);

        \wp_enqueue_style(
            'gdos-widget-pedidos',
            $css['url'],
            [],
            $css['ver']
        );
    }

    /**
     * Renderiza el contenido del widget implementando caché y seguridad.
     */
    public function render_widget_content()
    {
        // 1. Lógica de limpieza manual de caché (Botón refrescar)
        if (isset($_GET['gdos_clear_cache']) && $_GET['gdos_clear_cache'] == '1') {
            // CORRECCIÓN: isset no debe llevar barra invertida
            if (isset($_GET['_wpnonce']) && \wp_verify_nonce($_GET['_wpnonce'], 'gdos_clear_widget_cache')) {
                \delete_transient(self::TRANSIENT_KEY);
            }
        }

        // 2. Intentar obtener HTML del transient
        $cached_html = \get_transient(self::TRANSIENT_KEY);

        if (false !== $cached_html) {
            echo $cached_html; // PHPCS: ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->render_refresh_button();
            return;
        }

        // 3. Generar contenido si no hay caché
        \ob_start();

        try {
            $orders = \wc_get_orders([
                'status'   => 'en-preparacion',
                'limit'    => -1,
                'orderby'  => 'meta_value',
                'meta_key' => '_fecha_en_preparacion',
                'order'    => 'ASC',
            ]);

            if (empty($orders)) {
                echo '<p class="gdos-widget-empty">✅ No hay pedidos en preparación.</p>';
            } else {
                echo '<ul class="gdos-widget-list">';

                $timezone = \wp_timezone();
                $now      = new \DateTime('now', $timezone);

                foreach ($orders as $order) {
                    $id = $order->get_id();
                    $preparation_date_raw = \get_post_meta($id, '_fecha_en_preparacion', true);

                    if (! $preparation_date_raw) continue;

                    $preparation_date = new \DateTime($preparation_date_raw, $timezone);
                    $hours_passed     = $this->calculate_business_hours($preparation_date, $now);

                    // Lógica de semáforo
                    $status_class = 'status-ok';
                    if ($hours_passed >= 48) {
                        $status_class = 'status-danger';
                    } elseif ($hours_passed >= 24) {
                        $status_class = 'status-warning';
                    }

                    // Obtener método de envío de forma segura
                    $shipping_methods = $order->get_shipping_methods();
                    $shipping_method  = !empty($shipping_methods) ? \reset($shipping_methods)->get_name() : 'Sin envío asignado';

?>
                    <li class="widget-list-item <?php echo \esc_attr($status_class); ?>">
                        <div class="widget-item-title">
                            🧾 Pedido #<?php echo \esc_html($order->get_order_number()); ?> – <?php echo \esc_html($order->get_formatted_billing_full_name()); ?>
                        </div>
                        <div class="widget-item-details">
                            🚚 Envío: <?php echo \esc_html($shipping_method); ?>
                        </div>
                        <div class="widget-item-details">
                            🕒 En preparación desde <?php echo \esc_html($preparation_date->format('d/m H:i')); ?> — ⏱️ <?php echo \esc_html($hours_passed); ?> h hábiles
                        </div>
                        <div class="widget-item-actions">
                            <a href="<?php echo \esc_url(\admin_url('post.php?post=' . $id . '&action=edit')); ?>" target="_blank" class="widget-item-link">🔍 Ver pedido</a>
                        </div>
                    </li>
<?php
                }
                echo '</ul>';
            }

            // Capturar HTML
            $html_content = \ob_get_clean();

            // Guardar en Transient por 1 hora (3600 segundos)
            \set_transient(self::TRANSIENT_KEY, $html_content, 3600);

            // Imprimir
            echo $html_content; // PHPCS: ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        } catch (\Exception $e) {
            \ob_end_clean();
            echo '<p class="error">Error al cargar el widget: ' . \esc_html($e->getMessage()) . '</p>';
        }

        $this->render_refresh_button();
    }

    /**
     * Renderiza un botón pequeño para limpiar la caché manualmente.
     */
    private function render_refresh_button()
    {
        $refresh_url = \add_query_arg([
            'gdos_clear_cache' => '1',
            '_wpnonce'         => \wp_create_nonce('gdos_clear_widget_cache')
        ]);

        echo '<div style="text-align:right; margin-top:10px; font-size:11px;">';
        echo '<a href="' . \esc_url($refresh_url) . '" style="text-decoration:none; color:#666;">🔄 Actualizar datos</a>';
        echo '<br><span style="color:#aaa;">(Caché: 1h)</span>';
        echo '</div>';
    }

    /**
     * Calcula horas hábiles evitando bucles infinitos.
     */
    private function calculate_business_hours(\DateTime $start, \DateTime $end): int
    {
        if ($start >= $end) return 0;

        $total = 0;
        $current = clone $start;

        // Seguridad: Limitar bucle para evitar crash
        $safety_limit = 1000;
        $loops = 0;

        while ($current < $end) {
            $loops++;
            if ($loops > $safety_limit) break;

            // N: 1 (Lunes) a 7 (Domingo). Queremos < 6 (Lunes a Viernes)
            if ((int)$current->format('N') < 6) {
                $total++;
            }
            $current->modify('+1 hour');
        }

        return $total;
    }
}
