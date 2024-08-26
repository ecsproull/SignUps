<?php
/**
 * Summary
 * Data to send out notificiation about an upcomming class.
 *
 * @package SignUps
 */

/**
 * Data only class for session email data.
 *
 */
class SessionEmailData {
	/**
	 * Class Title.
	 *
	 *  string
	 */
	public $class_title;

	/**
	 * Class Location.
	 *
	 *  string
	 */
	public $class_location;

	/**
	 * Formatted date and time of the class.
	 *
	 *  string
	 */
	public $date_time_formatted;

	/**
	 * First name of the class contact.
	 *
	 *  string
	 */
	public $class_contact_firstname;

	/**
	 * Last name of the class contact.
	 *
	 *  string
	 */
	public $class_contact_lastname;

	/**
	 * Email of the class contact.
	 *
	 *  string
	 */
	public $class_contact_email;

	/**
	 * Minimum number to have the class.
	 *
	 *  int
	 */
	public $class_minimum;

	/**
	 * Maximum students per sessions.
	 *
	 *  int
	 */
	public $class_slots;

	/**
	 * Class signup id.
	 *
	 *  int
	 */
	public $class_signup_id;

	/**
	 * List of the attendees email addresses
	 *
	 *  array,string
	 */
	public $attendees;

	/**
	 * List of instructors name and email addresses.
	 *
	 *  array,string
	 */
	public $instructors;

	/**
	 * List of instructors name and email addresses.
	 *
	 *  array,string
	 */
	public $class_instructions;

	/**
	 * List of instructors name and email addresses.
	 *
	 *  array,string
	 */
	public $class_materials;
}
