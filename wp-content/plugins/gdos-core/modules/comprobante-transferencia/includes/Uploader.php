<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/includes/Uploader.php

namespace GDOS\Modules\ComprobanteTransferencia\includes;

use GDOS\Modules\ComprobanteTransferencia\ComprobanteTransferencia as Config;

if (! \defined('ABSPATH')) {
    exit;
}

class Uploader
{

    public function __construct()
    {
        // Hooks de subida
        \add_action('admin_post_' . Config::UPLOAD_ACTION, [$this, 'handle_upload']);
        \add_action('admin_post_nopriv_' . Config::UPLOAD_ACTION, [$this, 'handle_upload']);

        // Hooks de descarga segura
        \add_action('admin_post_' . Config::DOWNLOAD_ACTION, [$this, 'handle_download']);
        \add_action('admin_post_nopriv_' . Config::DOWNLOAD_ACTION, [$this, 'handle_download']);

        // Hooks de borrado
        \add_action('admin_post_gdos_delete_slip', [$this, 'handle_delete']);
        \add_action('admin_post_nopriv_gdos_delete_slip', [$this, 'handle_delete']);
    }

    /**
     * Procesa la subida del archivo.
     */
    public function handle_upload(): void
    {
        // 1. Verificación de Nonce y Datos
        if (! isset($_POST['order_id'], $_POST['order_key']) || ! \check_admin_referer(Config::UPLOAD_ACTION, Config::UPLOAD_NONCE)) {
            \wp_die('Solicitud de seguridad inválida.');
        }

        $order_id = \absint($_POST['order_id']);
        $order    = \wc_get_order($order_id);

        // 2. Validación de Orden
        if (! $order || $order->get_order_key() !== \sanitize_text_field($_POST['order_key']) || $order->get_payment_method() !== Config::TRANSFER_GATEWAY_ID) {
            \wp_die('Pedido no válido o acceso denegado.');
        }

        // Si ya existe comprobante, no sobreescribir (redireccionar)
        if ($order->get_meta(Config::SLIP_META_ID)) {
            \wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        // 3. Validación de Archivo
        if (! isset($_FILES['gdos_slip']) || ! empty($_FILES['gdos_slip']['error'])) {
            \wp_die('Error en la transmisión del archivo.');
        }

        if ($_FILES['gdos_slip']['size'] > Config::MAX_MB * 1024 * 1024) {
            \wp_die(\sprintf('El archivo supera el tamaño máximo permitido de %d MB.', Config::MAX_MB));
        }

        $file = $_FILES['gdos_slip'];
        $validation = $this->validate_file_type($file);

        if (! $validation) {
            \wp_die('Tipo de archivo no permitido o corrupto. Solo se permiten PDF, JPG, PNG o WebP reales.');
        }

        // 4. Carga de Librerías WP
        if (! \function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // 5. Preparación del nombre
        $prefilter = function ($f) use ($validation, $order) {
            // Renombramos para seguridad y orden: "pedido-1234-comprobante.ext"
            $ext = $validation['ext'];
            $f['name'] = \sprintf('pedido-%s-comprobante-%s.%s', $order->get_order_number(), \wp_generate_password(6, false), $ext);
            $f['type'] = $validation['mime'];
            return $f;
        };

        \add_filter('wp_handle_upload_prefilter', $prefilter);
        \add_filter('upload_mimes', [$this, 'filter_allowed_mimes']);

        // 6. Subida Física
        $movefile = \wp_handle_upload($file, [
            'test_form' => false,
            'unique_filename_callback' => fn($dir, $name, $ext) => \basename($name)
        ]);

        // Limpieza de filtros
        \remove_filter('upload_mimes', [$this, 'filter_allowed_mimes']);
        \remove_filter('wp_handle_upload_prefilter', $prefilter);

        if (empty($movefile['file'])) {
            \wp_die('Error al guardar el archivo: ' . \esc_html($movefile['error'] ?? 'Desconocido'));
        }

        // 7. Registro en Medios (Attachment)
        $attach_id = \wp_insert_attachment([
            'post_mime_type' => $movefile['type'],
            'post_title'     => \basename($movefile['file']),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ], $movefile['file']);

        // Generar metadatos (thumbnails si es imagen)
        $attach_data = \wp_generate_attachment_metadata($attach_id, $movefile['file']);
        \wp_update_attachment_metadata($attach_id, $attach_data);

        // 8. Asociación a la Orden (HPOS Compatible)
        $order->update_meta_data(Config::SLIP_META_ID, $attach_id);
        $order->update_meta_data(Config::SLIP_META_URL, \wp_get_attachment_url($attach_id));
        $order->update_meta_data(Config::SLIP_META_DT, \current_time('Y-m-d H:i'));
        $order->save(); // Importante guardar en HPOS

        // Nota y Email
        $order->add_order_note(\sprintf('El cliente subió el comprobante de transferencia. Adjunto ID: %d', $attach_id));
        $this->send_admin_email($order, $attach_id);

        \wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * Procesa el borrado del comprobante por parte del cliente.
     */
    public function handle_delete(): void
    {
        $order_id  = \absint($_GET['order_id'] ?? 0);
        $order_key = \sanitize_text_field($_GET['order_key'] ?? '');
        $nonce     = \sanitize_text_field($_GET['gdos_nonce'] ?? '');

        if (! \wp_verify_nonce($nonce, 'gdos_delete_slip_' . $order_id)) {
            \wp_die('Enlace de seguridad caducado. Intenta recargar la página.');
        }

        $order = \wc_get_order($order_id);
        if (! $order || $order->get_order_key() !== $order_key) {
            \wp_die('Pedido no encontrado.');
        }

        // 1. Obtener ID del adjunto
        $att_id = (int) $order->get_meta(Config::SLIP_META_ID);

        // 2. Borrar archivo físico y registro de WP
        if ($att_id) {
            \wp_delete_attachment($att_id, true); // true = forzar borrado sin papelera
        }

        // 3. Borrar metadatos del pedido (HPOS)
        $order->delete_meta_data(Config::SLIP_META_ID);
        $order->delete_meta_data(Config::SLIP_META_URL);
        $order->delete_meta_data(Config::SLIP_META_DT);
        $order->save();

        // 4. Nota en el pedido
        $order->add_order_note('El cliente eliminó el comprobante para subir uno nuevo.');

        // 5. Redirigir
        \wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    public function handle_download(): void
    {
        $order_id  = \absint($_GET['order_id'] ?? 0);
        $order_key = \sanitize_text_field($_GET['order_key'] ?? '');
        $nonce     = \sanitize_text_field($_GET['gdos_nonce'] ?? '');

        $action_hash = Config::DOWNLOAD_NONCE_ACTION . '|' . $order_id . '|' . $order_key;

        if (! $order_id || ! $order_key || ! $nonce || ! \wp_verify_nonce($nonce, $action_hash)) {
            \wp_die('Enlace de descarga inválido o caducado.');
        }

        $order = \wc_get_order($order_id);
        if (! $order || $order->get_order_key() !== $order_key) {
            \wp_die('Acceso denegado al pedido.');
        }

        $att_id = (int) $order->get_meta(Config::SLIP_META_ID);
        $path   = $att_id ? \get_attached_file($att_id) : false;

        if (! $path || ! \file_exists($path)) {
            \wp_die('El archivo físico no se encuentra en el servidor.');
        }

        // Descarga Segura (Stream)
        if (\ob_get_level()) {
            \ob_end_clean();
        }

        \nocache_headers();
        \header('Content-Type: ' . (\get_post_mime_type($att_id) ?: 'application/octet-stream'));
        \header('Content-Disposition: inline; filename="' . \basename($path) . '"');
        \header('Content-Length: ' . \filesize($path));
        \readfile($path);
        exit;
    }

    // --- Helpers de Validación de Archivos (Seguridad) ---

    private function validate_file_type($file): ?array
    {
        $tmp_path = $file['tmp_name'];
        $filename = $file['name'];

        // 1. PDF Magic Bytes (%PDF-)
        if (\stripos($filename, '.pdf') !== false) {
            if ($this->check_pdf_signature($tmp_path)) {
                return ['ext' => 'pdf', 'mime' => 'application/pdf'];
            }
        }

        // 2. Imágenes (exif_imagetype o getimagesize como fallback)
        $img_info = $this->detect_image_type($tmp_path);
        if ($img_info) {
            return $img_info;
        }

        return null;
    }

    private function check_pdf_signature($path): bool
    {
        if (! \is_readable($path)) return false;
        $fh = @\fopen($path, 'rb');
        if (! $fh) return false;

        $head = @\fread($fh, 5); // Leer primeros 5 bytes
        @\fclose($fh);

        return ($head === '%PDF-');
    }

    private function detect_image_type($path): ?array
    {
        if (! \is_readable($path)) return null;

        // Preferimos exif_imagetype por velocidad
        if (\function_exists('exif_imagetype')) {
            $type = @\exif_imagetype($path);
            $map  = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_GIF  => 'gif',
                18             => 'webp' // Constante IMAGETYPE_WEBP a veces no definida en PHP viejos
            ];

            if ($type && isset($map[$type])) {
                return ['ext' => $map[$type], 'mime' => \image_type_to_mime_type($type)];
            }
        }
        // Fallback a getimagesize
        elseif (\function_exists('getimagesize')) {
            $info = @\getimagesize($path);
            if ($info && isset($info['mime'])) {
                $mime = $info['mime'];
                $exts = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp'
                ];
                if (isset($exts[$mime])) {
                    return ['ext' => $exts[$mime], 'mime' => $mime];
                }
            }
        }

        return null;
    }

    public function filter_allowed_mimes($mimes)
    {
        // Solo permitimos estos tipos estrictamente durante la subida
        return [
            'pdf'      => 'application/pdf',
            'jpg|jpeg' => 'image/jpeg',
            'png'      => 'image/png',
            'webp'     => 'image/webp'
        ];
    }

    // --- Helpers URLs ---

    public function get_download_url(\WC_Order $order): string
    {
        $nonce_action = Config::DOWNLOAD_NONCE_ACTION . '|' . $order->get_id() . '|' . $order->get_order_key();

        $args = [
            'action'    => Config::DOWNLOAD_ACTION,
            'order_id'  => $order->get_id(),
            'order_key' => $order->get_order_key(),
            'gdos_nonce' => \wp_create_nonce($nonce_action)
        ];

        return \add_query_arg($args, \admin_url('admin-post.php'));
    }

    public function get_delete_url(\WC_Order $order): string
    {
        $nonce_action = 'gdos_delete_slip_' . $order->get_id();

        $args = [
            'action'    => 'gdos_delete_slip',
            'order_id'  => $order->get_id(),
            'order_key' => $order->get_order_key(),
            'gdos_nonce' => \wp_create_nonce($nonce_action)
        ];

        return \add_query_arg($args, \admin_url('admin-post.php'));
    }

    private function send_admin_email(\WC_Order $order, $attach_id)
    {
        $path = \get_attached_file($attach_id);

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if (Config::EMAIL_CC)  $headers[] = 'Cc: ' . Config::EMAIL_CC;
        if (Config::EMAIL_BCC) $headers[] = 'Bcc: ' . Config::EMAIL_BCC;

        \ob_start();
?>
        <div style="font-family: sans-serif; color: #333;">
            <h2 style="color: #0073aa;">Nuevo Comprobante de Transferencia</h2>
            <p>El cliente ha subido un comprobante para el <strong>Pedido #<?php echo \esc_html($order->get_order_number()); ?></strong>.</p>

            <table cellspacing="0" cellpadding="10" border="0" style="background: #f9f9f9; width: 100%; border: 1px solid #eee;">
                <tr>
                    <td width="30%"><strong>Cliente:</strong></td>
                    <td><?php echo \esc_html($order->get_formatted_billing_full_name()); ?> <br> <small>(<?php echo \esc_html($order->get_billing_email()); ?>)</small></td>
                </tr>
                <tr>
                    <td><strong>Total del Pedido:</strong></td>
                    <td><?php echo \wp_kses_post($order->get_formatted_order_total()); ?></td>
                </tr>
            </table>

            <p style="margin-top: 20px;">
                <a href="<?php echo \esc_url(\get_edit_post_link($order->get_id())); ?>" style="background: #0073aa; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Ver Pedido en Admin</a>
                &nbsp;
                <a href="<?php echo \esc_url($this->get_download_url($order)); ?>" style="background: #fff; color: #0073aa; border: 1px solid #0073aa; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Descargar Comprobante</a>
            </p>
        </div>
<?php
        $body = \ob_get_clean();

        $mailer = \WC()->mailer();
        $message = $mailer->wrap_message('Comprobante recibido', $body);
        $subject = \sprintf('Comprobante recibido - Pedido #%s', $order->get_order_number());

        // Adjuntamos el archivo físicamente al email para respaldo
        $attachments = ($path && \file_exists($path)) ? [$path] : [];

        $mailer->send(Config::EMAIL_TO, $subject, $message, $headers, $attachments);
    }
}
