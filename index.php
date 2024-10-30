<?php
/*
Plugin Name: Integration for Billingo & Gravity Forms
Plugin URI: https://visztpeter.me
Description: Billingo összeköttetés automatikus számlakészítéshez a Gravity Forms-ban készített űrlapokhoz(nem hivatalos bővítmény)
Version: 1.0.6
Author: Viszt Péter
License: GPL-2.0+
Text Domain: integration-for-billingo-gravity-forms
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'GF_BILLINGO_VERSION', '1.0.6' );

add_action( 'gform_loaded', array( 'GF_Billingo_Bootstrap', 'load' ), 5 );

class GF_Billingo_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		//Extend feed options
		require_once( 'class-gf-billingo.php' );

		GFAddOn::register( 'GFBillingo' );
	}

}

function gf_billingo() {
	return GFBillingo::get_instance();
}
