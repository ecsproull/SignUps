<?php
/**
 * Summary
 * Map settings.
 *
 * @package     Signups
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages the map settings including adding places to the map.
 */
class SignUpsBase {

	/**
	 * Database attendees table.
	 *
	 * @var mixed
	 */
	protected const ATTENDEES_TABLE = 'wp_scw_attendees';

	/**
	 * Database classes table..
	 *
	 * @var mixed
	 */
	protected const SIGNUPS_TABLE = 'wp_scw_signups';

	/**
	 * Database roster table.
	 *
	 * @var mixed
	 */
	protected const ROSTER_TABLE = 'roster';

	/**
	 * Database sessions table..
	 *
	 * @var mixed
	 */
	protected const SESSIONS_TABLE = 'wp_scw_sessions';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

	}
}
