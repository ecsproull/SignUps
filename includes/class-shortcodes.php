<?php
/**
 * Summary
 * Shortcode class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 */
class ShortCodes extends SignUpsBase {

	/**
	 * Add the select class shortcode
	 */
	public function user_signup() {
		$post = wp_unslash( $_POST );
		var_dump( $post );
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			if ( isset( $post['signup_id'] ) ) {
				if ( '-1' === $post['signup_id'] ) {
					$this->create_select_signup();
				} else {
					$this->create_signup( $post['signup_id'] );
				}
			} elseif ( isset( $post['add_attendee'] ) ) {
				$this->add_attendee( $post );
			}
		} else {
			$this->create_select_signup();
		}
	}

	/**
	 * Retrieves the available signups  and
	 * creates a form for the user to select a signup to add himself to.
	 *
	 * @return void
	 */
	private function create_select_signup() {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
				signup_name
				FROM %1s',
				self::SIGNUPS_TABLE
			),
			OBJECT
		);
		$this->create_select_signup_form( $results );
	}

	/**
	 * Creates the form to sign up.
	 *
	 * @param string $signup_id The id of the signup to create a form for.
	 * @return void
	 */
	private function create_signup( $signup_id ) {
		global $wpdb;
		$signups   = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id, signup_name, signup_rolling_template
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling = $signups[0]->signup_rolling_template > 0;
		$signup_name = $signups[0]->signup_name;
		$sessions    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT session_id,
				session_start_formatted,
				session_start_time,
				session_slots
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$signup_id
			),
			OBJECT
		);

		foreach ( $sessions as $session ) {
			$attendees[ $session->session_id ]   = array();
			$instructors[ $session->session_id ] = array();
			$session_list                        = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE attendee_session_id = %s
					AND attendee_email != ""',
					self::ATTENDEES_TABLE,
					$session->session_id
				),
				OBJECT
			);
		}

		if ( $rolling ) {
			$attendees = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE attendee_signup_id = %s
					ORDER BY attendee_start_time',
					self::ATTENDEES_ROLLING_TABLE,
					$signups[0]->signup_id
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
				$sessions,
				$attendees,
				$signup_id,
				$template[0]
			);
		} else {
			$instructors = array();
			$attendees   = array();

			foreach ( $session_list as $attendee ) {
				if ( 'INSTRUCTOR' === $attendee->attendee_item ) {
					$instructors[ $session->session_id ][] = $attendee;
				} else {
					$attendees[ $session->session_id ][] = $attendee;
				}
			}

			$this->create_session_select_form(
				$signup_name,
				$sessions,
				$attendees,
				$instructors,
				$signup_id
			);
		}
	}

	/**
	 * Add attendee for the selected spots
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	private function add_attendee( $post ) {
		var_dump( $post );
	}

	/**
	 * Creates the form for selecting a signup to add to.
	 *
	 * @param  mixed $results The results of a DB query for available classes.
	 * @return void
	 */
	private function create_select_signup_form( $results ) {
		?>
		<form method="POST">
			<div id="usercontent" class="container">
				<table class="mb-100px table table-striped mr-auto ml-auto mt-5">
					<?php
					foreach ( $results as $result ) {
						?>
						<tr>
							<td>
								<button class="button-signup" type="submit" name="signup_id" value="<?php echo esc_html( $result->signup_id ); ?>" >
									<i>
										<u><?php echo esc_html( $result->signup_name ); ?></u>
									</i>
								</button>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Creates a form that displays the sessions along with their attenees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  array  $instructors The list of instructors for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @return void
	 */
	private function create_session_select_form( $signup_name, $sessions, $attendees, $instructors, $signup_id ) {
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<div id="content" class="container">
					<table class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-125px">
						<?php
						foreach ( $sessions as $session ) {
							?>
							<form method="POST">
							<tr>
								<td class="text-left"> <?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
							<input type="hidden" name="signup_id" value="<?php echo esc_html( $signup_id ); ?>">
							<input type="hidden" name="session_id" value="<?php echo esc_html( $session->session_id ); ?>">
							<input  id=<?php echo esc_html( 'move_to' . $session->session_id ); ?> type="hidden" name="move_to" value="0">
							<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
							<?php
							foreach ( $instructors[ $session->session_id ] as $instructor ) {
								?>
								<tr>
									<td><?php echo esc_html( $instructor->attendee_firstname . ' ' . $instructor->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_item ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_email ); ?></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative selChk" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $instructor->attendee_id, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}
							?>

							<?php
							foreach ( $attendees[ $session->session_id ] as $attendee ) {
								?>
								<tr>
									<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
								</tr>
								<?php
							}

							for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
								?>
								<tr class="add-attendee-row" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
									<td></td>
									<td><?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?></td>
									<td class="text-center"> <input class="form-check-input position-relative addChk" type="checkbox" name="addedAttendee[]" value="<?php $this->session_attendee_string( -1, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}
							?>
						</form>
							<?php
						}
						?>
				</table>
			</div>
		</div>
		<input class="btn btn-danger mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Creates a form that displays the rolling sessions along with their attenees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @param  object $template The template for the rolling class.
	 * @return void
	 */
	private function create_rolling_session_select_form( $signup_name, $sessions, $attendees, $signup_id, $template ) {
		$date_time_zone = new DateTimeZone( 'America/Phoenix' );
		$start_time_parts = explode( ':', $template->rolling_start_time );
		$start_hour   = $start_time_parts[0];
		$start_minute = $start_time_parts[1];
		$start_date   = new DateTime( '07/01/2022', $date_time_zone );
		$end_date     = new DateTime( 'now', $date_time_zone );
		$end_date->add( new DateInterval( 'P' . $template->rolling_days . 'D' ) );
		$days_sessions_raw = explode( ',', $template->rolling_days_week );
		$days_sessions = array();
		foreach ( $days_sessions_raw as $dsr ) {
			$x = explode( '-', $dsr );
			$days_sessions[ $x[0] ] = $x[1];
		}

		$duration_parts = explode( ':', $template->rolling_session_length );
		$duration = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
		$slot_titles = explode( ',', $template->rolling_slot_items );

		$time_exceptions = array();
		$today = new DateTime( 'now', $date_time_zone );
		for ( $j = 0; $j < 12; $j++ ) {
			$day = new DateTime(
				sprintf(
					'First Tuesday of %s %s',
					$today->format( 'F' ),
					$today->format( 'Y' )
				),
				$date_time_zone
			);
			$day->SetTime( 12, 0 );
			$time_exception = new timeException();
			$time_exception->begin = new DateTime( $day->format( self::DATETIME_FORMAT ), $date_time_zone );
			$time_exception->end = $day;
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
			<h3 class="mb-2"><b><?php echo esc_html( $signup_name ); ?><b></h3>
			<div>
				<div class="container">
					<table class="mb-100px table table-bordered mr-auto ml-auto">
						<form method="POST">
						<?php
						$attendee_index = 0;
						$current_day    = '2000-07-01';
						while ( $start_date <= $end_date ) {
							$day_of_week = $start_date->format( 'w' );
							if ( array_key_exists( $day_of_week, $days_sessions ) ) {
								for ( $i = 0; $i < $days_sessions[ $day_of_week ]; $i++ ) {
									if ( 0 === $i ) {
										$start_date->setTime( $start_hour, $start_minute );
									} else {
										$start_date->add( $duration );
									}

									$add_session = true;
									foreach ( $time_exceptions as $except ) {
										if ( $start_date >= $except->begin && $start_date <= $except->end ) {
											$add_session = false;
										}
									}

									if ( $add_session ) {
										if ( $start_date->format( self::DATE_FORMAT ) !== $current_day ) {
											$current_day = $start_date->format( self::DATE_FORMAT );
											?>
											<tr class=" border-bottom2 border-top3 border-left3 border-right3 bg-lightsteelblue">
												<td><?php echo esc_html( $current_day ); ?></td>
												<td></td>
												<td><button type="submit" class="btn bt-md btn-primary mr-auto ml-auto mt-2"value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee">Submit</button></td>
											</tr>
											<?php
										}
										$temp_date = new DateTime( $start_date->format( self::DATETIME_FORMAT ), $date_time_zone );
										$temp_date->Add( $duration );
										?>
										<tr  class="border-left3 border-right3 border-bottom2">
											<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_date->format( self::TIME_FORMAT ) ); ?></td>
											<?php
											if ( count( $attendees ) > $attendee_index && $attendees[ $attendee_index ]->attendee_start_time === $start_date->format( 'U' ) ) {
												?>
												<td><?php echo esc_html( $attendees[ $attendee_index ]->attendee_firstname ); ?></td>
												<td><?php echo esc_html( $attendees[ $attendee_index ]->attendee_lastname ); ?></td>
												<?php
												$attendee_index++;
											} else {
												?>
												<td></td>
												<td class="text-center"> <input class="form-check-input position-relative addChk ml-auto" type="checkbox" name="time_slots[]" value="<?php echo esc_html( $start_date->format( self::TIME_FORMAT ) ); ?>"> </td>
												<?php
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
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						<tr>
							<td><button type="submit" class="btn bt-md btn-danger mr-auto ml-auto mt-2"value="-1" name="signup_id">Back</button></td>
							<td></td>
							<td><button type="submit" class="btn bt-md btn-primary mr-auto ml-auto mt-2"value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee">Submit</button>
						</tr>
					</form>
				</table>
			</div>
		</div>
		</div>
		<?php
	}
}
