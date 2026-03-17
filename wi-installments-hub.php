<?php
/*
Plugin Name: WI Installments Hub
Description: WooCommerce admin panel-ში განვადების მოთხოვნების ხელით შექმნა და მართვა. მხარს უჭერს TBC, Credo და საქართველოს ბანკს.
Version: 1.2.1
Author: Goga Trapaidze
Requires Plugins: woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WI_INSTALLMENTS_HUB_VERSION', '1.2.0' );
define( 'WI_INSTALLMENTS_HUB_FILE', __FILE__ );
define( 'WI_INSTALLMENTS_HUB_PATH', plugin_dir_path( __FILE__ ) );
define( 'WI_INSTALLMENTS_HUB_URL', plugin_dir_url( __FILE__ ) );

require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-installments-db.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-installments-logger.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-provider-interface.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-provider-tbc.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-provider-credo.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-provider-bog.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-installments-admin.php';
require_once WI_INSTALLMENTS_HUB_PATH . 'includes/class-wi-installments-plugin.php';

register_activation_hook( __FILE__, [ 'WI_Installments_DB', 'activate' ] );

function wi_installments_hub_bootstrap() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action(
            'admin_notices',
            static function() {
                echo '<div class="notice notice-error"><p><strong>WI Installments Hub:</strong> WooCommerce plugin is required.</p></div>';
            }
        );
        return;
    }

    WI_Installments_Plugin::instance();
}
add_action( 'plugins_loaded', 'wi_installments_hub_bootstrap' );
