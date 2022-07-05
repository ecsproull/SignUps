<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * TimeException for rolling signups
 */
class TimeException {
	/**
	 * Gegin time
	 *
	 * @var mixed
	 */

	public $begin;
	/**
	 * End time
	 *
	 * @var mixed
	 */
	public $end;
}