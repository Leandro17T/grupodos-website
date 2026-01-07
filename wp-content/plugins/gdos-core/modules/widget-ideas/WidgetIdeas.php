<?php
// REFACTORIZADO: 2025-05-21
// /modules/widget-ideas/WidgetIdeas.php

namespace GDOS\Modules\WidgetIdeas;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class WidgetIdeas implements ModuleInterface
{
    const META_KEY     = '_gdos_ideas_checklist';
    const NONCE_ACTION = 'gdos_ideas_nonce';

    public function boot(): void
    {
        // Solo cargar en el admin
        if (! \is_admin()) return;

        \add_action('wp_dashboard_setup', [$this, 'register_dashboard_widget']);

        // AJAX: Solo necesitamos guardar. La carga inicial se hace por preloading (WPO).
        \add_action('wp_ajax_gdos_guardar_notas', [$this, 'ajax_save_notes']);
    }

    public function register_dashboard_widget(): void
    {
        \wp_add_dashboard_widget(
            'gdos_widget_ideas_checklist',
            '💡 Ideas a implementar',
            [$this, 'render_widget_content']
        );

        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook): void
    {
        // Carga condicional estricta: Solo en el dashboard (index.php)
        if ($hook !== 'index.php') return;

        $css = Assets::get('assets/css/admin.css', __FILE__);
        \wp_enqueue_style('gdos-widget-ideas-css', $css['url'], [], $css['ver']);

        $js = Assets::get('assets/js/admin.js', __FILE__);
        \wp_enqueue_script('gdos-widget-ideas-js', $js['url'], ['jquery'], $js['ver'], true);

        // WPO: Pre-cargamos las notas aquí para evitar una petición AJAX extra al cargar la página.
        $saved_notes = \get_user_meta(\get_current_user_id(), self::META_KEY, true);
        $initial_notes = \is_array($saved_notes) ? $saved_notes : [];

        \wp_localize_script('gdos-widget-ideas-js', 'gdosIdeasWidget', [
            'nonce'    => \wp_create_nonce(self::NONCE_ACTION),
            'ajax_url' => \admin_url('admin-ajax.php'),
            'notes'    => $initial_notes, // Datos precargados
            'i18n'     => [
                'empty'   => 'Empieza a escribir tus ideas...',
                'saved'   => 'Guardado correctamente',
                'error'   => 'Error al guardar',
                'loading' => 'Guardando...'
            ]
        ]);
    }

    public function render_widget_content(): void
    {
        // WPO: Renderizado híbrido.
        // El HTML inicial está limpio, JS poblará la lista usando los datos de 'gdosIdeasWidget.notes'.
?>
        <div id="gdos-notas-wrapper" class="gdos-widget-wrapper">
            <label for="gdos-nota-texto" class="screen-reader-text">Nueva idea</label>
            <textarea id="gdos-nota-texto" rows="3" class="widefat" placeholder="Escribí tu idea o lista de tareas..."></textarea>

            <div class="gdos-actions-row" style="margin-top: 10px; display:flex; justify-content:space-between; align-items:center;">
                <button id="gdos-agregar-nota" class="button button-primary">Agregar idea</button>
                <span id="gdos-status-msg" style="font-size:12px; color:#666; font-style:italic;"></span>
            </div>

            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #f0f0f1;">

            <ul id="gdos-lista-notas" class="gdos-checklist">
            </ul>
        </div>
<?php
    }

    /**
     * Guarda las notas vía AJAX.
     * Incluye sanitización estricta de array.
     */
    public function ajax_save_notes(): void
    {
        \check_ajax_referer(self::NONCE_ACTION, 'nonce');

        // Permisos: Solo quien puede editar posts debería usar esto (o ajusta a 'read' si es para suscriptores)
        if (! \current_user_can('edit_posts')) {
            \wp_send_json_error('No tienes permisos para realizar esta acción.');
        }

        // Sanitización de entrada
        $raw_input = isset($_POST['notas']) ? \wp_unslash($_POST['notas']) : '[]'; // isset nativo sin barra
        $notes     = \json_decode($raw_input, true);

        if (! \is_array($notes)) {
            $notes = [];
        }

        // SEGURIDAD: Sanitizar cada string dentro del array antes de guardar
        $clean_notes = \array_map('sanitize_text_field', $notes);

        // Guardar en User Meta (No transient, es dato persistente por usuario)
        $updated = \update_user_meta(\get_current_user_id(), self::META_KEY, $clean_notes);

        if ($updated || $updated === false) { // false significa que el valor era el mismo, lo cual es éxito
            \wp_send_json_success(['message' => 'Notas guardadas.', 'count' => \count($clean_notes)]);
        } else {
            \wp_send_json_error('Error al actualizar la base de datos.');
        }
    }
}
