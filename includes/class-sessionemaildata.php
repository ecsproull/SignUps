<?php
/**
 * Summary
 * Data to send out notificiation about an upcomming class.
 *
 * @package signups
 */

/**
 * Data class.
 *
 * @package SignUps
 */
class SessionEmailData {
	/**
	 * Class Title.
	 *
	 * @var mixed
	 */
	public $class_title;

	/**
	 * Class Location.
	 *
	 * @var mixed
	 */
	public $class_location;

	/**
	 * Formatted date and time of the class.
	 *
	 * @var mixed
	 */
	public $date_time_formatted;

	/**
	 * First name of the class contact.
	 *
	 * @var mixed
	 */
	public $class_contact_firstname;

	/**
	 * Last name of the class contact.
	 *
	 * @var mixed
	 */
	public $class_contact_lastname;

	/**
	 * Email of the class contact.
	 *
	 * @var mixed
	 */
	public $class_contact_email;

	/**
	 * Minimum number to have the class.
	 *
	 * @var mixed
	 */
	public $class_minimum;

	/**
	 * Maximum students per sessions.
	 *
	 * @var mixed
	 */
	public $class_slots;

	/**
	 * Class signup id.
	 *
	 * @var mixed
	 */
	public $class_signup_id;

	/**
	 * List of the attendees email addresses
	 *
	 * @var mixed
	 */
	public $attendees;

	/**
	 * List of instructors name and email addresses.
	 *
	 * @var mixed
	 */
	public $instructors;

	/**
	 * List of instructors name and email addresses.
	 *
	 * @var mixed
	 */
	public $class_instructions;

	/**
	 * List of instructors name and email addresses.
	 *
	 * @var mixed
	 */
	public $class_materials;
}
