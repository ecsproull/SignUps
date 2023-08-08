<?php
/**
 * Summary
 * Place class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Create the database tables on activation.
 */
class SignUpsRestApis extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/members',
					'get_member',
					$this,
					array(
						'badge' => array(
							'description'       => esc_html( 'Member badge number' ),
							'type'              => 'string',
							'validate_callback' => array( $this, 'verify_badge_param' ),
						),
					),
					WP_REST_Server::READABLE
				);
			}
		);
	}

	/**
	 * The actual function that does the work of retrieving the points.
	 *
	 * @param string $request Members badge number.
	 * @return array The results of the query.
	 */
	public function get_member( $request ) {
		try {
			global $wpdb;
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1s WHERE badge = %1s && FIND_IN_SET( %s, `groups`)',
					self::ROSTER_TABLE,
					$request['badge'],
					$request['user-groups']
				),
				OBJECT
			);

			return $results;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * No permissions needed.
	 *
	 * @return boolean Always returns true.
	 */
	public function permissions_check() {
		return true;
	}

	/**
	 * Verify that the badge parameter is valid.
	 *
	 * @param  string $value Value of the badge.
	 * @param  mixed  $request The request object.
	 * @param  mixed  $param The name of the parameter.
	 * @return boolean
	 */
	public function verify_badge_param( $value, $request, $param ) {
		$pattern = '/^[0-9]{4}$/ms';
		if ( ! preg_match( $pattern, $value ) ) {
			return new WP_Error( 'Bad data format', esc_html__( 'OMG you cannot pass that crap for a badge number.', 'my-text-domain' ), array( 'status' => 402 ) );
		}

		return true;
	}
}
