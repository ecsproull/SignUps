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
	 * Slot start date and time.
	 *
	 * @var mixed
	 */
	public $start_time;

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

	/**
	 * Members first name.
	 *
	 * @var mixed
	 */
	public $first_name;

	/**
	 * Members last name.
	 *
	 * @var mixed
	 */
	public $last_name;

	/**
	 * Members last name.
	 *
	 * @var mixed
	 */
	public $email;
}
