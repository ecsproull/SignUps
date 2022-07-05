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

require 'includes/class-scripts.php';
require 'includes/class-signupsbase.php';
require 'includes/class-signupsrestapis.php';
require 'includes/class-dbsignuptables.php';
require 'includes/class-signupsettings.php';
require 'includes/class-classitem.php';
require 'includes/class-sessionitem.php';
require 'includes/class-shortcodes.php';
require 'includes/class-timeexception.php';

/**
 * Main signups class.
 */
class SignupsPlugin {
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
		add_shortcode( 'selectclass', array( new ShortCodes(), 'user_signup' ) );
	}

	/**
	 * Adds the one and only menu item for the plugin.
	 */
	public function signup_plugin_top_menu() {
		add_menu_page( 'SignUps', 'SignUps', 'manage_options', 'SignUps', array( new SignupSettings(), 'signup_settings_page' ), plugins_url( '/signups/img/frenchie.bmp' ) );
	}

	/**
	 * Adds the CSS that is used to style the admin side plug-in.
	 *
	 * @param string $host Who is calling.
	 */
	public function add_scripts_and_css( $host ) {
		if ( 'toplevel_page_SignUps' !== $host ) {
			return;
		}

		wp_register_style( 'signup_bs_style', plugins_url( '/signups/bootstrap/css/bootstrap.min.css' ), array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugins_url( '/signups/css/style.css' ), array(), 1 );
		wp_enqueue_style( 'signup_style' );
		wp_enqueue_script( 'sigup_member_script', plugins_url( 'js/signups.js', __FILE__ ), array( 'jquery' ), '1.0.0.0' );
		wp_localize_script(
			'sigup_member_script',
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
	}
}

$signups = new SignupsPlugin();
