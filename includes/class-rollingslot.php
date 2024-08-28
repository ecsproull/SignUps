<?php
/*
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
	 *  mixed
	 */
	public $start_time_date;

	/**
	 * Slot start date and time.
	 *
	 *  mixed
	 */
	public $start_time;

	/**
	 * Slot item title.
	 *
	 *  mixed
	 */
	public $item;

	/**
	 * Badge is the slot is filled
	 *
	 *  mixed
	 */
	public $badge;

	/**
	 * Members first name.
	 *
	 *  mixed
	 */
	public $first_name;

	/**
	 * Members last name.
	 *
	 *  mixed
	 */
	public $last_name;

	/**
	 * Members last name.
	 *
	 *  mixed
	 */
	public $email;
}
