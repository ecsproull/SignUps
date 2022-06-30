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
				'SELECT class_id,
				class_name
				FROM %1s',
				self::CLASSES_TABLE
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
								<button class="button-signup" type="submit" name="signup_id" value="<?php echo esc_html( $result->class_id ); ?>" >
									<i>
										<u><?php echo esc_html( $result->class_name ); ?></u>
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
}
