<?php
/*
 * Plugin Name: Novikov LiqPay
 * Description: Wordpress Ликпей донатс
 * Version: 0.1
 * Author: RN
 * Text Domain: eliqpay
 * Domain Path: /languages
*/

define( 'ELIQPAY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ELIQPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ELIQPAY_CORE', ELIQPAY_PLUGIN_PATH .'include' );
define( 'ELIQPAY_CLASSES_PATH', ELIQPAY_CORE . '/classes' );
define( 'ELIQPAY_PUBLIC_PATH', ELIQPAY_PLUGIN_PATH . 'public' );
define( 'ELIQPAY_WIDGETS_PATH', ELIQPAY_PUBLIC_PATH . '/widgets' );
define( 'ELIQPAY_CALLBACK_API', ELIQPAY_PLUGIN_URL .'elp-callback-api.php' );
define( 'ELIQPAY_TEXTDOMAIN', 'eliqpay');

// Перевод
add_action( 'plugins_loaded', 'eliqpay_load_textdomain' );
function eliqpay_load_textdomain() {
	load_plugin_textdomain( ELIQPAY_TEXTDOMAIN, false, ELIQPAY_PLUGIN_PATH . 'languages' );
}

require_once ELIQPAY_CLASSES_PATH.'/ELiq_Pay_Exception.php';
require_once ELIQPAY_CLASSES_PATH.'/ELiq_Pay.php';

try {
    register_activation_hook(__FILE__, array('ELiq_Pay', 'setDefaultOptions'));
    
    $ELiqPay = ELiq_Pay::getInstance();
} catch (ELiq_Pay_Exception $ex) {
    wp_die($ex);
}
require_once ELIQPAY_PLUGIN_PATH .'/common.php';
