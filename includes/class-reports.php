<?php
/*
 * Summary
 * Reports class.
 *
 * @package SignUps
 */

/**
 * Reports generates reports such as cnc signup histor
 */
class Reports extends SignUpsBase {
	/**
	 * Add the select class shortcode
	 */
	public function class_reports() {
		$post = wp_unslash( $_POST );
		if ( 0 === count( $post ) ) {
			$post = wp_unslash( $_GET );
		}

		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
			if ( isset( $post['signup_id'] ) ) {
				$this->load_signup_sessions( $post['signup_id'] );
			} else {
				$this->load_signup_selection();
			}
		} else {
			$this->load_signup_selection();
		}
	}

	/**
	 * Loads the report
	 *
	 * @param mixed $signup_id The id for the signup to retrieve the attendees.
	 * @return void
	 */
	private function load_signup_sessions( $signup_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT wp_scw_attendees.attendee_firstname,
					wp_scw_attendees.attendee_lastname,
					wp_scw_attendees.attendee_email,
					wp_scw_attendees.attendee_phone,
					wp_scw_attendees.attendee_badge,
					wp_scw_sessions.session_start_formatted,
					wp_scw_sessions.session_id,
					wp_scw_sessions.session_signup_id
				FROM wp_scw_sessions
				LEFT JOIN wp_scw_attendees 
				ON wp_scw_attendees.attendee_session_id = wp_scw_sessions.session_id
				WHERE wp_scw_sessions.session_signup_id = %d
				ORDER BY wp_scw_sessions.session_start_time',
				$signup_id
			),
			OBJECT
		);

		$current_session_date = null;
		$current_session_id   = null;
		foreach ( $results as $attendee ) {
			?>
			<div class="attendees_list">
				<?php
				if ( $current_session_date !== $attendee->session_start_formatted ) {
					$current_session_date = $attendee->session_start_formatted;
					$current_session_id   = 'class_' . $attendee->session_id;
					$instructors = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT wp_scw_instructors.instructors_name,
							wp_scw_instructors.instructors_email,
							wp_scw_instructors.instructors_phone,
							wp_scw_instructors.instructors_badge
						from wp_scw_instructors
						LEFT JOIN wp_scw_session_instructors
						ON wp_scw_session_instructors.si_instructor_id =  wp_scw_instructors.instructors_id
						WHERE wp_scw_session_instructors.si_session_id = %d',
							$attendee->session_id
						),
						OBJECT
					);
			
					?>
					
					<div class="mt-2 font-weight-bold bg-lg">
						<?php echo esc_html( 'Session:' . $attendee->session_start_formatted ); ?>
					</div>
					<div class="mt-2 font-weight-bold bg-lg text-center">
						<button class="print-button instructors-print-class" value="<?php echo esc_html( $attendee->session_id ); ?>" >Print</button>
					</div>
					<div class="mt-2 font-weight-bold bg-lg">
						<button class="email-button instructors-email-class" value="<?php echo esc_html( $current_session_id ); ?>" >Copy Email Addresses</button>
					</div>
					<div class="mt-2 font-weight-bold bg-lg">
						Badge
					</div>
					<?php
					foreach ( $instructors as $instructor ) {
						?>
						<div class="instructor">
							<b>Instructor:</b> <?php echo esc_html( $instructor->instructors_name ); ?>
						</div>
						<div class="instructor <?php echo esc_html( $current_session_id ); ?>">
							<?php echo esc_html( $instructor->instructors_email ); ?>
						</div>
						<div class="instructor">
							<?php echo esc_html( $instructor->instructors_phone ); ?>
						</div >
						<div class="instructor">
							<?php echo esc_html( $instructor->instructors_badge ); ?>
						</div >
						<?php
					}
				}
				if ( ! $attendee->attendee_firstname && ! $attendee->attendee_lastname ) {
					?>
					<div class="bg-warning">
					 No Attendees for this session.
					</div>
					<?php
				} else {
					?>
					<div>
						<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
					</div>
					<?php
				}
				?>
				<div class="<?php echo esc_html( $current_session_id ); ?>">
					<?php echo esc_html( $attendee->attendee_email ); ?>
				</div>
				<div>
					<?php echo esc_html( $attendee->attendee_phone ); ?>
				</div>
				<div>
					<?php echo esc_html( $attendee->attendee_badge ); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Loads the report
	 *
	 * @return void
	 */
	private function load_signup_selection() {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
				signup_name
				FROM %1s
				WHERE signup_rolling_template = 0
				ORDER BY signup_name',
				self::SIGNUPS_TABLE
			),
			OBJECT
		);

		?>
		<form class="report-form" method="GET">
		<?php
		foreach ( $results as $signup ) {
			?>
			<div>
				<button name="signup_id" value="<?php echo esc_html( $signup->signup_id ); ?>" ><?php echo esc_html( $signup->signup_name ); ?></button>
			</div>
			<?php
		}
		wp_nonce_field( 'signups', 'mynonce' );
		?>
		</form>
		<?php
	}
}
