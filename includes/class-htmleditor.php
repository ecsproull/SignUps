<?php
/**
 * Summary
 * Database class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages the map settings including adding places to the map.
 */
class HtmlEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
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
				<ul class="nav mt-2 border">
					<li class="nav-item">
						<a class="nav-link active" aria-current="page" href="#">Long</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#">Short</a>
					</li>
				</ul>
				<div class="description-box">
					<div class="text-right">
						<label class="label-margin-top mr-2" for="description_instructors">Instructors:</label>
					</div>
					<div>
						<input type="text" id="description_instructors" class="mt-2 w-100" 
							value="<?php echo esc_html( $description_object ? $description_object->description_instructors : '' ); ?>" 
							placeholder="Tom, Dick and Harry" name="description_instructors">
					</div>

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

					<div class="text-right">
						<label class="label-margin-top mr-2" for="description_instructions">Preclass:</label>
					</div>
					<div>
						<textarea type="text" id="description_instructions" class="mt-2 w-100" 
							placeholder="Glue wood in layers..., Leave blank to omit this section." name="description_instructions"
							><?php echo esc_html( $description_object ? $description_object->description_instructions : '' ); ?></textarea>
					</div>
				</div>
				<div id="html-signup-description">
					<?php
					$name      = 'description_html';
					$content   = $description_object ? html_entity_decode( $description_object->description_html ) : 'Add description here.';
					$editor_id = 'description_long';
					$settings  = array(
						'textarea_name' => $name,
					);
					wp_editor( $content, $editor_id, $settings );
					?>
				</div>
				<div id="html-signup-description-short" style="display: none;">
					<?php
					$name      = 'description_html_short';
					$content   = $description_object ? html_entity_decode( $description_object->description_html_short ) : 'Add description here.';
					$editor_id = 'description_short';
					$settings  = array(
						'textarea_name' => $name,
					);
					wp_editor( $content, $editor_id, $settings );
					?>
				</div>
				<div class="mt-2">
					<!-- button type="button" id="display-html" class="btn bt-md btn-primary mr-auto ml-auto mt-2">Preview</button -->
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
		$short_description              = $post['description_html'];
		$post['description_html']       = htmlentities( $post['description_html'] );
		$post['description_html_short'] = htmlentities( $post['description_html_short'] );
		unset( $post['_wp_http_referer'] );
		unset( $post['submit_html'] );
		unset( $post['signup'] );

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
				FROM %1s',
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
