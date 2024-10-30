<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-error gf-billingo-notice gf-billingo-welcome">
	<div class="gf-billingo-welcome-body">
    <button type="button" class="notice-dismiss gf-billingo-hide-notice" data-nonce="<?php echo esc_attr(wp_create_nonce( 'gf-billingo-hide-notice' )); ?>" data-notice="error"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss' ); ?></span></button>
		<h2><?php esc_html_e('Invoice generation failed', 'integration-for-billingo-gravity-forms'); ?></h2>
		<p><?php printf( esc_html__( 'The invoice for order %s could not be created automatically for some reason. You will see the exact error in the order notes.', 'integration-for-billingo-gravity-forms' ), esc_html($order_number) ); ?></p>
		<p>
			<a class="button-secondary" href="<?php echo esc_url($order_link); ?>"><?php esc_html_e( 'Order details', 'integration-for-billingo-gravity-forms' ); ?></a>
		</p>
	</div>
</div>
