<?php
/**
 * Plugin Name: RestroPress Clover
 * Description: RestroPress Clover POS, businesses can easily manage inventory, track sales and employee performance, transactions
 * Plugin URL: http://magnigenie.com/
 * Author: MagniGenie
 * Version: 1.0
 * Author URI: http://magnigenie.com/?utm_source=restropress&utm_campaign=author-uri&utm_medium=wp-dash
 * Text Domain: rp-clover
 * Domain Path: /languages/
 * 
*/

defined('ABSPATH') || exit;

if (!defined( 'RP_CLOVER_VERSION' ) ) {
    define( 'RP_CLOVER_VERSION', 1.0 );
}

if ( !defined( 'RP_CLOVER_FILE' ) ){
	define( 'RP_CLOVER_FILE', __FILE__ );
}
// Include the main RestroPress Clover class.
if ( ! class_exists( 'RP_Clover', false ) ) {

    include_once dirname( __FILE__ ) . '/includes/class-clover-pos.php';
}

/**
 * Returns the main instance of RestroPress Clover.
 *
 * @return RestroPress Clover
 */
function rp_clover_pos() {

    return RP_Clover::instance();
}

//Get RestroPress Clover Running.
rp_clover_pos();

?>