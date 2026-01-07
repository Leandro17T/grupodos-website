<?php
// REFACTORIZADO: 2025-12-06 (CORREGIDO: Lectura de key 'exclude_product_categories')
// /modules/gestion-cupones/views/form-view.php

if (! \defined('ABSPATH')) {
    exit;
}

// 1. Verificación inicial de contexto
$is_edit = isset($edit_id) && $edit_id > 0;
$post    = $is_edit ? \get_post($edit_id) : null;

if ($is_edit && (! $post || $post->post_type !== 'shop_coupon')) {
    echo '<div class="notice notice-error"><p>' . \esc_html__('El cupón solicitado no existe o ha sido eliminado.', 'gdos-core') . '</p></div>';
    return;
}

// 2. Preparación de variables (Separación de lógica)
$code      = $is_edit ? $post->post_title : '';
$type      = \get_post_meta($edit_id, 'discount_type', true) ?: 'percent';
$amount    = \get_post_meta($edit_id, 'coupon_amount', true);
$min_spend = \get_post_meta($edit_id, 'minimum_amount', true);
$max_disc  = \get_post_meta($edit_id, '_gdos_max_discount', true);

// Fechas
$date_from_meta = \get_post_meta($edit_id, '_gdos_date_from', true);
$date_from      = $date_from_meta ? \esc_attr($date_from_meta) : '';

$expires_ts = \get_post_meta($edit_id, 'date_expires', true);
$date_end   = $expires_ts ? \date('Y-m-d', (int) $expires_ts) : '';

// Límites
$usage_limit = \get_post_meta($edit_id, 'usage_limit', true);
$usage_per_u = \get_post_meta($edit_id, 'usage_limit_per_user', true) ?: '1';
$email_rest  = \implode(',', (array) \get_post_meta($edit_id, 'customer_email', true));

// Helpers para procesar Arrays y Selects
$split_and_clean = function ($str) {
    return \array_filter(\array_map('trim', \explode(',', (string) $str)));
};

// Productos (IDs)
$inc_pids = \array_filter(\array_map('absint', \explode(',', (string) \get_post_meta($edit_id, 'product_ids', true))));
$exc_pids = \array_filter(\array_map('absint', \explode(',', (string) \get_post_meta($edit_id, 'exclude_product_ids', true))));

// Categorías (Slugs)
$inc_cat_slugs = $split_and_clean($this->term_ids_to_slugs((array) \get_post_meta($edit_id, 'product_categories', true), 'product_cat'));
// CORREGIDO: Leemos la key nativa 'exclude_product_categories'
$exc_cat_slugs = $split_and_clean($this->term_ids_to_slugs((array) \get_post_meta($edit_id, 'exclude_product_categories', true), 'product_cat'));

// Marcas (Slugs)
$inc_brand_slugs = $split_and_clean(\get_post_meta($edit_id, '_gdos_include_brands', true));
$exc_brand_slugs = $split_and_clean(\get_post_meta($edit_id, '_gdos_exclude_brands', true));

// Referencia a constante de clase
$brand_tax = $this::BRAND_TAXONOMY;

?>

<h2><?php echo $is_edit ? \esc_html__('Editar cupón', 'gdos-core') : \esc_html__('Crear cupón', 'gdos-core'); ?></h2>

