<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */


/**
 * HtmlEditor is used for editing the Description, Instructions and Calendar Description.
 * It is accessed via the admin menu item named Descriptions.
 */
class HtmlEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 */
	public function __construct() {
	}

	/**
	 * Load the html editor.
	 *
	 * @return void
	 */
	public function load_html_editor() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			if ( isset( $post['submit_html'] ) ) {
				$this->submit_html( $post );
			} elseif ( isset( $post['signup'] ) ) {
				$this->load_description_form( $post['signup'] );
			} else {
				$this->load_description_form( -1 );
			}
		} else {
			$this->load_description_form( -1 );
		}
	}

	/**
	 * Loads the form to edit a description.
	 *
	 * @param  mixed $signup_id The id of the signup that the description is for.
	 * @return void
	 */
	private function load_description_form( $signup_id ) {
		global $wpdb;
		if ( -1 === $signup_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s',
					self::SIGNUPS_TABLE,
				),
				OBJECT
			);

			if ( $results ) {
				$signup_id = $results[0]->signup_id;
			}
		}

		$description_object = $this->get_signup_html( $signup_id );
		?>
			<form method="POST" name="html_form" >
				<?php
				$this->load_signup_selection( $signup_id );
				?>
				<div class="description-box mt-3">
					<div class="text-right">
						<label class="label-margin-top mr-2" for="description_prerequisite">Prerequisite:</label>
					</div>
					<div>
						<textarea type="text" id="description_prerequisite" class="mt-2 w-100" 
							placeholder="Prerequisites or Leave blank to omit this section." name="description_prerequisite"
							><?php echo esc_html( $description_object ? $description_object->description_prerequisite : '' ); ?></textarea>
					</div>

					<div class="text-right">
						<label class="label-margin-top mr-2" for="description_materials">Student Materials:</label>
					</div>
					<div>
						<textarea type="text" id="description_materials" class="mt-2 w-100" 
							placeholder="Wood, glue, ..., Leave blank to omit this section." name="description_materials"
							><?php echo esc_html( $description_object ? $description_object->description_materials : '' ); ?></textarea>
					</div>
				</div>
				<?php
				$this->create_description_section( $description_object )
				?>
				<div class="mt-2">
					<input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit" name="submit_html">
				</div> 
				<?php
				wp_nonce_field( 'signups', 'mynonce' );
				if ( $description_object ) {
					?>
					<input type="hidden" name="description_id" value=<?php echo esc_html( $description_object->description_id ); ?> >
					<?php
				}
				?>
				<input type="hidden" name="description_signup_id" value="<?php echo esc_html( $signup_id ); ?>" >
			</form>
			<?php
	}

	/**
	 * Submit the html to the database.
	 *
	 * @param  mixed $post Posted values.
	 * @return void
	 */
	private function submit_html( $post ) {
		global $wpdb;
		$short_description = $post['description_html_short'];
		if ( ! $short_description ) {
			$short_description = $post['description_html'];
		}

		$post['description_html']         = htmlentities( $post['description_html'] );
		$post['description_html_short']   = htmlentities( $post['description_html_short'] );
		$post['description_instructions'] = htmlentities( $post['description_instructions'] );

		$signup = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_name
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['description_signup_id']
			),
			OBJECT
		);

		$signup_name = $signup[0]->signup_name;
		unset( $post['_wp_http_referer'] );
		unset( $post['submit_html'] );
		unset( $post['signup'] );
		unset( $post['signup_name'] );

		if ( isset( $post['description_id'] ) ) {
			$where                   = array();
			$where['description_id'] = $post['description_id'];
			unset( $post['description_id'] );
			$rows_updated = $wpdb->update( self::DESCRIPTIONS_TABLE, $post, $where );
			if ( 1 === $rows_updated ) {
				$sessions = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE session_signup_id = %s',
						self::SESSIONS_TABLE,
						$post['description_signup_id']
					),
					OBJECT
				);

				$signup_url = get_site_url() . '/signups?signup_id=' . $post['description_signup_id'];
				foreach ( $sessions as $session ) {
					if ( $session->session_calendar_id ) {
						$where        = array( 'id' => $session->session_calendar_id );
						$data         = array( 'text_for_date' => $short_description . '<br><br><a href=' . $signup_url . " target='_blank' rel='noopener' >Signup</a>." );
						$rows_updated = $wpdb->update( self::SPIDER_CALENDAR_EVENT_TABLE, $data, $where );
					}
				}
			}
		} else {
			$rows_updated = $wpdb->insert( self::DESCRIPTIONS_TABLE, $post );
		}

		$this->add_remove_from_calendar( $post['description_signup_id'], $signup_name, true );

		$this->load_description_form( $post['description_signup_id'] );
	}

	/**
	 * Load the selected signup.
	 *
	 * @param  mixed $signup_id The id that is selected.
	 * @return void
	 */
	private function load_signup_selection( $signup_id ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
				signup_name
				FROM %1s
				ORDER BY signup_name',
				self::SIGNUPS_TABLE
			),
			OBJECT
		);

		$this->create_signup_dropdown_list( $signups, $signup_id );
	}

	/**
	 * Load the dropdown selection list for signups.
	 *
	 * @param  mixed $signups Names and Ids of signups.
	 * @param  mixed $signup_id Id of the selected signup.
	 * @return void
	 */
	private function create_signup_dropdown_list( $signups, $signup_id ) {
		?>
		<label for="signup" class="block-label mt-50px mb-10px">Select a Signup</label>
		<select id="signup-select" name="signup" id="signup">
		<?php
		foreach ( $signups as $signup ) {
			if ( $signup_id == $signup->signup_id ) {
				?>
				<option value="<?php echo esc_html( $signup->signup_id ); ?>" selected><?php echo esc_html( $signup->signup_name ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_html( $signup->signup_id ); ?>"><?php echo esc_html( $signup->signup_name ); ?></option>
				<?php
			}
		}
		?>
		</select>
		<?php
	}
}
