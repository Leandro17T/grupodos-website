<?php
// REFACTORIZADO: 2025-05-21 (Fix: Botón Styles & Subtítulo Color)
// /modules/modal-widget/includes/Widget_Modal.php

namespace GDOS\Modules\ModalWidget\Includes;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if (! \defined('ABSPATH')) exit;

class Widget_Modal extends Widget_Base
{
    public function get_name()
    {
        return 'gdos_modal';
    }
    public function get_title()
    {
        return \esc_html__('Cupones Minimal Tech', 'gdos-core');
    }
    public function get_icon()
    {
        return 'eicon-code';
    }
    public function get_categories()
    {
        return ['grupodos', 'general'];
    }
    public function get_style_depends()
    {
        return ['gdos-modal-widget-css'];
    }
    public function get_script_depends()
    {
        return ['gdos-modal-widget-js'];
    }

    protected function register_controls()
    {
        // ====================================================
        // 1. CONTENIDO
        // ====================================================
        $this->start_controls_section('section_layout', [
            'label' => \esc_html__('Configuración General', 'gdos-core'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'   => \esc_html__('Columnas', 'gdos-core'),
            'type'    => Controls_Manager::SELECT,
            'default' => '1',
            'options' => [
                '1' => \esc_html__('1 Columna (Lista)', 'gdos-core'),
                '2' => \esc_html__('2 Columnas (Grid)', 'gdos-core'),
                '3' => \esc_html__('3 Columnas (Grid)', 'gdos-core'),
            ],
            'selectors' => ['{{WRAPPER}} .gdos-coupon-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);'],
        ]);

        $this->add_responsive_control('gap', [
            'label'     => \esc_html__('Separación', 'gdos-core'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 50]],
            'default'   => ['unit' => 'px', 'size' => 20],
            'selectors' => ['{{WRAPPER}} .gdos-coupon-grid' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_coupons', [
            'label' => \esc_html__('Mis Cupones', 'gdos-core'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $rep = new Repeater();
        $rep->add_control('coupon_badge', ['label' => \esc_html__('Dato Clave', 'gdos-core'), 'type' => Controls_Manager::TEXT, 'default' => '15%', 'label_block' => true]);
        $rep->add_control('coupon_label', ['label' => \esc_html__('Título', 'gdos-core'), 'type' => Controls_Manager::TEXT, 'default' => \esc_html__('Descuento Santander', 'gdos-core'), 'label_block' => true]);
        $rep->add_control('coupon_desc', ['label' => \esc_html__('Descripción', 'gdos-core'), 'type' => Controls_Manager::TEXT, 'default' => \esc_html__('Tope de reintegro $2000', 'gdos-core')]);
        $rep->add_control('coupon_code', ['label' => \esc_html__('Código', 'gdos-core'), 'type' => Controls_Manager::TEXT, 'default' => 'SANTANDER15']);

        $rep->add_control('style_type', [
            'label'   => \esc_html__('Estilo Base', 'gdos-core'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'violet',
            'options' => ['violet' => 'Neon Violeta', 'gold' => 'Neon Dorado', 'white' => 'Minimal Blanco'],
            'description' => 'Selecciona el esquema de colores base. Puedes personalizarlo en la pestaña Estilo.',
        ]);

        $this->add_control('coupons', [
            'label'   => \esc_html__('Lista de Cupones', 'gdos-core'),
            'type'    => Controls_Manager::REPEATER,
            'fields'  => $rep->get_controls(),
            'default' => [
                ['coupon_badge' => '15%', 'coupon_label' => 'Descuento Santander', 'coupon_code' => 'SANTANDER15', 'style_type' => 'violet'],
            ],
            'title_field' => '{{{ coupon_label }}}',
        ]);

        $this->end_controls_section();

        // ====================================================
        // 2. ESTILO: TARJETA
        // ====================================================
        $this->start_controls_section('style_card_section', [
            'label' => \esc_html__('Tarjeta y Contenido', 'gdos-core'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_bg_color', [
            'label'     => \esc_html__('Color de Fondo', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon' => 'background-color: {{VALUE}}; --gdos-c-bg: {{VALUE}};'],
        ]);

        $this->add_control('card_border_color', [
            'label'     => \esc_html__('Color de Borde', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon' => 'border-color: {{VALUE}}; --gdos-c-border: {{VALUE}};'],
        ]);

        $this->add_control('heading_texts', [
            'label' => \esc_html__('Textos', 'gdos-core'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('card_accent_color', [
            'label'     => \esc_html__('Color Dato Clave (Izquierda)', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__value' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('card_title_color', [
            'label'     => \esc_html__('Color Título', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__title' => 'color: {{VALUE}};'],
        ]);

        // --- NUEVO: Control para la descripción ---
        $this->add_control('card_desc_color', [
            'label'     => \esc_html__('Color Descripción', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__desc' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'desc_typography',
                'label' => 'Tipografía Descripción',
                'selector' => '{{WRAPPER}} .gdos-coupon__desc',
            ]
        );

        $this->end_controls_section();

        // ====================================================
        // 3. ESTILO: BOTÓN COPIAR
        // ====================================================
        $this->start_controls_section('style_btn_section', [
            'label' => \esc_html__('Botón Copiar', 'gdos-core'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('tabs_button_style');

        // --- NORMAL ---
        $this->start_controls_tab('tab_button_normal', ['label' => \esc_html__('Normal', 'gdos-core')]);

        $this->add_control('btn_text_color', [
            'label'     => \esc_html__('Color de Texto', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            // Cambio: Aplicar directamente al botón para asegurar prioridad
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('btn_bg_color', [
            'label'     => \esc_html__('Color de Fondo', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('btn_border_color', [
            'label'     => \esc_html__('Color de Borde', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_tab();

        // --- HOVER ---
        $this->start_controls_tab('tab_button_hover', ['label' => \esc_html__('Al pasar el cursor', 'gdos-core')]);

        $this->add_control('btn_hover_text_color', [
            'label'     => \esc_html__('Color de Texto', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('btn_hover_bg_color', [
            'label'     => \esc_html__('Color de Fondo', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn:hover' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('btn_hover_border_color', [
            'label'     => \esc_html__('Color de Borde', 'gdos-core'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .gdos-coupon__btn:hover' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render()
    {
        \wp_enqueue_style('gdos-modal-widget-css');
        \wp_enqueue_script('gdos-modal-widget-js');

        $settings = $this->get_settings_for_display();
        $coupons  = $settings['coupons'] ?? [];

        if (empty($coupons)) return;

?>
        <div class="gdos-coupon-grid">
            <?php foreach ($coupons as $c):
                $badge = $c['coupon_badge'] ?? '';
                $label = $c['coupon_label'] ?? '';
                $desc  = $c['coupon_desc']  ?? '';
                $code  = $c['coupon_code']  ?? '';
                $style = $c['style_type']   ?? 'violet';

                // Validación básica de estilo
                $valid_styles = ['violet', 'gold', 'white'];
                if (!\in_array($style, $valid_styles, true)) $style = 'violet';

                $wrapper_class = 'gdos-coupon gdos-coupon--' . $style;
            ?>

                <div class="<?php echo \esc_attr($wrapper_class); ?>">
                    <div class="gdos-coupon__left">
                        <span class="gdos-coupon__value"><?php echo \esc_html($badge); ?></span>
                    </div>

                    <div class="gdos-coupon__divider">
                        <div class="gdos-coupon__notch gdos-coupon__notch--top"></div>
                        <div class="gdos-coupon__notch gdos-coupon__notch--bottom"></div>
                    </div>

                    <div class="gdos-coupon__center">
                        <h4 class="gdos-coupon__title"><?php echo \esc_html($label); ?></h4>
                        <?php if ($desc): ?>
                            <p class="gdos-coupon__desc"><?php echo \esc_html($desc); ?></p>
                        <?php endif; ?>
                        <input type="hidden" class="gdos-coupon__hidden-code" value="<?php echo \esc_attr($code); ?>" readonly>
                    </div>

                    <div class="gdos-coupon__right">
                        <button class="gdos-coupon__btn" type="button" aria-label="<?php echo \esc_attr__('Copiar código del cupón', 'gdos-core'); ?>">
                            <span class="gdos-coupon__btn-icon" aria-hidden="true"><i class="far fa-copy"></i></span>
                            <span class="gdos-coupon__btn-text"><?php \esc_html_e('COPIAR', 'gdos-core'); ?></span>
                        </button>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
<?php
    }
}
