<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Returned via the GetMonitors restful api.
 */
class RollingSlot {
	/**
	 * Slot start date and time.
	 *
	 * @var mixed
	 */
	public $start_time_date;
	/**
	 * Slot item title.
	 *
	 * @var mixed
	 */
	public $item;

	/**
	 * Badge is the slot is filled
	 *
	 * @var mixed
	 */
	public $badge;
}
