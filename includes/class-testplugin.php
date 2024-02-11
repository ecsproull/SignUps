<?php
/*
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages the map settings including adding places to the map.
 */
class TestPlugin extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Load the exceptions editor
	 *
	 * @return void
	 */
	public function load_test_page() {
		echo do_shortcode( '[scw_selectclass]' );
	}
}
