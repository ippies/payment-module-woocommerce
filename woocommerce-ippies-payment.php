<?php
/*
Plugin Name: ippies.nl payment
Plugin URI: https://github.com/ippies/payment-module-woocommerce/releases/latest
Description: De ippies.nl betaalmodule plugin voor WooCommerce webshops.
Version: 1.0
Author: Marshmallow
Author URI: https://marshmallow.dev
Text Domain: ippies-nl-payment
License: GPLv2
*/
if ( ! defined( 'WPINC' ) ) exit; // Exit if accessed directly

require_once dirname(__FILE__) . '/includes/required_plugins.php';

/**
* payment gateway integration for WooCommerce
* @ref http://www.woothemes.com/woocommerce/
*/
function init_woocommerce_gateway_ippies() {
	define('WOOCOMMERCE_IPPIES_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
	define('WOOCOMMERCE_IPPIES_PLUGIN_URL', plugin_dir_url( __FILE__ ));

	require_once('includes/class.WCGatewayIppies.php');
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	load_plugin_textdomain( 'ippies-payment-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	
	add_action( 'init', 'init_woocommerce_gateway_ippies' );

	function add_ippies_class($methods) {
		$methods[] = 'WC_Gateway_Ippies';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_ippies_class');	
}