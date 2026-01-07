<?php
if ( ! defined( 'WFACP_TEMPLATE_DIR' ) ) {
	return '';
}
$checkout            = WC()->checkout();
$template            = wfacp_template();
$cart_collapse_title = $template->get_mobile_mini_cart_collapsible_title();
$cart_expanded_title = $template->get_mobile_mini_cart_expand_title();

$class_added = '';
if ( $cart_collapse_title == '' || $cart_expanded_title == '' ) {
	$class_added = 'wfacp_no_title';
}

$order_summary_cart_price = apply_filters( 'wfacp_collapsible_order_summary_cart_price', wc_price( WC()->cart->total ) );

$label_position = '';
$mini_cart_classes=['wfacp_anim','wfacp_order_summary_container','wfacp_mb_mini_cart_wrap'];
if ( isset( $this->customizer_fields_data['wfacp_form']['form_data']['label_position'] ) ) {
	$mini_cart_classes[] = $this->customizer_fields_data['wfacp_form']['form_data']['label_position'];
}

?>

<div class="<?php echo implode(' ',$mini_cart_classes); ?>">
    <div class="wfacp_mb_cart_accordian clearfix" attr-collaps="<?php echo $cart_collapse_title; ?>" attr-expend="<?php echo $cart_expanded_title; ?>">
        <div class="wfacp_show_icon_wrap <?php echo $class_added; ?>">
            <a href="javascript:void(0)" class="wfacp_summary_link">
                <span><?php echo $cart_collapse_title; ?></span>
                <img src="<?php echo apply_filters( 'wfacp_collapsible_order_summary_dropdown_icon', WFACP_PLUGIN_URL . '/assets/img/down-arrow.svg' ); ?>" alt="">
            </a>
        </div>
        <div class="wfacp_show_price_wrap">
            <div class="wfacp_cart_mb_fragment_price">
                <span><?php echo $order_summary_cart_price; ?></span>
            </div>
        </div>
    </div>
    <div class="wfacp_mb_mini_cart_sec_accordion_content wfacp_display_none">
		<?php
		do_action( 'wfacp_before_sidebar_content' ); ?>
    </div>
</div>
