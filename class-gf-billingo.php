<?php

GFForms::include_feed_addon_framework();

class GFBillingo extends GFFeedAddOn {
	public static $activation_url;

	protected $_version = GF_BILLINGO_VERSION;
	protected $_min_gravityforms_version = '1.2.2';
	protected $_slug = 'integration-for-billingo-gravity-forms';
	protected $_path = 'integration-for-billingo-gravity-forms/billingo.php';
	protected $_full_path = __FILE__;
	protected $_url = 'https://visztpeter.me';
	protected $_title = 'Billingo Kiegészítő';
	protected $_short_title = 'Billingo';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_billingo', 'gravityforms_billingo_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_billingo';
	protected $_capabilities_form_settings = 'gravityforms_billingo';
	protected $_capabilities_uninstall = 'gravityforms_billingo_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFBillingo();
		}

		return self::$_instance;
	}

	public function init() {
		parent::init();
		require_once GFBillingo::get_base_path() . '/includes/class-admin-notices.php';

		//Define activation url
		self::$activation_url = 'https://visztpeter.me';

		add_filter('gform_replace_merge_tags', array($this, 'billingo_merge_tags'), 10, 7);
		add_action('gform_admin_pre_render', array($this, 'add_merge_tags') );
	}

	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'jquery-blockui',
				'src'     => $this->get_base_url() . '/assets/js/jquery-blockui/jquery.blockUI.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array( array( 'tab' => 'gravityforms_billingo' ) )
			),
			array(
				'handle'  => 'gravityformsbillingo_admin_js',
				'src'     => $this->get_base_url() . '/assets/js/admin.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array( array( 'tab' => 'gravityforms_billingo' ) ),
				'strings'   => array(
					'loading'  => plugins_url( '/assets/images/ajax-loader.gif',__FILE__ )
				)
			)
		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function styles() {
		$styles = array(
			array(
				'handle'  => 'gravityformsbillingo_admin_css',
				'src'     => $this->get_base_url() . '/assets/css/admin.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'tab' => 'gravityforms_billingo' ) )
			)
		);

		return array_merge( parent::styles(), $styles );
	}

	public function note_avatar() {
    return $this->get_base_url() . "/assets/images/avatar.png";
	}

	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_meta_box' ), 10, 3 );
		add_filter( 'gform_entry_list_columns', array($this, 'set_columns'), 10, 2 );
		add_filter( 'gform_entries_column_filter', array($this, 'get_columns'), 10, 5 );
		add_filter( 'plugin_action_links_' . str_replace('class-gf-billingo.php', 'index.php', plugin_basename( __FILE__ )), array( $this, 'plugin_action_links' ) );
	}

	public function init_frontend() {
		parent::init_frontend();

		add_action( 'gform_post_payment_completed', array( $this, 'process_payment' ), 10, 2 );
	}

	//------- AJAX FUNCTIONS ------------------//
	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_gf_billingo_generate_invoice', array( $this, 'generate_invoice_with_ajax' ) );
		add_action( 'wp_ajax_gf_billingo_void', array( $this, 'generate_invoice_void_with_ajax' ) );
		add_action( 'wp_ajax_gf_billingo_complete', array( $this, 'generate_invoice_complete_with_ajax' ) );
		add_action( 'wp_ajax_gf_billingo_pro_check', array( $this, 'pro_check' ) );
		add_action( 'wp_ajax_gf_billingo_pro_deactivate', array( $this, 'pro_deactivate' ) );
	}

	public function pro_version_html() {
		ob_start();
		?>
			<?php if(!get_option('_gf_billingo_pro_enabled')): ?>
				<p><?php _e('Acitvate the PRO version using the license key you bought on <a href="https://visztpeter.me" target="_blank">visztpeter.me</a>.', 'integration-for-billingo-gravity-forms'); ?></p>
			<?php endif; ?>
			<?php if(get_option('_gf_billingo_pro_enabled')): ?>
				<div class="gf_billingo_pro_version_active"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('PRO version is active', 'integration-for-billingo-gravity-forms'); ?></div>
				<p><?php esc_html_e('License key:', 'integration-for-billingo-gravity-forms'); ?> <?php echo esc_html(get_option('_gf_billingo_pro_key')); ?></p>
				<p><?php esc_html_e('E-mail address:', 'integration-for-billingo-gravity-forms'); ?> <?php echo esc_html(get_option('_gf_billingo_pro_email')); ?></p>
			<?php else: ?>
				<p><input class="input-text regular-input" style="min-width:250px" type="text" name="gf_billingo_pro_key" id="gf_billingo_pro_key" value="" placeholder="<?php esc_html_e('License key', 'integration-for-billingo-gravity-forms'); ?>"></p>
				<p><input class="input-text regular-input" style="min-width:250px" type="text" name="gf_billingo_pro_email" id="gf_billingo_pro_email" value="" placeholder="<?php esc_html_e('E-mail address used for purchase', 'integration-for-billingo-gravity-forms'); ?>"></p>
			<?php endif; ?>
			<p style="margin-bottom: 0px;">
				<?php if(get_option('_gf_billingo_pro_enabled')): ?>
					<button data-nonce="<?php echo wp_create_nonce( 'gf_billingo_license_check' )?>"  class="button-secondary" type="button" name="gf_billingo_wc_billingo_pro_key_deactivate" id="gf_billingo_wc_billingo_pro_key_deactivate"><?php esc_html_e('Deactivate', 'integration-for-billingo-gravity-forms'); ?></button>
				<?php else: ?>
					<button data-nonce="<?php echo wp_create_nonce( 'gf_billingo_license_check' )?>"  class="button-primary" type="button" name="gf_billingo_wc_billingo_pro_key_activate" id="gf_billingo_wc_billingo_pro_key_activate"><?php esc_html_e('Activate', 'integration-for-billingo-gravity-forms'); ?></button>
				<?php endif; ?>
			</p>
			<div class="gf_billingo_pro_alert delete-alert alert_red"><p></p></div>
		<?php
		return ob_get_clean();
	}

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		$settings = array(
			array(
				'title'       => esc_html__('PRO version', 'integration-for-billingo-gravity-forms' ),
				'fields'			=> array(array('type' => 'hidden')), //Workaround, so the description renders
				'description' => $this->pro_version_html(),
			),
			array(
				'title'       => esc_html__('Billingo account settings', 'integration-for-billingo-gravity-forms' ),
				'description' => '<p>'.__( 'Sign in into your Billingo account and generate a new API V3 key. Next, go to the form settings and setup a new Billingo feed, where you can setup your customer and invoice data.', 'integration-for-billingo-gravity-forms' ).'</p><br>',
				'fields'      => array(
					array(
						'name' => 'api_key',
						'label' => esc_html__('API V3 key', 'integration-for-billingo-gravity-forms' ),
						'type' => 'text',
						'class' => 'large'
					),
				)
			)
		);

		return $settings;
	}

	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {
		if ( ! $this->is_valid_credentials() ) {
			?>
			<div>
				<?php echo sprintf( esc_html__( 'To generate an invoice, enter your API key in the %ssettings%s.', 'integration-for-billingo-gravity-forms' ), "<a href='" . esc_url( $this->get_plugin_settings_url() ) . "'>", '</a>' ); ?>
			</div>
			<?php
			return;
		}

		parent::feed_edit_page( $form, $feed_id );
	}

	public function get_vat_types() {
		$vat_types = array();

		$text_types = array(
			'AAM' => 'AAM',
			'AM' => 'AM',
			'EU' => 'EU',
			'EUK' => 'EUK',
			'F.AFA' => 'F.AFA',
			'FAD' => 'FAD',
			'K.AFA' => 'K.AFA',
			'MAA' => 'MAA',
			'TAM' => 'TAM',
			'ÁKK' => 'ÁKK',
			'ÁTHK' => 'ÁTHK',
		);

		$number_types = array(
			'0' => '0%',
			'1' => '1%',
			'10' => '10%',
			'11' => '11%',
			'12' => '12%',
			'13' => '13%',
			'14' => '14%',
			'15' => '15%',
			'16' => '16%',
			'17' => '17%',
			'18' => '18%',
			'19' => '19%',
			'2' => '2%',
			'20' => '20%',
			'21' => '21%',
			'22' => '22%',
			'23' => '23%',
			'24' => '24%',
			'25' => '25%',
			'26' => '26%',
			'27' => '27%',
			'3' => '3%',
			'4' => '4%',
			'5' => '5%',
			'6' => '6%',
			'7' => '7%',
			'8' => '8%',
			'9' => '9%',
		);

		foreach ($number_types as $key => $label) {
			$vat_types[] = array(
				'label' => $label,
				'value' => $label
			);
		}

		foreach ($text_types as $key => $label) {
			$vat_types[] = array(
				'label' => $label,
				'value' => $label
			);
		}

		return $vat_types;
	}

  public function feed_settings_fields() {
		$feed = $this->get_current_feed();
		$disabled = '';
		$pro_icon = '';
		if(!get_option('_gf_billingo_pro_enabled')) {
			$disabled = 'disabled';
			$pro_icon = '<i class="gf_billingo_pro_label">PRO</i>';
		}

		return array(
			array(
				'title'  => esc_html__('Invoice settings', 'integration-for-billingo-gravity-forms' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__('Feed name', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => esc_html__('Can be anything, like invoice generation', 'integration-for-billingo-gravity-forms' ),
					),
					array(
						'name'     => 'api_key',
						'label'    => esc_html__('API V3 key', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'text',
						'required' => false,
						'class'    => 'medium',
						'tooltip'  => esc_html__('If left empty, it will use the value set in plugin settings', 'integration-for-billingo-gravity-forms' ),
					),
					array(
						'label'      => esc_html__('Invoice type', 'integration-for-billingo-gravity-forms' ),
						'type'       => 'radio',
						'horizontal' => true,
						'name'       => 'invoice_type',
						'choices'    => array(
							array(
								'label' => esc_html__('Electronic', 'integration-for-billingo-gravity-forms' ),
								'value' => 'electronic'
							),
							array(
								'label' => esc_html__('Paper', 'integration-for-billingo-gravity-forms' ),
								'value' => 'paper'
							)
						),
						'default_value' => 'electronic',
						'required' => true
					),
					array(
						'label' => esc_html__( 'Automatic invoicing', 'integration-for-billingo-gravity-forms' ).$pro_icon,
						'type'  => 'proform_invoice_type',
						'name'  => 'auto_invoice',
						$disabled => $disabled
					),
					array(
						'label' => esc_html__( 'Proform', 'integration-for-billingo-gravity-forms' ).$pro_icon,
						'type'  => 'proform_invoice_type',
						'name'  => 'proform_invoice',
						$disabled => $disabled
					),
					array(
						'label' => esc_html__( 'Mark as paid', 'integration-for-billingo-gravity-forms' ).$pro_icon,
						'type'  => 'proform_invoice_type',
						'name'  => 'invoice_auto_complete',
						$disabled => $disabled
					),
					array(
						'label'             => esc_html__('Payment deadline(days)', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'text',
						'name'              => 'payment_deadline',
						'tooltip'						=> esc_html__('Just enter a number', 'integration-for-billingo-gravity-forms' ),
						'class'             => 'small'
					),
					array(
						'label'             => esc_html__('Note', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'textarea',
						'tooltip'						=> esc_html__('A note visible on the invoice', 'integration-for-billingo-gravity-forms' ),
						'name'              => 'comment',
						'class'             => 'medium merge-tag-support'
					),
					array(
						'label'   => esc_html__('TAX rate', 'integration-for-billingo-gravity-forms' ),
						'type'    => 'select',
						'name'    => 'vat_type',
						'tooltip' => esc_html__('This tax rate will be applied to all items on the invoice. The prices should be gross on the form.', 'integration-for-billingo-gravity-forms' ),
						'choices' => $this->get_vat_types()
					),
					array(
						'label'   => esc_html__('Rounding', 'integration-for-billingo-gravity-forms' ),
						'type'    => 'select',
						'name'    => 'rounding',
						'tooltip' => esc_html__('This changes the total value on the invoice.', 'integration-for-billingo-gravity-forms' ),
						'choices' => $this->get_rounding_options()
					),
					array(
						'label'             => esc_html__('Bank account', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'select',
						'name'              => 'bank_account',
						'tooltip'						=> esc_html__("You can register your bank account number on Billingo's website under Settings / Bank accounts. If you don't see your bank account in the dropdown, refresh the page.", 'integration-for-billingo-gravity-forms' ),
						'class'             => 'small',
						'choices' 					=> $this->get_billingo_bank_accounts($feed)
					),
					array(
						'label'             => esc_html__('Invoice block', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'select',
						'name'              => 'block_uid',
						'tooltip'						=> esc_html__("You can create a document block on Billingo's website under Settings / Document blocks. If you don't see your document block in the dropdown, refresh the page.", 'integration-for-billingo-gravity-forms' ),
						'class'             => 'small',
						'choices' 					=> $this->get_billingo_invoice_blocks($feed)
					),
					array(
						'label'   => esc_html__('Invoice language', 'integration-for-billingo-gravity-forms' ).$pro_icon,
						'type'    => 'select',
						'name'    => 'language',
						$disabled => $disabled,
						'choices' => array(
							array(
								'label' => esc_html__('Hungarian', 'integration-for-billingo-gravity-forms' ),
								'value' => 'hu'
							),
							array(
								'label' => esc_html__('German', 'integration-for-billingo-gravity-forms' ),
								'value' => 'de'
							),
							array(
								'label' => esc_html__('English', 'integration-for-billingo-gravity-forms' ),
								'value' => 'en'
							),
							array(
								'label' => esc_html__('Italian', 'integration-for-billingo-gravity-forms' ),
								'value' => 'it'
							),
							array(
								'label' => esc_html__('French', 'integration-for-billingo-gravity-forms' ),
								'value' => 'fr'
							),
							array(
								'label' => esc_html__('Croatian', 'integration-for-billingo-gravity-forms' ),
								'value' => 'hr'
							),
							array(
								'label' => esc_html__('Romanian', 'integration-for-billingo-gravity-forms' ),
								'value' => 'ro'
							),
							array(
								'label' => esc_html__('Slovak', 'integration-for-billingo-gravity-forms' ),
								'value' => 'sk'
							)
						)
					),
					array(
            'type'    => 'checkbox',
            'name'    => 'auto_email',
            'label'   => esc_html__( 'Invoice notification', 'integration-for-billingo-gravity-forms' ),
            'choices' => array(
							array(
								'label'         => esc_html__( 'If turned on, Billingo will email the customer about the invoice automatically.', 'integration-for-billingo-gravity-forms' ),
								'name'          => 'auto_email',
								'default_value' => 1,
							)
            ),
        	),
					array(
						'name'     => 'payment_method',
						'label'    => esc_html__('Payment method', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_billingo_payment_methods(),
						'required' => true,
						'tooltip'  => esc_html__('This will be the payment method on the invoice.', 'integration-for-billingo-gravity-forms')
					),
				)
			),
			array(
				'title'  => esc_html__('Customer details', 'integration-for-billingo-gravity-forms' ),
				'fields' => array(

					array(
						'name'     => 'customer_email',
						'label'    => esc_html__('Email address', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_lastName',
						'label'    => esc_html__('Last name', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_firstName',
						'label'    => esc_html__('First name', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_company',
						'label'    => esc_html__('Company', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => false,
					),
					array(
						'name'     => 'customer_address_country',
						'label'    => esc_html__('Country', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => false,
					),
					array(
						'name'     => 'customer_address_city',
						'label'    => esc_html__('City', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_address_postcode',
						'label'    => esc_html__('Postcode', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_address_street',
						'label'    => esc_html__('Address (street, number)', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => true,
					),
					array(
						'name'     => 'customer_vat',
						'label'    => esc_html__('VAT number', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => false,
					),
					array(
						'name'     => 'customer_vat_eu',
						'label'    => esc_html__('EU Adószám', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => false,
					),
					array(
						'name'     => 'customer_phone',
						'label'    => esc_html__('Phone number', 'integration-for-billingo-gravity-forms' ),
						'type'     => 'select',
						'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
						'required' => false,
					)
				)
			),
			array(
				'title'  => esc_html__('Invoice line items', 'integration-for-billingo-gravity-forms' ),
				'fields' => array(
					array(
						'label'      => esc_html__('Line items', 'integration-for-billingo-gravity-forms' ).$pro_icon,
						'type'       => 'radio',
						'horizontal' => true,
						'name'       => 'items',
						$disabled => $disabled,
						'onChange'	 => 'GFBillingoToggleItems()',
						'choices'    => array(
							array(
								'label' => esc_html__('Single item with the total cost', 'integration-for-billingo-gravity-forms' ),
								'value' => 'single_total'
							),
							array(
								'label' => esc_html__('Generate items based on the products', 'integration-for-billingo-gravity-forms' ),
								'value' => 'itemized'
							)
						),
						'default_value' => 'single_total'
					),
					array(
						'label'             => esc_html__('Item name', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'text',
						'name'              => 'single_item_name',
						'id'								=> 'gfbillingo_single_item_name_field',
						'class'             => 'small'
					),
					array(
						'label'             => esc_html__('Item quantity unit', 'integration-for-billingo-gravity-forms' ),
						'type'              => 'text',
						'name'              => 'items_unit',
						'tooltip'						=> esc_html__('For example pcs', 'integration-for-billingo-gravity-forms'),
						'class'             => 'small',
						'default'						=> esc_html__('pcs', 'integration-for-billingo-gravity-forms'),
					)
				)
			),
			array(
				'title'  => esc_html__('Other settings', 'integration-for-billingo-gravity-forms' ),
				'fields' => array(
					array(
						'name'    => 'optin',
						'label'   => esc_html__('Conditional mode', 'integration-for-billingo-gravity-forms' ),
						'type'    => 'feed_condition',
						'instructions'   => esc_html__('Generate invoice, if ', 'integration-for-billingo-gravity-forms' ),
						'tooltip' => '<h6>' . esc_html__('Conditional mode', 'integration-for-billingo-gravity-forms' ) . '</h6>' . esc_html__('The invoice will be generated only if this condition is met. You can generate multiple feeds with different conditions.', 'integration-for-billingo-gravity-forms' )
					)
				)
			),
		);
  }

  public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__('Name', 'integration-for-billingo-gravity-forms' ),
			'invoice_type' => esc_html__('Invoice type', 'integration-for-billingo-gravity-forms' ),
			'vat_type' => esc_html__('TAX rate', 'integration-for-billingo-gravity-forms' )
		);
	}

	public function get_column_value_invoice_type( $feed ) {
		return $feed['meta']['invoice_type'] == 'electronic' ? esc_html__('Electronic', 'integration-for-billingo-gravity-forms') : esc_html__('Paper', 'integration-for-billingo-gravity-forms');
	}

	public function get_column_value_vat_type( $feed ) {
		if(isset($feed['meta']['vat_type'])) {
			return is_numeric($feed['meta']['vat_type']) ? $feed['meta']['vat_type'].'%' : $feed['meta']['vat_type'];
		} else {
			return '';
		}
	}

	// customize the value of post_type before it's rendered to the list
	public function get_column_value_mytextbox( $feed ) {
		return '<b>' . $feed['meta']['post_type'] . '</b>';
	}

	public function is_valid_credentials() {
		$settings  = $this->get_plugin_settings();
		if(isset($settings['api_key']) && $settings['api_key'] != '') {
			return true;
		} else {
			return false;
		}
	}

	public function settings_proform_invoice_type( $field, $echo = true ) {

		// Get the setting name.
		$name = $field['name'];
		$label = $field['label'];
		$disabled = '';
		if(isset($field['disabled'])) {
			$disabled = 'disabled';
		}
		$onchange = '';
		if(isset($field['onChange'])) {
			$onchange = $field['onChange'];
		}

		// Define the properties for the checkbox to be used to enable/disable access to the simple condition settings.
		$checkbox_field = array(
			'name'    => $name,
			'type'    => 'checkbox',
			$disabled => $disabled,
			'choices' => array(
				array(
					'label' => esc_html__( 'Bekapcsolás', 'integration-for-billingo-gravity-forms' ),
					'name'  => $name . '_enabled',
				),
			),
			'onclick' => "if(this.checked){jQuery('#{$name}_condition_container').show();} else{jQuery('#{$name}_condition_container').hide();}",
			'onChange' => $onchange
		);

		// Determine if the checkbox is checked, if not the simple condition settings should be hidden.
		$is_enabled      = $this->get_setting( $name . '_enabled' ) == '1';
		$container_style = ! $is_enabled ? "style='display:none;'" : '';

		// Put together the field markup.
		$str = sprintf( "%s<div id='%s_condition_container' class='gf-billingo-conditional-options' %s><span>%s, ha</span> %s</div>",
			$this->settings_checkbox( $checkbox_field, false ),
			$name,
			$container_style,
			$label,
			$this->simple_condition( $name )
		);

		echo $str;
	}

	public function get_conditional_logic_fields() {
    $form   = $this->get_current_form();
    $fields = array();
    foreach ( $form['fields'] as $field ) {
        if ( $field->is_conditional_logic_supported() ) {
            $inputs = $field->get_entry_inputs();

            if ( $inputs ) {
                $choices = array();

                foreach ( $inputs as $input ) {
                    if ( rgar( $input, 'isHidden' ) ) {
                        continue;
                    }
                    $choices[] = array(
                        'value' => $input['id'],
                        'label' => GFCommon::get_label( $field, $input['id'], true )
                    );
                }

                if ( ! empty( $choices ) ) {
                    $fields[] = array( 'choices' => $choices, 'label' => GFCommon::get_label( $field ) );
                }

            } else {
                $fields[] = array( 'value' => $field->id, 'label' => GFCommon::get_label( $field ) );
            }

        }
    }

    return $fields;
	}

	private function init_api($api_key) {
		require_once GFBillingo::get_base_path() . '/includes/class-api.php';
		return new GFBillingo_API($api_key);
	}

	public function process_feed( $feed, $entry, $form ) {
		if($this->is_custom_logic_met( 'proform_invoice', $form, $entry, $feed )) {
			$return_info = $this->generate_invoice($feed, $entry, $form, true);

			//If there was an error while generating invoices automatically
			if($return_info && $return_info['error']) {
				update_option('_gf_billingo_error', $entry['id']);
			}
		}
	}

	public function process_payment( $entry, $action ) {
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_single_submission_feed($entry, $form);
		if($this->is_custom_logic_met( 'auto_invoice', $form, $entry, $feed )) {
			$return_info = $this->generate_invoice($feed, $entry, $form, false);

			//If credit entry enabled for payment method
			if($this->is_custom_logic_met( 'invoice_auto_complete', $form, $entry, $feed )) {
				$this->generate_invoice_complete($feed, $entry, $form);
			}

		}
	}

	public function register_meta_box( $meta_boxes, $entry, $form ) {
    // If the form has an active feed belonging to this add-on and the API can be initialized, add the meta box.
    if ( $this->get_active_feeds( $form['id'] ) ) {
        $meta_boxes[ $this->_slug ] = array(
            'title'    => $this->get_short_title(),
            'callback' => array( $this, 'add_details_meta_box' ),
            'context'  => 'side',
        );
    }

    return $meta_boxes;
	}

	public function add_details_meta_box( $args ) {
	    $form  = $args['form'];
	    $entry = $args['entry'];
			$feed = $this->get_single_submission_feed($entry, $form);
			?>

			<?php if(!$this->is_valid_credentials()): ?>
				<p style="text-align: center;"><?php esc_html_e('To generate an invoice, you need to specify your API key in the plugin settings!','integration-for-billingo-gravity-forms'); ?></p>
			<?php else: ?>
				<div id="gf-billingo-messages"></div>

				<?php if(gform_get_meta($entry['id'],'gf_billingo_proform_url')): ?>
					<p>Díjbekérő <span class="alignright"><?php echo esc_html(gform_get_meta($entry['id'],'gf_billingo_proform_number')); ?> - <a href="<?php echo esc_url($this->generate_download_link($entry['id'],'proform')); ?>"><?php esc_html_e('Download', 'integration-for-billingo-gravity-forms'); ?></a></span></p>
					<hr/>
				<?php endif; ?>

				<?php if($this->is_invoice_generated($entry['id'])): ?>
					<div style="text-align:center;" id="gf-billingo-generate-button">
						<div id="gf-billingo-generated-data">
							<p><?php esc_html_e('Invoice generated and sent to the customer.','integration-for-billingo-gravity-forms'); ?></p>
							<p><?php esc_html_e('Invoice number:','integration-for-billingo-gravity-forms'); ?> <strong><?php echo esc_html(gform_get_meta($entry['id'],'gf_billingo_invoice_name')); ?></strong></p>
							<p><a href="<?php echo esc_url($this->generate_download_link($entry['id'])); ?>" class="button button-primary" target="_blank"><?php esc_html_e('View invoice','integration-for-billingo-gravity-forms'); ?></a></p>
							<?php if(!gform_get_meta($entry['id'],'gf_billingo_completed')): ?>
								<p><a href="#" id="gf_billingo_generate_complete" data-order="<?php echo esc_attr($entry['id']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce( "gf_generate_invoice" )); ?>" target="_blank"><?php esc_html_e('Completed','integration-for-billingo-gravity-forms'); ?></a></p>
							<?php else: ?>
								<p><?php esc_html_e('Marked as paid','integration-for-billingo-gravity-forms'); ?>: <?php echo esc_html(date('Y-m-d',gform_get_meta($entry['id'],'gf_billingo_completed'))); ?></p>
							<?php endif; ?>
						</div>
						<p class="plugins"><a href="#" id="gf_billingo_generate_void" data-order="<?php echo esc_attr($entry['id']); ?>" data-nonce="<?php echo wp_create_nonce( "gf_generate_invoice" ); ?>" class="delete"><?php esc_html_e('Void invoice','integration-for-billingo-gravity-forms'); ?></a></p>
					</div>
				<?php else: ?>
					<div style="text-align:center;" id="gf-billingo-generate-button">
						<div class="gf_billingo_options_buttons">
							<div class="gf_billingo_options_buttons_row">
								<a href="#" id="gf_billingo_options"><?php esc_html_e('Options','integration-for-billingo-gravity-forms'); ?></a>
								<a href="#" id="gf_billingo_generate" data-order="<?php echo esc_attr($entry['id']); ?>" data-nonce="<?php echo wp_create_nonce( "gf_generate_invoice" ); ?>" class="button button-primary" target="_blank">
									<?php esc_html_e('Genrate invoice','integration-for-billingo-gravity-forms'); ?>
								</a>
							</div>
						</div>
						<div id="gf_billingo_options_form" style="display:none;">
							<div class="fields">
								<h4><?php esc_html_e('Note','integration-for-billingo-gravity-forms'); ?></h4>
								<textarea id="gf_billingo_invoice_note"><?php echo esc_textarea(rgars( $feed, 'meta/comment' )); ?></textarea>
								<h4><?php esc_html_e('Payment deadline(days)','integration-for-billingo-gravity-forms'); ?></h4>
								<input type="number" id="gf_billingo_invoice_deadline" value="<?php echo esc_attr(rgars( $feed, 'meta/payment_deadline' )); ?>" />
								<h4><?php esc_html_e('Fullfilment date','integration-for-billingo-gravity-forms'); ?></h4>
								<input type="text" class="date-picker" id="gf_billingo_invoice_completed" maxlength="10" value="<?php echo esc_attr(date('Y-m-d')); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
								<div class="gf_billingo_invoice_types">
									<h4><?php esc_html_e('Invoice type','integration-for-billingo-gravity-forms'); ?></h4>
									<label for="gf_billingo_invoice_normal">
										<input type="radio" name="invoice_extra_type" id="gf_billingo_invoice_normal" value="1" checked="checked" />
										<span><?php esc_html_e('Invoice','integration-for-billingo-gravity-forms'); ?></span>
									</label>
									<label for="gf_billingo_invoice_request">
										<input type="radio" name="invoice_extra_type" id="gf_billingo_invoice_request" value="1" />
										<span><?php esc_html_e('Proform','integration-for-billingo-gravity-forms'); ?></span>
									</label>
								</div>
							</div>
						</div>
					</div>
					<?php if(gform_get_meta($entry['id'],'gf_billingo_void_id')): ?>
						<p><?php esc_html_e('Void invoice:','integration-for-billingo-gravity-forms'); ?> <span class="alignright"><?php echo esc_html(gform_get_meta($entry['id'],'gf_billingo_void_number')); ?> - <a href="<?php echo esc_url($this->generate_download_link($entry['id'],'void')); ?>" target="_blank"><?php esc_html_e('Download','integration-for-billingo-gravity-forms'); ?></a></span></p>
					<?php endif; ?>

				<?php endif; ?>
			<?php endif; ?>

			<?php
	}

	//Check if it was already generated or not
	public function is_invoice_generated( $order_id ) {
		$invoice_name = gform_get_meta($order_id,'gf_billingo_invoice_id');
		if($invoice_name) {
			return true;
		} else {
			return false;
		}
	}

	public function generate_invoice_with_ajax() {
		check_ajax_referer( 'gf_generate_invoice', 'nonce' );
		if ( ! current_user_can( 'gform_full_access' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?', 'gf-szamlazz' ) );
		}
		$order_id = intval($_POST['order']);

		$entry = GFAPI::get_entry( $order_id );
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_single_submission_feed($entry, $form);

		$return_info = $this->generate_invoice($feed, $entry, $form);

		//If credit entry enabled for payment method
		if(rgars( $feed, 'meta/invoice_auto_complete_enabled' )) {
			$this->generate_invoice_complete($feed, $entry, $form);
		}

		wp_send_json_success($return_info);
	}

	//Generate XML for Szamla Agent
	public function generate_invoice($feed, $entry, $form, $payment_request = false) {
		$api_key = $this->get_account_info($feed);
		$billingo = $this->init_api($api_key);

		//Response
		$response = array();
		$response['error'] = false;

		//If custom details
		if(isset($_POST['note']) && isset($_POST['deadline']) && isset($_POST['completed'])) {
			$note = sanitize_textarea_field($_POST['note']);
			$deadline = sanitize_text_field($_POST['deadline']);
			$completed_date = sanitize_text_field($_POST['completed']);
		} else {
			$note = rgars( $feed, 'meta/comment' );
			$deadline = rgars( $feed, 'meta/payment_deadline' );
			$completed_date = date('Y-m-d');
		}

		//Replace variables in note
		$note = sanitize_textarea_field(GFCommon::replace_variables( $note, $form, $entry ));

		//Create partner
		$partnerData = [
			'name' => '',
			'emails' => [$this->get_field_value( $form, $entry, $feed['meta']['customer_email'] )],
			'phone' => $this->get_field_value( $form, $entry, $feed['meta']['customer_phone'] ),
			'taxcode' => '',
			'address' => [
				'address' => $this->get_field_value( $form, $entry, $feed['meta']['customer_address_street'] ),
				'city' => $this->get_field_value( $form, $entry, $feed['meta']['customer_address_city'] ),
				'post_code' => $this->get_field_value( $form, $entry, $feed['meta']['customer_address_postcode'] ),
				'country_code' => $this->get_field_value( $form, $entry, $feed['meta']['customer_address_country'] ) ? : 'HU',
			]
		];

		//Set partner name
		if($this->get_field_value( $form, $entry, $feed['meta']['customer_company'] )) {
			$partnerData['name'] = $this->get_field_value( $form, $entry, $feed['meta']['customer_company'] );
		} else {
			$partnerData['name'] = $this->get_field_value( $form, $entry, $feed['meta']['customer_lastName'] ) . ' ' . $this->get_field_value( $form, $entry, $feed['meta']['customer_firstName'] );
		}

		//Set partner HU TAX number
		if($taxcode = $this->get_field_value( $form, $entry, $feed['meta']['customer_vat'] )) {
			$partnerData['taxcode'] = $taxcode;
		}

		//Set partner EU TAX number
		if($taxcode = $this->get_field_value( $form, $entry, $feed['meta']['customer_vat_eu'] )) {
			$partnerData['taxcode'] = $taxcode;
		}

		//Mark client type
		$partnerData['tax_type'] = '';
		if(!$partnerData['taxcode']) {
			$partnerData['tax_type'] = 'NO_TAX_NUMBER';
			if($this->get_field_value( $form, $entry, $feed['meta']['customer_address_country'] ) != 'HU') {
				$partnerData['tax_type'] = 'FOREIGN';
			}
		} else {
			$partnerData['tax_type'] = 'HAS_TAX_NUMBER';
		}

		//Create partner
		$partner = $billingo->post('partners', apply_filters('gf_billingo_partner', $partnerData, $feed, $entry, $form));

		//Check for errors
		if(is_wp_error($partner)) {
			$response['error'] = true;
			$response['messages'][] = $partner->get_error_message();
			$this->add_feed_error(sprintf(esc_html__('Billingo invoice generation failed! Unable to create customer. Error code: %s', 'integration-for-billingo-gravity-forms'), $partner->get_error_message()), $feed, $entry, $form );
			return $response;
		}

		//If it was successful, but still no partner created
		if(!$partner['id']) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Unable to create the customer.', 'integration-for-billingo-gravity-forms');
			return $response;
		}

		//Create invoice data array
		$invoiceData = [
			'partner_id' => (int)$partner['id'],
			'block_id' => (int)rgars( $feed, 'meta/block_uid' ),
			'bank_account_id' => (int)rgars( $feed, 'meta/bank_account' ),
			'type' => 'invoice',
			'fulfillment_date' => $completed_date,
			'due_date' => ($deadline) ? date_i18n('Y-m-d', strtotime('+'.$deadline.' days', current_time('timestamp'))) : date_i18n('Y-m-d'),
			'payment_method' => rgars( $feed, 'meta/payment_method' ),
			'language' => (rgars( $feed, 'meta/language' )) ? rgars( $feed, 'meta/language' ) : 'hu',
			'currency' => rgar( $entry, 'currency' ),
			'electronic' => (rgars( $feed, 'meta/invoice_type' ) == 'electronic'),
			'paid' => false,
			'comment' => $note,
			'settings' => array(
				'round' => (rgars( $feed, 'meta/rounding' )) ? rgars( $feed, 'meta/rounding' ) : 'none',
			),
			'items' => array()
		];

		//If the base currency is not HUF, we should define currency rates
		if($invoiceData['currency'] != 'HUF') {
			$transient_name = 'gf_billingo_plus_currency_rate_'.strtolower($invoiceData['currency']);
			$exchange_rate = get_transient( $transient_name );
			if(!$exchange_rate) {
				$exchange_rate = 1;
				$get_conversion_rate = $billingo->get("currencies?to=HUF&from={$invoiceData['currency']}");
				if(is_wp_error($get_conversion_rate)) {

				} else {
					$exchange_rate = $get_conversion_rate['conversation_rate'];
				}
				set_transient( $transient_name, $exchange_rate, 60*60*12 );
			}
			$invoiceData['conversion_rate'] = $exchange_rate;
		}

		//Set invoice type
		if($payment_request) {
			if(gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_proform_id')) {
				$invoiceData['type'] = 'invoice';
				$payment_request = false;
			} else {
				$invoiceData['type'] = 'proforma';
				$payment_request = true;
			}
		} else {
			if(isset($_POST['request']) && $_POST['request'] == 'on') {
				$invoiceData['type'] = 'proforma';
				$payment_request = true;
			} else {
				$invoiceData['type'] = 'invoice';
				$payment_request = false;
			}
		}

		//Order Items
		$products = GFCommon::get_product_fields( $form, $entry, true, false );
		$vat_type = rgars( $feed, 'meta/vat_type' );
		$items_type = rgars( $feed, 'meta/items' );
		if(!$items_type) $items_type = 'single_total';

		//Calculate taxes
		$tax_percentage = 0;
		if(strpos($vat_type, '%') !== false) {
			$tax_percentage = (int)$vat_type;
		}

		if($items_type == 'single_total') {
			$gross_total = 0;
			foreach ( $products['products'] as $product ) {
				$gross_unit_price = GFCommon::to_number( $product['price'] );
				$quantity = floatval( $product['quantity'] );
				$gross_total += $quantity * $gross_unit_price;
			}

			if ( ! empty( $products['shipping']['name'] ) ) {
				$gross_unit_price = floatval( $products['shipping']['price'] );
				$gross_total += $gross_unit_price;
			}

			$gross_total = round($gross_total, 2);
			$vat_amount = round($gross_total/(100+$tax_percentage) * $tax_percentage, 2);
			$net_total = round($gross_total-$vat_amount, 2);
			$net_unit_price = $net_total;

			$invoiceData['items'][] = array(
				'name' => rgars( $feed, 'meta/single_item_name'),
				'quantity' => 1,
				'unit' => rgars( $feed, 'meta/items_unit' ),
				'vat' => $vat_type,
				'unit_price' => $net_unit_price,
				'unit_price_type' => 'net'
			);

		} else {

			//Add each product separately
			foreach ( $products['products'] as $product ) {
				$product_name = $product['name'];
				$gross_unit_price = GFCommon::to_number( $product['price'] );
				$comment = '';
				if ( ! empty( $product['options'] ) ) {
					$options = array();
					foreach ( $product['options'] as $option ) {
						$gross_unit_price += GFCommon::to_number( $option['price'] );
						$options[] = $option['option_name'];
					}
					$comment = implode( ', ', $options );
				}
				$quantity = floatval( $product['quantity'] );
				$gross_total = round($quantity * $gross_unit_price, 2);
				$vat_amount = round($gross_total/(100+$tax_percentage) * $tax_percentage, 2);
				$net_total = round($gross_total-$vat_amount, 2);
				$net_unit_price = round($net_total/$quantity, 2);

				$invoiceData['items'][] = array(
					'name' => esc_html( $product['name'] ),
					'quantity' => $product['quantity'],
					'unit' => rgars( $feed, 'meta/items_unit' ),
					'vat' => $vat_type,
					'unit_price' => $net_unit_price,
					'unit_price_type' => 'net',
					'comment' => $comment
				);

			}

			//adding shipping if form has shipping
			if ( ! empty( $products['shipping']['name'] ) ) {
				$gross_total = floatval( $products['shipping']['price'] );
				$gross_total = round($gross_total, 2);
				$vat_amount = round($gross_total/(100+$tax_percentage) * $tax_percentage, 2);
				$net_total = round($gross_total-$vat_amount, 2);
				$net_unit_price = $net_total;
				$gross_unit_price = $gross_total;

				$invoiceData['items'][] = array(
					'name' => esc_html( $products['shipping']['name'] ),
					'quantity' => 1,
					'unit' => rgars( $feed, 'meta/items_unit' ),
					'vat' => $vat_type,
					'unit_price' => $net_unit_price,
					'unit_price_type' => 'net',
					'comment' => ''
				);
			}
		}

		//If theres a proform already existed, we are using a different api call
		if(!$payment_request && gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_proform_id') && empty($options)) {
			$proform_id = gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_proform_id');
			$invoice = $billingo->post('documents/'.$proform_id.'/create-from-proforma', array());
		} else {
			$invoice = $billingo->post('documents', apply_filters('gf_billingo_invoice', $invoiceData, $feed, $entry, $form));
		}

		//Check for errors
		if(is_wp_error($invoice)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice generation failed.', 'integration-for-billingo-gravity-forms');
			$response['messages'][] = $invoice->get_error_message();
			$this->add_feed_error(sprintf(esc_html__('Billingo invoice generation failed! Error code: %s', 'integration-for-billingo-gravity-forms'), $invoice->get_error_message()), $feed, $entry, $form );
			return $response;
		}

		//If successful, but still no invoice id
		if (!$invoice['id']) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice generation failed for an unknown reason.', 'integration-for-billingo-gravity-forms');
			return $response;
		}

		//Send via email if needed
		$auto_email_sent = false;
		if(rgars( $feed, 'meta/auto_email' )) {
			$send_email = $billingo->post("documents/{$invoice['id']}/send", array());
			if(is_wp_error($send_email)) {
				$response['messages'][] = esc_html__('Failed to email invoice.', 'integration-for-billingo-gravity-forms');
				return false;
			} else {
				$auto_email_sent = true;
			}
		}

		//Create download link
		$billingo_url = '';
		$get_public_url = $billingo->get("documents/{$invoice['id']}/public-url");
		if(is_wp_error($get_public_url)) {
			$response['messages'][] = esc_html__('Failed to create a download link for the invoice.', 'integration-for-billingo-gravity-forms');
		} else {
			$billingo_url = $get_public_url['public_url'];
		}

		//Get invoice data
		$invoice_name = $invoice['id'];
		if(isset($invoice['invoice_number'])) {
			$invoice_name = $invoice['invoice_number'];
		}
		$invoice_id = $invoice['id'];
		$invoice_pdf = $pdf_file_name;

		//Save data
		if($payment_request) {
			if($auto_email_sent) {
				$xml_response['messages'][] = esc_html__('Billingo proform invoice successfully generated and sent to the customer.','integration-for-billingo-gravity-forms');
			} else {
				$xml_response['messages'][] = esc_html__('Billingo proform invoice successfully generated.','integration-for-billingo-gravity-forms');
			}

			//Store as a custom field
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_id', $invoice_id );
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_name', $invoice_name );
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_url', $billingo_url );

			//Update order notes
			$this->add_note( rgar( $entry, 'id' ), esc_html__('Billingo proform invoice successfully generated. Invoice number: ', 'integration-for-billingo-gravity-forms' ).$invoice_name, 'success' );

		} else {
			if($auto_email_sent) {
				$xml_response['messages'][] = esc_html__('Invoice generated and sent to the customer.','integration-for-billingo-gravity-forms');
			} else {
				$xml_response['messages'][] = esc_html__('Invoice successfully generated.','integration-for-billingo-gravity-forms');
			}

			//Store as a custom field
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_id', $invoice_id );
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_name', $invoice_name );
			gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_url', $billingo_url );

			//Update order notes
			$this->add_note( rgar( $entry, 'id' ), esc_html__('Invoice successfully generated. Invoice number: ', 'integration-for-billingo-gravity-forms' ).$invoice_name, 'success' );

		}

		//Return the download url
		$button_label = esc_html__('View invoice','integration-for-billingo-gravity-forms');
		$xml_response['link'] = '<p><a href="'.$billingo_url.'" id="gf_billingo_download" class="button button-primary" target="_blank">'.$button_label.'</a></p>';

		return $xml_response;
	}

	//Generate Sztornó invoice with Ajax
	public function generate_invoice_void_with_ajax() {
		check_ajax_referer( 'gf_generate_invoice', 'nonce' );
		if ( ! current_user_can( 'gform_full_access' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?', 'gf-szamlazz' ) );
		}
		$order_id = intval($_POST['order']);
		$entry = GFAPI::get_entry( $order_id );
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_single_submission_feed($entry, $form);
		$return_info = $this->generate_invoice_void($feed, $entry, $form);
		wp_send_json_success($return_info);
	}

	//Generate XML for Szamla Agent Sztornó
	public function generate_invoice_void($feed, $entry, $form) {
		$api_key = $this->get_account_info($feed);
		$billingo = $this->init_api($api_key);

		//Response
		$response = array();
		$response['error'] = false;

		//Get existing invoice id
		$invoice_id = gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_invoice_id');

		//Check for invoice
		if(!$invoice_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice not found.', 'integration-for-billingo-gravity-forms');
			return $response;
		}

		//Create void invoice
		$invoice_void = $billingo->post("documents/{$invoice_id}/cancel");

		//Check for errors
		if(is_wp_error($invoice_void)) {

			//Create response
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to create a reverse invoice.', 'integration-for-billingo-gravity-forms');
			$response['messages'][] = $invoice_void->get_error_message();
			$this->add_feed_error( sprintf(esc_html__('Billingo reverse invoice generation failed! Error code: %s', 'integration-for-billingo-gravity-forms'), $invoice_void->get_error_message()), $feed, $entry, $form );

			return $response;

		}

		//Create download link
		$billingo_url = '';
		$get_public_url = $billingo->get("documents/{$invoice_void['id']}/public-url");
		if(is_wp_error($get_public_url)) {
			$response['messages'][] = esc_html__('Failed to create a download link for the invoice.', 'integration-for-billingo-gravity-forms');
		} else {
			$billingo_url = $get_public_url['public_url'];
		}

		//Get invoice data
		$invoice_void_name = $invoice_void['id'];
		if(isset($invoice_void['invoice_number'])) {
			$invoice_void_name = $invoice_void['invoice_number'];
		}
		$invoice_void_id = $invoice_void['id'];
		$invoice_void_pdf = $pdf_file_name;

		//Create response
		$response['name'] = $invoice_void_name;

		//Store as a custom field
		gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_void_id', $invoice_id );
		gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_void_name', $invoice_name );
		gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_void_url', $billingo_url );

		//Remove existing szamla
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_id' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_number' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_invoice_url' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_id' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_number' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_proform_url' );
		gform_delete_meta( rgar( $entry, 'id' ), 'gf_billingo_completed' );

		//Save data
		$response['messages'][] = esc_html__('Void invoice successfully generated.','integration-for-billingo-gravity-forms');

		//Update order notes
		$this->add_note( rgar( $entry, 'id' ), esc_html__('Void invoice successfully generated: ', 'integration-for-billingo-gravity-forms' ).$invoice_name, 'success' );

		//Return the download url
		$response['link'] = '<p>'.__('Void invoice','integration-for-billingo-gravity-forms').': '.date("Y-m-d").'</a></p>';
		$response['link'] = '<p>'.__('Void invoice generated:', 'integration-for-billingo-gravity-forms') . '<a href="'.$pdf_url.'" target="_blank">'.__('View','integration-for-billingo-gravity-forms').'</a><br><small>'.esc_html__('Refresh this page to generate a new invoice.', 'integration-for-billingo-gravity-forms').'</small></p>';

		return $response;
	}

	//Generate complete invoice with Ajax
	public function generate_invoice_complete_with_ajax() {
		check_ajax_referer( 'gf_generate_invoice', 'nonce' );
		if ( ! current_user_can( 'gform_full_access' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?', 'gf-szamlazz' ) );
		}
		$order_id = intval($_POST['order']);
		$entry = GFAPI::get_entry( $order_id );
		$form = GFAPI::get_form( $entry['form_id'] );
		$feed = $this->get_single_submission_feed($entry, $form);
		$return_info = $this->generate_invoice_complete($feed, $entry, $form);
		wp_send_json_success($return_info);
	}

	//Generate XML for Szamla Agent
	public function generate_invoice_complete($feed, $entry, $form) {
		$api_key = $this->get_account_info($feed);
		$billingo = $this->init_api($api_key);

		//Response
		$response = array();
		$response['error'] = false;

		//Get existing invoice data
		$invoice_id = gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_invoice_id');
		$completed = gform_get_meta(rgar( $entry, 'id' ),'gf_billingo_completed');

		//If invoice doesn't exists
		if(!$invoice_id) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Invoice not found.', 'integration-for-billingo-gravity-forms');
			return $response;
		}

		//If already marked as paid
		if($completed) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('The invoice has already been marked as paid.', 'integration-for-billingo-gravity-forms');
			return $response;
		}

		//Calculate order total
		$gross_total = 0;
		$products = GFCommon::get_product_fields( $form, $entry, true, false );
		foreach ( $products['products'] as $product ) {
			$gross_unit_price = GFCommon::to_number( $product['price'] );
			$quantity = floatval( $product['quantity'] );
			$gross_total += $quantity * $gross_unit_price;
		}

		if ( ! empty( $products['shipping']['name'] ) ) {
			$gross_unit_price = floatval( $products['shipping']['price'] );
			$gross_total += $gross_unit_price;
		}

		//Create data for the request
		$paydata = array(
			'date' => date('Y-m-d'),
			'price' => round($gross_total,2),
			'payment_method' => rgars( $feed, 'meta/payment_method' )
		);
		
		//Mark invoice as paid
		$paid = $billingo->put("documents/{$invoice_id}/payments", apply_filters('gf_billingo_complete', array($paydata), $feed, $entry, $form));

		//Check for errors
		if(is_wp_error($paid)) {
			$response['error'] = true;
			$response['messages'][] = esc_html__('Failed to mark the invoice as paid.', 'integration-for-billingo-gravity-forms');
			$response['messages'][] = $paid->get_error_message();
			$this->add_feed_error( sprintf(esc_html__( 'Failed to mark the Billingo invoice as paid! Error code: %s', 'integration-for-billingo-gravity-forms' ), $paid->get_error_message()), $feed, $entry, $form );
			return $response;
		}

		//Save data
		$response['messages'][] = esc_html__('Invoice successfully marked as paid.','integration-for-billingo-gravity-forms');

		//Store as a custom field
		gform_update_meta( rgar( $entry, 'id' ), 'gf_billingo_completed', time() );

		//Update order notes
		$this->add_note( rgar( $entry, 'id' ), esc_html__('Invoice successfully marked as paid', 'integration-for-billingo-gravity-forms' ), 'success' );

		$response['link'] = '<p>'.__('Invoice successfully marked as paid','integration-for-billingo-gravity-forms').': '.date("Y-m-d").'</a></p>';

		return $response;
	}

	public function get_account_info($feed) {
		if(rgars( $feed, 'meta/api_key' )) {
			return rgars( $feed, 'meta/api_key' );
		} else {
			return $this->get_plugin_setting( 'api_key');
		}
	}

	//Generate download url
	public function generate_download_link( $order_id, $type = false, $absolute = false) {
		if($order_id) {
			$pdf_name = '';
			if($type && $type == 'proform') {
				$pdf_name = gform_get_meta( $order_id, 'gf_billingo_proform_url' );
			} else if($type && $type == 'void') {
				$pdf_name = gform_get_meta( $order_id, 'gf_billingo_void_url' );
			} else {
				$pdf_name = gform_get_meta( $order_id, 'gf_billingo_invoice_url' );
			}

			if($pdf_name) {
				return $pdf_name;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function set_columns( $table_columns, $form_id ){
		if($this->has_feed($form_id)) {
			$table_columns['field_id-gf_billingo_column'] = 'Billingo';
		}
		return $table_columns;
	}

	public function get_columns( $value, $form_id, $field_id, $entry, $query_string ){
		if($field_id == 'gf_billingo_column') {
			$value = '';

			if($this->is_invoice_generated($entry['id'])) {
				$value .= '<a href="'.$this->generate_download_link($entry['id']).'" class="button gf-billingo-entry-list-button gf-billingo-entry-list-button-invoice" target="_blank" title="Billingo számla" style="float:left;margin-right:5px;border:1px solid #FF6867"><span></span></a>';
			}

			if(gform_get_meta($entry['id'],'gf_billingo_proform_url')) {
				$value .= '<a href="'.$this->generate_download_link($entry['id'], 'proform').'" class="button gf-billingo-entry-list-button gf-billingo-entry-list-button-proform" target="_blank" title="Billingo díjbekérő" style="float:left;margin-right:5px;border:1px solid #FF6867"><span></span></a>';
			}

		}

		return $value;
	}

	//Plugin links
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . esc_url(admin_url( 'admin.php?page=gf_settings&subview=gravityformsbillingo' )) . '" aria-label="' . esc_attr__( 'Settings', 'integration-for-billingo-gravity-forms' ) . '">' . esc_html__( 'Settings', 'integration-for-billingo-gravity-forms' ) . '</a>',
			'documentation' => '<a href="https://visztpeter.me/dokumentacio/" target="_blank" aria-label="' . esc_attr__( 'Documentation', 'integration-for-billingo-gravity-forms' ) . '">' . esc_html__( 'Documentation', 'integration-for-billingo-gravity-forms' ) . '</a>'
		);
		return array_merge( $action_links, $links );
	}

	public function billingo_merge_tags($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
		$custom_merge_tag = '{billingo_link}';
		if (strpos($text, $custom_merge_tag) === false) {
				return $text;
		}

		if($this->is_invoice_generated($entry['id'])) {
			$pdf_link = $this->generate_download_link($entry['id']);
			$text = str_replace($custom_merge_tag, $pdf_link, $text);
		}

		return $text;
	}

	public function add_merge_tags( $form ) {
		if ( GFCommon::is_entry_detail() ) {
				return $form;
		}
		?>
		<script type="text/javascript">
		if (typeof gform !== 'undefined') {
			gform.addFilter('gform_merge_tags', 'add_merge_tags');
			function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option) {
					mergeTags["custom"].tags.push({
						tag: '{billingo_link}',
						label: 'Billingo Számla Link'
					});
					return mergeTags;
			}
		}
		</script>
		<?php
		return $form;
	}

	public function is_custom_logic_met( $name, $form, $entry, $feed ) {
		$is_enabled = rgars( $feed, 'meta/'.$name.'_enabled' );

		if ( ! $is_enabled ) {
			// The setting is not enabled so we handle it as if the rules are met.
			return false;
		}

		// Build the logic array to be used by Gravity Forms when evaluating the rules.
		$logic = array(
			'logicType' => 'all',
			'rules'     => array(
				array(
					'fieldId'  => rgars( $feed, 'meta/'.$name . '_field_id' ),
					'operator' => rgars( $feed, 'meta/'.$name . '_operator' ),
					'value'    => rgars( $feed, 'meta/'.$name . '_value' ),
				),
			)
		);

		return GFCommon::evaluate_conditional_logic( $logic, $form, $entry );
	}

	public function get_billingo_bank_accounts($feed, $refresh = false) {
		$api_key = $this->get_account_info($feed);
		$api_key_id = substr($api_key, 0, 5);

		$bank_accounts = get_transient('gf_billingo_bank_accounts_'.$api_key_id);
		if (!$bank_accounts || $refresh) {

			//Load billingo API
			$billingo = $this->init_api($api_key);
			$billingo_bank_accounts = false;

			//Get bank accounts
			$billingo_bank_accounts = $billingo->get('bank-accounts');

			//Create a simple array
			$bank_accounts = array();

			if(is_array($billingo_bank_accounts)) {
				foreach ($billingo_bank_accounts as $billingo_bank_account) {
					$bank_accounts[$billingo_bank_account['id']] = $billingo_bank_account['name'];
				}
			}

			//Save bank accounts for a day
			set_transient('gf_billingo_bank_accounts_'.$api_key_id, $bank_accounts, 60 * 60 * 24);
		}

		$bank_account_choices = array();
		foreach ($bank_accounts as $key => $label) {
			$bank_account_choices[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		return $bank_account_choices;
	}

	public function get_billingo_invoice_blocks($feed, $refresh = false) {
		$api_key = $this->get_account_info($feed);
		$api_key_id = substr($api_key, 0, 5);

		$invoice_blocks = get_transient('gf_billingo_invoice_blocks_'.$api_key_id);
		if (!$invoice_blocks || $refresh) {

			//Load billingo API
			$billingo = $this->init_api($api_key);
			$billingo_invoice_blocks = false;

			//Get blocks
			$billingo_invoice_blocks = $billingo->get('document-blocks');

			//Create a simple array
			$invoice_blocks = array();
			if(is_array($billingo_invoice_blocks)) {
				foreach ($billingo_invoice_blocks as $billingo_invoice_block) {
					$invoice_blocks[$billingo_invoice_block['id']] = $billingo_invoice_block['name'];
				}
			}

			//Save vat ids for a day
			set_transient('gf_billingo_invoice_blocks_'.$api_key_id, $invoice_blocks, 60 * 60 * 24);
		}

		$invoice_block_choices = array();
		foreach ($invoice_blocks as $key => $label) {
			$invoice_block_choices[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		return $invoice_block_choices;
	}

	public function get_billingo_payment_methods() {
		$payment_methods = apply_filters('wc_billingo_plus_payment_methods', array(
			'wire_transfer' => esc_html__('Wire transfer', 'integration-for-billingo-gravity-forms'),
			'aruhitel' => esc_html__('Loan', 'integration-for-billingo-gravity-forms'),
			'bankcard' => esc_html__('Credit card', 'integration-for-billingo-gravity-forms'),
			'barion' => esc_html__('Barion', 'integration-for-billingo-gravity-forms'),
			'barter' => esc_html__('Barter', 'integration-for-billingo-gravity-forms'),
			'ep_kartya' => esc_html__('Health insurance card', 'integration-for-billingo-gravity-forms'),
			'elore_utalas' => esc_html__('Advance payment', 'integration-for-billingo-gravity-forms'),
			'kompenzacio' => esc_html__('Compensation', 'integration-for-billingo-gravity-forms'),
			'coupon' => esc_html__('Coupon', 'integration-for-billingo-gravity-forms'),
			'cash' => esc_html__('Cash', 'integration-for-billingo-gravity-forms'),
			'levonas' => esc_html__('Deduction', 'integration-for-billingo-gravity-forms'),
			'online_bankcard' => esc_html__('Online credit card', 'integration-for-billingo-gravity-forms'),
			'paypal' => esc_html__('PayPal', 'integration-for-billingo-gravity-forms'),
			'paypal_utolag' => esc_html__('PayPal post-paid', 'integration-for-billingo-gravity-forms'),
			'payu' => esc_html__('PayU', 'integration-for-billingo-gravity-forms'),
			'paylike' => esc_html__('Paylike', 'integration-for-billingo-gravity-forms'),
			'payoneer' => esc_html__('Payoneer', 'integration-for-billingo-gravity-forms'),
			'pick_pack_pont' => esc_html__('Pick Pack Pont', 'integration-for-billingo-gravity-forms'),
			'postai_csekk' => esc_html__('Post office cheque', 'integration-for-billingo-gravity-forms'),
			'postautalvany' => esc_html__('Postal voucher', 'integration-for-billingo-gravity-forms'),
			'szep_card' => esc_html__('SZÉP card', 'integration-for-billingo-gravity-forms'),
			'skrill' => esc_html__('Skrill', 'integration-for-billingo-gravity-forms'),
			'transferwise' => esc_html__('Transferwise', 'integration-for-billingo-gravity-forms'),
			'upwork' => esc_html__('Upwork', 'integration-for-billingo-gravity-forms'),
			'utalvany' => esc_html__('Voucher', 'integration-for-billingo-gravity-forms'),
			'cash_on_delivery' => esc_html__('Cash on delivery', 'integration-for-billingo-gravity-forms'),
			'valto' => esc_html__('Bill of exchange', 'integration-for-billingo-gravity-forms'),
		));

		$payment_method_choices = array();
		foreach ($payment_methods as $key => $label) {
			$payment_method_choices[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		return $payment_method_choices;
	}

	public function get_rounding_options() {
		$options = array(
			'none' => esc_html__('None', 'integration-for-billingo-gravity-forms'),
			'one' => esc_html__('Round to 1', 'integration-for-billingo-gravity-forms'),
			'five' => esc_html__('Round to 5', 'integration-for-billingo-gravity-forms'),
			'ten' => esc_html__('Round to 10', 'integration-for-billingo-gravity-forms'),
		);

		$rounding_choices = array();
		foreach ($options as $key => $label) {
			$rounding_choices[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		return $rounding_choices;
	}

	public function pro_check() {
		check_ajax_referer( 'gf_billingo_license_check', 'nonce' );
		if ( !current_user_can( 'gform_full_access' ) )  {
			wp_die( esc_html__('You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = sanitize_text_field($_POST['key']);
		$pro_email = sanitize_text_field($_POST['email']);

		$args = array(
			'request'     => 'activation',
			'licence_key' => $pro_key,
			'email'			  => $pro_email,
			'product_id' => 'GF_BILLINGO'
		);

		$base_url = add_query_arg('wc-api', 'software-api-extended', self::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$data = json_decode($data['body']);

		if(isset($data->error)) {
			wp_send_json_error(array(
				'message' => esc_html__('Unable to activate the PRO version. Please make sure all the details are correct.', 'integration-for-billingo-gravity-forms')
			));
		} else {

			//Store the key and email
			update_option('_gf_billingo_pro_key', $pro_key);
			update_option('_gf_billingo_pro_email', $pro_email);
			update_option('_gf_billingo_pro_enabled', true);

			wp_send_json_success();
		}

	}

	public function pro_deactivate() {
		check_ajax_referer( 'gf_billingo_license_check', 'nonce' );
		if ( !current_user_can( 'gform_full_access' ) )  {
			wp_die( esc_html__('You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = get_option('_gf_billingo_pro_key');
		$pro_email = get_option('_gf_billingo_pro_email');

		$args = array(
			'request' => 'activation_reset',
			'email' => $pro_email,
			'licence_key' => $pro_key,
			'product_id' => 'GF_BILLINGO'
		);

		//Execute request (function below)
		$base_url = add_query_arg('wc-api', 'software-api-extended', self::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$data = json_decode($data['body']);

		if(isset($data->error)) {
			wp_send_json_error(array(
				'message' => esc_html__('Unable to deactivate the PRO version. Please make sure all the details are correct.', 'integration-for-billingo-gravity-forms')
			));
		} else {

			//Store the key and email
			delete_option('_gf_billingo_pro_key');
			delete_option('_gf_billingo_pro_email');
			delete_option('_gf_billingo_pro_enabled');

			wp_send_json_success();
		}

	}

}
