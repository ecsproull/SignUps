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
 * User
 */
class User {
	/**
	 * Member Badge
	 *
	 * @var mixed
	 */

	public $badge;
	/**
	 * First Name
	 *
	 * @var mixed
	 */
	public $first;

	/**
	 * Last Name
	 *
	 * @var mixed
	 */
	public $last;
}
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
					'/monitors',
					'get_monitors',
					$this,
					array(),
					WP_REST_Server::READABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/members',
					'get_member',
					$this,
					array(),
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
					array (
						'description'       => esc_html( 'Endpoint for text messages' ),
						'type'              => 'string',
						'validate_callback' => array( $this, 'verify_phone_number' ),
					),
					WP_REST_Server::ALLMETHODS
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/members',
					'recieve_members',
					$this,
					array(
						'description'       => esc_html( 'Endpoint to update the members database' ),
						'type'              => 'string',
						'validate_callback' => array( $this, 'verify_member_data' ),
					),
					WP_REST_Server::CREATABLE,
					'verify_member_data'
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/cookies',
					'set_member_cookie',
					$this,
					array(),
					WP_REST_Server::READABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/unsubscribe',
					'unsubscribe_list',
					$this,
					array(),
					WP_REST_Server::CREATABLE
				);
			}
		);
	}

	public function unsubscribe_list( $data ) {
		global $wpdb;
		$key      = '8c62a157-7ee8-4104-9f91-930eac39fe2f';
		$data_obj = json_decode( $data->get_body(), false );
		if ( $data_obj->key !== $key ) {
			return;
		}

		if ( 'get' === $data_obj->action ) {
			$list = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE unsubscribe_complete = false',
					self::UNSUBSCRIBE_TABLE
				),
				OBJECT
			);

			return $list;
		} elseif ( 'done' === $data_obj->action ) {
			foreach ( $data_obj->unsubscribe_secret as $secret ) {
				$data                         = array();
				$data['unsubscribe_complete'] = true;
				$where                        = array();
				$where['unsubscribe_key']     = $secret;
				$wpdb->update( self::UNSUBSCRIBE_TABLE, $data, $where );
			}
		}
	}

	/**
	 * Gets the monitors for the specified date.
	 *
	 * @param  mixed $request Request from an AJAX call.
	 * @return mixed $results the results of the query.
	 */
	public function get_monitors( $request ) {
		global $wpdb;
		$date    = $request['date'];
		$pattern = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/ms';
		if ( preg_match( $pattern, $date ) ) {
			$members = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT attendee_badge, attendee_item, attendee_start_formatted
					FROM %1s
					WHERE attendee_signup_id = 6 && attendee_start_formatted LIKE  %s',
					self::ATTENDEES_ROLLING_TABLE,
					$wpdb->esc_like( $date ) . '%'
				),
				OBJECT
			);

			return $members;
		} else {
			return 'nice try';
		}
	}

	/**
	 * Set a members badge as a cookie.
	 *
	 * @param  mixed $request Request from an AJAX call.
	 * @return void
	 */
	public function set_member_cookie( $request) {
		$nonce    = $request->get_header( 'X-WP-Nonce' );
		$verified = wp_verify_nonce( $nonce, 'wp_rest' );
		if ( $verified ) {
			if ( $request['badge'] ) {
				setcookie( 'signups_scw_badge', $request['badge'] );
			} else {
				unset( $_COOKIE['signups_scw_badge'] );
			}
		}
	}

	/**
	 * 
	 * Endpoint to recieve the member list needed for signups.
	 *
	 * @param  mixed $data json list of members or permissions.
	 * @return void
	 */
	public function recieve_members( $data ) {
		global $wpdb;
		$key      = '8c62a157-7ee8-4104-9f91-930eac39fe2f';
		$data_obj = json_decode( $data->get_body(), false );
		if ( $data_obj->key !== $key ) {
			return;
		}
		$length   = count( $data_obj->members );
		for ( $i = 0; $i < $length; $i++ ) {
			$member = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE member_badge = %s',
					self::MEMBERS_TABLE,
					$data_obj->members[ $i ]->badge
				),
				OBJECT
			);

			$data = array();
			$data['member_badge']     = $data_obj->members[ $i ]->badge;
			$data['member_lastname']  = $data_obj->members[ $i ]->last;
			$data['member_firstname'] = $data_obj->members[ $i ]->first;
			$data['member_phone']     = $data_obj->members[ $i ]->phone;
			$data['member_email']     = $data_obj->members[ $i ]->email;
			$data['member_secret']    = $data_obj->members[ $i ]->secret;

			if ( $member ) {
				$wpdb->update( self::MEMBERS_TABLE, $data, $member->badge );
			} else {
				$wpdb->insert( self::MEMBERS_TABLE, $data );
			}
		}

		$length = count( $data_obj->permissions );
		for ( $i = 0; $i < $length; $i++ ) {
			$permission = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE permission_badge = %s && permission_machine_id = %d',
					self::MACHINE_PERMISSIONS_TABLE,
					$data_obj->permissions[ $i ]->badge,
					$data_obj->permissions[ $i ]->macine_name
				),
				OBJECT
			);

			if ( ! $permission ) {
				$data                          = array();
				$data['permission_badge']      = $data_obj->permissions[ $i ]->badge;
				$data['permission_machine_name'] = $data_obj->permissions[ $i ]->machine_name;
				$wpdb->insert( self::MACHINE_PERMISSIONS_TABLE, $data );
			}
		}
	}

	public function verify_member_data( $data ) {
		return true;
	}

	/**
	 * Endpoint for twilio to post a text message.
	 *
	 * @param  mixed $request Parameters for the request.
	 * @return void
	 */
	public function recieve_text( $request ) {
		global $wpdb;
		$data   = $request->get_params();
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

		$server   = 'WC_SERVER\\SQLEXPRESS';
		$database = 'WoodClub';
		$username = 'memberapp';
		$password = 'member';
		$handle   = new PDO( "sqlsrv:Server=$server;Database=$database;", $username, $password );
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
		$nonce    = $request->get_header( 'X-WP-Nonce' );
		$verified = wp_verify_nonce( $nonce, 'wp_rest' );
		$pattern  = '/^[0-9]{4}$/ms';
		if ( $verified && preg_match( $pattern, $request['badge'] ) ) {
			try {
				global $wpdb;
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s
						WHERE member_badge = %s',
						self::MEMBERS_TABLE,
						$request['badge']
					),
					OBJECT
				);

				if ( $results ) {
					if ( $request['user-groups'] ) {
						$permission = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT * FROM %1s
								WHERE permission_badge = %s && permission_machine_name = %s',
								self::MACHINE_PERMISSIONS_TABLE,
								$request['badge'],
								$request['user-groups']
							),
							OBJECT
						);

						if ( $permission ) {
							return $results;
						} else {
							return new WP_REST_Response( 'Permission Denied.', 401 );
						}
					} else {
						return $results;
					}
				} else {
					return new WP_REST_Response( array( 'message' => 'Badge not found.' ), 400 );
				}
 
				return $results;

			} catch ( Exception $e ) {
				return $e->getMessage();
			}
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
}
