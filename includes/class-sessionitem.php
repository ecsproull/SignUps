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
	 * @param number $class_id DB primary key.
	 */
	public function __construct( $class_id ) {
		$this->session_class_id = $class_id;
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
	 * @var session_class_id.
	 */
	public $session_class_id;

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
}

