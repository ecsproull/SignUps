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
	 * Date and time exception for rolling signups. Shop closures.
	 *
	 * @var mixed
	 */
	protected const ROLLING_EXCEPTIONS_TABLE = 'wp_scw_rolling_exceptions';

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
	 * Signup descriptions table.
	 *
	 * @var mixed
	 */
	protected const DESCRIPTIONS_TABLE = 'wp_scw_signup_descriptions';

	/**
	 * Signup template table.
	 *
	 * @var mixed
	 */
	protected const SIGNUP_TEMPLATE_TABLE = 'wp_scw_template';

	/**
	 * Signup template item table.
	 *
	 * @var mixed
	 */
	protected const SIGNUP_TEMPLATE_ITEM_TABLE = 'wp_scw_template_item';

	/**
	 * Signup category table.
	 *
	 * @var mixed
	 */
	protected const SIGNUP_CATEGORY_TABLE = 'wp_scw_signup_categories';

	/**
	 * Spider Calendar Event table.
	 *
	 * @var mixed
	 */
	protected const SPIDER_CALENDAR_EVENT_TABLE = 'wp_spidercalendar_event';

	/**
	 * Spider Calendar Event table.
	 *
	 * @var mixed
	 */
	protected const TEXT_TABLE = 'wp_scw_text_messages';

	/**
	 * Machine permissions table.
	 *
	 * @var mixed
	 */
	protected const MEMBERS_TABLE = 'wp_scw_members';

	/**
	 * Machine permissions table.
	 *
	 * @var mixed
	 */
	protected const MACHINE_PERMISSIONS_TABLE = 'wp_scw_machine_permissions';

	/**
	 * Unsubscribe table.
	 *
	 * @var mixed
	 */
	protected const UNSUBSCRIBE_TABLE = 'wp_scw_unsubscribe';

	/**
	 * Log table.
	 *
	 * @var mixed
	 */
	protected const LOG_TABLE = 'wp_scw_logs';

	/**
	 * Format DateTime as 2020-08-13 6:00 am.
	 *
	 * @var mixed
	 */
	protected const DATETIME_FORMAT = 'Y-m-d g:i A';

	/**
	 * Format DateTime as 2020-08-13T6:00 am.
	 * Acceptable for HTML Date Input
	 *
	 * @var mixed
	 */
	protected const DATETIME_FORMAT_INPUT = 'Y-m-d\TH:i';

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
	 * Write_log.
	 *
	 * @param  mixed $log_text The string to write to the log.
	 * @return void
	 */
	protected function write_log( $log_text ) {
		global $wpdb;
		$log_data['logs_text'] = $log_text;
		$wpdb->insert( self::LOG_TABLE, $log_data );
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
		$basic_args = array(
			'methods'             => $method,
			'callback'            => array( $class_inst, $func ),
			'permission_callback' => array( $class_inst, 'permissions_check' ),
		);

		array_merge( $basic_args, $args );

		register_rest_route(
			$namespace,
			$route,
			$basic_args
		);
	}

	/**
	 * Return html description for a signup.
	 *
	 * @param int $signup_id The signup id.
	 * @return array
	 */
	protected function get_signup_html( $signup_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE description_signup_id = %1s',
				self::DESCRIPTIONS_TABLE,
				(int) $signup_id
			),
			OBJECT
		);

		if ( $results ) {
			return $results[0];
		} else {
			return null;
		}
	}
	
	/**
	 * Is this a rolling signup
	 *
	 * @param  mixed $signup_id
	 * @return boolean True for rolling, else false
	 */
	private function is_rolling_signup( $signup_id ) {
		global $wpdb;
		$signup = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		return $signup[0]->signup_rolling_template > '0';
	}

	/**
	 * Creates a section of HTML for the user to identify themselves.
	 *
	 * @param string $user_group The required group for this signup. Normally "member".
	 * @param string $secret Secret supplied by member to edit their signups.
	 * @return boolean
	 */
	protected function create_user_table( $user_group, $signup_id, $secret = null ) {
		global $wpdb;
		$return_val  = null;
		$results     = array( 1 );
		$remember_me = false;
		$user_secret = null;

		$rolling_signup = $this->is_rolling_signup( $signup_id );

		if ( $secret ) {
			$attendees_rolling = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE attendee_secret = %s',
					self::ATTENDEES_ROLLING_TABLE,
					$secret
				),
				OBJECT
			);

			if ( $attendees_rolling ) {
				$badge   = $attendees_rolling[0]->attendee_badge;
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE member_badge = %s',
						self::MEMBERS_TABLE,
						$badge
					),
					OBJECT
				);

				$user_secret = $results[0]->member_secret;
				$return_val  = $badge;
			}
			?>
			<?php
		}

		if ( ! $return_val && isset( $_COOKIE['signups_scw_badge'] ) ) {
			$remember_me = true;
			$cookie      = wp_unslash( $_COOKIE );
			$badge       = $_COOKIE['signups_scw_badge'];
			$results     = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE member_badge = %s',
					self::MEMBERS_TABLE,
					$badge
				),
				OBJECT
			);

			$permission = true;
			if ( $results && $user_group ) {
				$permission = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s
						WHERE permission_badge = %s && permission_machine_name = %s',
						self::MACHINE_PERMISSIONS_TABLE,
						$badge,
						$user_group
					),
					OBJECT
				);
			}

			if ( $results && $permission ) {
				$return_val = $results[0]->member_badge;
			} else {
				$return_val = null;
				$results    = array( 1 );
			}

			$user_secret = $results[0]->member_secret;
		} else {
			$remember_me = isset( $_COOKIE['signups_scw_badge'] );
		}
		?>

		<table id="lookup-member" class="mb-100px table table-bordered mr-auto ml-auto selection-font">
			<tr>
				<td class="text-right">Enter Badge#</td>
				<td class="text-left"><input id="badge-input" class="member-badge" type="number" name="badge_number" 
					value="<?php echo $return_val ? esc_html( $results[0]->member_badge ) : ''; ?>" required></td>
				<td><input type="button" id="get_member_button" class="btn btn-primary" value='Lookup'></td>
			</tr>
			<tr>
				<td class="text-right"><input id="first-name" class=" member-first-name" type="text" name="firstname" value=<?php echo $return_val ? esc_html( $results[0]->member_firstname ) : 'First'; ?> required readonly></td>
				<td  class="text-left"><input id="last-name" class="member-last-name" type="text" name="lastname" value=<?php echo $return_val ? esc_html( $results[0]->member_lastname ) : 'Last'; ?> required readonly></td>
				<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
			</tr>
			<tr>
				<td class="text-right">
					<span class="mr-1">Remember Badge</span>
					<input id="remember_me" class="position-relative remember-me-chk mr-1" 
						type="checkbox" name="remember_me" value='' <?php echo $remember_me ? 'checked' : ''; ?>></td>
				<td class="text-left"><input id="user-edit-id" class="user-edit-id" 
					type="text" name="secret" value="<?php echo esc_html( $secret ); ?>" placeholder="Enter secret to edit"
					<?php $rolling_signup ? '' : 'hidden'; ?>></td>
				<td><button id="update-butt" type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" 
					value=<?php echo esc_html( $signup_id ); ?> name="continue_signup" disabled
					<?php echo $rolling_signup ? '' : 'hidden'; ?> >Reload</button></td>
			</tr>
			<tr hidden>
				<td><input id="phone" class="member-phone" type="text" name="phone"
					value=<?php echo $return_val ? esc_html( $results[0]->member_phone ) : '888-888-8888'; ?> placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required readonly></td>
				<td><input id="email" class="member-email" type="email" name="email"
					value=<?php echo $return_val ? esc_html( $results[0]->member_email ) : 'foo@bar.com'; ?> placeholder="foo@bar.com" required readonly></td>
				<td></td>
			</tr>
		</table>
		<input id="user_groups" type="hidden" name="user_groups" value="<?php echo esc_html( $user_group ); ?>">
		<input id="user-secret" type="hidden" name="user_secret" value="<?php echo esc_html( $user_secret ); ?>">
		<input id="rest-nonce" type="hidden" name="rest_nonce" value ="<?php echo wp_create_nonce( 'wp_rest' ); ?>">
		<?php

		return $return_val;
	}

	/**
	 * Creates a rolling signup based on a template
	 *
	 * @param  mixed $rolling_signup_id The id for the signup.
	 * @param  mixed $admin Set to true if an admin is using this function.
	 * @return void
	 */
	protected function create_rolling_session( $rolling_signup_id, $secret, $admin = false ) {
		global $wpdb;
		$today = new DateTime( 'now', $this->date_time_zone );
		$today->SetTime( 8, 0 );
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
					signup_name,
					signup_rolling_template,
					signup_default_price_id,
					signup_group
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$rolling_signup_id
			),
			OBJECT
		);

		$attendees_rolling = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE attendee_signup_id = %s AND attendee_start_time >= %d
				ORDER BY attendee_start_time',
				self::ATTENDEES_ROLLING_TABLE,
				$signups[0]->signup_id,
				$today->format( 'U' )
			),
			OBJECT
		);

		$template = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE template_id = %s',
				self::SIGNUP_TEMPLATE_TABLE,
				$signups[0]->signup_rolling_template
			),
			OBJECT
		);

		$template_items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT template_item_day_of_week,
				template_item_title,
				template_item_slots,
				template_item_start_time,
				template_item_duration,
				template_item_shifts,
				template_item_group,
				template_item_column
				FROM %1s
				WHERE template_item_template_id = %s',
				self::SIGNUP_TEMPLATE_ITEM_TABLE,
				$template[0]->template_id
			),
			OBJECT
		);

		$this->create_rolling_session_select_form2(
			$signups[0]->signup_name,
			$attendees_rolling,
			$rolling_signup_id,
			$template[0],
			$template_items,
			$signups[0]->signup_group,
			$admin,
			$secret
		);
	}

	/**
	 * Get the attendees for a rolling signup slot. Based on start time.
	 *
	 * @param  mixed $attendees Attendees for this signup.
	 * @param  mixed $start_date When to look look for attendees that match the start date.
	 * @param  mixed $template_item_title The title of the signup.
	 * @return array
	 */
	private function get_slot_attendees( $attendees, $start_date, $template_item_title ) {
		$slot_attendees = array();
		foreach ( $attendees as $attendee ) {
			if ( $attendee->attendee_start_time === $start_date->format( 'U' ) &&
				$template_item_title === $attendee->attendee_item ) {
				$slot_attendees[] = $attendee;
			}
		}

		return $slot_attendees;
	}

	/**
	 * Get the attendees based on start time. Used to check if an attendee is double booking.
	 *
	 * @param  mixed $attendees Attendees for this signup.
	 * @param  mixed $start_date When to look look for attendees that match the start date.
	 * @param  mixed $badge Current users badge.
	 * @return array
	 */
	private function is_attendee_free( $attendees, $start_date, $badge ) {
		$slot_attendees = array();
		foreach ( $attendees as $attendee ) {
			if ( $attendee->attendee_start_time === $start_date->format( 'U' ) &&
				$badge === $attendee->attendee_badge ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create_meeting_exceptions
	 *
	 * @param  mixed $start_date Date to start creating from.
	 * @param  mixed $end_date Date to end creating exceptions.
	 * @return array
	 */
	private function create_meeting_exceptions( $start_date, $end_date ) {
		global $wpdb;
		$exceptions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE exc_start >= %s AND exc_start <= %s',
				self::ROLLING_EXCEPTIONS_TABLE,
				$start_date->format('Y-m-d'), 
				$end_date->format('Y-m-d')
			),
			OBJECT
		);

		$time_exceptions = array();
		foreach ( $exceptions as $exc ) {
			$dte               = new TimeException();
			$dte->template     = $exc->exc_template_id;
			$dte->begin        = new DateTime( $exc->exc_start, $this->date_time_zone );
			$dte->end          = new DateTime( $exc->exc_end, $this->date_time_zone );
			$dte->reason       = $exc->exc_reason;
			$time_exceptions[] = $dte;
		}

		return $time_exceptions;

		/* $today = new DateTime( 'now', $this->date_time_zone );
		for ( $j = 0; $j < 12; $j++ ) {
			$day      = new DateTime(
				sprintf(
					'First Monday of %s %s',
					$today->format( 'F' ),
					$today->format( 'Y' )
				),
				$this->date_time_zone
			);
			$interval = DateInterval::createFromDateString( '1 day' );
			$day->add( $interval );
			$day->SetTime( 12, 0 );
			$time_exception        = new TimeException();
			$time_exception->begin = new DateTime( $day->format( self::DATETIME_FORMAT ), $this->date_time_zone );
			$time_exception->end   = $day;
			$time_exception->end->add( new DateInterval( 'PT4H' ) );

			if ( $time_exception->begin >= $start_date &&
				$time_exception->begin <= $end_date ) {
				$time_exceptions[] = $time_exception;
			}

			$today->add( new DateInterval( 'P1M' ) );
		} 

		return $time_exceptions;*/
	}

	/**
	 * Creates a form that displays the rolling sessions along with their attenees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @param  object $template The template for the rolling class.
	 * @param  array  $template_items Each one describe a signup for that day.
	 * @param  string $user_group The group that is allowed to sign up.
	 * @param  bool   $admin This being accessed by an administrator.
	 * @return void
	 */
	protected function create_rolling_session_select_form2(
		$signup_name,
		$attendees,
		$signup_id,
		$template,
		$template_items,
		$user_group,
		$admin,
		$secret
	) {
		$start_date = new DateTime( 'now', $this->date_time_zone );
		$end_date = new DateTime( 'now', $this->date_time_zone );
		$end_date->add( new DateInterval( 'P' . $template->template_rolling_days . 'D' ) );
		$one_day_interval = new DateInterval( 'P1D' );
		$time_exceptions  = $this->create_meeting_exceptions( $start_date, $end_date );

		if ( ! $secret && get_query_var( 'secret' ) ) {
			$secret = get_query_var( 'secret' );
		}
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><b><?php echo esc_html( $signup_name ); ?></b></h1>
			<div>
				<div>
					<form class="signup_form" method="POST">
						<?php
						wp_nonce_field( 'signups', 'mynonce' );
						$user_badge = $this->create_user_table( $user_group, $signup_id, $secret );
						?>

						<table id="selection-table" class="table-bordered mb-100px mr-auto ml-auto selection-font"
							<?php echo null === $user_badge && ! $admin ? 'hidden' : ''; ?> >
							<?php
							$current_day    = '2000-07-01';
							$comment_index  = 0;
							$comment_name   = 'comment-';
							$comment_row_id = 'comment-row-';
							while ( $start_date <= $end_date ) {
								$day_of_week = $start_date->format( 'N' );
								$day_items   = array_filter(
									$template_items,
									function( $value, $key ) use ( $day_of_week ) {
										return str_contains( $value->template_item_day_of_week, $day_of_week );
									},
									ARRAY_FILTER_USE_BOTH
								);

								usort(
									$day_items,
									function ( $a, $b ) {
										$st_time1 = strtotime( $a->template_item_start_time );
										$st_time2 = strtotime( $b->template_item_start_time );
										return $st_time1 < $st_time2;
									}
								);

								$slot_titles = array();
								$group_items = array();
								foreach ( $day_items as $day_item ) {
									$slot_titles[ $day_item->template_item_title ] = $day_item->template_item_title;
									if ( ! array_key_exists( $day_item->template_item_group, $group_items ) ) {
										$group_items[ $day_item->template_item_group ] = array();
									}

									$group_items[ $day_item->template_item_group ][] = $day_item;
								}

								usort(
									$group_items,
									function ( $a, $b ) {
										return $a[0]->template_item_start_time > $b[0]->template_item_start_time;
									}
								);

								$current_day = $start_date->format( self::DATE_FORMAT );
								$count       = 0;
								$col_span    = $template->template_columns + 1;
								?>
								<tr class="submit-row" colspan='<?php echo esc_html( $col_span ); ?>'>
								<td colspan='<?php echo esc_html( $col_span ); ?>'><button type="submit" class="btn btn-md mr-auto ml-auto bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
								</tr>
								<tr class="date-row">
									<td colspan='<?php echo esc_html( $col_span ); ?>'><span class='mt-3'><?php echo esc_html( $current_day ); ?></span></td>
								</tr>
								<?php
								foreach ( $group_items as $group_item ) {
									$start_time_parts = explode( ':', $group_item[0]->template_item_start_time );
									$duration_parts   = explode( ':', $group_item[0]->template_item_duration );
									$start_hour       = $start_time_parts[0];
									$start_minutes    = $start_time_parts[1];
									$start_date       = $start_date->SetTime( $start_hour, $start_minutes );
									$duration         = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
									$temp_end_date    = new DateTime( $start_date->format( self::DATETIME_FORMAT ), $this->date_time_zone );
									$temp_end_date->Add( $duration );

									for ( $s = 0; $s < $group_item[0]->template_item_shifts; $s++ ) {
										?>
										<tr  class="attendee-row"  style=<?php echo $s % 2 ? 'background:#cfcfcf;' : 'background:#efefef;'; ?> >
											<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_end_date->format( self::TIME_FORMAT ) ); ?></td>
										<?php

										$current_column = 0;
										foreach ( $group_item as $item ) {
											for ( ; $current_column < $item->template_item_column; $current_column++ ) {
												?>
												<td></td>
												<?php
											}
											$slot_attendees = $this->get_slot_attendees( $attendees, $start_date, $item->template_item_title );
											if ( $slot_attendees ) {
												if ( '1' === $item->template_item_slots ) {
													$attendee = $slot_attendees[0];
													?>
													<td>
														<span class='text-primary'><i><?php echo esc_html( $item->template_item_title ); ?> </i></span><br>
														<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
														<input class="form-check-input ml-2 rolling-remove-chk mt-2 <?php echo esc_html( $attendee->attendee_badge ); ?>" 
															type="checkbox" name="remove_slots[]" 
															<?php echo ( $user_badge === $attendee->attendee_badge && $attendee->attendee_secret === $secret ) || $admin ? '' : 'hidden'; ?>
															value="
															<?php
															echo esc_html(
																$start_date->format( self::DATETIME_FORMAT ) . ',' .
																$temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $item->template_item_title . ',' .
																$comment_index . ',' . $attendee->attendee_id
															);
															?>
															"
															>
													</td>
													<?php
												} else {
													?>
													<td>
													<?php
													if ( count( $slot_attendees ) < $item->template_item_slots ) {
														?>
														<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo esc_html( str_replace( ' ', '', $item->template_item_title ) ); ?>" 
														type="checkbox" name="time_slots[]" 
														value="
														<?php
														echo esc_html(
															$start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) .
															',' . $item->template_item_title . ',' . $comment_index . ',0,' . $item->template_item_slots
														);
														?>
														" <?php echo $this->is_attendee_free( $attendees, $start_date, $user_badge ) ? '' : 'disabled'; ?> ><br>
														<?php
													}

													if ( count( $slot_attendees ) === (int) $item->template_item_slots ) {
														echo "<span class='text-primary'><i>All " . esc_html( $item->template_item_slots . ' ' . $item->template_item_title ) . ' Slots Filled</i></span><br>';
													} else {
														echo "<span class='text-primary'><i>" . count( $slot_attendees ) . ' of ' . esc_html( $item->template_item_slots ) . ' Slots Filled </i></span><br>';
													}

													$count         = 1;
													$random_number = wp_rand();
													foreach ( $slot_attendees as $attendee ) {
														?>
														<div class="<?php echo $count > 3 ? esc_html( $random_number ) : ''; ?>" <?php echo $count > 3 ? 'hidden' : ''; ?> >
															<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
															<input class="form-check-input ml-2 rolling-remove-chk mt-2 <?php echo esc_html( $attendee->attendee_badge ); ?>" 
																type="checkbox" name="remove_slots[]" 
																<?php echo ( $user_badge === $attendee->attendee_badge && $attendee->attendee_secret === $secret ) || $admin ? '' : 'hidden'; ?>
																value="
																<?php
																echo esc_html(
																	$start_date->format( self::DATETIME_FORMAT ) . ',' .
																	$temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $item->template_item_title . ',' .
																	$comment_index . ',' . $attendee->attendee_id
																);
																?>
																">
															<br>
														</div>
														<?php
														$count++;
													}

													if ( $count > 3 ) {
														?>
														<button class="btn btn-sm bg-primary mr-auto ml-auto expand-button" type='button' 
															data-button='{"session_id": <?php echo esc_html( $random_number ); ?>}' >Show All</button>
														<?php
													}
													?>
													</td>
													<?php
												}
											} else {
												$skip_time_slot = false;
												$reason         = '';
												foreach ( $time_exceptions as $exception ) {
													if ( $start_date >= $exception->begin && 
														$start_date <= $exception->end  &&
														($exception->template === $template ||
														$exception->template === '0')) {
														$reason = $exception->reason;
														$skip_time_slot = true;
														break;
													}
												}

												if ( $skip_time_slot ) {
													?>
													<td><?php echo esc_html( $reason ); ?></td>
													<?php
												} else {
													$com_name = $comment_name . $comment_index;
													$value    = $start_date->format( self::DATETIME_FORMAT ) . ',';
													$value   .= $temp_end_date->format( self::DATETIME_FORMAT ) . ',';
													$value   .= $item->template_item_title . ',' . $comment_index . ',0,' . $item->template_item_slots;
													?>
													<td class="text-center">
														<span class="mr-2"><?php echo esc_html( $item->template_item_title ); ?></span> 
														<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo esc_html( str_replace( ' ', '', $item->template_item_title ) ); ?>" 
															type="checkbox" name="time_slots[]" 
															value="<?php echo esc_html( $value ); ?>"
															<?php echo $this->is_attendee_free( $attendees, $start_date, $user_badge ) ? '' : 'disabled'; ?> >
															<?php
															if ( $item->template_item_slots > '1' && count( $slot_attendees ) === 0 ) {
																echo "<br><span class='text-primary'><i>" . count( $slot_attendees ) . ' of ' . esc_html( $item->template_item_slots ) . ' Slots Filled </i></span><br>';
															}
															?>
													</td>
													<?php
												}
											}

											$current_column++;
										}

										for ( ; $current_column < $template->template_columns; $current_column++ ) {
											?>
											<td></td>
											<?php
										}
										?>
										</tr>
										<?php
										$start_date->add( $duration );
										$temp_end_date->Add( $duration );
									}
								}
								$start_date->add( $one_day_interval );
							}
							?>
							<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
							<input type="hidden" name="add_attendee_session" value="<?php echo esc_html( $signup_id ); ?>">
							<tr class="footer-row">
								<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
								<td></td>
								<td><button type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
							</tr>
						</form>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add attendee for the selected spots
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	protected function add_attendee_rolling( $post ) {
		global $wpdb;

		$badge = $post['badge_number'];
		if ( strlen( $badge ) !== 4 || ! preg_match( '~[0-9]+~', $badge ) ) {
			?>
			<h2>Data verification failed. Error:0x80420</h2>
			<?php
			return;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE member_badge = %s',
				self::MEMBERS_TABLE,
				$post['badge_number']
			),
			OBJECT
		);

		if ( ! $results ||
			$results[0]->member_firstname !== $post['firstname'] ||
			$results[0]->member_lastname !== $post['lastname'] ||
			$results[0]->member_email !== $post['email'] ||
			$results[0]->member_phone !== $post['phone'] ) {
			?>
			<h2>Data verification failed. Error:0x80421</h2>
			<?php
			return;
		}

		$new_attendee                       = array();
		$new_attendee['attendee_signup_id'] = $post['add_attendee_session'];
		$new_attendee['attendee_email']     = $post['email'];
		$new_attendee['attendee_phone']     = $post['phone'];
		$new_attendee['attendee_lastname']  = $post['lastname'];
		$new_attendee['attendee_firstname'] = $post['firstname'];
		$new_attendee['attendee_badge']     = $post['badge_number'];
		$new_attendee['attendee_secret']    = $post['user_secret'];
		$insert_return_value                = false;
		$send_mail                          = false;
		$body                               = '<h2>' . $post['signup_name'] . '</h2><br>';
		?>
		<div class="container">
			<form method="POST">
				<table class="mb-100px mr-auto ml-auto">
					<tr class="attendee-row">
						<th>Date</th>
						<th>Time</th>
						<th>Name</th>
						<th>Item</th>
						<th>Status</th>
					</tr>
				<?php
				$body .= '<b><pre>      Date           Time           Name             Item         Status</pre></b>';
				if ( isset( $post['time_slots'] ) ) {
					foreach ( $post['time_slots'] as $slot ) {
						$slot_parts                               = explode( ',', $slot );
						$slot_start                               = new DateTime( $slot_parts[0], $this->date_time_zone );
						$new_attendee['attendee_start_time']      = $slot_start->format( 'U' );
						$new_attendee['attendee_start_formatted'] = $slot_start->format( self::DATETIME_FORMAT );
						$slot_end                                 = new DateTime( $slot_parts[1], $this->date_time_zone );
						$new_attendee['attendee_end_time']        = $slot_end->format( 'U' );
						$new_attendee['attendee_end_formatted']   = $slot_end->format( self::DATETIME_FORMAT );
						$new_attendee['attendee_item']            = trim( $slot_parts[2] );
						$comment_name                             = 'comment-' . $slot_parts[3];
						$slot_count                               = $slot_parts[5];

						$wpdb->query(
							$wpdb->prepare(
								'LOCK TABLES %1s WRITE',
								self::ATTENDEES_ROLLING_TABLE
							)
						);

						$dup_rows = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT * FROM %1s WHERE attendee_start_time = %d AND attendee_signup_id = %d AND attendee_item = %s',
								self::ATTENDEES_ROLLING_TABLE,
								$new_attendee['attendee_start_time'],
								$new_attendee['attendee_signup_id'],
								$new_attendee['attendee_item']
							)
						);

						$insert_id = -1;
						if ( count( $dup_rows ) < $slot_count ) {
							$insert_return_value = $wpdb->insert( self::ATTENDEES_ROLLING_TABLE, $new_attendee );
							if ( $insert_return_value ) {
								$insert_id = $wpdb->insert_id;
							}
						} else {
							?>
							<h2 class="text-danger">Timeslot is already taken, refresh page and reselect another time.</h2>
							<?php
						}

						$wpdb->query( 'UNLOCK TABLES' );
						?>
						<tr class="attendee-row">
							<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
							<td><?php echo esc_html( $slot_parts[2] ); ?></td>
							<?php
							$body .= '<pre>' . $slot_start->format( self::DATE_FORMAT ) . ' ' . $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT );
							$body .= '  ' . $post['firstname'] . ' ' . $post['lastname'] . '    ' . $slot_parts[2];
							if ( ! $insert_return_value || count( $dup_rows ) > $slot_count ) {
								?>
								<td style="color:red"><b><i>Failed</i></b></td>
								<?php
								$body .= '   ' . "<b style='color:red'><i>   Failed </pre></i></b></pre>";
							} else {
								$send_mail = true;
								?>
								<td>Success</td>
								<?php
								$body .= '   Success </pre>';
							}
							?>
						</tr>
						<?php
					}
				}

				if ( isset( $post['remove_slots'] ) ) {
					foreach ( $post['remove_slots'] as $slot ) {
						$slot_parts           = explode( ',', $slot );
						$slot_start           = new DateTime( $slot_parts[0], $this->date_time_zone );
						$slot_end             = new DateTime( $slot_parts[1], $this->date_time_zone );
						$slot_id              = $slot_parts[4];
						$where                = array();
						$where['attendee_id'] = $slot_id;

						$wpdb->query(
							$wpdb->prepare(
								'LOCK TABLES %1s WRITE',
								self::ATTENDEES_ROLLING_TABLE
							)
						);

						$delete_return_value = $wpdb->delete( self::ATTENDEES_ROLLING_TABLE, $where );
						$wpdb->query( 'UNLOCK TABLES' );
						?>
						<tr class="attendee-row" style="background-color:#FFCCCB;">
							<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
							<td><?php echo esc_html( $slot_parts[2] ); ?></td>
							<?php
							if ( ! $delete_return_value ) {
								?>
								<td style="color:red"><b><i>Failed</i></b></td>
								<?php
							} else {
								?>
								<td>Success</td>
								<?php
							}
							?>
						</tr>
						<?php
					}
				}
				?>
				<tr class="attendee-row">
					<td></td>
					<td class="text-center">
						<button class="btn btn-primary signup-submit" type="submit" name="signup_id" value="<?php echo esc_html( $post['add_attendee_session'] ); ?>" >Return</button>
					</td>
					<td></td>
					<td class="text-center"><button class="btn btn-primary back-button" type="button" name="signup_id" value="-1" >Cancel</button></td>
					<td></td>
				</tr>
				</table>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
			<h2>Signup complete</h2>
			<br>
			<div class="fs-3 text-dark">
				<a href="https://woodclubtest.site/signups/?signup_id=<?php echo esc_html( $post['add_attendee_session'] ); ?>&secret=<?php echo esc_html( $post['user_secret'] ); ?>" >Change Signup</a><br>
				<br>
				<p>Your key to edit this signup is: &emsp; &emsp; <?php echo esc_html( $post['user_secret'] ); ?> </p>
				<p>ALSO: An email has been sent to <b><i><?php echo esc_html( $post['email'] ) ?></i></b> with a link to edit this signup.<p>
			</div>
			
		</div>
		<?php
		if ( $send_mail ) {
			$sgm   = new SendGridMail();
			$link  = "<a href='https://woodclubtest.site/signups/?signup_id=" . $post['add_attendee_session'] . '&secret=' . $post['user_secret'] . "'>Edit Signup</a>";
			$body .= '<br><br>' . $link . '<br>';
			$body .= '<p>Your key to edit this signup is: &emsp; &emsp;' . $post['user_secret'] . '</p>';
			$sgm->send_mail( $post['email'], 'Woodshop Signup', $body );
		}

		clean_post_cache( $post );
	}

	/**
	 * Loads the template selection dropdown list.
	 *
	 * @param  int     $template_id The id of the selected template.
	 * @param  boolean $add_new Adds an option to add a new template.
	 * @param  string  $select_id The name of the selected template.
	 * @param  boolean $default_title Default title.
	 * @return void
	 */
	protected function load_template_selection(
		$template_id,
		$add_new = false,
		$select_id = 'template-select',
		$default_title = 'None'
		) {
		global $wpdb;
		$templates = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT template_id, template_name
				FROM %1s',
				self::SIGNUP_TEMPLATE_TABLE
			),
			OBJECT
		);

		?>
		<select id="<?php echo esc_html( $select_id ); ?>" name="template_id">
		<option value="0"><?php echo esc_html( $default_title ); ?></option>
		<?php
		foreach ( $templates as $result ) {
			if ( $template_id === $result->template_id ) {
				?>
				<option value="<?php echo esc_html( $result->template_id ); ?>" selected><?php echo esc_html( $result->template_name ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_html( $result->template_id ); ?>"><?php echo esc_html( $result->template_name ); ?></option>
				<?php
			}
		}

		if ( $add_new ) {
			?>
			<option value="-1">New Template</option>
			<?php
		}
		?>
		<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</select>
		<?php
	}
}
