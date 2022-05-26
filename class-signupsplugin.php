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

require 'includes/class-dbsignuptables.php';
require 'includes/class-signupsettings.php';
require 'includes/class-classitem.php';
require 'includes/class-sessionitem.php';

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
	}

	/**
	 * Adds the one and only menu item for the plugin.
	 */
	public function signup_plugin_top_menu() {
		add_menu_page( 'SignUps', 'SignUps', 'manage_options', 'SignUps', array( new SignupSettings(), 'signup_settings_page' ), plugins_url( '/signups/img/frenchie.bmp' ) );
	}

	/**
	 * Adds the CSS that is used to style the plug-in.
	 */
	public function add_scripts_and_css() {
		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), 1 );
		wp_enqueue_style( 'signup_style' );
	}
}

$signups = new SignupsPlugin();
