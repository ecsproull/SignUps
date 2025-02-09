<?php
/*
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy. Licensed under GPL-2.0+
 *
 * SignUps is a WordPress plugin used by the Sun City West Woodshop members to sign up for classes and other club activities.
 */

/**
 * Plugin Name: SignUps
 * Plugin URI:
 * Description: SignUps administration tools for the Sun City West WoodShop.
 * Version: 1.0
 * Author: Ed Sproull
 * Author URI:
 * Author Email:
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpccp
 * Domain Path: /languages
 */
declare(strict_types=1);
require_once 'vendor/autoload.php';
require_once 'includes/class-signupsbase.php';
require_once 'includes/class-signupsrestapis.php';
require_once 'includes/class-dbsignuptables.php';
require_once 'includes/class-signupsettings.php';
require_once 'includes/class-classitem.php';
require_once 'includes/class-sessionitem.php';
require_once 'includes/class-shortcodes.php';
require_once 'includes/class-timeexception.php';
require_once 'includes/class-htmleditor.php';
require_once 'includes/class-stripepayments.php';
require_once 'includes/class-rollingtemplateseditor.php';
require_once 'includes/class-rollingexceptionseditor.php';
require_once 'includes/class-sendgridmail.php';
require_once 'includes/class-reports.php';
require_once 'includes/class-paymentsreview.php';
require_once 'includes/class-rollingslot.php';
require_once 'includes/class-instructorseditor.php';
require_once 'includes/class-sessionemaildata.php';
require_once 'includes/class-settings.php';

/**
 * Main SignUps class. This is the entry point for the plugin.
 * Several other classes are require_onced in this file and all are instantiated here.
 */
class SignUpsPlugin extends SignUpsBase {

	/**
	 * Reference to the ShortCode object.
	 */
	private $short_codes;

	/**
	 * Reference to the StripePayments object.
	 */
	private $stripe_payments;

	/**
	 * Reference to the Reports object.
	 */
	private $reports;



