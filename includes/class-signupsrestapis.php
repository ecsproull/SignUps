<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

use Twilio\Rest\Client;

/**
 * User Data
 */
class User {
	/**
	 * Member Badge
	 *
	 *  string
	 */
	public $badge;
	
	/**
	 * First Name
	 *
	 *  string
	 */
	public $first;

	/**
	 * Last Name
	 *
	 *  string
	 */
	public $last;
}

/**
 * Create the database tables on activation.
 */
class SignUpsRestApis extends SignUpsBase {

	/**
	 * Stripe payments object.
	 *
	 *  object
	 */
	private $stripe_payments;

	/**
	 * __construct
	 * All of routes for the RestFul APIs supported in this class are registered here.
	 *
	 */
	public function __construct() {
		$this->stripe_payments = new StripePayments();
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
					'receive_text',
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
					'receive_members',
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

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/orientation',
					'get_orientation_list',
					$this,
					array(),
					WP_REST_Server::CREATABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/attendees',
					'get_attendee_list',
					$this,
					array(),
					WP_REST_Server::CREATABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/reminders',
					'get_class_reminder_email_data',
					$this,
					array(),
					WP_REST_Server::CREATABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/class-status',
					'get_class_status',
					$this,
					array(),
					WP_REST_Server::CREATABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route(
					'scwmembers/v1',
					'/search',
					'search_members',
					$this,
					array(),
					WP_REST_Server::READABLE
				);
			}
		);

		add_action(
			'rest_api_init',
			array( $this, 'register_payment_route' )
		);
	}

	/**
	 * Get the status of a class for an instructor. When creating the reminder
	 * emails concerning classes this is used to get the class status. This 
	 * information is usually used to inform the instructors of the class.
	 *
	 * @param  mixed $request Request data.
	 * @return mixed Class contacts and number of students signed up.
	 */
	public function get_class_status( $request ) {
		global $wpdb;
		$key = '8c62a157-7fe8-4105-9f91-932eac39fe2g';
		if ( $request['key'] !== $key ) {
			return new WP_REST_Response( 'Nice Try.', 401 );
		}

		$dt       = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
		$interval = DateInterval::createFromDateString( '2 days' );
		$dt->add( $interval );
		$date = $dt->format( self::DATE_FORMAT3 );

		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %1s
				WHERE session_start_formatted LIKE %s',
				self::SESSIONS_TABLE,
				$wpdb->esc_like( $date ) . '%'
			),
			OBJECT
		);

		$results = array();
		foreach ( $sessions as $session ) {
			$session_data = $this->get_session_email_data( $session->session_id );
			$results[]    = $session_data[0];
		}

		return $results;
	}

	/**
	 * Get data to send reminder emails.
	 *
	 * @param  mixed $request Request data.
	 * @return mixed All the data for each session that should be notified.
	 */
	public function get_class_reminder_email_data( $request ) {
		global $wpdb;
		$key = '8c62a157-7fe8-4105-9f91-932eac39fe2g';
		if ( $request['key'] !== $key ) {
			return new WP_REST_Response( 'Nice Try.', 401 );
		}

		return $this->get_session_email_data();
	}

	/**
	 * Search the member database table for a member. This is used from the admin
	 * pages to allow easy look up of a member. The search only requires part of a
	 * name, badge, email or phone number to locate a matching list of members to choose from.
	 *
	 * @param  mixed $request Posted data to search with.
	 * @return object
	 */
	public function search_members( $request ) {
		global $wpdb;
		$key      = '9523a157-8ee7-5401-9f91-abccea39fe2f';
		if ( $request['key'] !== $key || ! current_user_can( 'administrator' ) ) {
			return new WP_REST_Response( 'Unauthorized.', 401 );
		}

		$pattern = '/^[a-zA-Z0-9@\.]{3,}$/ms';
		if ( preg_match( $pattern, $request['text'] ) ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1s
					WHERE member_badge LIKE %s OR 
						member_firstname LIKE %s OR
						member_lastname LIKE %s OR
						member_email LIKE %s',
					self::MEMBERS_TABLE,
					'%' . $wpdb->esc_like( $request['text'] ) . '%',
					'%' . $wpdb->esc_like( $request['text'] ) . '%',
					'%' . $wpdb->esc_like( $request['text'] ) . '%',
					'%' . $wpdb->esc_like( $request['text'] ) . '%'
				),
				OBJECT
			);

			return $results;
		}

		return new WP_REST_Response( 'Bad search string.', 409 );
	}

	/**
	 * Get the attendees for an upcoming class. This is used for sending the 
	 * email reminders to class attendees.
	 *
	 * @param  mixed $data Posted data that tells the date for the classes.
	 * @return void
	 */
	public function get_attendee_list( $data ) {
		global $wpdb;
		$key      = '8c52a157-8ee7-5401-9f91-930cea39fe2f';
		$data_obj = json_decode( $data->get_body(), false );
		if ( $data_obj->key !== $key ) {
			return;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT wp_scw_attendees.attendee_email,
					wp_scw_attendees.attendee_firstname,
					wp_scw_attendees.attendee_lastname,
					wp_scw_signups.signup_name,
					wp_scw_sessions.session_start_formatted
				FROM wp_scw_attendees
				LEFT JOIN wp_scw_sessions
				ON wp_scw_sessions.session_id = wp_scw_attendees.attendee_session_id
				LEFT JOIN wp_scw_signups
				ON wp_scw_signups.signup_id =  wp_scw_sessions.session_signup_id
				WHERE wp_scw_sessions.session_start_time > %s AND wp_scw_sessions.session_start_time < %s',
				$data_obj->start_date,
				$data_obj->end_date
			),
			OBJECT
		);

		return $results;
	}

	/**
	 * Get's the next orientation attendee list. This is used when importing
	 * new members into the shop and QB database.
	 *
	 * @param  mixed $data Data for the request.
	 * @return void
	 */
	public function get_orientation_list( $data ) {
		global $wpdb;
		$key      = '8c62a157-7ee8-5401-9f91-930eac39fe2f';
		$data_obj = json_decode( $data->get_body(), false );
		if ( $data_obj->key !== $key ) {
			return;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT wp_scw_new_member.new_member_rec_card,
					wp_scw_new_member.new_member_first,
					wp_scw_new_member.new_member_last,
					wp_scw_new_member.new_member_phone,
					wp_scw_new_member.new_member_email,
					wp_scw_new_member.new_member_street,
					wp_scw_sessions.session_start_formatted
				FROM  wp_scw_new_member
				LEFT JOIN wp_scw_attendees
				ON wp_scw_attendees.attendee_badge = wp_scw_new_member.new_member_id
				LEFT JOIN wp_scw_sessions
				ON wp_scw_sessions.session_id = wp_scw_attendees.attendee_session_id
				WHERE wp_scw_sessions.session_start_time > %s AND wp_scw_sessions.session_start_time < %s',
				$data_obj->start_date,
				$data_obj->end_date
			),
			OBJECT
		);

		return $results;
	}

	/**
	 * Registers the route used for Stripe.com callback.
	 *
	 * @return void
	 */
	public function register_payment_route() {
		register_rest_route(
			'scwmembers/v1',
			'/payments',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->stripe_payments, 'payment_event' ),
				'permission_callback' => array( $this->stripe_payments, 'permissions_check' ),
			)
		);
	}
	
	/**
	 * Get the list of members wishing to unsubscribe from the nag mailer.
	 * This function is also used to clear the list after they have been unsubscribed.
	 * The list of who is subscribed and unsubscribed is kept in the shop database. The 
	 * nag mailer application updates that list based on the data requested here.
	 *
	 * @param  mixed $data
	 * @return void
	 */
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
					WHERE unsubscribe_complete = 0',
					self::UNSUBSCRIBE_TABLE
				),
				OBJECT
			);

			return $list;
		} elseif ( 'done' === $data_obj->action ) {
			foreach ( $data_obj->unsubscribe_secret as $secret ) {
				$data                         = array();
				$data['unsubscribe_complete'] = 1;
				$where                        = array();
				$where['unsubscribe_key']     = $secret;
				$wpdb->update( self::UNSUBSCRIBE_TABLE, $data, $where );
			}

			foreach ( $data_obj->unsubscribe_secret_failed as $secret ) {
				$data                         = array();
				$data['unsubscribe_complete'] = -1;
				$where                        = array();
				$where['unsubscribe_key']     = $secret;
				$wpdb->update( self::UNSUBSCRIBE_TABLE, $data, $where );
			}
		}
	}

	/**
	 * Gets the monitors for the specified date. Again, this is used by the nag mailer 
	 * to get the data for the email.
	 *
	 * @param  mixed $request The request data that contains the date of interest.
	 * @return mixed $results the results of the query.
	 */
	public function get_monitors( $request ) {
		global $wpdb;
		$signup_id = 1;
		$date        = $request['date'];
		$pattern     = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/ms';
		if ( preg_match( $pattern, $date ) ) {
			$date_time = new DateTime( $date );
			$temp_id = 1;

			// When this is in use it should be checking the request date.
			if ( $date_time > new Datetime( "9/21/2024") ) {
				$temp_id = 1;
			}
			$templates  = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE template_item_template_id = %d',
					self::SIGNUP_TEMPLATE_ITEM_TABLE,
					$temp_id
				),
				OBJECT
			);

			$exc_end_date    = clone $date_time;
			$time_exceptions = $this->create_meeting_exceptions( $date_time, $exc_end_date->add( new DateInterval( 'P1D' ) ) );
			$attendees       = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wp_scw_rolling_attendees.attendee_badge,
						wp_scw_rolling_attendees.attendee_firstname,
						wp_scw_rolling_attendees.attendee_lastname,
						wp_scw_rolling_attendees.attendee_item,
						wp_scw_rolling_attendees.attendee_start_formatted,
						wp_scw_members.member_email
					FROM %1s
					LEFT JOIN %1s ON wp_scw_rolling_attendees.attendee_badge = wp_scw_members.member_badge
					WHERE attendee_signup_id = %d && attendee_start_formatted LIKE  %s',
					self::ATTENDEES_ROLLING_TABLE,
					self::MEMBERS_TABLE,
					$signup_id,
					$wpdb->esc_like( $date ) . '%'
				),
				OBJECT
			);

			$slots       = array();
			$day_of_week = $date_time->format( 'N' );
			foreach ( $templates as $template ) {
				if ( str_contains( $template->template_item_day_of_week, (string) $day_of_week ) ) {
					$start_time_parts = explode( ':', $template->template_item_start_time );
					$duration_parts   = explode( ':', $template->template_item_duration );
					$start_hour       = $start_time_parts[0];
					$start_minutes    = $start_time_parts[1];
					$start_date       = $date_time->SetTime( $start_hour, $start_minutes );
					$duration         = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
					for ( $i = 0; $i < $template->template_item_shifts; $i++ ) {
						$skip_slot = false;
						foreach ( $time_exceptions as $exception ) {
							if ( $start_date >= $exception->begin &&
								$start_date < $exception->end &&
								( $exception->template === $template ||
								'0' === $exception->template ) ) {
								$start_date = $start_date->add( $duration );
								$skip_slot = true;
								continue;
							}
						}

						if ( ! $skip_slot ) {
							$slot_attendees = array();
							foreach ( $attendees as $attendee ) {
								if ( $start_date->format( self::DATETIME_FORMAT ) === $attendee->attendee_start_formatted &&
									$attendee->attendee_item === $template->template_item_title ) {
										$slot_attendees[] = $attendee;
								}
							}
							for ( $j = 0; $j < $template->template_item_slots; $j++ ) {
								$rolling_slot                  = new RollingSlot();
								$rolling_slot->start_time_date = $start_date->format( self::DATETIME_FORMAT );
								$rolling_slot->start_time      = strtotime( $rolling_slot->start_time_date );
								$rolling_slot->item            = $template->template_item_title;

								if ( $j < count( $slot_attendees ) ) {
									$rolling_slot->badge      = $slot_attendees[ $j ]->attendee_badge;
									$rolling_slot->first_name = $slot_attendees[ $j ]->attendee_firstname;
									$rolling_slot->last_name  = $slot_attendees[ $j ]->attendee_lastname;
									$rolling_slot->email      = $slot_attendees[ $j ]->member_email;
								}

								$slots[]    = $rolling_slot;
							}
							$start_date = $start_date->add( $duration );
						}
					}
				}
			}

			return $slots;
		} else {
			return 'nice try';
		}
	}

	/**
	 * Set a members badge as a cookie on the server.
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
	 * Endpoint to receive the current member list.
	 * Once a day this is called to first clear all members
	 * and then add the current list back. This assures that anyone
	 * removed from the server database is removed from the website list.
	 *
	 * @param  mixed $data json list of members or permissions.
	 * @return void
	 */
	public function receive_members( $data ) {
		global $wpdb;
		$dt_now = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
		$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Begin member update ' . $dt_now->format( self::DATETIME_FORMAT_INPUT ) );
		$key      = '8c62a157-7ee8-4104-9f91-930eac39fe2f';
		$data_obj = json_decode( $data->get_body(), false );
		if ( $data_obj->key !== $key ) {
			return;
		}

		$all_members = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				ORDER BY member_badge',
				self::MEMBERS_TABLE,
			),
			OBJECT
		);

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
			$data['member_badge']        = $data_obj->members[ $i ]->badge;
			$data['member_lastname']     = $data_obj->members[ $i ]->last;
			$data['member_firstname']    = $data_obj->members[ $i ]->first;
			$data['member_phone']        = $data_obj->members[ $i ]->phone;
			$data['member_email']        = $data_obj->members[ $i ]->email;
			$data['member_email_secret'] = $data_obj->members[ $i ]->email_secret;

			if ( ! $member ) {
				$data['member_secret'] = $data_obj->members[ $i ]->secret;
			}

			if ( $member ) {
				$where           = array( 'member_badge' => $member[0]->member_badge );
				$result          = $wpdb->update( self::MEMBERS_TABLE, $data, $where );
				$count_remaining = count( $all_members );
				for ( $m =  0; $m < $count_remaining; $m++ ) {
					if ( $all_members[ $m ]->member_badge === $member[0]->member_badge ) {
						array_splice( $all_members, $m, 1 );
						break;
					}
				}
			} else {
				$wpdb->insert( self::MEMBERS_TABLE, $data );
			}
		}

		if ( $data_obj->clean_permissions ) {
			$delete = $wpdb->query(
				$wpdb->prepare(
					'TRUNCATE TABLE %1s',
					self::MACHINE_PERMISSIONS_TABLE,
				)
			);
		}

		$length = count( $data_obj->permissions );
		for ( $i = 0; $i < $length; $i++ ) {
			$machine_badge = trim( $data_obj->permissions[ $i ]->badge );
			$machine_name  = trim( $data_obj->permissions[ $i ]->machine_name );
			$permission = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE permission_badge = %s && permission_machine_name = %s',
					self::MACHINE_PERMISSIONS_TABLE,
					$machine_badge,
					$machine_name
				),
				OBJECT
			);

			if ( ! $permission ) {
				$data                            = array();
				$data['permission_badge']        = $data_obj->permissions[ $i ]->badge;
				$data['permission_machine_name'] = $data_obj->permissions[ $i ]->machine_name;
				$wpdb->insert( self::MACHINE_PERMISSIONS_TABLE, $data );
			}
		}

		foreach( $all_members as $extra_member ) {
			$where = array( 'member_ID' => $extra_member->member_ID );
			$wpdb->delete( self::MEMBERS_TABLE, $where );

			$where = array( 'badge' => $extra_member->member_badge );
			$wpdb->delete( self::MACHINE_PERMISSIONS_TABLE, $where );
		}

		$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Member update complete' );
	}
	
	/**
	 * A validation callback that always returns true.
	 * Validation is done elsewhere.
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public function verify_member_data( $data ) {
		return true;
	}

	/**
	 * Endpoint for twilio to post a text message.
	 * The text message is recorded in the database as well
	 * as forwarding the text to a selected phone number.
	 *
	 * @param  mixed $request Parameters for the request.
	 * @return void
	 */
	public function receive_text( $request ) {
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
	
	/**
	 * A validation endpoint that always returns true.
	 * Validation is done elsewhere.
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public function verify_phone_number( $data ) {
		return true;
	}

	/**
	 * Returns a member's data. Called from JS to populate the member lookup table.
	 * This has two error codes. 401 is returned if a member tries to sign up for
	 * something that he doesn't have permission to do. 400 is returned if the member
	 * isn't found in the database. 
	 *
	 * @param  object $request Members badge number.
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
