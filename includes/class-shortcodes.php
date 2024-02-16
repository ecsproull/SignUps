<?php
/**
 * Summary
 * Shortcode class.
 *
 * @package signups
 */

ob_start();

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 *
 * @package SignUps
 */
class ShortCodes extends SignUpsBase {

	/**
	 * Add the select class shortcode
	 */
	public function user_signup( $admin_view = false ) {
		$admin = false;
		if ( isset( $admin_view['admin'] ) ) {
			$admin = $admin_view['admin'];
		}
		$post = wp_unslash( $_POST );
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			if ( isset( $post['continue_signup'] ) ) {
				$this->create_signup_form( $post['continue_signup'], $post['secret'] );
			} elseif ( isset( $post['add_attendee_session'] ) ) {
				$this->add_attendee_rolling( $post );
			} elseif ( isset( $post['add_attendee_class'] ) ) {
				$this->add_attendee_class( $post );
			} elseif ( isset( $post['signup_id'] ) ) {
				if ( '-1' === $post['signup_id'] ) {
					$this->create_select_signup();
				} else {
					$this->create_description_form( $post['signup_id'] );
				}
			}
		} else {
			if ( get_query_var( 'signup_id' ) ) {
				if ( get_query_var( 'secret' ) ) {
					$this->create_description_form( get_query_var( 'signup_id' ), get_query_var( 'secret' ) );
				} else {
					$this->create_description_form( get_query_var( 'signup_id' ) );
				}
			} else {
				$this->create_select_signup( $admin_view );
			}
		}
	}

	/**
	 * Creates a section of HTML for a new user to identify themselves.
	 *
	 * @return void
	 */
	protected function create_new_user_table() {
		?>
		<table id="new-member" class="mb-100px table table-bordered mr-auto ml-auto">
			<tr>
				<td class="text-right font-weight-bold">SCW Rec Number:</td>
				<td class="text-left"><input type="number" name="reccard" placeholder="123456" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">First Name:</td>
				<td class="text-left"><input type="text" name="firstname" placeholder="First Name" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Last Name:</td>
				<td class="text-left"><input type="text" name="lastname" placeholder="Last Name" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Phone Number:</td>
				<td class="text-left"><input  type="text" name="phone"
					placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Email:</td>
				<td class="text-left"><input type="email" name="email" placeholder="foo@bar.com" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Street Address 1:</td>
				<td class="text-left"><input type="text" name="address1"
					placeholder="Sun City West Street Address" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Street Address 2:</td>
				<td class="text-left"><input type="text" name="address2" placeholder="Unit 1234"></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">City:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="city" value="Sun City West" required readonly></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">State:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="state"	value="AZ"  required readonly></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Zip Code:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="zip"	value="85375"  required readonly></td>
			</tr>
		</table>
		<input id="user_groups" type="hidden" name="user_groups" value="none">
		<?php
	}

	/**
	 * Retrieves the available signups  and
	 * creates a form for the user to select a signup to add himself to.
	 *
	 * @return void
	 */
	private function create_select_signup( $admin_view = false ) {
		global $wpdb;
		$signups = null;
		if ( $admin_view ) {
			$signups = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT signup_id,
					signup_name,
					signup_category
					FROM %1s
					ORDER BY signup_order',
					self::SIGNUPS_TABLE
				),
				OBJECT
			);
		} else {
			$signups = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT signup_id,
					signup_name,
					signup_category
					FROM %1s
					WHERE signup_admin_approved = 1
					ORDER BY signup_order',
					self::SIGNUPS_TABLE
				),
				OBJECT
			);
		}

		$categories = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::SIGNUP_CATEGORY_TABLE
			),
			OBJECT
		);

		$this->create_select_signup_form( $signups, $categories );
	}

	/**
	 * Creates the form to sign up.
	 *
	 * @param string $signup_id The id of the signup to create a form for.
	 * @return void
	 */
	private function create_signup_form( $signup_id, $secret = null ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id, signup_name, signup_cost, signup_rolling_template, signup_default_price_id, signup_group
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling     = $signups[0]->signup_rolling_template > 0;
		$signup_name = $signups[0]->signup_name;

		if ( $rolling ) {
			$this->create_rolling_session( $signup_id, $secret );
		} else {
			$bad_debt = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT attendee_id, 
					attendee_payment_start
					FROM %1s
					WHERE 0 < attendee_balance_owed',
					self::ATTENDEES_TABLE
				),
				OBJECT
			);

			$dt_now       = new DateTime( 'now', $this->date_time_zone );
			$five_minutes = new DateInterval( 'PT5M' );
			foreach ( $bad_debt as $bd ) {
				$dt_start = new DateTime( $bd->attendee_payment_start, $this->date_time_zone );
				$dt_start->add( $five_minutes );
				if ( $dt_start->format( 'U' ) < $dt_now->format( 'U' ) ) {
					$where = array( 'attendee_id' => $bd->attendee_id );
					$wpdb->delete( self::ATTENDEES_TABLE, $where );
				}
			}

			$signup_cost             = $signups[0]->signup_cost;
			$signup_default_price_id = $signups[0]->signup_default_price_id;
			$sessions                = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT session_id,
					session_start_formatted,
					session_end_formatted,
					session_start_time,
					session_slots,
					session_price_id
					FROM %1s
					WHERE session_signup_id = %s
					ORDER BY session_start_time',
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

			$this->create_session_select_form(
				$signup_name,
				$sessions,
				$attendees,
				$instructors,
				$signup_cost,
				$signup_id,
				$signups[0]->signup_group
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
		$now                                    = new DateTime( 'now', $this->date_time_zone );
		$slot_parts                             = explode( ',', $post['time_slots'][0] );
		$slot_start                             = new DateTime( $slot_parts[0], $this->date_time_zone );
		$slot_end                               = new DateTime( $slot_parts[1], $this->date_time_zone );
		$signup_name                            = $post['signup_name'];
		$cost                                   = $slot_parts[4];
		$new_attendee                           = array();
		$new_attendee['attendee_session_id']    = $slot_parts[3];
		$new_attendee['attendee_email']         = $post['email'];
		$new_attendee['attendee_phone']         = $post['phone'];
		$new_attendee['attendee_balance_owed']  = $cost;
		$new_attendee['attendee_lastname']      = $post['lastname'];
		$new_attendee['attendee_firstname']     = $post['firstname'];
		$new_attendee['attendee_item']          = $post['signup_name'];
		$new_attendee['attendee_badge']         = $post['badge_number'];
		$new_attendee['attendee_payment_start'] = $now->format( self::DATETIME_FORMAT );
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

					$signed_up_already   = false;
					$insert_return_value = false;
					$last_id             = 0;
					if ( count( $current_session_attendees ) < $available_slots[0]->session_slots ) {
						if ( $new_attendee['attendee_badge'] ) {
							$signed_up_already = $wpdb->get_results(
								$wpdb->prepare(
									'SELECT attendee_badge FROM %1s WHERE attendee_session_id = %d AND attendee_badge = %d',
									self::ATTENDEES_TABLE,
									$new_attendee['attendee_session_id'],
									$new_attendee['attendee_badge']
								)
							);
						} else {
							$signed_up_already = $wpdb->get_results(
								$wpdb->prepare(
									'SELECT * FROM %1s WHERE attendee_session_id = %d AND 
										attendee_firstname = %s AND
										attendee_lastname  = %s AND
										attendee_phone     = %s',
									self::ATTENDEES_TABLE,
									$new_attendee['attendee_session_id'],
									$new_attendee['attendee_firstname'],
									$new_attendee['attendee_lastname'],
									$new_attendee['attendee_phone']
								)
							);
						}

						if ( ! $signed_up_already ) {
							$insert_return_value = $wpdb->insert( self::ATTENDEES_TABLE, $new_attendee );
							$last_id             = $wpdb->insert_id;
						}
					}
					$wpdb->query( 'UNLOCK TABLES' );

					/**
					 * Four checks before we collect money.
					 * 1.) There is a balance owed which will be the full amount.
					 * 2.) The last inserted ID is valid.
					 * 3.) The insert didn't fail.
					 * 4.) Exactly one row is inserted. It can be 0 but never more than 1.
					 */
					if (
						0 !== (int) $new_attendee['attendee_balance_owed'] &&
						0 !== $last_id &&
						$insert_return_value
					) {
						$description = $signup_name . ' - ' . $slot_start->format( self::DATETIME_FORMAT );
						$payments    = new StripePayments();
						if ( ! $post['session_price_id'] ) {
							$signups = $wpdb->get_results(
								$wpdb->prepare(
									'SELECT signup_product_id
									FROM %1s
									WHERE signup_id = %s',
									self::SIGNUPS_TABLE,
									$signup_id
								),
								OBJECT
							);

							if ( ! $signups[0]->signup_product_id ) {
								$ret = $payments->create_product( $post['signup_name'], $cost );
								if ( $ret ) {
									$data                            = array();
									$data['signup_product_id']       = $ret['product_id'];
									$data['signup_default_price_id'] = $ret['price_id'];

									$where              = array();
									$where['signup_id'] = $post['session_signup_id'];
									$affected_row_count = $wpdb->update(
										'wp_scw_signups',
										$data,
										$where
									);

									if ( ! $affected_row_count ) {
										echo 'Failed to update signup with pricing and product info.';
										return;
									}

									$post['session_price_id'] = $ret['price_id'];

								} else {
									echo 'Failed to create stripe pricing and product info.';
									return;
								}
							}
						}

						$payments->collect_money( $description, $post['session_price_id'], $new_attendee['attendee_badge'], $last_id, $cost );
					}
					?>
					<tr class="attendee-row">
						<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
						<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
						<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
						<td><?php echo esc_html( $slot_parts[2] ); ?></td>
						<?php
						if ( $signed_up_already ) {
							?>
							<td style="color:red"><b><i>Failed, Signed up alread</i></b></td>
							<?php
						} elseif ( ! $insert_return_value ) {
							?>
							<td style="color:red"><b><i>Failed DB Insert</i></b></td>
							<?php
						} else {
							?>
							<td>Success</td>
							<?php
						}
						?>
					</tr>
				</table>
			</form>
		</div>
		<?php
	}


	/**
	 * Creates the form for selecting a signup to add to.
	 *
	 * @param  mixed $signups The results of a DB query for available classes.
	 * @param  mixed $categories the list of categories.
	 * @return void
	 */
	private function create_select_signup_form( $signups, $categories ) {
		?>
		<form method="POST">
			<div id="usercontent">
				<div id="signup-select" class="signup-category-list selection-font mb-100px mr-auto ml-auto mt-5">
					<?php
					$count = 0;
					foreach ( $categories as $category ) {
						?>
						<div class="text-center mb-4">
							<div class="border-top3 pt-2 bg-lightgray h-65px">
								<h2><?php echo esc_html( $category->category_title ); ?></h2>
							</div>
							<?php
							foreach ( $signups as $signup ) {
								if ( $signup->signup_category === $category->category_id ) {
									?>
									<button class="button-signup" type="submit" name="signup_id" value="<?php echo esc_html( $signup->signup_id ); ?>" >
										<i><u><?php echo esc_html( $signup->signup_name ); ?></u></i>
									</button>
									<?php
								}
							}
							?>
						</div>
						<?php
						$count++;
					}

					if ( $count % 4 > 0) {
						$remainder = 4 - ( $count % 4 );
						for ( $i = 0; $i < $remainder; $i++ ) {
							?>
							<div class="text-center mb-4">
								<div class="border-top3 pt-2 bg-lightgray h-65px">
								</div>
							</div>
							<?php
						}
					}
					?>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Creates a form that displays the sessions along with their attendees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  array  $instructors The list of instructors for the class.
	 * @param  int    $cost The cost of the signup in dollars.
	 * @param  string $signup_id The signup id.
	 * @param  string $user_group The group that defines who can signup.  CNC, Member...etc.
	 * @return void
	 */
	private function create_session_select_form( $signup_name, $sessions, $attendees, $instructors, $cost, $signup_id, $user_group ) {
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<form class="signup_form" method="POST">
					<div id="usercontent">
						<?php
						if ( 'none' == $user_group ) {
							$user_badge = true;
							$this->create_new_user_table();
						} else {
							$user_badge = $this->create_user_table( $user_group, $signup_id );
						}
						?>
						<table id="selection-table" class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-125px selection-font"
							<?php echo null === $user_badge ? 'hidden' : ''; ?> >
							<?php
							$sessions_displayed = 0;
							foreach ( $sessions as $session ) {
								$now = new DateTime( 'now', $this->date_time_zone );
								if ( $session->session_start_time < $now->format( 'U' ) ) {
									continue;
								}
								$sessions_displayed++;
								?>
								<tr class="submit-row">
									<td colspan='3'><button id=<?php echo esc_html( 'submit_' . $session->session_id ); ?>
												class="btn btn-md btn-primary mr-auto ml-auto mt-2 signup-submit"
												type="submit">Submit</button>
									</td>
								</tr>
								<?php
								$start_date = new DateTime( $session->session_start_formatted );
								$end_date   = new DateTime( $session->session_end_formatted );
								?>
								<tr id="submit-row" class="date-row">
									<td class="text-center" colspan="3"> 
										<?php
										echo esc_html(
											$start_date->format( self::DATE_FORMAT ) .
											' - ' . $start_date->format( self::TIME_FORMAT ) . ' - ' . $end_date->format( self::TIME_FORMAT )
										);
										?>
								</tr>
								<input type="hidden" name="add_attendee_class">
								<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
								<input type="hidden" name="session_price_id" value="<?php echo esc_html( $session->session_price_id ); ?>">
								<input type="hidden" name="session_signup_id" value="<?php echo esc_html( $signup_id ); ?>">
								<input type="hidden" name="paid" value=false>
								<?php
								wp_nonce_field( 'signups', 'mynonce' );

								$available_slots = $session->session_slots - count( $attendees[ $session->session_id ] );
								for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
									?>
									<tr class="attendee-row bg-lightgreen" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
										<td>Cost: $<?php echo esc_html( $cost ); ?></td>
										<td><?php echo esc_html( $signup_name ); ?></td>
										<td>
											<input class="ml-auto mr-auto addChk" type="radio" 
												name="time_slots[]" 
												value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $end_date->format( self::DATETIME_FORMAT ) . ',' . $signup_name . ',' . $session->session_id . ',' . $cost ); ?>">
										</td>
									</tr>
									<?php
									break;
								}
								?>
								<tr class="attendee-row bg-lg">
									<td></td>
									<td><b><?php echo esc_html( $available_slots . ' slots open - ' . count( $attendees[ $session->session_id ] ) . ' filled' ); ?></b></td>
									<td></td>
								</tr>
								<?php
								$count = 0;
								if ( isset( $attendees[ $session->session_id ] ) ) {
									foreach ( $attendees[ $session->session_id ] as $attendee ) {
										$count++;
										?>
										<tr class="attendee-row <?php echo esc_html( $count > 3 ? $session->session_id : '' ); ?>" <?php echo $count > 3 ? 'hidden' : ''; ?> >
											<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
											<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
											<?php
											if ( 'INSTRUCTOR' === $attendee->attendee_item ) {
												?>
												<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
												<?php
											} elseif ( '0' === $attendee->attendee_balance_owed ) {
												?>
												<td><?php echo esc_html( 'Paid' ); ?></td>
												<?php
											} else {
												?>
												<td><?php echo esc_html( 'Payment Pending' ); ?></td>
												<?php
											}
											?>
										</tr>
										<?php
									}
								}
								?>
								<tr class="attendee-row bg-dark">
									<td></td>
									<td></td>
									<td>
									<?php
									if ( $count > 3 ) {
										?>
										<button class="btn btn-sm bg-primary mr-auto ml-auto expand-button" type='button' 
											data-button='{"session_id": <?php echo esc_html( $session->session_id ); ?>}' >Show All</button>
										<?php
									}
									?>
									</td>
								</tr>
								<?php
							}

							if ( 0 === $sessions_displayed ) {
								?>
								<h1>"There are currently no future sessions scheduled for this class."</h1>
								<?php
							}

							$this->create_table_footer();
							?>
						</table>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Creates a signup description block
	 *
	 * @param  mixed $signup_id Id of the signup.
	 * @return void
	 */
	private function create_description_form( $signup_id, $secret = null ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_name,
				signup_contact_email,
				signup_default_contact_name,
				signup_default_duration,
				signup_default_days_between_sessions,
				signup_default_day_of_month,
				signup_cost,
				signup_default_slots,
				signup_default_minimum,
				signup_multiple_days,
				signup_schedule_desc
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$signup             = $signups[0];
		$description_object = $this->get_signup_html( $signup_id );
		$schedule           = 'Schedule for this class has not been set';
		if ( $signup->signup_schedule_desc ) {
			$schedule = $signup->signup_schedule_desc;
		} elseif ( $signup->signup_default_duration ) {
			$dt_parts = explode( ':', $signup->signup_default_duration );

			if ( 1 === (int) $dt_parts[0] ) {
				$schedule = (int) $dt_parts[0] . ' hour';
			} else {
				$schedule = (int) $dt_parts[0] . ' hours';
			}

			if ( '00' !== $dt_parts[1] ) {
				$schedule .= ' & ' . $dt_parts[1] . ' minutes';
			}

			if ( $signup->signup_multiple_days > 1 ) {
				$schedule .= ' for ' . $signup->signup_multiple_days . ' days';
			}

			if ( $signup->signup_default_day_of_month ) {
				$schedule .= ', The ' . $signup->signup_default_day_of_month . ' of the month';
			} elseif ( $signup->signup_default_days_between_sessions ) {
				$schedule .= ', Every ' . $signup->signup_default_days_between_sessions . ' days';
			}
		}

		if ( $signup->signup_default_slots ) {
			$schedule .= '. Max ' . $signup->signup_default_slots . ' students';
		} else {
			$schedule .= '.';
		}

		if ( $signup->signup_default_minimum ) {
			$schedule .= '. Min ' . $signup->signup_default_minimum . ' students.';
		} else {
			$schedule .= '.';
		}

		if ( ! $description_object ) {
			$this->create_signup_form( $signup_id, $secret );
		} else {
			?>
			<div class="text-center"><h1 ><?php echo esc_html( $signup->signup_name ); ?></h1></div>
			<div class="description-box description-block">
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Cost: </div>
				<div><?php echo '$' . esc_html( $signup->signup_cost ) . '.00'; ?></div>

				<?php
				if ( $description_object->description_instructors ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Instructors: </div>
					<div><?php echo esc_html( $description_object->description_instructors ); ?></div>
					<?php
				}
				?>
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Schedule: </div>
				<div><?php echo esc_html( $schedule ); ?></div>

				<?php
				if ( $description_object->description_prerequisite ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Prerequisite: </div>
					<div><?php echo $description_object->description_prerequisite; ?></div>
					<?php
				}

				if ( $description_object->description_materials ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Materials: </div>
					<div><?php echo esc_html( $description_object->description_materials ); ?></div>
					<?php
				}

				if ( $description_object->description_instructions ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Instructions: </div>
					<div><?php echo $description_object->description_instructions; ?></div>
					<?php
				}
				?>

				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Description: </div>
				<div><?php echo html_entity_decode( $description_object->description_html ); ?></div>

				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Contact: </div>
				<div><a href="mailto:<?php echo esc_html( $signup->signup_contact_email ); ?>?subject=<?php echo esc_html( $signup->signup_name ); ?>">
					<?php echo esc_html( $signup->signup_default_contact_name ); ?></a></div>
			</div>
			<div class="row">
				<form class="ml-auto mr-auto" method="POST">
					<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
					<button type="submit" class="btn btn-md bg-primary mr-2" value="-1" name="signup_id">Cancel</button>
					<button id='accept_conditions' class="btn btn-primary" type='submit' value="<?php echo esc_html( $signup_id ); ?>" name="continue_signup">Continue</button>
					<input type="hidden" name="secret" value="<?php echo esc_html( $secret ); ?>">
				</form>
			</div>
			<?php
		}
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
	 * Create footer for a table with a Cancel button.
	 *
	 * @param int $column_count The number of columns to generate in the row.
	 * @return void
	 */
	private function create_table_footer( $column_count = 3 ) {
		?>
		<form method="POST">
			<tr class="footer-row">
				<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
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
