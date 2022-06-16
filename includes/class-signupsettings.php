<?php
/**
 * Summary
 * Database class.
 *
 * @package SignUps
 */

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 */
class SignupSettings extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}


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
				$this->create_class_form( new ClassItem( null ) );
			} elseif ( isset( $post['add_new_session'] ) ) {
				var_dump( $post );
				$this->add_new_session_form( $post );
			} elseif ( isset( $post['attendees'] ) ) {
				$this->edit_session_attendees( $post );
			} elseif ( isset( $post['delete_attendees'] ) ) {
				$this->delete_session_attendees( $post );
			} elseif ( isset( $post['move_attendees'] ) ) {
				$this->move_session_attendees( $post );
			} elseif ( isset( $post['add_attendee'] ) ) {
				$this->add_session_attendees( $post );
			} elseif ( isset( $post['select_session'] ) ) {
				$this->load_session_selection( $post );
			} elseif ( isset( $post['delete_session'] ) ) {
				$this->delete_session( $post );
			}
		} else {
			$this->load_class_selection();
		}
	}

	/**
	 * Submit class to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_class( $post ) {
		global $wpdb;
		$where             = array();
		$where['class_id'] = $post['id'];
		unset( $post['submit_class'] );
		unset( $post['id'] );
		$affected_row_count = 0;
		if ( $where['class_id'] ) {
			$affected_row_count = $wpdb->update(
				'wp_scw_classes',
				$post,
				$where
			);
		} else {
			$affected_row_count = $wpdb->insert( 'wp_scw_classes', $post );
		}
		if ( $affected_row_count ) {
			?>
			<div class="text-center mb-4">
				<h1><?php echo esc_html( $affected_row_count ); ?> Rows Updated</h1>
			</div>
			<?php
		}

		?>
		<input class="button-primary ml-auto mr-auto" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		<?php
	}

	/**
	 * Submit session to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_session( $post ) {
		global $wpdb;
		$where = array( 'session_id' => $post['id'] );
		unset( $post['id'] );
		unset( $post['submit_session'] );
		$rows_updated = 0;
		if ( $where['session_id'] ) {
			$rows_updated = $wpdb->update( 'wp_scw_sessions', $post, $where );
		} else {
			$rows_updated = $wpdb->insert( 'wp_scw_sessions', $post );
		}
		$this->update_message( $rows_updated );
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
				FROM awp.wp_scw_classes
				WHERE class_id = %s',
				$post['edit_class']
			),
			OBJECT
		);
		$this->create_class_form( $results[0] );
	}

	/**
	 * Add a new session to a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_new_session_form( $post ) {
		var_dump( $post );
		$session_item = new SessionItem( $post['add_new_session'] );
		$this->create_session_form( $session_item, $post['class_name'], true );
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
				FROM awp.wp_scw_sessions
				WHERE session_id = %s',
				$post['session_id']
			),
			OBJECT
		);

		$this->create_session_form( $results[0], $post['class_name'], false );
	}

	/**
	 * Edit a session of a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_session( $post ) {
		global $wpdb;
		$where_session = array( 'session_id' => $post['session_id'] );
		$rows_updated = $wpdb->delete( self::SESSIONS_TABLE, $where_session );
		$where_attendees = array ( 'attendee_session_id' => $post['session_id'] );
		$wpdb->delete( self::ATTENDEES_TABLE, $where_attendees );
		$this->update_message( $rows_updated );
	}

	/**
	 * Edit the attendees of a session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function edit_session_attendees( $post ) {
		global $wpdb;
		$results_session = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM awp.wp_scw_sessions
				WHERE session_id = %s',
				$post['attendees']
			),
			OBJECT
		);
		$session         = $results_session[0];

		$results_class = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT class_default_slots
				FROM awp.wp_scw_classes
				WHERE class_id = %s',
				$session->session_class_id
			),
			OBJECT
		);

		$default_attendee_slots = $results_class[0]->class_default_slots;

		$session_list = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM awp.wp_scw_attendees
				WHERE attendee_session_id = %s  AND
				attendee_email != "" ',
				$session->session_id
			),
			OBJECT
		);

		$attendees = array_filter(
			$session_list,
			function ( $obj ) {
				if ( 'INSTRUCTOR' === $obj->attendee_item ) {
					return false;
				} else {
					return true;
				}
			}
		);

		$instructors = array_filter(
			$session_list,
			function ( $obj ) {
				if ( 'INSTRUCTOR' === $obj->attendee_item ) {
					return true;
				} else {
					return false;
				}
			}
		);

		$this->create_attendee_form( $default_attendee_slots, $attendees, $instructors, $post['class_name'], $session->session_id );
	}

	/**
	 * Add attendees to a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_session_attendees( $post ) {
		var_dump( $post );
		$this->create_attendee_select_form( $post );
	}

	/**
	 * Delete attendees from a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_session_attendees( $post ) {
		echo 'deleteSessionAttendees';
		echo var_dump( $post );
	}

	/**
	 * Move attendees from one class session to another.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function move_session_attendees( $post ) {
		echo 'moveSessionAttendees';
		echo var_dump( $post );
	}

	/**
	 * Load the class selection.
	*/
	private function load_class_selection() {
		global $wpdb;
		$results = $wpdb->get_results(
			'SELECT class_id,
			class_name
			FROM awp.wp_scw_classes',
			OBJECT
		);

		$this->create_class_select_form( $results );
	}

	/**
	 * Load the session selection form. The session belong to one class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function load_session_selection( $post ) {

		$class_name = $post[ $post['select_session'] ];
		global $wpdb;
		$instructors = array();
		$attendees   = array();
		$class   = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT class_rolling
				FROM awp.wp_scw_classes
				WHERE class_id = %s',
				$post['select_session']
			),
			OBJECT
		);

		$rolling =  $class[0]->class_rolling > 0;

		$sessions    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT session_id,
				session_start_formatted,
				session_start_time,
				session_slots
				FROM awp.wp_scw_sessions
				WHERE session_class_id = %s',
				$post['select_session']
			),
			OBJECT
		);

		foreach ( $sessions as $session ) {
			$attendees[ $session->session_id ]   = array();
			$instructors[ $session->session_id ] = array();
			$session_list                        = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM awp.wp_scw_attendees
					WHERE attendee_session_id = %s
					AND attendee_email != ""',
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

		if ( $rolling ) {
			$this->create_rolling_session_select_form( 
				$class_name,
				$sessions,
				$attendees,
				$post['select_session']
			);
		} else {
			$this->create_session_select_form( 
				$class_name,
				$sessions,
				$attendees,
				$instructors,
				$post['select_session']
			);
		}
	}

	/**
	 * Formats a string to be passed back in form data.
	 *
	 * @param int $attendee_id Attendee ID.
	 * @param int $session_id Session ID.
	 */
	private function session_attendee_string( $attendee_id, $session_id ) {
		echo esc_html( $attendee_id . ',' . $session_id );
	}
	
	/**
	 * create_attendee_select_form
	 *
	 * @param  mixed $post
	 * @return void
	 */
	private function create_attendee_select_form( $post ) {
		?>
		<div class="text-center mt-5">
			<h1><?php echo esc_html( $post['class_name'] ); ?></h1> <br>
			<h2>Add Attendee</h2>
			<div>
				<div id="content" class="container">
					<table class="mb-100px table table-striped mr-auto ml-auto">
						<tr>
							<td class="w-25">Enter Badge#</td>
							<td class="w-25"><input id="badge_input" type="number" #badgeNumber></td>
							<td class="w-25"><button id="get_member_button" class="btn btn-primary">Get Member</button></td>
							<td class="w-25"></td>
							<td class='w-75px'></td>
						</tr>
						<tr>
							<td><input id="firstname" name="firstname" type='text'></td>
							<td><input id="lastname" name="lastname" type='text'></td>
							<td><input id="phone" name="phone" type='text'></td>
							<td><input id="email" name="email" type='text'></td>
							<td><input id="badge" name="badge" type='text'></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Create attendee information form.
	 *
	 * @param  int    $default_attendee_slots Number of slots available.
	 * @param  array  $attendees List of attendees.
	 * @param  array  $instructors List of instructors.
	 * @param  string $class_name Name of the class.
	 * @param  int    $session_id Session ID.
	 * @return void
	 */
	private function create_attendee_form( $default_attendee_slots, $attendees, $instructors, $class_name, $session_id ) {
		?>
		<form method="POST">
			<div class="text-center mt-5">
				<h1><?php echo esc_html( $class_name ); ?></h1> <br>
				<h2>Add Remove Attendees</h2>
				<div>
					<div id="content" class="container">
						<table class="mb-100px table table-striped mr-auto ml-auto">
							<?php
							if ( count( $attendees ) < $default_attendee_slots ) {
								?>
								<tr>
									<td>Add Attendee</td>
									<td></td>
									<td></td>
									<td> <input class="submitbutton addItem" type="submit" name="add_attendee" value="<?php echo esc_html( $session_id ); ?>"></td>
								</tr>
								<?php
							}

							foreach ( $instructors as $instructor ) {
								?>
								<tr>
									<td> <?php echo esc_html( $instructor->attendee_firstname . ' ' . $instructor->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_item ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_email ); ?></td>
									<td> <input class="form-check-input position-relative" type="checkbox" name="selectedAttendee[]" value="<?php $this->sessionAttendeeString( $instructor->attendee_id, $session_id ); ?>"> </td>
								</tr>
								<?php
							}
							?>

							<?php
							foreach ( $attendees as $attendee ) {
								?>
								<tr>
									<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
									<td> <input class="form-check-input position-relative" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $attendee->attendee_id, $session_id ); ?>"> </td>
								</tr>
								<?php
							}
							?>
						</table>
						<input class="btn btn-danger" type="submit" value="Delete" name="delete_attendees">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
					</div>
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
	private function update_message( $rows_updated ) {
		if ( 1 === $rows_updated ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated </h2>
			</div>
			<?php
		} else {
			?>
			<div class="text-center mt-5">
				<h2> Something went wrong. </h2>
				<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			</div>
			<?php
		}
		?>
		<div class="text-center mr-2">
			<input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Create the form to select a class to update.
	 *
	 * @param  array $results The class results from teh DB to list on the form.
	 * @return void
	 */
	private function create_class_select_form( $results, ) {
		?>
		<form method="POST">
			<div id="content" class="container">
				<table class="mb-100px table table-striped mr-auto ml-auto">
					<tr>
						<td>Add SignUp</td>
						<td></td>
						<td></td>
						<td> <input class="submitbutton addItem" type="submit" name="add_new_class" value=""></td>
					</tr>
					<?php
					foreach ( $results as $result ) {
						?>
						<tr>
							<td> <?php echo esc_html( $result->class_name ); ?></td>
							<td> <input class="submitbutton editImage" type="submit" name="edit_class" value="<?php echo esc_html( $result->class_id ); ?>"> </td>
							<td> <input class="submitbutton sessionsImage" type="submit" name="select_session" value="<?php echo esc_html( $result->class_id ); ?>"> </td>
							<td> <input class="submitbutton deleteImage" type="submit" name="deleteClass" value="<?php echo esc_html( $result->class_id ); ?>">
								<input type="hidden" name="<?php echo esc_html( $result->class_id ); ?>" value="<?php echo esc_html( $result->class_name ); ?>" >
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
	 * @param  string $class_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  array  $instructors The list of instructors for the class.
	 * @param  int    $class_id The ID of the class.
	 * @return void
	 */
	private function create_session_select_form( $class_name, $sessions, $attendees, $instructors, $class_id) {
		?>
		<div id="session_select" class="text-center mt-5">
			<h1><?php echo esc_html( $class_name ); ?></h1>
			<div>
				<div id="content" class="container">
					<table class="mb-100px table table-bordered mr-auto ml-auto">
						<form method="POST">
						<tr style="background-color: lightyellow;">
							<td class="text-left" style="min-width: 200px;">Add Session</td>
							<td style="width: 200px;"></td>
							<td></td>
							<td><input class="submitbutton addItem" type="submit" name="add_new_session" value="<?php echo esc_html( $class_id ); ?>"></td>
						</tr>
						<input type="hidden" name="class_name" value="<?php echo esc_html( $class_name ); ?>">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						</form>
						<?php
						foreach ( $sessions as $session ) {
							?>
							<form method="POST">
							<tr>
								<td class="text-left"> <?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?>
								<td></td>
								<td></td>
								<td>
									<div class="popup" data-textid=<?php echo esc_html( 'sessionid' . $session->session_id ); ?> ><b><i><u>Edit</u></i></b>
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
											<input class="btn btn-primary w-90 mb-1 mt-2"
												type="submit"
												name="move_attendees"
												value="Move Selected">
											<input class="btn btn-danger w-90 mb-1" 
												type="submit"
												name="delete_attendees"
												value="Delete Selected"
												onclick="return confirm('Confirm Attendee Delete')" >
										</span>
									</div>
								</td>
							</tr>
							<input type="hidden" name="class_name" value="<?php echo esc_html( $class_name ); ?>">
							<input type="hidden" name="classId" value="<?php echo esc_html( $class_id ); ?>">
							<input type="hidden" name="session_id" value="<?php echo esc_html( $session->session_id ); ?>">
							<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
							<?php
							foreach ( $instructors[ $session->session_id ] as $instructor ) {
								?>
								<tr>
									<td><?php echo esc_html( $instructor->attendee_firstname . ' ' . $instructor->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_item ); ?></td>
									<td><?php echo esc_html( $instructor->attendee_email ); ?></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $instructor->attendee_id, $session->session_id ); ?>"> </td>
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
									<td class="centerCheckBox"> <input class="form-check-input position-relative" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}

							for ($i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
								?>
								<tr>
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
		<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Creates a form that displays the rolling sessions along with their attenees
	 *
	 * @param  string $class_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $class_id The ID of the class.
	 * @return void
	 */
	private function create_rolling_session_select_form( $class_name, $sessions, $attendees, $class_id) {
		?>
		<div id="session_select" class="text-center mt-5">
			<h1><?php echo esc_html( $class_name ); ?></h1>
			<div>
				<div id="content" class="container">
					<table class="mb-100px table table-bordered mr-auto ml-auto">
						<form method="POST">
						<tr style="background-color: lightyellow;">
							<td></td>
							<td style="width: 200px;"></td>
							<td></td>
							<td>
								<div class="popup" data-textid="popup_id" ><b><i><u>Edit</u></i></b>
									<span class="popuptext" id="popup_id">
										<input class="btn btn-primary w-90 mb-1" type="submit" name="edit_session" value="Edit Sessions"> 
										<input class="btn btn-success w-90" type="submit" name="add_attendee" value="Add Attendee">
										<input class="btn btn-primary w-90 mb-1 mt-2" type="submit" value="Move Selected" name="move_attendees">
										<input class="btn btn-danger w-90 mb-1" type="submit" value="Delete Selected" name="delete_attendees">
									</span>
								</div>
							</td>
						</tr>
						<input type="hidden" name="class_name" value="<?php echo esc_html( $class_name ); ?>">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						<?php
						foreach ( $sessions as $session ) {
							foreach ( $attendees[ $session->session_id ] as $attendee ) {
								?>
								<tr>
									<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
									<td><?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?></td>
									<td><?php echo esc_html( $attendee->attendee_email ); ?></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative" type="checkbox" name="selectedAttendee[]" value="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}

							if ( count( $attendees[ $session->session_id ] ) < $session->session_slots ) {
								?>
								<tr>
									<td class='addAtt'> Add Attendee</td>
									<td><?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?></td>
									<td></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative addChk" type="checkbox" name="addedAttendee[]" value="<?php $this->session_attendee_string( -1, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}
						}
						?>
						<input type="hidden" name="class_name" value="<?php echo esc_html( $class_name ); ?>">
						<input type="hidden" name="classId" value="<?php echo esc_html( $class_id ); ?>">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
					</form>
				</table>
			</div>
		</div>
		<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Creates a form used to create a class.
	 *
	 * @param  class $data Raw data retrieved from the data base or an empty class if a new class is being created.
	 * @return void
	 */
	private function create_class_form( $data ) {
		?>
		<div class="text-center mb-4">
			<h1><?php echo esc_html( $data->class_name ); ?> </h1>
			<img id="displayThumb" src="<?php echo esc_html( $data->class_thumbnail_url ); ?>" alt="Class Thumbnail">
		</div>
		<form method="POST" >
			<table class="table table-striped mr-auto ml-auto">
				<tr>
					<td class="text-right mr-2"><label>Class Name:</label></td>
					<td><input class="w-250px" type="text" name="class_name" value="<?php echo esc_html( $data->class_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Email:</label></td>
					<td><input class="w-250px" type="email" name="class_contact_email" value="<?php echo esc_html( $data->class_contact_email ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Location:</label></td>
					<td><input class="w-250px" type="text" name="class_location" value="<?php echo esc_html( $data->class_location ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Description URL: </label></td>
					<td><input class="w-250px" type="url" name="class_description_url" value="<?php echo esc_html( $data->class_description_url ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Thumbnail URL:</label></td>
					<td><input id="thumbnail" class="w-250px" type="url" name="class_thumbnail_url" value="<?php echo esc_html( $data->class_thumbnail_url ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Cost:</label></td>
					<td><input class="w-75px" type="number" name="class_cost" value="<?php echo esc_html( $data->class_cost ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Slots:</label></td>
					<td><input class="w-75px" type="number" name="class_default_slots" value="<?php echo esc_html( $data->class_default_slots ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Rolling Class: </label></td>
					<td><input class="w-75px" type="text" name="class_rolling" value="<?php echo esc_html( $data->class_rolling ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto" type="submit" value="Submit" name="submit_class"></td>
				</tr>
			</table>
			<input type="hidden" name="id" value="<?php echo esc_html( $data->class_id ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Creates a form used to create a class session.
	 *
	 * @param  class  $data Either an empty class or the data that represents the session being updated.
	 * @param  string $class_name The name of the class the session belongs to.
	 * @param  bool   $add_new Adding a new class or updating an existing one.
	 * @return void
	 */
	private function create_session_form( $data, $class_name, $add_new ) {
		?>
		<div class="text-center mb-4 mr-100px">
			<h1><?php echo esc_html( $class_name ); ?></h1>
		</div>
		<form method="POST">
			<table class="table table-striped mr-auto ml-auto">
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
					<td class="text-right mr-2"><label>Start Time:</label></td>
					<td><input class="w-250px" type="datetime-local" name="session_start_formatted" value="<?php echo esc_html( $data->session_start_formatted ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>End Time:</label></td>
					<td><input class="w-250px" type="datetime-local" name="session_end_formatted" value="<?php echo esc_html( $data->session_end_formatted ); ?>" /> </td>
				</tr>

				<tr>
					<td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto" type="submit" value="Submit Session" name="submit_session"></td>
				</tr>
			</table>
			<?php
			if ( $add_new ) {
				?>
				<input type="hidden" name="session_class_id" value="<?php echo esc_html( $data->session_class_id ); ?>">
				<?php
			}
			?>
			<input type="hidden" name="id" value="<?php echo esc_html( $data->session_id ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Format a date
	 *
	 * @param  mixed $timestamp Unix timestamp to be formated.
	 * @return string Formatted date.
	 */
	private function format_date( $formatted_time ) {
		$dt       = new DateTime( $formatted_time );
		return $dt->format( 'Y-m-d g:ia' );
	}
}
