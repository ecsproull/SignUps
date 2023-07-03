<?php
/**
 * Summary
 * Database class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Plugin Name: Signups
 * Plugin URI:
 * Description: Signups administration tools.
 * Version: 1.0
 * Author: Ed Sproull
 * Author URI:
 * Author Email:
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpccp
 * Domain Path: /languages
 */

require 'includes/class-signupsbase.php';
require 'includes/class-signupsrestapis.php';
require 'includes/class-dbsignuptables.php';
require 'includes/class-signupsettings.php';
require 'includes/class-classitem.php';
require 'includes/class-sessionitem.php';
require 'includes/class-shortcodes.php';
require 'includes/class-timeexception.php';
require 'includes/class-htmleditor.php';
require 'includes/class-productseditor.php';
require 'includes/class-stripepayments.php';
require 'includes/class-rollingtemplateseditor.php';
require_once 'vendor/autoload.php';

/**
 * Main signups class.
 */
class SignupsPlugin extends SignUpsBase {

	/**
	 * Shortcode object for use in api callback.
	 *
	 * @var $short_codes
	 */
	private $short_codes;

	/**
	 * Shortcode object for use in api callback.
	 *
	 * @var $stripe_payments
	 */
	private $stripe_payments;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( new DbSignUpTables(), 'create_db_tables' ) );
		add_action( 'admin_menu', array( $this, 'signup_plugin_top_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_users_scripts_and_css' ) );
		new SignUpsRestApis();
		$this->short_codes = new ShortCodes();
		$this->stripe_payments = new SripePayments();
		add_shortcode( 'scw_selectclass', array( $this->short_codes, 'user_signup' ) );
		add_shortcode( 'scw_payment_success', array( $this->stripe_payments, 'payment_success' ) );
		add_shortcode( 'scw_payment_failure', array( $this->stripe_payments, 'payment_failure' ) );
		add_action(
			'rest_api_init',
			array( $this, 'regester_payment_route' )
		);

		add_filter( 'query_vars', array( $this, 'wwp_custom_query_vars_filter' ) );
	}

	/**
	 * Adds the query vars used by this plugin.
	 *
	 * @param mixed $vars Array of vars to add to.
	 * @return void
	 */
	public function wwp_custom_query_vars_filter($vars) {
		$vars[] .= 'attendee_id';
		$vars[] .= 'badge';
		$vars[] .= 'signup_id';
		return $vars;
	}
	

	/**
	 * Route used for Stripe.com callback.
	 *
	 * @return void
	 */
	public function regester_payment_route() {
		register_rest_route(
			'scwmembers/v1',
			'/payments',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->stripe_payments, 'payment_event' ),
				'permission_callback' => array( $this->stripe_payments, 'permissions_check' ),
			)
		);
	}

	/**
	 * Adds the one and only menu item for the plugin.
	 */
	public function signup_plugin_top_menu() {
		add_menu_page( '', 'SignUps', 'manage_options', 'sign_ups', array( new SignupSettings(), 'signup_settings_page' ), plugins_url( '/signups/img/frenchie.bmp' ) );
		add_submenu_page( 'sign_ups', 'Html Editor', 'Descriptions', 'manage_options', 'html_editor', array( new HtmlEditor(), 'load_html_editor' ) );
		add_submenu_page( 'sign_ups', 'Rolling Templates Editor', 'Rolling Templates', 'manage_options', 'template_editor', array( new RollingTemplatesEditor(), 'load_templates_editor' ) );
	}

	/**
	 * Adds the CSS that is used to style the admin side plug-in.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_scripts_and_css( $host ) {
		if ( 'toplevel_page_SignUps' !== $host ) {
			//return;  TODO fix this up for just our pages
		}

		wp_register_style( 'signup_bs_style', plugins_url( '/signups/bootstrap/css/bootstrap.min.css' ), array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugins_url( '/signups/css/style.css' ), array(), 1 );
		wp_enqueue_style( 'signup_style' );
		wp_enqueue_script( 'signup_member_script', plugins_url( 'js/signups.js', __FILE__ ), array( 'jquery' ), '1.0.0.0', false, true );
		wp_localize_script(
			'signup_member_script',
			'wpApiSettings',
			array(
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Adds the CSS that is used to style the users side of the plug-in.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_users_scripts_and_css( $host ) {
		if ( ! is_page( 'signups' ) ) {
			return;
		}

		wp_register_style( 'signup_bs_style', plugins_url( '/signups/bootstrap/css/bootstrap.min.css' ), array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugins_url( '/signups/css/users-styles.css' ), array(), 1 );
		wp_enqueue_style( 'signup_style' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'signup_cookie_script', plugins_url( 'cookie/node_modules/js-cookie/dist/js.cookie.min.js', __FILE__ ), array( 'jquery' ), '3.0.5', false, true );
		wp_enqueue_script( 'signup_member_script', plugins_url( 'js/users-signup.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog', 'signup_cookie_script' ), '1.0.0.0', false, true );
		wp_localize_script(
			'signup_member_script',
			'wpApiSettings',
			array(
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

$signups = new SignupsPlugin();
