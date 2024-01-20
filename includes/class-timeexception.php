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
	 * Template that it pertains to.
	 *
	 * @var mixed
	 */
	public $template;
	/**
	 * Reason for the exception.
	 *
	 * @var mixed
	 */
	public $reason;

	/**
	 * begin time
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