	/**
	 * The constructor does a lot of work by instantiated several objects that
	 * are require_onced for various ShortCodes. The ShortCodes are also registered here.
	 *
	 * It is important to understand that this plugin is one big ShortCode. The ShortCodes
	 * class in the root of the public user interface. SignupSettings class is the root of the
	 * administration interface.
	 */
	public function __construct() {
		setcookie( 'signups_scw_cache', 'ignore', time()+3600 );
		register_activation_hook( __FILE__, array( new DbSignUpTables(), 'create_db_tables' ) );
		add_action( 'admin_menu', array( $this, 'signup_plugin_top_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts_and_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_users_scripts_and_css' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		new SignUpsRestApis();
		$this->short_codes     = new ShortCodes();
		$this->stripe_payments = new StripePayments();
		$this->reports         = new Reports();
		add_shortcode( 'scw_selectclass', array( $this->short_codes, 'user_signup' ) );
		add_shortcode( 'scw_payment_success', array( $this->stripe_payments, 'payment_success' ) );
		add_shortcode( 'scw_payment_canceled', array( $this->stripe_payments, 'payment_canceled' ) );
		add_shortcode( 'scw_reports', array( $this->reports, 'class_reports' ) );
		add_filter( 'query_vars', array( $this, 'wwp_custom_query_vars_filter' ) );
		add_filter( 'nonce_user_logged_out', array( $this, 'wpdocs_modify_nonce_for_logged_out_users' ), 10, 2 );
		add_filter( 'nonce_life', array( $this, 'modify_nonce_life' ), 10, 2 );
		add_filter( 'show_admin_bar', array( $this, 'restrict_admin_bar' ) );
		update_option( 'signups_clear_cache', '0', '', false );
	}

	/**
	 * Don't show the admin bar unless an admin is logged in.
	 *
	 * @param  mixed $show Unused in our case.
	 * @return void
	 */
	public function restrict_admin_bar( $show ) {
		return current_user_can( 'edit_plugins' ) ? true : false;
	}

	/**
	 * Modify the life time of the nounce.
	 *
	 * @param  mixed $lifespan Normally set to 24 hours but we want to shorten it to an hour.
	 * @param  mixed $action The nonce by name.
	 * @return The life span.
	 */
	public function modify_nonce_life( $lifespan, $action ) {
		if ( 'signups_attendee' === $action ) {
			$lifespan = 3600;
		}

		return $lifespan;
	}

	/**
	 * Custom function to modify the nonce value for logged-out users.
	 *
	 * @param int    $uid    The user ID (0 for logged-out users).
	 * @param string $action The nonce action.
	 *
	 * @return int The modified user ID.
	 * */
	public function wpdocs_modify_nonce_for_logged_out_users( $uid, $action ) {
		if ( 'signups' === $action || 'wp_rest' === $action ) {
 			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$uid = (int) str_replace( '.', '', wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			} else {
				$uid = 324554;
			}
		}

		return $uid;
	}
	
	/**
	 * Called when all of the WordPress plugins have been loaded
	 * This the only time that the WP-Optimize object is available to clear the cache.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		if ( '1' === get_option( 'signups_clear_cache' ) ) {
			WP_Optimize()->get_page_cache()->purge();
			$this->set_clear_cache( '0' );
		}
	}

	/**
	 * Adds the query vars used by this plugin.
	 * Query vars are items allowed to be passed in the url of our pages.
	 * Example: https://scwwoodshop.com/signups?signup_id=5
	 *
	 * @param mixed $vars Array of vars to add to.
	 * @return array The altered vars array.
	 */
	public function wwp_custom_query_vars_filter( $vars ) {
		$vars[] .= 'attendee_id';
		$vars[] .= 'badge';
		$vars[] .= 'signup_id';
		$vars[] .= 'secret';
		$vars[] .= 'unsubscribe';
		$vars[] .= 'mail_group';
		return $vars;
	}


	/**
	 * Adds the main menu item and set of submenu items for the plugin.
	 * This menu is available only to the administrators.
	 */
	public function signup_plugin_top_menu() {
		add_menu_page( '', 'SignUps', 'manage_options', 'sign_ups', array( new SignupSettings(), 'signup_settings_page' ), plugin_dir_url( __FILE__ ) . 'img/frenchie.bmp' );
		add_submenu_page( 'sign_ups', '', '', 'manage_options', 'sign_ups', '' );
		add_submenu_page( 'sign_ups', 'Html Editor', 'Descriptions', 'manage_options', 'html_editor', array( new HtmlEditor(), 'load_html_editor' ) );
		add_submenu_page( 'sign_ups', 'Rolling Templates Editor', 'Rolling Templates', 'manage_options', 'template_editor', array( new RollingTemplatesEditor(), 'load_templates_editor' ) );
		add_submenu_page( 'sign_ups', 'Rolling Exceptions Editor', 'Exceptions', 'manage_options', 'exceptions_editor', array( new RollingExceptionsEditor(), 'load_exceptions_editor' ) );
		add_submenu_page( 'sign_ups', 'Payments Report', 'Payments Report', 'manage_options', 'payments_report', array( new PaymentsReview(), 'review_payments' ) );
		add_submenu_page( 'sign_ups', 'Instructors', 'Instructors', 'manage_options', 'instructors_editor', array( new InstructorsEditor(), 'instructors_editor' ) );

		$current_user    = wp_get_current_user();
		if ( 'ecsproull' === $current_user->user_login ) {
			$settings_editor = new Settings();
			add_submenu_page( 'sign_ups', 'Settings', 'Settings', 'manage_options', 'signups_settings_editor', array( $settings_editor, 'signups_plugin_option_page' ) );
			$settings_editor->signups_register_settings();
		}
	}

	/**
	 * Adds the CSS that is used to style the admin side plug-in.
	 * Note that the user styles are also available on the admin pages.
	 * Each side, admin and user, have their own JS file.
	 * The use of the filemtime function is to break caching when the css and js files are changed.
	 * The $host parameter is used to verify that this is being called from our internal pages.
	 * The call to wp_localize_scripts is used to enhance security between ajax calls to the RESTful apis.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_admin_scripts_and_css( $host ) {
		$user_pages   = array();
		$user_pages[] = 'toplevel_page_sign_ups';
		$user_pages[] = 'signups_page_html_editor';
		$user_pages[] = 'signups_page_template_editor';
		$user_pages[] = 'signups_page_exceptions_editor';
		$user_pages[] = 'cncusagereport';
		$user_pages[] = 'signups_page_test_page';
		$user_pages[] = 'signups_page_payments_report';
		$user_pages[] = 'signups_page_instructors_editor';
		$user_pages[] = 'signups_page_signups_settings_editor';
		if ( ! in_array( $host, $user_pages, true ) ) {
			return;
		}

		$captcha_keys = get_option( 'signups_captcha' );

		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		
		$ver_styles = filemtime( plugin_dir_path( __FILE__ ) . 'css/style.css' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $ver_styles );
		wp_enqueue_style( 'signup_style' );
		
		$ver_user_styles = filemtime( plugin_dir_path( __FILE__ ) . 'css/users-styles.css' );
		wp_register_style( 'user_signup_style', plugin_dir_url( __FILE__ ) . 'css/users-styles.css', array(), $ver_user_styles );
		wp_enqueue_style( 'user_signup_style' );
		
		wp_enqueue_script( 'jquery' );
		
		wp_register_script( 'recap', 'https://www.google.com/recaptcha/api.js?render=' . $captcha_keys['captcha_api_key'], array(), '1.0.0.0', false );
		wp_enqueue_script( 'recap' );
		
		$ver_common_js = filemtime( plugin_dir_path( __FILE__ ) . 'js/common.js' );
		wp_register_script( 'signup_common_script', plugin_dir_url( __FILE__ ) . 'js/common.js', __FILE__, array( 'jquery' ), $ver_common_js, false );
		wp_enqueue_script( 'signup_common_script' );
		
		$ver_js = filemtime( plugin_dir_path( __FILE__ ) . 'js/signups.js' );
		wp_register_script( 'signup_member_script', plugin_dir_url( __FILE__ ) . 'js/signups.js', __FILE__, array( 'jquery' ), $ver_js, false );
		wp_enqueue_script( 'signup_member_script' );
		
		wp_register_script( 'signup_cookie_script', plugin_dir_url( __FILE__ ) . 'cookie/node_modules/js-cookie/dist/js.cookie.min.js', array( 'jquery' ), '3.0.5', false );
		wp_enqueue_script( 'signup_cookie_script' );

		wp_register_script( 'signup_ckeditor', 'https://cdn.ckeditor.com/ckeditor5/41.2.1/super-build/ckeditor.js', array(), '1.0.0.0', false );
		wp_enqueue_script( 'signup_ckeditor' );
		
		wp_localize_script(
			'signup_member_script',
			'wpApiSettings',
			array(
				'root'     => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'location' => 'Admin Scripts',
			)
		);
	}

	/**
	 * Adds the JS adn CSS that is used to style the users side of the plug-in.
	 * 
	 * The use of the filemtime function is to break caching when the css and js files are changed.
	 * The $host parameter is used to verify that this is being called from our internal pages.
	 * The call to wp_localize_scripts is used to enhance security between ajax calls to the RESTful apis.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_users_scripts_and_css( $host ) {
		$user_pages   = array();
		$user_pages[] = 'signup-description-editor';
		$user_pages[] = 'submit-new-signup';
		$user_pages[] = 'signups';
		$user_pages[] = 'reports';
		$user_pages[] = 'reports_old';
		if ( ! is_page( $user_pages ) ) {
			return;
		}

		$captcha_keys = get_option( 'signups_captcha' );

		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		
		$ver_user_styles = filemtime( plugin_dir_path( __FILE__ ) . 'css/users-styles.css' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/users-styles.css', array(), $ver_user_styles );
		wp_enqueue_style( 'signup_style' );
		
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		
		wp_register_script( 'signup_cookie_script', plugin_dir_url( __FILE__ ) . 'cookie/node_modules/js-cookie/dist/js.cookie.min.js', array( 'jquery' ), '3.0.5', false );
		wp_enqueue_script( 'signup_cookie_script' );

		wp_register_script( 'recap', 'https://www.google.com/recaptcha/api.js?render=' . $captcha_keys['captcha_api_key'], array(), '1.0.0.0', false );
		wp_enqueue_script( 'recap' );
		
		$ver_common_js = filemtime( plugin_dir_path( __FILE__ ) . 'js/common.js' );
		wp_register_script( 'signup_common_script', plugin_dir_url( __FILE__ ) . 'js/common.js', __FILE__, array( 'jquery' ), $ver_common_js, false );
		wp_enqueue_script( 'signup_common_script' );
		
		$ver_users_js = filemtime( plugin_dir_path( __FILE__ ) . 'js/users-signup.js' );
		wp_register_script( 'signup_member_script', plugin_dir_url( __FILE__ ) . 'js/users-signup.js', array( 'jquery', 'jquery-ui-dialog', 'signup_cookie_script' ), $ver_users_js, false );
		wp_enqueue_script( 'signup_member_script' );
		
		wp_localize_script(
			'signup_member_script',
			'wpApiSettings',
			array(
				'root'     => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'location' => 'Users Scripts' . $host,
			)
		);
	}
}

$signups = new SignUpsPlugin();
