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
	 * Spider Calendar Event table.
	 *
	 * @var mixed
	 */
	protected const SPIDER_CALENDAR_EVENT_TABLE = 'wp_spidercalendar_event';

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
	 * @param int     $signup_id The signup id.
	 * @param boolean $long Return the long version of the description.
	 * @return string Formatted html.
	 */
	protected function get_signup_html( $signup_id, $long = true ) {
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
			if ( $long ) {
				return html_entity_decode( $results[0]->description_html );
			} else {
				return html_entity_decode( $results[0]->description_html_short );
			}
		} else {
			return null;
		}
	}

	/**
	 * Creates a section of HTML for the user to identify themselves.
	 *
	 * @param string $user_group The required group for this signup. Normally "member".
	 * @return boolean
	 */
	protected function create_user_table( $user_group = '' ) {
		global $wpdb;
		$return_val = null;

		if ( ! $user_group ) {
			$user_group = 'member';
		}

		if ( isset( $_COOKIE['signups_scw_badge'] ) ) {
			$cookie  = wp_unslash( $_COOKIE );
			$badge   = $cookie['signups_scw_badge'];
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE badge = %s && FIND_IN_SET( %s, `groups`)',
					self::ROSTER_TABLE,
					$badge,
					$user_group
				),
				OBJECT
			);

			if ( $results ) {
				$return_val = $results[0]->badge;
			}
		}
		?>
		<table id="lookup-member" class="mb-100px table table-bordered mr-auto ml-auto selection-font">
			<tr>
				<td class="text-right">Enter Badge#</td>
				<td class="text-left"><input id="badge-input" class="member-badge" type="number" name="badge_number" 
					value="<?php echo $return_val ? esc_html( $results[0]->badge ) : ''; ?>" required></td>
				<td><input type="button" id="get_member_button" class="btn btn-primary" value='Lookup'></td>
			</tr>
			<tr>
				<td><input id="first-name" class=" member-first-name" type="text" name="firstname" value=<?php echo $return_val ? esc_html( $results[0]->firstname ) : 'First'; ?> required readonly></td>
				<td><input id="last-name" class="member-last-name" type="text" name="lastname" value=<?php echo $return_val ? esc_html( $results[0]->lastname ) : 'Last'; ?> required readonly></td>
				<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
			</tr>
			<tr>
				<td><input id="phone" class="member-phone" type="text" name="phone"
					value=<?php echo $return_val ? esc_html( $results[0]->phone ) : '888-888-8888'; ?> placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required readonly></td>
				<td><input id="email" class="member-email" type="email" name="email"
					value=<?php echo $return_val ? esc_html( $results[0]->email ) : 'foo@bar.com'; ?> placeholder="foo@bar.com" required readonly></td>
				<td></td>
			</tr>
		</table>
		<input id="user_groups" type="hidden" name="user_groups" value="<?php echo esc_html( $user_group ); ?>">
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
	protected function create_rolling_session( $rolling_signup_id, $admin = false ) {
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
			$signup_name,
			$attendees_rolling,
			$rolling_signup_id,
			$template[0],
			$template_items,
			$signups[0]->signup_group,
			$admin
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
	 * Create_meeting_exceptions
	 *
	 * @param  mixed $start_date Date to start creating from.
	 * @param  mixed $end_date Date to end creating exceptions.
	 * @return array
	 */
	private function create_meeting_exceptions( $start_date, $end_date ) {
		$time_exceptions = array();
		$today           = new DateTime( 'now', $this->date_time_zone );
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

		return $time_exceptions;
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
		$admin
	) {
		$start_date = new DateTime( 'now', $this->date_time_zone );
		$end_date   = new DateTime( 'now', $this->date_time_zone );
		$end_date->add( new DateInterval( 'P' . $template->template_rolling_days . 'D' ) );
		$one_day_interval = new DateInterval( 'P1D' );
		$time_exceptions  = $this->create_meeting_exceptions( $start_date, $end_date );
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><b><?php echo esc_html( $signup_name ); ?></b></h1>
			<div>
				<div class="container">
					<form class="signup_form" method="POST">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>

						<?php $user_badge = $this->create_user_table( $user_group ); ?>

						<table id="selection-table" class="table-bordered mb-100px mr-auto ml-auto container selection-font"
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
								?>
								<tr class="submit-row" colspan='4'>
								<td colspan='4'><button type="submit" class="btn btn-md mr-auto ml-auto bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
								</tr>
								<tr class="date-row">
									<td colspan='4'><span class='mt-3'><?php echo esc_html( $current_day ); ?></span></td>
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
										<tr  class="attendee-row"  style=<?php echo $i % 2 ? 'background:#cfcfcf;' : 'background:#efefef;'; ?> >
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
															<?php echo $user_badge === $attendee->attendee_badge || $admin ? '' : 'hidden'; ?>
															value="
															<?php
															echo esc_html(
																$start_date->format( self::DATETIME_FORMAT ) . ',' .
																$temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $item->template_item_title . ',' .
																$comment_index . ',' . $attendee->attendee_id
															);
															?>
															">
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
															',' . $item->template_item_title . ',' . $comment_index . ',' . $item->template_item_slots
														);
														?>
														"><br>
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
																<?php echo $user_badge === $attendee->attendee_badge || $admin ? '' : 'hidden'; ?> 
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
												$com_name = $comment_name . $comment_index;
												?>
												<td class="text-center">
													<span class="mr-2"><?php echo esc_html( $item->template_item_title ); ?></span> 
													<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo esc_html( str_replace( ' ', '', $item->template_item_title ) ); ?>" 
														type="checkbox" name="time_slots[]" 
														value="
														<?php
														echo esc_html(
															$start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) .
															',' . $item->template_item_title . ',' . $comment_index . ',' . $item->template_item_slots
														)
														?>
														">
														<?php
														if ( $item->template_item_slots > '1' && count( $slot_attendees ) === 0 ) {
															echo "<br><span class='text-primary'><i>" . count( $slot_attendees ) . ' of ' . esc_html( $item->template_item_slots ) . ' Slots Filled </i></span><br>';
														}
														?>
												</td>
												<?php
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
						$slot_count                               = $slot_parts[4];
						$new_attendee['attendee_comment']         = $post[ $comment_name ];

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

						if ( count( $dup_rows ) < $slot_count ) {
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
							if ( ! $insert_return_value || count( $dup_rows ) > $slot_count ) {
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

		</div>
		<?php

		clean_post_cache( $post );
	}

	/**
	 * Loads the template selection dropdown list.
	 *
	 * @param  int     $template_id The id of the selected template.
	 * @param  boolean $add_new Adds an option to add a new template.
	 * @return void
	 */
	protected function load_template_selection( $template_id, $add_new = false ) {
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
		<select id="template-select" name="template_id" id="templates">
		<option value="0">None</option>
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
