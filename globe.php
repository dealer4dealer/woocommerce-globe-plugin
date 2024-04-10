<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
/*
   Plugin Name: globe Rest API extension
   Plugin URI: http://www.dealer4dealer.nl
   description: Extend WC Rest API to support globe requests
   @Version: 1.1.0
   @Author: Dealer4Dealer
   Author URI: http://www.dealer4dealer.nl
   Requires at least: 4.7.5
   Tested up to: 4.9.8
   License: GPL2
   WC requires at least: 3.3.0
   WC tested up to: 3.4.5
   */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if WooCommerce is active
 **/
if (!is_plugin_active( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', 'woocommerce_not_activated' );
    return;
}


if (!class_exists('globe')) {
    include_once dirname(__FILE__) . '/includes/class-globe.php';
}

add_action('plugins_loaded', 'run_globe', 10, 1);
function run_globe()
{
    if (class_exists('Globe')) {
        return Globe::instance();
    }
}

add_action(
    'before_woocommerce_init',
    function() {
        if (class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

function globe_check_woocommerce() {
    ?>
    <div class="error notice">
        <p><b><?php _e( 'Globe Rest API extension requires WooCommerce to be activated to work.', 'http://www.dealer4dealer.nl' ); ?></b></p>
    </div>
    <?php
}