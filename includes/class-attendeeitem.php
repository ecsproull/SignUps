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
	 * @var attendee_id.
	 */
	public $attendee_id;

	/**
	 * The session id.
	 *
	 * @var attendee_session_id.
	 */
	public $attendee_session_id;

	/**
	 * Email.
	 *
	 * @var attendee_email.
	 */
	public $attendee_email;

	/**
	 * Phone Number.
	 *
	 * @var attendee_phone.
	 */
	public $attendee_phone;

	/**
	 * Amount Paid.
	 *
	 * @var attendee_paid_amount.
	 */
	public $attendee_paid_amount;

	/**
	 * Last name.
	 *
	 * @var attendee_lastname.
	 */
	public $attendee_lastname;

	/**
	 * First name.
	 *
	 * @var attendee_firstname.
	 */
	public $attendee_firstname;

	/**
	 * Item.
	 *
	 * @var attendee_item.
	 */
	public $attendee_item;

	/**
	 * Badge.
	 *
	 * @var attendee_badge.
	 */
	public $attendee_badge;

	/**
	 * Secret.
	 *
	 * @var attendee_Secret.
	 */
	public $attendee_secret;
}
