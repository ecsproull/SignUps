<?php
/*
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database wp_scw_signups table.
 * Used for creating new signup items to be added to the DB.
 */
class ClassItem {

	/**
	 * The primary key.
	 *
	 *  int
	 */
	public $signup_id;

	/**
	 * The name or title of the class.
	 *
	 *  string
	 */
	public $signup_name;

	/**
	 * Contact email.
	 *
	 *  string
	 */
	public $signup_contact_email;

	/**
	 * Class location.
	 *
	 *  string
	 */
	public $signup_location;

	/**
	 * Url for the description.
	 *
	 *  string
	 */
	public $signup_description_url;

	/**
	 * Thumbnail location.
	 *
	 *  string
	 */
	public $signup_thumbnail_url;

	/**
	 * Class cost.
	 *
	 *  int
	 */
	public $signup_cost;

	/**
	 * Class size. Number of slots available.
	 *
	 *  int
	 */
	public $signup_default_slots;

	/**
	 * Rolling template id or zero if not a rolling class.
	 *
	 *  int
	 */
	public $signup_rolling_template;
}
