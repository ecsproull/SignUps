<?php
/**
 * Summary
 * Admin page for the signups plubin. Containes the functions for editing the signups.
 *
 * @package SignUps
 */
class SignupSettings extends SignUpsBase {

	/**
	 * The main function of the Plugin.
	 * This delegates all the real work to helper functions.
	 * Loading the class selection is the default.
	 * All others are triggered by a form submission.
	 */
	public function signup_settings_page() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
			if ( isset( $post['submit_class'] ) ) {
				$this->submit_class( $post );
			} elseif ( isset( $post['submit_session'] ) ) {
				$this->submit_session( $post );
			} elseif ( isset( $post['edit_class'] ) ) {
				$this->edit_class( $post );
			} elseif ( isset( $post['edit_session'] ) ) {
				$this->edit_session( $post );
			} elseif ( isset( $post['add_new_class'] ) ) {
				$this->create_signup_form( new ClassItem( null ) );
			} elseif ( isset( $post['add_new_session'] ) ) {
				$this->add_new_session_form( $post );
			} elseif ( isset( $post['delete_attendees'] ) ) {
				$this->delete_session_attendees( $post );
			} elseif ( isset( $post['move_attendees'] ) ) {
				$this->move_session_attendees( $post );
			} elseif ( isset( $post['add_attendee'] ) ) {
				$this->add_session_attendees( $post );
			} elseif ( isset( $post['submit_attendees'] ) ) {
				$this->submit_session_attendees( $post );
			} elseif ( isset( $post['edit_sessions_signup_id'] ) ) {
				$this->load_session_selection( $post );
			} elseif ( isset( $post['delete_session'] ) ) {
				$this->delete_session( $post );
			} elseif ( isset( $post['add_attendee_session'] ) ) {
				$this->add_attendee_rolling( $post );
			} elseif ( isset( $post['update_calendar'] ) ) {
				$this->update_calendar( $post );
			} elseif ( isset( $post['delete_class'] ) ) {
				$this->confirm_class_delete( $post );
			} elseif ( isset( $post['confirm_delete_class'] ) ) {
				$this->delete_class( $post );
			} else {
				$this->load_signup_selection();
			}
		} else {
			$this->load_signup_selection();
		}
	}

	/**
	 * Updates the clubs calendar.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function update_calendar( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_id = %s',
				self::SESSIONS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$session = $results[0];
		$new_calendar_id = 0;
		if ( $session->session_calendar_id > 0 ) {
			$where_session = array( 'id' => $session->session_calendar_id );
			$wpdb->delete( self::SPIDER_CALENDAR_EVENT_TABLE, $where_session );
		} else {
			$datetime = new DateTime( $session->session_start_formatted);
			$date = $datetime->format('Y-m-d');
			$start_time = $datetime->format('g:iA');
			$datetime = new DateTime( $session->session_end_formatted);
			$end_time = $datetime->format('g:iA');
			$signup_url = get_site_url() . '/signups?signup_id=' . $post['signup_id'];
			$text_for_date = 'For more information click <a href=' . $signup_url . " target='_blank' rel='noopener' >here</a>.";

			$data = Array();
			$data['calendar']      = 1;
			$data['date']          = $date;
			$data['date_end']      = $date;
			$data['title']         = $post['signup_name'];
			$data['category']      = 7;
			$data['time']          = $start_time . '-' . $end_time;
			$data['text_for_date'] = $text_for_date;
			$data['userID']        = '';
			$data['repeat_method'] = 'no_repeat';
			$data['repeat']        = '';
			$data['week']          = '';
			$data['month']         = '';
			$data['month_type']    = '1';
			$data['monthly_list']  = '';
			$data['month_week']    = '';
			$data['year_month']    = '1';
			$data['published']     = 1;
			$rows                  = $wpdb->insert( self::SPIDER_CALENDAR_EVENT_TABLE, $data );
			$new_calendar_id       = $wpdb->insert_id;
		}

		$where = Array();
		$update = Array();
		$where['session_id']           = $post['session_id'];
		$update['session_calendar_id'] = $new_calendar_id;
		$affected_row_count = $wpdb->update(
			'wp_scw_sessions',
			$update,
			$where
		);

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id']
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Submit class to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_class( $post ) {
		global $wpdb;
		$where                   = array();
		$where['signup_id']      = (int) $post['id'];
		$original_cost           = $post['original_cost'];
		$signup_default_price_id = $post['signup_default_price_id'];
		$signup_product_id       = $post['signup_product_id'];
		unset( $post['submit_class'] );
		unset( $post['id'] );
		unset( $post['original_cost'] );
		unset( $post['template_id'] );

		$post['signup_cost']             = (int) $post['signup_cost'];
		$post['signup_default_slots']    = (int) $post['signup_default_slots'];
		$post['signup_rolling_template'] = (int) $post['signup_rolling_template'];

		if ( isset( $post['signup_admin_approved'] ) ) {
			$post['signup_admin_approved'] = 1;
		} else {
			$post['signup_admin_approved'] = 0;
		}

		$affected_row_count = 0;
		if ( $where['signup_id'] ) {
			if ( $original_cost != $post['signup_cost'] ) {
				$stripe = new SripePayments();
				$new_price_id = $stripe->update_price( $signup_default_price_id, $signup_product_id, $post['signup_cost'] );
				if ( $new_price_id ) {
					$post['signup_default_price_id'] = $new_price_id;
				}
			}

			$affected_row_count = $wpdb->update(
				'wp_scw_signups',
				$post,
				$where
			);
		} else {
			if ( ( int )$post['signup_cost'] > 0 ) {
				$stripe = new SripePayments();
				$ret = $stripe->create_product( $post['signup_name'], $post['signup_cost'] );
				if ( $ret ) {
					$post['signup_product_id'] = $ret['product_id'];
					$post['signup_default_price_id'] = $ret['price_id'];
				} else {
					echo "Failed to create stripe pricing and product info";
					return;
				}
			}
			
			$affected_row_count = $wpdb->insert( self::SIGNUPS_TABLE, $post );
		}
		if ( $affected_row_count ) {
			?>
			<div class="text-center mb-4">
				<h1><?php echo esc_html( $affected_row_count ); ?> Rows Updated</h1>
			</div>
			<?php
		} else {
			?>
			<div class="text-center mb-4">
				<h1>Failed to insert Class: <?php echo esc_html( $wpdb->last_error ); ?></h1>
			</div>
			<?php
		}

		?>
		<input class="button-primary ml-auto mr-auto mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		<?php
	}

	/**
	 * Submit session to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_session( $post ) {
		global $wpdb;
		$signup = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_default_price_id
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['session_signup_id']
			),
			OBJECT
		);

		array_map(
			function( $start, $end, $keys ) use ( $post, $signup ) {
				global $wpdb;
				if ( $start && $end ) {
					$session = $post;
					$where = Array();
					
					if ( isset( $session['id'] )) {
						$where['session_id'] = $session['id'];
						unset( $session['id'] );
					}

					unset( $session['submit_session'] );
					$rows_updated                       = 0;
					$start_date                         = new DateTime( $start, $this->date_time_zone );
					$end_date                           = new DateTime( $end, $this->date_time_zone );
					$session['session_start_time']      = $start_date->format( 'U' );
					$session['session_end_time']        = $end_date->format( 'U' );
					$session['session_start_formatted'] = $start_date->format( self::DATETIME_FORMAT );
					$session['session_end_formatted']   = $end_date->format( self::DATETIME_FORMAT );

					if ( $signup[0]->signup_default_price_id ) {
						$session['session_price_id'] = $signup[0]->signup_default_price_id;
					} else {
						unset( $session['session_price_id'] );
					}

					if ( $where['session_id'] && $keys === 0) {
						$rows_updated = $wpdb->update( 'wp_scw_sessions', $session, $where );
					} else {
						$rows_updated = $wpdb->insert( 'wp_scw_sessions', $session );
					}

					$this->update_message( $rows_updated, $wpdb->last_error );
					$first_item = false;
				}
			},
			$post['session_start_formatted'],
			$post['session_end_formatted'],
			array_keys( $post['session_start_formatted'] )
		);
	}

	/**
	 * Edit a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function edit_class( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['edit_class']
			),
			OBJECT
		);
		$this->create_signup_form( $results[0] );
	}

	/**
	 * Confirm a class deletion.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function confirm_class_delete( $post ) {
		global $wpdb;
		$class = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['delete_class']
			),
			OBJECT
		);

		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$post['delete_class']
			),
			OBJECT
		);

		?>
		<div class="container ml-3 mt-3">
			<h1>Delete:  <?php echo esc_html( $class[0]->signup_name ); ?></h1>
			<h2>These sessions will also be deleted.</h2>
			<table class="mb-100px mt-4 table table-striped mr-auto ml-auto">
				<?php
				foreach ( $sessions as $session ) {
					?>
					<tr>
						<td class="w-25"><?php echo $class[0]->signup_name; ?></td>
						<td class="w-25"><?php echo $session->session_start_formatted; ?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<form method="POST">
				<div class="mt-2">
					<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="window.history.go( -0 );" value="Cancel">
					<button class="btn btn-success" type="submit" name="confirm_delete_class" value=<?php echo esc_html( $post['delete_class'] ); ?>>Confirm</button>
				</div>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Delete class and sessions.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_class( $post ) {
		global $wpdb;
		$where = array( 'signup_id' => $post['confirm_delete_class'] );
		$wpdb->delete( self::SIGNUPS_TABLE, $where_session );
		
		$where = array( 'session_signup_id' => $post['confirm_delete_class'] );
		$wpdb->delete( self::SESSIONS_TABLE, $where_session );
		$this->load_signup_selection();
	}

	/**
	 * Add a new session to a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_new_session_form( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_default_slots
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['add_new_session']
			),
			OBJECT
		);
		
		$session_item = new SessionItem( $post['add_new_session'] );
		$session_item->session_slots = $results[0]->signup_default_slots;
		$this->create_session_form( $session_item, $post['signup_name'], true );
	}

	/**
	 * Edit a session of a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function edit_session( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_id = %s',
				self::SESSIONS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$this->create_session_form( $results[0], $post['signup_name'], false );
	}

	/**
	 * Edit a session of a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_session( $post ) {
		global $wpdb;
		$where_session   = array( 'session_id' => $post['session_id'] );
		$rows_updated    = $wpdb->delete( self::SESSIONS_TABLE, $where_session );
		$where_attendees = array( 'attendee_session_id' => $post['session_id'] );
		$wpdb->delete( self::ATTENDEES_TABLE, $where_attendees );
		$this->update_message( $rows_updated, $wpdb->last_error );
	}

	/**
	 * Add attendees to a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_session_attendees( $post ) {
		global $wpdb;
		$query = '';
		if ( isset( $post['session_id'] ) ) {
			$query = 'SELECT * FROM ' . self::SESSIONS_TABLE . ' WHERE session_id = ' . $post['session_id'];
		} else {
			foreach ( $post['addedAttendee'] as $attendee_session ) {
				$parts = explode( ',', $attendee_session );
				if ( $query == '' ) {
					$query = 'SELECT * FROM ' . self::SESSIONS_TABLE . ' WHERE session_id = ' . $parts[1];
				} else {
					$query .= ' OR session_id = ' . $parts[1];
				}
			}
		}

		$sessions = null;
		if ( $query != '' ) {
			$sessions = $wpdb->get_results( $query, OBJECT );
		}

		$this->create_attendee_select_form( $post['signup_name'], $post['signup_id'], $sessions );
	}

	/**
	 * Submit attendees to a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_session_attendees( $post ) {
		global $wpdb;
		$sessions = unserialize( $post['sessions'] );
		foreach ( $sessions as $session ) {

			$new_attendee = array(
				'attendee_session_id'   => (int) $session->session_id,
				'attendee_email'        => $post['email'],
				'attendee_firstname'    => $post['firstname'],
				'attendee_lastname'     => $post['lastname'],
				'attendee_phone'        => $post['phone'],
				'attendee_badge'        => $post['badge_number'],
				'attendee_balance_owed' => 0,
				'attendee_item'         => $session->session_item,
			);

			$wpdb->insert( self::ATTENDEES_TABLE, $new_attendee );
		}

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id']
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Delete attendees from a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_session_attendees( $post ) {
		global $wpdb;
		foreach ( $post['selectedAttendee'] as $attendee ) {
			$attendee_id = explode( ',', $attendee )[0];
			$wpdb->delete( self::ATTENDEES_TABLE, array( 'attendee_id' => $attendee_id ) );
		}

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id']
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Move attendees from one class session to another session in the same class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function move_session_attendees( $post ) {
		global $wpdb;
		$ids   = explode( ',', $post['selectedAttendee'][0] );
		$data  = array( 'attendee_session_id' => $post['move_to'] );
		$where = array( 'attendee_id' => $ids[0] );
		$wpdb->update( self::ATTENDEES_TABLE, $data, $where );

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id']
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Load the class selection.
	 */
	private function load_signup_selection() {
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

		$this->create_signup_select_form( $results );
	}

	/**
	 * Load the session selection form. The session belong to one class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function load_session_selection( $post ) {
		$signup_id = $post['edit_sessions_signup_id'];
		$signup_name = $post[$signup_id];
		global $wpdb;
		$instructors = array();
		$attendees   = array();
		$class       = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_rolling_template
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling = $class[0]->signup_rolling_template > 0;

		if ( $rolling ) {
			$this->create_rolling_session( $signup_id, true );
		} else {

			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT session_id,
					session_start_formatted,
					session_start_time,
					session_slots,
					session_calendar_id
					FROM %1s
					WHERE session_signup_id = %s',
					self::SESSIONS_TABLE,
					$post['edit_sessions_signup_id']
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
				$post['edit_sessions_signup_id']
			);
		}
	}

	/**
	 * create_attendee_select_form
	 *
	 * @param mixed  $signup_name The name of the signup.
	 * @param number $signup_id Signup Id
	 * @param array  $sessions An array fo sessions.
	 * @return void
	 */
	private function create_attendee_select_form( $signup_name, $signup_id, $sessions ) {
		?>
		<form method="POST">
			<div class="text-center mt-5">
				<h1><?php echo esc_html( $signup_name ); ?></h1> <br>
				<h2>Add Attendee</h2>
				<div id="content" class="container">
					<table class="mb-100px mt-4 table table-striped mr-auto ml-auto">
						<?php
						foreach ( $sessions as $session ) {
							?>
							<tr>
								<td class="w-25"><?php echo $session->session_item; ?></td>
								<td class="w-25"><?php echo $session->session_start_formatted; ?></td>
								<td class="w-25"><?php echo $signup_name; ?></td>
								<td class="w-25"></td>
								<td class='w-75px'></td>
							</tr>
						
							<?php
						}
						?>
					</table>
					<?php
					$this->create_user_table( 'member' );
					?>
					<input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back"></td>
					<input id="submit_attendees" class="btn btn-primary mt-2" type="submit" value="Complete Add" name="submit_attendees" disabled="true"><td>
					<input type='hidden' name='sessions' value="<?php echo htmlentities( serialize( $sessions ) ); ?>" />
					<input type='hidden' name='signup_id' value="<?php echo $signup_id; ?>" />
					<input type='hidden' name='signup_name' value="<?php echo $signup_name; ?>" />
					<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Formats the message to display after an upate to the DB has been made.
	 *
	 * @param  mixed $rows_updated How many rows were updated in the database.
	 * @return void
	 */
	private function update_message( $rows_updated, $last_error ) {
		if ( 1 === $rows_updated ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated </h2>
				<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			</div>
			<?php
		} elseif ( $last_error === '' ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated, No Change </h2>
			</div>
			<?php
		} else {
			?>
			<div class="text-center mt-5">
				<h2> Error: <?php echo esc_html( $last_error ); ?> </h2>
				<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			</div>
			<?php
		}
		?>
		<div class="text-center mr-2">
			<input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Create the form to select a class to update.
	 *
	 * @param  array $results The class results from teh DB to list on the form.
	 * @return void
	 */
	private function create_signup_select_form( $results ) {
		?>
		<form method="POST">
			<div id="content" class="container">
				<table class="mb-100px table table-striped mr-auto ml-auto mt-5">
					<tr>
						<td>Add SignUp</td>
						<td></td>
						<td></td>
						<td> <input class="submitbutton addItem" type="submit" name="add_new_class" value="">
							<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						</td>
					</tr>
					<?php
					foreach ( $results as $result ) {
						?>
						<tr>
							<td> <?php echo esc_html( $result->signup_name ); ?></td>
							<td> <input class="submitbutton editImage" type="submit" name="edit_class" value="<?php echo esc_html( $result->signup_id ); ?>"> </td>
							<td> <input class="submitbutton sessionsImage" type="submit" name="edit_sessions_signup_id" value="<?php echo esc_html( $result->signup_id ); ?>"> </td>
							<td> <input class="submitbutton deleteImage" type="submit" name="delete_class" value="<?php echo esc_html( $result->signup_id ); ?>">
								<input type="hidden" name="<?php echo esc_html( $result->signup_id ); ?>" value="<?php echo esc_html( $result->signup_name ); ?>" >
								<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
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
		<div id="session_select" class="text-center mt-5">
			<h1><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<div id="content" class="container">
					<table class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-200px">
						<form method="POST">
						<tr style="background-color: lightyellow;">
							<td class="text-left" >
								<button class="ml-2 border-0 bg-transparent" type="submit" name="add_new_session" value="<?php echo esc_html( $signup_id ); ?>">
									<b>
										<i>
											<u>Add Session</u>
										</i>
									</b>
								</button>
							</td>
							<td style="width: 200px;"></td>
							<td></td>
							<td></td>
						</tr>
						<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						</form>
						<?php
						foreach ( $sessions as $session ) {
							$session_date_time = esc_html( $this->format_date( $session->session_start_formatted ) );
							if ( $session->session_calendar_id > 0 ) {
								$session_date_time = $session_date_time . '  &#128197';
							}
							?>
							<form method="POST">
							<tr>
								<td class="text-left"> 
									<?php echo $session_date_time; ?></td>
								<td></td>
								<td></td>
								<td>
									<div class="popup" data-textid=<?php echo esc_html( 'sessionid' . $session->session_id ); ?> ><b><i><u>Actions</u></i></b>
										<span class="popuptext" id=<?php echo esc_html( 'sessionid' . $session->session_id ); ?> >
											<input class="btn btn-primary w-90 mb-1" 
												type="submit"
												name="edit_session"
												value="Edit Session"> 
											<input class="btn btn-danger w-90 mb-1" 
												type="submit"
												name="delete_session"
												value="Delete Session" 
												onclick="return confirm('Confirm Session Delete')">
											<?php
											if ( count( $attendees[ $session->session_id ] ) < $session->session_slots ) {
												?>
												<input class="btn btn-success w-90" type="submit" name="add_attendee" value="Add Attendee">
												<?php
											}
											?>
											<input  id=<?php echo esc_html( 'move' . $session->session_id ); ?>
												class="btn btn-primary w-90 mb-1 mt-2"
												type="submit"
												name="move_attendees"
												value="Move Selected"
												disabled="true">
											<input class="btn btn-danger w-90 mb-1" 
												type="submit"
												name="delete_attendees"
												value="Delete Selected"
												onclick="return confirm('Confirm Attendee Delete')" >
											<?php
											if ( $session->session_calendar_id > 0 ) {
												?>
												<input class="btn btn-danger w-90" type="submit" name="update_calendar" value="Remove From Cal">
												<?php
											} else {
												?>
												<input class="btn btn-success w-90" type="submit" name="update_calendar" value="Add To Cal">
												<?php
											}
											?>
										</span>
									</div>
								</td>
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
								<tr class="drag-row" draggable="true" data-dragable="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>" >
									<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative selChk" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}

							for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
								?>
								<tr class="add-attendee-row" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
									<td class='addAtt'> Add Attendee</td>
									<td><?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?></td>
									<td></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative addChk" type="checkbox" name="addedAttendee[]" value="<?php $this->session_attendee_string( -1, $session->session_id ); ?>"> </td>
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
	 * Creates a form used to create a class.
	 *
	 * @param  class $data Raw data retrieved from the data base or an empty class if a new class is being created.
	 * @return void
	 */
	private function create_signup_form( $data ) {
		?>
		<div class="text-center mb-4">
			<h1><?php echo esc_html( $data->signup_name ); ?> </h1>
			<!-- img id="displayThumb" src="<?php echo esc_html( $data->signup_thumbnail_url ); ?>" alt="Class Thumbnail" -->
		</div>
		<form method="POST" >
			<table class="table table-striped mr-auto ml-auto">
				<tr>
					<td class="text-right mr-2"><label>Class Name:</label></td>
					<td><input class="w-250px" type="text" name="signup_name" value="<?php echo esc_html( $data->signup_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Email:</label></td>
					<td><input class="w-250px" type="email" name="signup_contact_email" value="<?php echo esc_html( $data->signup_contact_email ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Location:</label></td>
					<td><input class="w-250px" type="text" name="signup_location" value="<?php echo esc_html( $data->signup_location ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>User Group:</label></td>
					<td><select name="signup_group">
						<option value="member">Members</option>
						<option value="cnc">Cnc Users</option>
					</select> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Cost:</label></td>
					<td><input class="w-75px" type="number" name="signup_cost" value="<?php echo esc_html( $data->signup_cost ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Slots:</label></td>
					<td><input class="w-75px" type="number" name="signup_default_slots" value="<?php echo esc_html( $data->signup_default_slots ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Rolling Template:</label></td>
					<td>
					<?php
						$this->load_template_selection( $data->signup_rolling_template, false );
					?>
					</td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Admin Approved:</label></td>
					<td><input class="w-75px" type="checkbox" name="signup_admin_approved" value="" 
						<?php echo esc_html( $data->signup_admin_approved ) == '1'? 'checked ': ''; ?> /> </td>
		
				</tr>
				<tr>
					<td class="text-right mr-2"><input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit" name="submit_class"></td>
				</tr>
			</table>
			<input type="hidden" name="id" value="<?php echo esc_html( $data->signup_id ); ?>">
			<input type="hidden" name="original_cost" value="<?php echo esc_html( $data->signup_cost ); ?>">
			<input type="hidden" name="signup_default_price_id" value="<?php echo esc_html( $data->signup_default_price_id ); ?>">
			<input type="hidden" name="signup_product_id" value="<?php echo esc_html( $data->signup_product_id ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Creates a form used to create a class session.
	 *
	 * @param  class  $data Either an empty class or the data that represents the session being updated.
	 * @param  string $signup_name The name of the class the session belongs to.
	 * @param  bool   $add_new Adding a new class or updating an existing one.
	 * @return void
	 */
	private function create_session_form( $data, $signup_name, $add_new ) {
		$dt_start = new DateTime( $data->session_start_formatted );
		$start    = $dt_start->format( 'Y-m-d\TH:i' );
		$dt_end   = new DateTime( $data->session_end_formatted );
		$end      = $dt_end->format( 'Y-m-d\TH:i' );
		?>
		<div class="text-center mb-4 mr-100px">
			<h1><?php echo esc_html( $signup_name ); ?></h1>
		</div>
		<form method="POST">
			<table id="session-table" class="table table-striped mr-auto ml-auto">
				<tr>
					<td class="text-right mr-2"><label>Contact Name:</label></td>
					<td><input class="w-250px" type="text" name="session_contact_name" value="<?php echo esc_html( $data->session_contact_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Email:</label></td>
					<td><input class="w-250px" type="email" name="session_contact_email" value="<?php echo esc_html( $data->session_contact_email ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Location:</label></td>
					<td><input class="w-250px" type="text" name="session_location" value="<?php echo esc_html( $data->session_location ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Session Item:</label></td>
					<td><input class="w-250px" type="text" name="session_item" value="<?php echo esc_html( $data->session_item ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Slots: </label></td>
					<td><input class="w-250px" type="number" name="session_slots" value="<?php echo esc_html( $data->session_slots ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Minutes: </label></td>
					<td><input id="default-minutes" class="w-250px" type="number" value="180" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Start Time:</label></td>
					<td><input id="start-time" class="w-250px start-time" type="datetime-local" name="session_start_formatted[]" value="<?php echo esc_html( $start ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>End Time:</label></td>
					<td><input id="end-time" class="w-250px" type="datetime-local" name="session_end_formatted[]" value="<?php echo esc_html( $end ); ?>" /> </td>
				</tr>
			</table>
			<table class="table table-striped mr-auto ml-auto">
				<tr>
					<td></td>
					<td><button id="add-time-slot" type="button" value="1"><b><i>Add Time Slot</i></b></button></td>
				</tr>
				<tr>
					<td class="text-right mr-2"><input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit Session" name="submit_session"></td>
				</tr>
			</table>
			<input type="hidden" name="session_signup_id" value="<?php echo esc_html( $data->session_signup_id ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_html( $data->session_id ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}
}
