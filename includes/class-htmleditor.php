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

		$html = $this->get_signup_html( $signup_id );
		if ( ! $html ) {
			$html = '';
		}

		$html_short = $this->get_signup_html( $signup_id, false );
		if ( ! $html_short ) {
			$html_short = '';
		}

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
				<div id="html-signup-description">
					<textarea class="html-textarea" name="html"><?php echo esc_html( $html ); ?></textarea>
				</div>
				<div id="html-signup-description-short" style="display: none;">
					<textarea class="html-textarea" name="html_short" hidden><?php echo esc_html( $html_short ); ?></textarea>
				</div>
				<div class="mt-2">
					<!-- button type="button" id="display-html" class="btn bt-md btn-primary mr-auto ml-auto mt-2">Preview</button -->
					<input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit" name="submit_html">
				</div> 
				<!-- div id="html-description-display" class="mt-25px;"></div -->
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
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
		$desc        = htmlentities( $post['html'] );
		$desc_short  = htmlentities( $post['html_short'] );
		$description = array();
		if ( $desc ) {
			$description['description_html'] = $desc;
		}

		if ( $desc_short ) {
			$description['description_html_short'] = $desc_short;
		}

		if ( ! $desc_short && ! $desc ) {
			return;
		}

		$results = $this->get_signup_html( $post['signup'] );

		if ( $results ) {
			$where = array();
			$where['description_signup_id'] = $post['signup'];
			$rows_updated = $wpdb->update( self::SIGNUP_DESCRIPTIONS_TABLE, $description, $where );
		} else {
			$description['description_signup_id'] = $post['signup'];
			$rows_updated = $wpdb->insert( self::SIGNUP_DESCRIPTIONS_TABLE, $description );
		}

		$this->load_description_form( $post['signup'] );
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
