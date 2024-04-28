<?php
/**
 * Summary
 * Reportscode class.
 *
 * @package signups
 */

/**
 * Used for creating class reports.
 *
 * @package SignUps
 */
class Reports extends SignUpsBase {

	/**
	 * All usage qyery.
	 *
	 * @var mixed
	 */
	private $users_report = 'SELECT * from CncSignUpHistory ORDER BY Machine, StartTime';

	private $users_report_where = 'SELECT * from CncSignUpHistory WHERE %s ORDER BY Machine, StartTime';

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
					wp_scw_sessions.session_start_formatted
				FROM wp_scw_sessions
				LEFT JOIN wp_scw_attendees 
				ON wp_scw_attendees.attendee_session_id = wp_scw_sessions.session_id
				WHERE wp_scw_sessions.session_signup_id = %d AND wp_scw_attendees.attendee_firstname IS NOT NULL
				ORDER BY wp_scw_sessions.session_start_time',
				$signup_id
			),
			OBJECT
		);

		$current_session_date = null;
		foreach ( $results as $attendee ) {
			?>
			<div class="attendees_list">
				<?php
				if ( $current_session_date !== $attendee->session_start_formatted ) {
					$current_session_date = $attendee->session_start_formatted;
					?>
					<div class="mt-2 font-weight-bold bg-lg text-right">
						Session:
					</div>
					<div class="mt-2 font-weight-bold bg-lg">
						<?php echo esc_html( $attendee->session_start_formatted ); ?>
					</div>
					<div class="mt-2 font-weight-bold bg-lg">
					</div>
					<?php
				}
				?>
				<div>
					<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
				</div>
				<div>
					<?php echo esc_html( $attendee->attendee_email ); ?>
				</div>
				<div>
					<?php echo esc_html( $attendee->attendee_phone ); ?>
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
