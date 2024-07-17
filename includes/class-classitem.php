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
	 * @var int
	 */
	public $signup_id;

	/**
	 * The name or title of the class.
	 *
	 * @var string
	 */
	public $signup_name;

	/**
	 * Contact email.
	 *
	 * @var string
	 */
	public $signup_contact_email;

	/**
	 * Class location.
	 *
	 * @var string
	 */
	public $signup_location;

	/**
	 * Url for the description.
	 *
	 * @var string
	 */
	public $signup_description_url;

	/**
	 * Thumbnail location.
	 *
	 * @var string
	 */
	public $signup_thumbnail_url;

	/**
	 * Class cost.
	 *
	 * @var int
	 */
	public $signup_cost;

	/**
	 * Class size. Number of slots available.
	 *
	 * @var int
	 */
	public $signup_default_slots;

	/**
	 * Rolling template id or zero if not a rolling class.
	 *
	 * @var int
	 */
	public $signup_rolling_template;
}
