<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info gf-billingo-notice gf-billingo-welcome">
	<div class="gf-billingo-welcome-body">
    <button type="button" class="notice-dismiss gf-billingo-hide-notice" data-nonce="<?php echo esc_attr(wp_create_nonce( 'gf-billingo-hide-notice' )); ?>" data-notice="welcome"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss' ); ?></span></button>
		<h2><?php esc_html_e('Gravity Forms + Billingo', 'integration-for-billingo-gravity-forms'); ?></h2>
		<p><?php esc_html_e('Thank you for installing this extension. Go to the settings page to specify your API key. To generate an invoice, go to your form settings and setup a Billingo feed, pairing your form fields with the invoice data.', 'integration-for-billingo-gravity-forms'); ?></p>
		<p><?php esc_html_e('In order to use every function, like automatic invoicing, language settings and more, you might need the PRO version.', 'integration-for-billingo-gravity-forms'); ?></p>
		<p>
			<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://visztpeter.me/"><?php esc_html_e( 'Buy the PRO version', 'integration-for-billingo-gravity-forms' ); ?></a>
			<a class="button-secondary" href="<?php echo esc_url(admin_url( wp_nonce_url('admin.php?page=gf_settings&subview=gravityformsbillingo&welcome=1', 'wc-billingo-hide-notice' ) )); ?>"><?php esc_html_e( 'Settings', 'integration-for-billingo-gravity-forms' ); ?></a>
		</p>
	</div>
</div>
