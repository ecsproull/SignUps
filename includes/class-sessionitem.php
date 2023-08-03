<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 */
class SessionItem {

	/**
	 * Constructor.
	 *
	 * @param number $signup_id DB primary key.
	 */
	public function __construct( $signup_id ) {
		$this->session_signup_id = $signup_id;
		$this->session_item = 'Attendee';
	}

	/**
	 * Session id.
	 *
	 * @var session_id.
	 */
	public $session_id;

	/**
	 * Id of the class that this session belongs to.
	 *
	 * @var session_signup_id.
	 */
	public $session_signup_id;

	/**
	 * Contact email for the session.
	 *
	 * @var session_contact_email.
	 */
	public $session_contact_email;

	/**
	 * Name of the person in charge of this session.
	 *
	 * @var session_contact_name.
	 */
	public $session_contact_name;

	/**
	 * Session start time formatted.
	 *
	 * @var session_start_formatted.
	 */
	public $session_start_formatted;

	/**
	 * Session end time formatted..
	 *
	 * @var session_end_formatted.
	 */
	public $session_end_formatted;

	/**
	 * Session location.
	 *
	 * @var session_location.
	 */
	public $session_location;

	/**
	 * Deprecated! this was to represent the SignupGenisus id.
	 *
	 * @var session_sig_slotitemid.
	 */
	public $session_sig_slotitemid;

	/**
	 * Number of slots in this session.
	 * This can vary from the default number of slots for the class.
	 *
	 * @var session_slots.
	 */
	public $session_slots;

	/**
	 * Session item name
	 *
	 * @var session_item.
	 */
	public $session_item;

	/**
	 * Default start time specified in the signup
	 *
	 * @var session_default_start_time.
	 */
	public $session_default_start_time;
	
	/**
	 * Default duration specified in the signup
	 *
	 * @var session_default_duration.
	 */
	public $session_duration;
	
	/**
	 * Default number of days between a session
	 *
	 * @var session_default_days_between_sessions.
	 */
	public $session_days_between_sessions;
	
	/**
	 * Default days of the month. String to be parsed by PHP DateTime.
	 *
	 * @var session_default_day_of_month.
	 */
	public $session_day_of_month;
	
}