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
class SignupSettings {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_print_scripts', array( $this, 'add_image_script' ) );
	}

	/**
	 *  When you change the link to a class thumbnail this bit of JS updates the image on the page in real time.
	 */
	public function add_image_script() {
		?>
		<script>
			function updateImage() {
				var thumbDisplay = document.getElementById( "displayThumb" );
				var thumbUrl = document.getElementById( "thumbnail" );
				thumbDisplay.src = thumbUrl.value;
			}
		</script>
		<?php
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
			} elseif ( isset( $post['addNewClass'] ) ) {
				$this->create_class_form( new ClassItem( null ) );
			} elseif ( isset( $post['addNewSession'] ) ) {
				$this->add_new_session_form( $post );
			} elseif ( isset( $post['attendees'] ) ) {
				$this->edit_session_attendees( $post );
			} elseif ( isset( $post['deleteAttendees'] ) ) {
				$this->delete_session_attendees( $post );
			} elseif ( isset( $post['moveAttendees'] ) ) {
				$this->move_session_attendees( $post );
			} elseif ( isset( $post['addAttendee'] ) ) {
				$this->add_session_attendees( $post );
			} elseif ( isset( $post['selectSession'] ) ) {
				$this->load_session_selection( $post );
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
		$rows_pdated = 0;
		if ( $where['session_id'] ) {
			$rows_pdated = $wpdb->update( 'wp_scw_sessions', $post, $where );
		} else {
			$rows_pdated = $wpdb->insert( 'wp_scw_sessions', $post );
		}
		$this->update_message( $rows_pdated );
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
		$session_item = new SessionItem( $post['addNewSession'] );
		$this->create_session_form( $session_item, $post[ $post['selectSession'] ], true );
	}

	/**
	 * Ed it a session of a class.
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
				$post['edit_session']
			),
			OBJECT
		);
		$this->create_session_form( $results[0], $post['class_name'], false );
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
		echo 'add_session_attendees';
		echo var_dump( $post );
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
	 *
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
		$class_name = $post[ $post['selectSession'] ];
		global $wpdb;
		$instructors = array();
		$attendees   = array();
		$sessions    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT session_id,
				session_start_formatted,
				session_start_time,
				session_slots
				FROM awp.wp_scw_sessions
				WHERE session_class_id = %s',
				$post['selectSession']
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

		$this->create_session_select_form( $class_name, $sessions, $attendees, $instructors, $post['selectSession'] );
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
									<td> <input class="submitbutton addItem" type="submit" name="addAttendee" value="<?php echo esc_html( $session_id ); ?>"></td>
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
						<input class="btn btn-danger" type="submit" value="Delete" name="deleteAttendees">
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
	 * @param  mixed $rows_pdated How many rows were updated in the database.
	 * @return void
	 */
	private function update_message( $rows_pdated ) {
		if ( 1 === $rows_pdated ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated </h2>
			</div>
			<?php
		} else {
			?>
			<div class="text-center mt-5">
				<h2> Something went wrong. </h2>
				<h3><?php echo esc_html( $rows_pdated ); ?> Rows Updated</h3>
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
						<td> <input class="submitbutton addItem" type="submit" name="addNewClass" value=""></td>
					</tr>
					<?php
					foreach ( $results as $result ) {
						?>
						<tr>
							<td> <?php echo esc_html( $result->class_name ); ?></td>
							<td> <input class="submitbutton editImage" type="submit" name="edit_class" value="<?php echo esc_html( $result->class_id ); ?>"> </td>
							<td> <input class="submitbutton sessionsImage" type="submit" name="selectSession" value="<?php echo esc_html( $result->class_id ); ?>"> </td>
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
	private function create_session_select_form( $class_name, $sessions, $attendees, $instructors, $class_id ) {
		?>
		<form method="POST">
			<div class="text-center mt-5">
				<h1><?php echo esc_html( $class_name ); ?></h1>
				<div>
					<div id="content" class="container">
						<table class="mb-100px table table-bordered mr-auto ml-auto">
							<tr style="background-color: lightyellow;">
								<td class="text-left" style="min-width: 200px;">Add Session</td>
								<td style="width: 200px;"></td>
								<td></td>
								<td> <input class="submitbutton addItem" type="submit" name="addNewSession" value="<?php echo esc_html( $class_id ); ?>">
								</td>
							</tr>
							<?php
							foreach ( $sessions as $session ) {
								?>
								<tr>
									<td class="text-left"> <?php echo esc_html( $this->format_date( $session->session_start_time ) ); ?></td>
									<td> <input class="submitbutton editImage mr-auto ml-auto" type="submit" name="edit_session" value="<?php echo esc_html( $session->session_id ); ?>"> </td>
									<td> <input class="submitbutton attendeesImage mr-auto ml-auto" type="submit" name="attendees" value="<?php echo esc_html( $session->session_id ); ?>"> </td>
									<td> <input class="submitbutton deleteImage" type="submit" name="deleteSession" value="<?php echo esc_html( $session->session_id ); ?>"> </td>
								</tr>
								<input type="hidden" name="class_name" value="<?php echo esc_html( $class_name ); ?>">
								<input type="hidden" name="classId" value="<?php echo esc_html( $class_id ); ?>">
								<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
								<?php
								if ( count( $attendees[ $session->session_id ] ) < $session->session_slots ) {
									?>
									<tr>
										<td>Add Attendee</td>
										<td></td>
										<td></td>
										<td> <input class="submitbutton addItem" type="submit" name="addAttendee" value="<?php echo esc_html( $session->session_id ); ?>"></td>
									</tr>
									<?php
								}

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
								?>
								<tr style="background: darkgray;">
									<td></td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
		</form>
		</div>
		<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		<input class="btn btn-danger" type="submit" value="Delete Selected" name="deleteAttendees">
		<input class="btn btn-primary" type="submit" value="Move Selected" name="moveAttendees">
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
					<td><input id="thumbnail" class="w-250px" type="url" name="class_thumbnail_url" value="<?php echo esc_html( $data->class_thumbnail_url ); ?>" onChange="updateImage()" /> </td>
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
	private function format_date( $timestamp ) {
		$timezone = 'America/Phoenix';
		$dt       = new DateTime();
		$dt->setTimestamp( $timestamp );
		$dt->setTimezone( new DateTimeZone( $timezone ) );
		return $dt->format( 'Y-m-d g:ia' );
	}
}
