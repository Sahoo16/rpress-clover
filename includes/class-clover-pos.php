<?php
/**
 * Rpress Clover setup
 *
 * @package Rpress_clover
 * @since   2.2
 */

defined( 'ABSPATH' ) || exit;

class RP_Clover { 
	/**
   * Rpress clover version.
   *
   * @var string
   */
  public $version = '1.0';

	/**
   * The single instance of the class.
   *
   * @var Rpress_clover
   * @since  1.0
   */
 protected static $_instance = null;

  public static function instance() {
  	if ( is_null( self::$_instance ) ) {

			self::$_instance = new self();
		}
		return self::$_instance;
  }
  /**
   * RestroPress clover Constructor.
   * @author magnigeeks
   *@package Rpress_clover
   *@since   1.0
     */
  public function __construct() {
  	$this->define_constants();
  	$this->init_hooks();
    $this->includes();
  }

  private function define( $name, $value ) {

		if ( ! defined( $name ) ) {

			define( $name, $value );
		}
	}
  /**
     * Define Constants
     * @author magnigeeks
     *@package Rpress_clover
     *@since   1.0
     */
  private function define_constants() {

  	$this->define( 'RP_CLOVER_PLUGIN_DIR', plugin_dir_path( RP_CLOVER_FILE ) );

	  $this->define( 'RP_CLOVER_PLUGIN_URL', plugin_dir_url( RP_CLOVER_FILE ) );

	  $this->define( 'RP_CLOVER_BASE', plugin_basename( RP_CLOVER_FILE ) );
  }


  /**
	* Hook into actions and filters.
	*@author magnigeeks
	*@since   1.0
	*/
  private function init_hooks() {

    add_action( 'admin_notices', array( $this, 'clover_required_plugins' ) );

  	add_filter( 'plugin_action_links_'.RP_CLOVER_BASE, array( $this, 'settings_links' ) );

  	add_action( 'plugins_loaded', array( $this, 'clover_load_textdomain' ) );

    add_action( 'admin_enqueue_scripts', array( $this, 'rp_admin_clover_enqueue' ) );

  }	

  /**
   * Check required plugin
   *@author magnigeeks
   *@since   1.0
   */

  public function clover_required_plugins() {

      if ( ! is_plugin_active( 'restropress/restro-press.php' ) ) {
        $plugin_link = 'https://wordpress.org/plugins/restropress/';

        echo '<div id="notice" class="error"><p>' . sprintf( __( 'Rpress Discount Code requires <a href="%1$s" target="_blank"> RestroPress </a> plugin to be installed. Please install and activate it', 'rp-clover' ), esc_url( $plugin_link ) ).  '</p></div>';

        deactivate_plugins( '/restropress-clover/rpress-clover.php' );
      }
  }
  /**
   * Add settings link for the plugin
   *
   * @since 1.0
   */
  public function settings_links( $links ) {

     $action_links = array(

              'settings' => '<a href="' . admin_url( 'admin.php?page=rpress-clover-pos') .  '">' . esc_html__( 'Settings', 'rp-clover' ) . '</a>',

          );
      return array_merge( $action_links, $links );
  }

  /**
   * Load text domain
   *
   * @since 1.0
   */
  public function clover_load_textdomain() {

    load_plugin_textdomain( 'rp-clover', false, dirname( plugin_basename( RP_CLOVER_FILE ) ). '/languages/' );
  }

  /**
   * Include required files for settings
   *@author magnigeeks
   *@package Discount_Loader
   *@since   1.0
  */

  private function includes() {
    
    require_once RP_CLOVER_PLUGIN_DIR . 'admin/class-clover-settings.php';

  }

  /**
  * Add js for admin
  *@author magnigeeks
  *@package Rewards_loader
  *@since   1.0
  */

  public function rp_admin_clover_enqueue() {

    wp_enqueue_style( 'clover-css', RP_CLOVER_PLUGIN_URL . '/assets/css/clover.css' );

    wp_enqueue_script( 'clover-js', RP_CLOVER_PLUGIN_URL . '/assets/js/clover.js', array('jquery'), '', true );
      wp_localize_script('clover-js', 'ajax_actions', array(
      'ajaxurl' => admin_url( 'admin-ajax.php' )
    ));
  }


}
new RP_Clover();
?>