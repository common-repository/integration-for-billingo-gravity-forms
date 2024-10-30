<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info gf-billingo-notice gf-billingo-request-review">
	<p>⭐️ <?php printf( esc_html__('If you like the %sGravity Forms Billingo%s extension, please leave a rating on WordPress.org, it just takes a minute to do. Thanks!', 'integration-for-billingo-gravity-forms' ), '<strong>', '</strong>' ); ?></p>
	<p>
		<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/integration-for-billingo-gravity-forms/reviews/?filter=5#new-post"><?php esc_html_e( 'Yes, i will rate it!', 'integration-for-billingo-gravity-forms' ); ?></a>
		<a class="button-secondary gf-billingo-hide-notice remind-later" data-nonce="<?php echo esc_attr(wp_create_nonce( 'gf-billingo-hide-notice' )); ?>" data-notice="request_review" href="#"><?php esc_html_e( 'Remind me later', 'integration-for-billingo-gravity-forms' ); ?></a>
		<a class="button-secondary gf-billingo-hide-notice" data-nonce="<?php echo esc_attr(wp_create_nonce( 'gf-billingo-hide-notice' )); ?>" data-notice="request_review" href="#"><?php esc_html_e( 'No, thanks', 'integration-for-billingo-gravity-forms' ); ?></a>
	</p>
</div>
