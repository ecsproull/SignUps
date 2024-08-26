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
	 * int
	 */
	public $attendee_id;

	/**
	 * The session id.
	 * 
	 * int
	 */
	public $attendee_session_id;

	/**
	 * Email.
	 * 
	 * string
	 */
	public $attendee_email;

	/**
	 * Phone Number.
	 * 
	 * string
	 */
	public $attendee_phone;

	/**
	 * Amount Paid.
	 * 
	 * string
	 */
	public $attendee_paid_amount;

	/**
	 * Last name.
	 * 
	 * string
	 */
	public $attendee_lastname;

	/**
	 * First name.
	 * 
	 * string
	 */
	public $attendee_firstname;

	/**
	 * Item.
	 * 
	 * string
	 */
	public $attendee_item;

	/**
	 * Badge.
	 * 
	 * string
	 */
	public $attendee_badge;

	/**
	 * Secret.
	 * 
	 * string
	 */
	public $attendee_secret;
}
