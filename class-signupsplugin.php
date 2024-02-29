<?php
/**
 * Summary
 * Database class.
 *
 * @package     signups
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Plugin Name: signups
 * Plugin URI:
 * Description: Signups administration tools for the Sun City West Woodshop.
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
require 'includes/class-signupsbase.php';
require 'includes/class-signupsrestapis.php';
require 'includes/class-dbsignuptables.php';
require 'includes/class-signupsettings.php';
require 'includes/class-classitem.php';
require 'includes/class-sessionitem.php';
require 'includes/class-shortcodes.php';
require 'includes/class-timeexception.php';
require 'includes/class-htmleditor.php';
require 'includes/class-descriptioneditor.php';
require 'includes/class-stripepayments.php';
require 'includes/class-rollingtemplateseditor.php';
require 'includes/class-rollingexceptionseditor.php';
require 'includes/class-sendgridmail.php';
require 'includes/class-reports.php';
require 'includes/class-testplugin.php';
require 'includes/class-paymentsreview.php';

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
	 * Shortcode description editor.
	 *
	 * @var $short_codes
	 */
	private $description_editor;

	/**
	 * Shortcode object for use in api callback.
	 *
	 * @var $stripe_payments
	 */
	private $stripe_payments;

	/**
	 * Shortcode object for use in api callback.
	 *
	 * @var $reports
	 */
	private $reports;

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
		$this->short_codes        = new ShortCodes();
		$this->stripe_payments    = new StripePayments();
		$this->description_editor = new DescriptionEditor();
		$this->reports            = new Reports();
		add_shortcode( 'scw_selectclass', array( $this->short_codes, 'user_signup' ) );
		add_shortcode( 'scw_payment_success', array( $this->stripe_payments, 'payment_success' ) );
		add_shortcode( 'scw_payment_failure', array( $this->stripe_payments, 'payment_failure' ) );
		add_shortcode( 'scw_description_editor', array( $this->description_editor, 'load_description_editor' ) );
		add_shortcode( 'scw_reports', array( $this->reports, 'class_reports' ) );
		add_filter( 'query_vars', array( $this, 'wwp_custom_query_vars_filter' ) );
	}

	/**
	 * Adds the query vars used by this plugin.
	 *
	 * @param mixed $vars Array of vars to add to.
	 * @return $vars
	 */
	public function wwp_custom_query_vars_filter( $vars ) {
		$vars[] .= 'attendee_id';
		$vars[] .= 'badge';
		$vars[] .= 'signup_id';
		$vars[] .= 'secret';
		$vars[] .= 'unsubscribe';
		return $vars;
	}


	/**
	 * Adds the one and only menu item for the plugin.
	 */
	public function signup_plugin_top_menu() {
		add_menu_page( '', 'SignUps', 'manage_options', 'sign_ups', array( new SignupSettings(), 'signup_settings_page' ), plugin_dir_url( __FILE__ ) . 'img/frenchie.bmp' );
		add_submenu_page( 'sign_ups', 'Html Editor', 'Descriptions', 'manage_options', 'html_editor', array( new HtmlEditor(), 'load_html_editor' ) );
		add_submenu_page( 'sign_ups', 'Rolling Templates Editor', 'Rolling Templates', 'manage_options', 'template_editor', array( new RollingTemplatesEditor(), 'load_templates_editor' ) );
		add_submenu_page( 'sign_ups', 'Rolling Exceptions Editor', 'Exceptions', 'manage_options', 'exceptions_editor', array( new RollingExceptionsEditor(), 'load_exceptions_editor' ) );
		add_submenu_page( 'sign_ups', 'Payments Report', 'Payments Report', 'manage_options', 'payments_report', array( new PaymentsReview(), 'review_payments' ) );
		add_submenu_page( 'sign_ups', 'Test Page', 'Test Drive', 'manage_options', 'test_page', array( new TestPlugin(), 'load_test_page' ) );
	}

	/**
	 * Adds the CSS that is used to style the admin side plug-in.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_scripts_and_css( $host ) {
		$user_pages   = array();
		$user_pages[] = 'toplevel_page_sign_ups';
		$user_pages[] = 'signups_page_html_editor';
		$user_pages[] = 'signups_page_template_editor';
		$user_pages[] = 'signups_page_exceptions_editor';
		$user_pages[] = 'cncusagereport';
		$user_pages[] = 'signups_page_test_page';
		$user_pages[] = 'signups_page_payments_report';
		if ( ! in_array( $host, $user_pages, true ) ) {
			return;
		}

		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), 1 );
		wp_enqueue_style( 'signup_style' );
		wp_register_style( 'user_signup_style', plugin_dir_url( __FILE__ ) . 'css/users-styles.css', array(), 1 );
		wp_enqueue_style( 'user_signup_style' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'signup_member_script', plugin_dir_url( __FILE__ ) . 'js/signups.js', __FILE__, array( 'jquery' ), '1.0.0.0', false, true );
		wp_enqueue_script( 'signup_cookie_script', plugin_dir_url( __FILE__ ) . 'cookie/node_modules/js-cookie/dist/js.cookie.min.js', array( 'jquery' ), '3.0.5', false, true );
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
	 * @param string $host Who is calling. Always empty for 
	 */
	public function add_users_scripts_and_css( $host ) {
		$user_pages   = array();
		$user_pages[] = 'signup-description-editor';
		$user_pages[] = 'submit-new-signup';
		$user_pages[] = 'signups';
		$user_pages[] = 'cnc-usage-report';
		if ( ! is_page( $user_pages ) ) {
			return;
		}

		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/users-styles.css', array(), 1 );
		wp_enqueue_style( 'signup_style' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'signup_cookie_script', plugin_dir_url( __FILE__ ) . 'cookie/node_modules/js-cookie/dist/js.cookie.min.js', array( 'jquery' ), '3.0.5', false, true );
		wp_enqueue_script( 'signup_member_script', plugin_dir_url( __FILE__ ) . 'js/users-signup.js', array( 'jquery', 'jquery-ui-dialog', 'signup_cookie_script' ), '1.0.0.0', false, true );
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
