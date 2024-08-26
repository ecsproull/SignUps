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
		$this->session_item      = 'Attendee';
	}

	/**
	 * Session id.
	 *
	 *  int
	 */
	public $session_id;

	/**
	 * Id of the class that this session belongs to.
	 *
	 *  int
	 */
	public $session_signup_id;

	/**
	 * Contact email for the session.
	 *
	 *  string
	 */
	public $session_contact_email;

	/**
	 * Name of the person in charge of this session.
	 *
	 *  string
	 */
	public $session_contact_name;

	/**
	 * Session start time formatted.
	 *
	 *  string
	 */
	public $session_start_formatted;

	/**
	 * Session end time formatted.
	 *
	 *  string
	 */
	public $session_end_formatted;

	/**
	 * Session location.
	 *
	 * 	public  string
	 */
	public $session_location;

	/**
	 * Deprecated! this was to represent the SignupGenisus id.
	 *
	 *  int
	 */
	public $session_sig_slotitemid;

	/**
	 * Number of slots in this session.
	 * This can vary from the default number of slots for the class.
	 *
	 *  int
	 */
	public $session_slots;

	/**
	 * Minimum number of required attendees.
	 *
	 *  int
	 */
	public $signup_default_minimum;

	/**
	 * Session item name
	 *
	 * 	string
	 */
	public $session_item;

	/**
	 * Default start time specified in the signup
	 *
	 * string
	 */
	public $session_time_of_day;

	/**
	 * Default duration specified in the signup
	 *
	 *  public  string
	 */
	public $session_duration;

	/**
	 * Default number of days between a session
	 *
	 *  int
	 */
	public $session_days_between_sessions;

	/**
	 * Default days of the month. String to be parsed by PHP DateTime.
	 *
	 * string
	 */
	public $session_day_of_month;

	/**
	 * Database id for the calendar item.
	 *
	 *  int
	 */
	public $session_calendar_id;

	/**
	 * Scheduling descriptions. Overrides the automatically
	 * generated schedule description.
	 *
	 *  string
	 */
	public $signup_schedule_desc;
}
