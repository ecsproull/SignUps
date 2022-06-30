<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database Class object.
 * Used for creating new signup items to be added to the DB.
 */
class ClassItem {

	/**
	 * The primary key.
	 *
	 * @var signup_id.
	 */
	public $signup_id;

	/**
	 * The name or title of the class.
	 *
	 * @var signup_name.
	 */
	public $signup_name;

	/**
	 * Contact email.
	 *
	 * @var signup_contact_email.
	 */
	public $signup_contact_email;

	/**
	 * Class location.
	 *
	 * @var signup_location.
	 */
	public $signup_location;

	/**
	 * Contact email..
	 *
	 * @var signup_description_url.
	 */
	public $signup_description_url;

	/**
	 * Contact email..
	 *
	 * @var signup_contact_email.
	 */
	public $signup_thumbnail_url;

	/**
	 * Class cost.
	 *
	 * @var signup_cost.
	 */
	public $signup_cost;

	/**
	 * Class size. Number of slots available.
	 *
	 * @var signup_default_slots.
	 */
	public $signup_default_slots;

	/**
	 * Class is a continuing signup.
	 *
	 * @var signup_rolling.
	 */
	public $signup_rolling;
}
