<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database Class object.
 * Used for creating new attendee items to be added to the DB.
 */
class AttendeeItem {

	/**
	 * The primary key.
	 *
	 * @var number
	 */
	public $attendee_id;

	/**
	 * The session id.
	 *
	 * @var number
	 */
	public $attendee_session_id;

	/**
	 * Email.
	 *
	 * @var string
	 */
	public $attendee_email;

	/**
	 * Phone Number.
	 *
	 * @var string
	 */
	public $attendee_phone;

	/**
	 * Amount Paid.
	 *
	 * @var number
	 */
	public $attendee_paid_amount;

	/**
	 * Last name.
	 *
	 * @var string
	 */
	public $attendee_lastname;

	/**
	 * First name.
	 *
	 * @var string
	 */
	public $attendee_firstname;

	/**
	 * Item.
	 *
	 * @var object
	 */
	public $attendee_item;

	/**
	 * Badge.
	 *
	 * @var string
	 */
	public $attendee_badge;

	/**
	 * Secret.
	 *
	 * @var string
	 */
	public $attendee_secret;
}
