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
	 * @var class_id.
	 */
	public $class_id;

	/**
	 * Contact email.
	 *
	 * @var class_contact_email.
	 */
	public $class_contact_email;

	/**
	 * Class location.
	 *
	 * @var class_location.
	 */
	public $class_location;

	/**
	 * Contact email..
	 *
	 * @var class_description_url.
	 */
	public $class_description_url;

	/**
	 * Contact email..
	 *
	 * @var class_contact_email.
	 */
	public $class_thumbnail_url;

	/**
	 * Class cost.
	 *
	 * @var class_cost.
	 */
	public $class_cost;

	/**
	 * Class size. Number of slots available.
	 *
	 * @var class_default_slots.
	 */
	public $class_default_slots;

	/**
	 * Class is a continuing signup.
	 *
	 * @var class_rolling.
	 */
	public $class_rolling;
}