<form method="post">
    <?php \wp_nonce_field('gdos_save_coupon', 'gdos_coupon_nonce'); ?>
    <input type="hidden" name="edit_id" value="<?php echo (int) $edit_id; ?>" />

    <table class="form-table">
        <tr>
            <th><label for="gdos_code"><?php \esc_html_e('Código del cupón', 'gdos-core'); ?></label></th>
            <td>
                <input type="text" name="code" id="gdos_code" value="<?php echo \esc_attr($code); ?>" required <?php echo $is_edit ? 'readonly' : ''; ?> class="regular-text">
                <?php if (! $is_edit): ?>
                    <button type="button" class="button" id="gdos-gen-code"><?php \esc_html_e('Generar Aleatorio', 'gdos-core'); ?></button>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Tipo de descuento', 'gdos-core'); ?></label></th>
            <td>
                <select name="discount_type">
                    <option value="percent" <?php \selected($type, 'percent'); ?>><?php \esc_html_e('Porcentaje (%)', 'gdos-core'); ?></option>
                    <option value="fixed_cart" <?php \selected($type, 'fixed_cart'); ?>><?php \esc_html_e('Monto fijo ($) – Carrito', 'gdos-core'); ?></option>
                    <option value="fixed_product" <?php \selected($type, 'fixed_product'); ?>><?php \esc_html_e('Monto fijo ($) – Producto', 'gdos-core'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Importe del cupón', 'gdos-core'); ?></label></th>
            <td><input type="number" name="amount" step="any" value="<?php echo \esc_attr($amount); ?>" required class="small-text"></td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Fecha de inicio', 'gdos-core'); ?></label></th>
            <td><input type="date" name="date_start" value="<?php echo \esc_attr($date_from); ?>"></td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Fecha de fin (Expiración)', 'gdos-core'); ?></label></th>
            <td><input type="date" name="date_end" value="<?php echo \esc_attr($date_end); ?>"></td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Gasto mínimo', 'gdos-core'); ?></label></th>
            <td><input type="number" name="min_spend" step="any" value="<?php echo \esc_attr($min_spend); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Tope de reintegro ($)', 'gdos-core'); ?></label></th>
            <td>
                <input type="number" name="max_discount" step="any" value="<?php echo \esc_attr($max_disc); ?>" placeholder="500" class="small-text">
                <p class="description"><?php \esc_html_e('Máximo descuento total permitido si el tipo es porcentual.', 'gdos-core'); ?></p>
            </td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Incluir productos', 'gdos-core'); ?></label></th>
            <td>
                <select class="wc-product-search" multiple="multiple" style="width:50%;" name="include_products_ids[]" data-placeholder="<?php \esc_attr_e('Buscar productos...', 'gdos-core'); ?>">
                    <?php
                    if (! empty($inc_pids)) {
                        foreach ($inc_pids as $pid) {
                            $prod = \wc_get_product($pid);
                            if ($prod) {
                                echo '<option value="' . \esc_attr($pid) . '" selected>' . \esc_html($prod->get_formatted_name()) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Excluir productos', 'gdos-core'); ?></label></th>
            <td>
                <select class="wc-product-search" multiple="multiple" style="width:50%;" name="exclude_products_ids[]" data-placeholder="<?php \esc_attr_e('Buscar productos...', 'gdos-core'); ?>">
                    <?php
                    if (! empty($exc_pids)) {
                        foreach ($exc_pids as $pid) {
                            $prod = \wc_get_product($pid);
                            if ($prod) {
                                echo '<option value="' . \esc_attr($pid) . '" selected>' . \esc_html($prod->get_formatted_name()) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Incluir categorías', 'gdos-core'); ?></label></th>
            <td>
                <select class="gdos-term-search" multiple="multiple" name="include_categories_slugs[]" data-placeholder="<?php \esc_attr_e('Buscar categorías...', 'gdos-core'); ?>" data-taxonomy="product_cat">
                    <?php
                    if (! empty($inc_cat_slugs)) {
                        $terms = \get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'slug' => $inc_cat_slugs]);
                        if (! \is_wp_error($terms)) {
                            foreach ($terms as $t) {
                                echo '<option value="' . \esc_attr($t->slug) . '" selected>' . \esc_html($t->name) . ' (' . \esc_html($t->slug) . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Excluir categorías', 'gdos-core'); ?></label></th>
            <td>
                <select class="gdos-term-search" multiple="multiple" name="exclude_categories_slugs[]" data-placeholder="<?php \esc_attr_e('Buscar categorías...', 'gdos-core'); ?>" data-taxonomy="product_cat">
                    <?php
                    if (! empty($exc_cat_slugs)) {
                        $terms = \get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'slug' => $exc_cat_slugs]);
                        if (! \is_wp_error($terms)) {
                            foreach ($terms as $t) {
                                echo '<option value="' . \esc_attr($t->slug) . '" selected>' . \esc_html($t->name) . ' (' . \esc_html($t->slug) . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Incluir marcas', 'gdos-core'); ?></label></th>
            <td>
                <select class="gdos-term-search" multiple="multiple" name="include_brands_slugs[]" data-placeholder="<?php \esc_attr_e('Buscar marcas...', 'gdos-core'); ?>" data-taxonomy="<?php echo \esc_attr($brand_tax); ?>">
                    <?php
                    if (! empty($inc_brand_slugs)) {
                        $terms = \get_terms(['taxonomy' => $brand_tax, 'hide_empty' => false, 'slug' => $inc_brand_slugs]);
                        if (! \is_wp_error($terms)) {
                            foreach ($terms as $t) {
                                echo '<option value="' . \esc_attr($t->slug) . '" selected>' . \esc_html($t->name) . ' (' . \esc_html($t->slug) . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Excluir marcas', 'gdos-core'); ?></label></th>
            <td>
                <select class="gdos-term-search" multiple="multiple" name="exclude_brands_slugs[]" data-placeholder="<?php \esc_attr_e('Buscar marcas...', 'gdos-core'); ?>" data-taxonomy="<?php echo \esc_attr($brand_tax); ?>">
                    <?php
                    if (! empty($exc_brand_slugs)) {
                        $terms = \get_terms(['taxonomy' => $brand_tax, 'hide_empty' => false, 'slug' => $exc_brand_slugs]);
                        if (! \is_wp_error($terms)) {
                            foreach ($terms as $t) {
                                echo '<option value="' . \esc_attr($t->slug) . '" selected>' . \esc_html($t->name) . ' (' . \esc_html($t->slug) . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th><label><?php \esc_html_e('Restricción de correo', 'gdos-core'); ?></label></th>
            <td>
                <input type="text" name="email_restriction" value="<?php echo \esc_attr($email_rest); ?>" class="regular-text" placeholder="ej: usuario@email.com, otro@email.com">
            </td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Límite de usos totales', 'gdos-core'); ?></label></th>
            <td><input type="number" name="usage_limit" value="<?php echo \esc_attr($usage_limit); ?>" placeholder="∞" class="small-text"></td>
        </tr>
        <tr>
            <th><label><?php \esc_html_e('Límite por usuario', 'gdos-core'); ?></label></th>
            <td><input type="number" name="usage_limit_per_user" value="<?php echo \esc_attr($usage_per_u); ?>" class="small-text"></td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" class="button button-primary"><?php echo $is_edit ? \esc_html__('Actualizar Cupón', 'gdos-core') : \esc_html__('Guardar Cupón', 'gdos-core'); ?></button>
        <a class="button" href="<?php echo \esc_url(\admin_url('admin.php?page=gdos-coupons')); ?>"><?php \esc_html_e('Cancelar', 'gdos-core'); ?></a>

        <?php if ($is_edit):
            $del_url = \wp_nonce_url(\admin_url('admin.php?page=gdos-coupons&action=delete&id=' . $edit_id), 'gdos_delete_coupon_' . $edit_id);
        ?>
            <a class="button button-link-delete" style="color:#b32d2e; margin-left:8px;" href="<?php echo \esc_url($del_url); ?>" onclick="return confirm('<?php \esc_attr_e('¿Seguro que deseas eliminar este cupón permanentemente?', 'gdos-core'); ?>');">
                <?php \esc_html_e('Eliminar', 'gdos-core'); ?>
            </a>
        <?php endif; ?>
    </p>
</form>

<?php if (! $is_edit): ?>
    <script type="text/javascript">
        (function() {
            const btn = document.getElementById('gdos-gen-code');
            if (!btn) return;
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Previene submit accidental
                const part = () => Math.random().toString(36).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4);
                const code = `G2-${part()}${part()}`;
                const input = document.getElementById('gdos_code');
                if (input && !input.readOnly) {
                    input.value = code;
                    input.focus();
                }
            });
        })();
    </script>
<?php endif; ?>