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
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			if ( isset( $post['signup_id'] ) ) {
				if ( '-1' === $post['signup_id'] ) {
					$this->create_select_signup();
				} else {
					$this->create_signup( $post['signup_id'] );
				}
			} elseif ( isset( $post['add_attendee_session'] ) ) {
				$this->add_attendee( $post );
			} elseif ( isset( $post['add_attendee_class'] ) ) {
				$slots_parts = explode( ',', $post['time_slots'][0] );
				if ( $slots_parts[4] > 0 && $post['paid'] === "false" ) {
					$this->collect_money( $post, $slots_parts[4] );
				} else {
					$this->add_attendee_class( $post );
				}
			}
		} else {
			$this->create_select_signup();
		}
	}

	/**
	 * Payment form.
	 *
	 * @param array $post The values from the previous for where member slected a signup.
	 * @return void
	 */
	private function collect_money( $post, $cost ) {
		?>
		<div class="container">
			<ul class="collapsible popout">
				<li>
					<div class="collapsible-body" id="order-body">
						<div class="container">
							<div class="row">
								<div class="card-panel blue-grey">
									<div class="card-content white-text center">
										<span class="card-title">Total: $<?php echo esc_html( $cost ); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</li>
				<li class="active">
					<div class="collapsible-header"><i class="material-icons">place</i>Address</div>
					<div class="collapsible-body row" id="address-body">
						<div class="row">
							<div class="col s12">
								<div class="card blue-grey">
									<div class="card-content white-text">
										<span class="card-title">Billing Address</span>
										<p>Enter the address associated with your card.</p>
									</div>
								</div>
							</div>
						</div>
						<div class="container">
							<form class="col s12">
								<div class="row">
									<div class="input-field col s6">
										<input placeholder="first name" id="first_name" type="text" class="validate">
										<label for="first_name">first name</label>
									</div>
									<div class="input-field col s6">
										<input placeholder="last name" id="last_name" type="text" class="validate">
										<label for="last_name">last name</label>
									</div>
								</div>
								<div class="row">
									<div class="input-field col s12">
										<input placeholder="email" id="email" type="text" class="validate">
										<label for="email">email</label>
									</div>
								</div>
								<div class="row">
									<div class="input-field col s6">
										<input placeholder="street address" id="street_address" type="text" class="validate">
										<label for="street_address">street address</label>
									</div>
									<div class="input-field col s6">
										<input placeholder="suite / apt." id="suite" type="text" class="validate">
										<label for="suite">suite / apt.</label>
									</div>
								</div>
								<div class="row">
									<div class="input-field col s4">
										<input placeholder="city" id="city" type="text" class="validate">
										<label for="city">city</label>
									</div>
									<div class="input-field col s4">
										<input placeholder="state" id="state" type="text" class="validate">
										<label for="state">state</label>
									</div>
									<div class="input-field col s4">
										<input placeholder="zip" id="zip" type="text" class="validate">
										<label for="zip">zip</label>
									</div>
								</div>
							</form>
						</div>
					</div>
					<button id="collapse-button" class="btn waves-effect waves-light" type="submit" name="action"
						onclick="closeAddress(); openSubmit();">Next
						<i class="material-icons right">send</i>
					</button>
				</div>
				</li>
				<li id="checkout">
					<div class="collapsible-header"><i class="material-icons">credit_card</i>Submit</div>
					<div class="collapsible-body" id="submit">
						<span>
							<div class="container">
								<div class="row">
									<img class="responsive-img" id="accepted-cards"
										src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/t-1671/card-brands.png" border="0"
										alt="Accepted Cards" />
								</div>
								<div class="row">
									<div id="credit_card_iframe"></div>
								</div>
								<div class="row">
									<div class="card blue-grey lighten-2">
										<div class="card-content white-text">
											<p>By clicking submit, you agree to bring donunts to the Woodshop next Friday.</p>
										</div>
									</div>
								</div>
								<button class="btn waves-effect waves-light" type="submit" name="action"
									id="submit-credit-card-button">Submit
									<i class="material-icons right">send</i>
								</button>
								<div id="token"></div>
							</div>
						</span>
					</div>
				</li>
			</ul>
		</div>
		<?php
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
				'SELECT signup_id, signup_name, signup_cost, signup_rolling_template
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling = $signups[0]->signup_rolling_template > 0;
		$signup_name = $signups[0]->signup_name;
		$signup_cost = $signups[0]->signup_cost;
		$sessions    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT session_id,
				session_start_formatted,
				session_end_formatted,
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

			foreach ( $session_list as $attendee ) {
				if ( 'INSTRUCTOR' === $attendee->attendee_item ) {
					$instructors[ $session->session_id ][] = $attendee;
				} else {
					$attendees[ $session->session_id ][] = $attendee;
				}
			}
		}

		$today = new DateTime( 'now', $this->date_time_zone );
		$today->SetTime( 8, 0 );

		if ( $rolling ) {
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
				$signup_id,
				$template[0]
			);
		} else {
			$this->create_session_select_form(
				$signup_name,
				$sessions,
				$attendees,
				$instructors,
				$signup_cost
			);
		}
	}

	/**
	 * Add attendee to a class
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	private function add_attendee_class( $post ) {
		global $wpdb;
		$slot_parts = explode( ',', $post['time_slots'][0] );
		$slot_start = new DateTime( $slot_parts[0], $this->date_time_zone );
		$slot_end   = new DateTime( $slot_parts[1], $this->date_time_zone );
		$new_attendee = array();
		$new_attendee['attendee_session_id'] = $slot_parts[3];
		$new_attendee['attendee_email'] = $post['email'];
		$new_attendee['attendee_phone'] = $post['phone'];
		$new_attendee['attendee_paid_amount'] = 0;
		$new_attendee['attendee_lastname'] = $post['lastname'];
		$new_attendee['attendee_firstname'] = $post['firstname'];
		$new_attendee['attendee_item'] = $post['signup_name'];
		$new_attendee['attendee_badge'] = $post['badge_number'];
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
					$wpdb->query(
						$wpdb->prepare(
							'LOCK TABLES %1s WRITE, %1s READ',
							self::ATTENDEES_TABLE,
							self::SESSIONS_TABLE
						)
					);

					$current_session_attendees = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT * FROM %1s WHERE attendee_session_id = %d AND attendee_item != "INSTRUCTOR"',
							self::ATTENDEES_TABLE,
							$new_attendee['attendee_session_id'],
						)
					);

					$available_slots = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT session_slots FROM %1s WHERE session_id = %d',
							self::SESSIONS_TABLE,
							$new_attendee['attendee_session_id']
						)
					);

					$signed_up_already = false;
					$insert_return_value = false;
					if ( count( $current_session_attendees ) < $available_slots[0]->session_slots ) {
						$signed_up_already = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT attendee_badge FROM %1s WHERE attendee_session_id = %d AND attendee_badge = %d',
								self::ATTENDEES_TABLE,
								$new_attendee['attendee_session_id'],
								$new_attendee['attendee_badge']
							)
						);

						if ( ! $signed_up_already ) {
							$insert_return_value = $wpdb->insert( self::ATTENDEES_TABLE, $new_attendee );
						}
					}
					$wpdb->query( 'UNLOCK TABLES' );
					?>
					<tr class="attendee-row">
						<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
						<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
						<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
						<td><?php echo esc_html( $slot_parts[2] ); ?></td>
						<?php
						if ( ! $insert_return_value || $signed_up_already ) {
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
				<table>
			</form>
		</div>
		<?php
	}

	/**
	 * Add attendee for the selected spots
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	private function add_attendee( $post ) {
		global $wpdb;

		$new_attendee                       = array();
		$new_attendee['attendee_signup_id'] = $post['add_attendee_session'];
		$new_attendee['attendee_email']     = $post['email'];
		$new_attendee['attendee_phone']     = $post['phone'];
		$new_attendee['attendee_lastname']  = $post['lastname'];
		$new_attendee['attendee_firstname'] = $post['firstname'];
		$new_attendee['attendee_badge']     = $post['badge_number'];
		$insert_return_value = false;

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

				foreach ( $post['time_slots'] as $slot ) {
					$slot_parts = explode( ',', $slot );
					$slot_start = new DateTime( $slot_parts[0], $this->date_time_zone );
					$new_attendee['attendee_start_time']      = $slot_start->format( 'U' );
					$new_attendee['attendee_start_formatted'] = $slot_start->format( self::DATETIME_FORMAT );
					$slot_end = new DateTime( $slot_parts[1], $this->date_time_zone );
					$new_attendee['attendee_end_time']        = $slot_end->format( 'U' );
					$new_attendee['attendee_end_formatted']   = $slot_end->format( self::DATETIME_FORMAT );
					$new_attendee['attendee_item']            = trim( $slot_parts[2] );

					$comment_name = 'comment-' . $slot_parts[3];
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
				?>
				<tr class="attendee-row">
					<td></td>
					<td class="text-center">
						<button class="btn btn-primary signup-submit" type="submit" name="signup_id" value="<?php echo esc_html( $post['add_attendee_session'] ); ?>" >Return</button>
					</td>
					<td></td>
					<td class="text-center"><button class="btn btn-primary signup-submit" type="submit" name="signup_id" value="-1" >SignUps</button></td>
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
	 * Creates the form for selecting a signup to add to.
	 *
	 * @param  mixed $results The results of a DB query for available classes.
	 * @return void
	 */
	private function create_select_signup_form( $results ) {
		?>
		<form method="POST">
			<div id="usercontent" class="container">
				<table id="signup-select" class="mb-100px mr-auto ml-auto mt-5">
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
	 * @param  int    $cost The cost of the signup in dollars.
	 * @return void
	 */
	private function create_session_select_form( $signup_name, $sessions, $attendees, $instructors, $cost ) {
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2 entry-title"><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<div id="usercontent" class="container">
					<?php $this->create_user_table(); ?>
					<table id="selection-table" class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-125px" hidden>
						<?php
						foreach ( $sessions as $session ) {
							$start_date = new DateTime( $session->session_start_formatted );
							$end_date   = new DateTime( $session->session_end_formatted );
							?>
							<form class="signup_form" method="POST">
								<tr id="submit-row" class="date-row">
									<td class="text-left"> <?php echo esc_html( $start_date->format( self::DATE_FORMAT ) ); ?>
									<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $end_date->format( self::TIME_FORMAT ) ); ?></td>
									<td><button id=<?php echo esc_html( 'submit_' . $session->session_id ); ?>
												class="btn bth-md btn-primary mr-auto ml-auto mt-2 signup-submit"
												style="display: none;"
												type="submit">Submit</button></td>
								</tr>
								<?php $this->create_hidden_user(); ?>
								<input type="hidden" name="add_attendee_class">
								<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
								<input type="hidden" name="paid" value=false>
								<?php
								wp_nonce_field( 'signups', 'mynonce' );
								if ( isset( $attendees[ $session->session_id ] ) ) {
									foreach ( $attendees[ $session->session_id ] as $attendee ) {
										?>
										<tr class="attendee-row">
											<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
											<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
											<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
										</tr>
										<?php
									}
								}

								for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
									?>
									<tr class="attendee-row bg-lightgray" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
										<td>Cost: $<?php echo esc_html( $cost ); ?></td>
										<td><?php echo esc_html( $signup_name ); ?></td>
										<td class="text-center">
											<input class="form-check-input position-relative addChk" type="radio" 
												name="time_slots[]" 
												value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $end_date->format( self::DATETIME_FORMAT ) . ',' . $signup_name . ',' . $session->session_id . ',' . $cost ); ?>">
										</td>
									</tr>
									<?php
								}
								?>
							</form>
							<?php
						}
						$this->create_table_footer();
						?>
					</table>
				</div>
			</div>
		</div>
		<?php
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
	private function create_rolling_session_select_form( $signup_name, $attendees, $signup_id, $template ) {

		$start_time_parts = explode( ':', $template->rolling_start_time );
		$start_hour   = $start_time_parts[0];
		$start_minute = $start_time_parts[1];
		$start_date   = new DateTime( 'now', $this->date_time_zone );
		$end_date     = new DateTime( 'now', $this->date_time_zone );
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
		$today = new DateTime( 'now', $this->date_time_zone );
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
			$time_exception = new timeException();
			$time_exception->begin = new DateTime( $day->format( self::DATETIME_FORMAT ), $this->date_time_zone );
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
					<form class="signup_form" method="POST">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>

						<?php $this->create_user_table(); ?>

						<table id="selection-table" class="mb-100px mr-auto ml-auto" hidden>
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
													<td><?php echo esc_html( $current_day ); ?></td>
													<?php
													if ( '1' === $template->rolling_slots ) {
														?>
														<td><?php echo esc_html( $slot_titles[0] ); ?></td>
														<?php
													} else {
														?>
														<td></td>
														<?php
													}
													?>
													<td><button type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee">Submit</button></td>
												</tr>
												<?php
											}
											$temp_end_date = new DateTime( $start_date->format( self::DATETIME_FORMAT ), $this->date_time_zone );
											$temp_end_date->Add( $duration );
											$count = 0;
											foreach ( $slot_titles as $title ) {
												if ( count( $attendees ) > $attendee_index && $attendees[ $attendee_index ]->attendee_start_time === $start_date->format( 'U' ) &&
													$title === $attendees[ $attendee_index ]->attendee_item ) {
													?>
													<tr  class="attendee-row">
														<?php
														if ( 0 === $count ) {
															?>
															<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_end_date->format( self::TIME_FORMAT ) ); ?></td>
															<?php
															$count++;
														} else {
															?>
															<td></td>
															<?php
														}

														if ( null === $attendees[ $attendee_index ]->attendee_comment ) {
															if ( '1' === $template->rolling_slots ) {
																?>
																<td class="opacity-33"><?php echo esc_html( 'No Comment' ); ?></td>
																<?php
															} else {
																?>
																<td><?php echo esc_html( $title ); ?></td>
																<?php
															}
														} else {
															?>
															<td><?php echo esc_html( $attendees[ $attendee_index ]->attendee_comment ); ?></td>
															<?php
														}
														?>
														<td><?php echo esc_html( $attendees[ $attendee_index ]->attendee_firstname . ' ' . $attendees[ $attendee_index ]->attendee_lastname ); ?></td>
													</tr>
													<?php
													$attendee_index++;
												} else {
													$com_name = $comment_name . $comment_index;
													?>
													<tr class="attendee-row">
														<?php
														if ( 0 === $count ) {
															?>
															<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_end_date->format( self::TIME_FORMAT ) ); ?></td>
															<?php
															$count++;
														} else {
															?>
															<td></td>
															<?php
														}
														if ( $template->rolling_slots > 1 ) {
															?>
															<td><?php echo esc_html( $title ); ?></td>
															<?php
														} else {
															?>
															<td><input class="comment-text" type="hidden" name="<?php echo esc_html( $com_name ); ?>" placeholder="Comment"></td>
															<?php
														}
														?>
														<td class="text-center"> 
															<input class="form-check-input position-relative rolling-add-chk ml-auto" 
																type="checkbox" name="time_slots[]" 
																value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $slot_titles[0] . ',' . $comment_index . ',0' ); ?>">
														</td>
													</tr>
													<?php
												}
												$comment_index++;
											}
										}
									}
								}

								$start_date->add( $one_day_interval );
							}
							?>
							<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
							<input type="hidden" name="add_attendee_session" value="<?php echo esc_html( $signup_id ); ?>">
						</form>
						<?php
							$this->create_table_footer();
						?>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Creates a section of HTM for the user to identify themselves.
	 *
	 * @return void
	 */
	private function create_user_table() {
		?>
		<table id="lookup-member" class="mb-100px table table-bordered mr-auto ml-auto">
			<tr>
				<td>Enter Badge#</td>
				<td><input id="badge-input" class="member-badge" type="number" name="badge_number" value="4038" required></td>
				<td><input type="button" id="get_member_button" class="btn btn-primary" value='Lookup Member'></td>
			</tr>
			<tr>
				<td><input id="first-name" class=" member-first-name" type="text" name="firstname" value="First" required readonly></td>
				<td><input id="last-name" class="member-last-name" type="text" name="lastname" value="Last" required readonly></td>
				<td><button id="back-button" type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary"value="-1" name="signup_id">Back to SignUps</button></td>
			</tr>
			<tr>
				<td><input id="phone" class="member-phone" type="tel" name="phone" placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required readonly></td>
				<td><input id="email" class="member-email" type="email" name="email" placeholder="foo@bar.com" required readonly></td>
				<td></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Create hidden fields for the user info.
	 *
	 * @return void
	 */
	private function create_hidden_user() {
		?>
		<input class="member-badge" type="hidden" name="badge_number">
		<input class="member-first-name" type="hidden" name="firstname">
		<input class="member-last-name" type="hidden" name="lastname">
		<input class="member-phone" type="hidden" name="phone">
		<input class="member-email" type="hidden" name="email">
		<?php
	}

	/**
	 * Create footer for a table with a back to signups button.
	 *
	 * @param int $column_count The number of columns to generate in the row.
	 * @return void
	 */
	private function create_table_footer( $column_count = 3 ) {
		?>
		<form method="POST">
			<tr class="footer-row">
				<td><button id="back-button" type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="-1" name="signup_id">SignUps</button></td>
				<?php
				for ( $i = 1; $i < $column_count; $i++ ) {
					?>
					<td></td>
					<?php
				}
				?>
			</tr>
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}
}

