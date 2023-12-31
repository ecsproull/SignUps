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

use Twilio\Rest\Client;

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
						'From' => array(
							'description'       => esc_html( 'Member badge number' ),
							'type'              => 'string',
							'validate_callback' => array( $this, 'verify_badge_param' ),
						),
					),
					WP_REST_Server::READABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/text',
					'recieve_text',
					$this,
					array(
						'badge' => array(
							'description'       => esc_html( 'Endpoint for text messages' ),
							'type'              => 'string',
							'validate_callback' => array( $this, 'verify_phone_number' ),
						),
					),
					WP_REST_Server::ALLMETHODS
				);
			}
		);
	}

	/**
	 * Endpoint for twilio to post a text message.
	 *
	 * @param  mixed $request Parameters for the request.
	 * @return void
	 */
	public function recieve_text( $request ) {
		global $wpdb;
		$data    = $request->get_params();
		$sid    = getenv( "TWILIO_ACCOUNT_SID" );
		$token  = getenv( "TWILIO_AUTH_TOKEN" );
		$twilio = new Client( $sid, $token );
		if ( $data['AccountSid'] !== $sid  || ! $data['From'] ) {
			return;
		}

		$phone_number = preg_replace( '~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1-$2-$3', $data['From'] );
		$now          = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
		$insert_data  = array(
			'text_body'       => esc_html( $data['Body'] ),
			'text_from_phone' => $phone_number,
			'text_date_time'  => $now->format( self::DATETIME_FORMAT ),
		);

		$wpdb->insert( self::TEXT_TABLE, $insert_data );

		$server       = 'WC_SERVER\\SQLEXPRESS';
		$database     = 'WoodClub';
		$username     = 'memberapp';
		$password     = 'member';
		$handle       = new PDO( "sqlsrv:Server=$server;Database=$database;", $username, $password );
		$handle->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$statement = $handle->prepare( "SELECT Badge, FirstName, LastName, Email from MemberRoster WHERE Phone = '$phone_number'" );
		$result    = $statement->execute();
		if ( $result ) {
			$all = $statement->fetchAll( PDO::FETCH_ASSOC );
			if ( $all ) {
				$message = $twilio->messages->create(
					'+14253513207',
					[
						"body" => $data['From'] . ' - ' . $all[0]['Badge'] . ' - ' . $all[0]['FirstName'] . ' - ' . $all[0]['LastName'] . ' - ' . $all[0]['Email'] . ' Msg:' . $data['Body'],
						"from" => '+16233049716'
					]
				);
			}
		}
	}

	public function verify_phone_number( $data ) {
		return true;
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
