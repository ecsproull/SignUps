<?php
/**
 * Summary
 * Map settings.
 *
 * @package     Signups
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages the map settings including adding places to the map.
 */
class SignUpsBase {

	/**
	 * Database attendees table.
	 *
	 * @var mixed
	 */
	protected const ATTENDEES_TABLE = 'wp_scw_attendees';

	/**
	 * Database classes table.
	 *
	 * @var mixed
	 */
	protected const SIGNUPS_TABLE = 'wp_scw_signups';

	/**
	 * Database roster table.
	 *
	 * @var mixed
	 */
	protected const ROSTER_TABLE = 'roster';

	/**
	 * Database sessions table.
	 *
	 * @var mixed
	 */
	protected const SESSIONS_TABLE = 'wp_scw_sessions';

	/**
	 * Database rolling attendees table.
	 *
	 * @var mixed
	 */
	protected const ATTENDEES_ROLLING_TABLE = 'wp_scw_rolling_attendees';

	/**
	 * Rolling signup table.
	 *
	 * @var mixed
	 */
	protected const ROLLING_TABLE = 'wp_scw_rolling';

	/**
	 * Payments table.
	 *
	 * @var mixed
	 */
	protected const PAYMENTS_TABLE = 'wp_scw_payments';

	/**
	 * Stripe table.
	 *
	 * @var mixed
	 */
	protected const STRIPE_TABLE = 'wp_scw_stripe';

	/**
	 * Signup desscriptions table.
	 *
	 * @var mixed
	 */
	protected const SIGNUP_DESCRIPTIONS_TABLE = 'wp_scw_signup_descriptions';

	/**
	 * Format DateTime as 2020-08-13 6:00 am.
	 *
	 * @var mixed
	 */
	protected const DATETIME_FORMAT = 'Y-m-d g:i A';

	/**
	 * Format Date as 2020-08-13.
	 *
	 * @var mixed
	 */
	protected const DATE_FORMAT = 'D Y-m-d';

	/**
	 * Format Date as 2020-08-13.
	 *
	 * @var mixed
	 */
	protected const TIME_FORMAT = 'g:iA';

	/**
	 * Date timezone.
	 *
	 * @var undefined
	 */
	protected $date_time_zone;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->date_time_zone = new DateTimeZone( 'America/Phoenix' );
	}

	/**
	 * Formats a string to be passed back in form data.
	 *
	 * @param int $attendee_id Attendee ID.
	 * @param int $session_id Session ID.
	 */
	protected function session_attendee_string( $attendee_id, $session_id ) {
		echo esc_html( $attendee_id . ',' . $session_id );
	}

	/**
	 * Format a date
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted date as 12 hour time.
	 */
	protected function format_date( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( self::DATETIME_FORMAT );
	}

	/**
	 * Format a date
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted time only.
	 */
	protected function format_time_only( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( 'g:iA' );
	}

	/**
	 * Format a date
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted date only.
	 */
	protected function format_date_only( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( 'Y-m-d' );
	}

	/**
	 * Helper function for registering routes.
	 *
	 * @param  string $namespace The namespace.
	 * @param  string $route End of the route.
	 * @param  object $func The endpoint function.
	 * @param  string $class_inst Instance of the class containing the function.
	 * @param  array  $args Arguments to the api call.
	 * @param  string $method POST, GET....etc.
	 * @return void
	 */
	protected function register_route( $namespace, $route, $func, $class_inst, $args, $method ) {
		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => $method,
				'callback'            => array( $class_inst, $func ),
				'permission_callback' => array( $class_inst, 'permissions_check' ),
				'args'                => $args,
			)
		);
	}

	/**
	 * Return html description for a signup.
	 *
	 * @param int $signup_id The signup id.
	 * @return string Formatted html.
	 */
	protected function get_signup_html( $signup_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE description_signup_id = %1s',
				self::SIGNUP_DESCRIPTIONS_TABLE,
				(int)$signup_id
			),
			OBJECT
		);

		if ( $results ) {
			return html_entity_decode( $results[0]->description_html );
		} else {
			return null;
		}
	}
}
