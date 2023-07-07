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

	/**
	 * Creates a section of HTM for the user to identify themselves.
	 *
	 * @param string $hidden To hide this initially pass the string 'hidden'.
	 * @return void
	 */
	protected function create_user_table( $hidden = '' ) {
		global $wpdb;
		$returnVal = null;
		if(isset($_COOKIE['signups_scw_badge'])) {
			$badge = $_COOKIE['signups_scw_badge'];
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE badge = %s',
					self::ROSTER_TABLE,
					$badge
				),
				OBJECT
			);

			if ( $results ) {
				$returnVal = $results[0]->badge;
			}
		}
		?>
		<table id="lookup-member" class="mb-100px table table-bordered mr-auto ml-auto" <?php echo esc_html( $hidden ); ?> >
			<tr>
				<td>Enter Badge#</td>
				<td><input id="badge-input" class="member-badge" type="number" name="badge_number" 
					value=<?php echo $returnVal ? $results[0]->badge : ''; ?> required></td>
				<td><input type="button" id="get_member_button" class="btn btn-primary" value='Lookup'></td>
			</tr>
			<tr>
				<td><input id="first-name" class=" member-first-name" type="text" name="firstname" value=<?php echo $returnVal ? $results[0]->firstname : 'First'; ?> required readonly></td>
				<td><input id="last-name" class="member-last-name" type="text" name="lastname" value=<?php echo $returnVal ? $results[0]->lastname : 'Last'; ?> required readonly></td>
				<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
			</tr>
			<tr>
				<td><input id="phone" class="member-phone" type="text" name="phone"
					value=<?php echo $returnVal ? $results[0]->phone : '888-888-8888'; ?> placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required readonly></td>
				<td><input id="email" class="member-email" type="email" name="email"
					value=<?php echo $returnVal ? $results[0]->email : 'foo@bar.com'; ?> placeholder="foo@bar.com" required readonly></td>
				<td></td>
			</tr>
		</table>
		<?php

		return $returnVal;
	}

	/**
	 * Creates a form that displays the rolling sessions along with their attenees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @param  object $template The template for the rolling class.
	 * @return void
	 */
	protected function create_rolling_session_select_form( $signup_name, $attendees, $signup_id, $template ) {

		$current_badge = '4038';
		$start_time_parts = explode( ':', $template->rolling_start_time );
		$start_hour       = $start_time_parts[0];
		$start_minute     = $start_time_parts[1];
		$start_date       = new DateTime( 'now', $this->date_time_zone );
		$end_date         = new DateTime( 'now', $this->date_time_zone );
		$end_date->add( new DateInterval( 'P' . $template->rolling_days . 'D' ) );
		$days_sessions_raw = explode( ',', $template->rolling_days_week );
		$days_sessions     = array();
		foreach ( $days_sessions_raw as $dsr ) {
			$x                      = explode( '-', $dsr );
			$days_sessions[ $x[0] ] = $x[1];
		}

		$duration_parts = explode( ':', $template->rolling_session_length );
		$duration       = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
		$slot_titles    = explode( ',', $template->rolling_slot_items );

		$time_exceptions = array();
		$today           = new DateTime( 'now', $this->date_time_zone );
		for ( $j = 0; $j < 12; $j++ ) {
			$day = new DateTime(
				sprintf(
					'First Tuesday of %s %s',
					$today->format( 'F' ),
					$today->format( 'Y' )
				),
				$this->date_time_zone
			);
			$day->SetTime( 12, 0 );
			$time_exception        = new timeException();
			$time_exception->begin = new DateTime( $day->format( self::DATETIME_FORMAT ), $this->date_time_zone );
			$time_exception->end   = $day;
			$time_exception->end->add( new DateInterval( 'PT4H' ) );

			if ( $time_exception->begin >= $start_date &&
				$time_exception->begin <= $end_date ) {
				$time_exceptions[] = $time_exception;
			}

			$today->add( new DateInterval( 'P1M' ) );
		}

		$one_day_interval = new DateInterval( 'P1D' );
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><b><?php echo esc_html( $signup_name ); ?></b></h1>
			<div>
				<div class="container">
					<form class="signup_form" method="POST">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>

						<?php $userBadge =  $this->create_user_table(); ?>

						<table id="selection-table" class="mb-100px mr-auto ml-auto container"
							<?php echo $userBadge == null ? 'hidden' : ''; ?> >
							<?php
							$attendee_index = 0;
							$current_day    = '2000-07-01';
							$comment_index  = 0;
							$comment_name   = 'comment-';
							$comment_row_id = 'comment-row-';
							while ( $start_date <= $end_date ) {
								$day_of_week = $start_date->format( 'w' );
								if ( array_key_exists( $day_of_week, $days_sessions ) ) {
									for ( $i = 0; $i < $days_sessions[ $day_of_week ]; $i++ ) {
										if ( 0 === $i ) {
											$start_date->setTime( $start_hour, $start_minute );
										} else {
											$start_date->add( $duration );
										}

										$add_day_header = true;
										foreach ( $time_exceptions as $except ) {
											if ( $start_date >= $except->begin && $start_date <= $except->end ) {
												$add_day_header = false;
											}
										}

										if ( $add_day_header ) {
											if ( $start_date->format( self::DATE_FORMAT ) !== $current_day ) {
												$current_day = $start_date->format( self::DATE_FORMAT );
												?>
												<tr class="date-row">
													<td></td>
													<td></td>
													<td><button type="submit" class="btn btn-md mr-auto ml-auto bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
												</tr>
												<tr class="date-row">
													<td><?php echo esc_html( $current_day ); ?></td>
													<td><?php echo esc_html( $slot_titles[0] ); ?></td>
													<td><?php echo esc_html( $slot_titles[1] ); ?></td>
												</tr>
												<?php
											}
											$temp_end_date = new DateTime( $start_date->format( self::DATETIME_FORMAT ), $this->date_time_zone );
											$temp_end_date->Add( $duration );
											$count = 0;
											?>
											<tr  class="attendee-row"  style=<?php echo $i % 2 ? "background:#cfcfcf;" : "background:#efefef;"; ?> >
												<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_end_date->format( self::TIME_FORMAT ) ); ?></td>
												<?php
											foreach ( $slot_titles as $title ) {
												if ( count( $attendees ) > $attendee_index && $attendees[ $attendee_index ]->attendee_start_time === $start_date->format( 'U' ) &&
													$title === $attendees[ $attendee_index ]->attendee_item ) {
													?>
													<td>
														<?php echo esc_html( $attendees[ $attendee_index ]->attendee_firstname . ' ' . $attendees[ $attendee_index ]->attendee_lastname ); ?>
														<input class="form-check-input ml-2 rolling-remove-chk mt-1 <?php echo esc_html( $attendees[ $attendee_index ]->attendee_badge ) ?>" 
															type="checkbox" name="remove_slots[]" <?php echo $userBadge == $attendees[ $attendee_index ]->attendee_badge ? '' : 'hidden'; ?> 
															value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $title . ',' . $comment_index . ',' . $attendees[ $attendee_index ]->attendee_id ); ?>">
													</td>
													<?php
													$attendee_index++;
												} else {
													$com_name = $comment_name . $comment_index;
													if ( 0 === $count ) {
														?>
														<td class="text-center"> 
															<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo str_replace(' ', '', $title) ?>" 
																type="checkbox" name="time_slots[]" 
																value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $title . ',' . $comment_index . ',0' ); ?>">
														</td>
													<?php
													}
												}
											}
											?>
											</tr>
											<?php
										}
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

		$new_attendee                       = array();
		$new_attendee['attendee_signup_id'] = $post['add_attendee_session'];
		$new_attendee['attendee_email']     = $post['email'];
		$new_attendee['attendee_phone']     = $post['phone'];
		$new_attendee['attendee_lastname']  = $post['lastname'];
		$new_attendee['attendee_firstname'] = $post['firstname'];
		$new_attendee['attendee_badge']     = $post['badge_number'];
		$insert_return_value                = false;

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
				if ( isset( $post['time_slots']) ) { 
					foreach ( $post['time_slots'] as $slot ) {
						$slot_parts                               = explode( ',', $slot );
						$slot_start                               = new DateTime( $slot_parts[0], $this->date_time_zone );
						$new_attendee['attendee_start_time']      = $slot_start->format( 'U' );
						$new_attendee['attendee_start_formatted'] = $slot_start->format( self::DATETIME_FORMAT );
						$slot_end                                 = new DateTime( $slot_parts[1], $this->date_time_zone );
						$new_attendee['attendee_end_time']        = $slot_end->format( 'U' );
						$new_attendee['attendee_end_formatted']   = $slot_end->format( self::DATETIME_FORMAT );
						$new_attendee['attendee_item']            = trim( $slot_parts[2] );

						$comment_name                     = 'comment-' . $slot_parts[3];
						$new_attendee['attendee_comment'] = $post[ $comment_name ];

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

						if ( ! $dup_rows ) {
							$insert_return_value = $wpdb->insert( self::ATTENDEES_ROLLING_TABLE, $new_attendee );
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
							if ( ! $insert_return_value || $dup_rows ) {
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

				if ( isset( $post['remove_slots']) ) { 
					foreach ( $post['remove_slots'] as $slot ) {
						$slot_parts = explode( ',', $slot );
						$slot_start = new DateTime( $slot_parts[0], $this->date_time_zone );
						$slot_end   = new DateTime( $slot_parts[1], $this->date_time_zone );
						$slot_id    = $slot_parts[4];
						$where = Array();
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

		</div>
		<?php

		clean_post_cache( $post );
	}

	protected function create_rolling_session( $rolling_signup_id ) {
		global $wpdb;
		$today = new DateTime( 'now', $this->date_time_zone );
		$today->SetTime( 8, 0 );
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id, signup_name, signup_cost, signup_rolling_template, signup_default_price_id, signup_users_db_table
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
				WHERE rolling_id = %s',
				self::ROLLING_TABLE,
				$signups[0]->signup_rolling_template
			),
			OBJECT
		);

		$this->create_rolling_session_select_form(
			$signup_name,
			$attendees_rolling,
			$rolling_signup_id,
			$template[0]
		);
	}
}
