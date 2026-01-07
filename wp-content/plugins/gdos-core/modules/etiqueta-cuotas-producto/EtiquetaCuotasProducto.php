<?php
// REFACTORIZADO: 2025-12-06
// /modules/etiqueta-cuotas-producto/EtiquetaCuotasProducto.php

namespace GDOS\Modules\EtiquetaCuotasProducto;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
	exit;
}

class EtiquetaCuotasProducto implements ModuleInterface
{

	public function boot(): void
	{
		// Manipulación de hooks de WooCommerce
		// Usamos priority 10 en 'init' para asegurar que WC ya cargó
		\add_action('init', [$this, 'move_product_title']);

		// Agrega la nueva etiqueta de 12 cuotas en el loop
		\add_action('woocommerce_shop_loop_item_title', [$this, 'show_12cuotas_label'], 10);

		// Carga de estilos condicional
		\add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
	}

	/**
	 * Muestra la etiqueta de 12 cuotas calculada dinámicamente.
	 */
	public function show_12cuotas_label(): void
	{
		global $product;

		// Validación estricta del objeto producto
		if (! $product instanceof \WC_Product) {
			$product = \wc_get_product(\get_the_ID());
		}

		if (! $product) {
			return;
		}

		// CORRECCIÓN FINANCIERA:
		// Usamos wc_get_price_to_display para calcular las cuotas sobre el precio FINAL (con impuestos si aplica).
		// get_price() a veces devuelve el precio sin impuestos dependiendo de la config de WC.
		$price = \wc_get_price_to_display($product);

		if (! $price) {
			return;
		}

		// Cálculo
		$installment = (float) $price / 12;

		// Formateo de moneda (maneja símbolo $, posición y decimales según config de WC)
		$formatted_installment = \wc_price($installment);

		// Construcción HTML segura
		$html = \sprintf(
			'<div class="etiqueta-cuotas"><span class="highlight">%s</span><br><span class="subline">%s %s</span></div>',
			\esc_html__('12 cuotas', 'gdos-core'),
			\esc_html__('sin recargo de', 'gdos-core'),
			$formatted_installment // wc_price devuelve HTML seguro
		);

		// Imprimimos permitiendo etiquetas HTML seguras (span, bdi, etc)
		echo \wp_kses_post($html);
	}

	/**
	 * Cambia la prioridad del título del producto en el loop.
	 * Retira el título de su posición original (10) y lo pone después (11)
	 * para que nuestras etiquetas (prioridad 10) salgan antes.
	 */
	public function move_product_title(): void
	{
		\remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
		\add_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 11);
	}

	/**
	 * Carga la hoja de estilos de forma optimizada y segura.
	 */
	public function enqueue_styles(): void
	{
		$load_styles = false;

		// Lógica Condicional Estricta
		if (\function_exists('is_woocommerce') && \is_woocommerce()) $load_styles = true;
		if (! $load_styles && \is_front_page()) $load_styles = true;
		if (! $load_styles && \is_page(67252)) $load_styles = true; // Landing específica

		// Detección de Shortcode en páginas no-WooCommerce
		global $post;
		if (! $load_styles && \is_a($post, 'WP_Post') && \has_shortcode($post->post_content, 'products')) {
			$load_styles = true;
		}

		if ($load_styles) {
			$css_rel  = 'assets/css/frontend.css';
			$css_path = \plugin_dir_path(__FILE__) . $css_rel;

			if (\file_exists($css_path)) {
				\wp_enqueue_style(
					'gdos-etiqueta-cuotas',
					\plugins_url($css_rel, __FILE__),
					[],
					\filemtime($css_path)
				);
			}
		}
	}
}
