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
	 * load_html_editor
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

	private function load_description_form( $signup_id ) {
		global $wpdb;
		if ( $signup_id === -1) {	
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
	 * Load the class selection.
	 */
	private function load_signup_selection( $signup_id ) {
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

		$this->create_signup_dropdown_list( $results, $signup_id );
	}

	private function create_signup_dropdown_list( $results, $signup_id ) {
		?>
	    <label for="signup" class="block-label mt-50px mb-10px">Select a Signup</label>
		<select id="signup-select" name="signup" id="signup">
		<?php
		foreach ( $results as $result ) {
			if ( $signup_id  == $result->signup_id ) {
				?>
				<option value="<?php echo esc_html( $result->signup_id ); ?>" selected><?php echo esc_html( $result->signup_name ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_html( $result->signup_id ); ?>"><?php echo esc_html( $result->signup_name ); ?></option>
				<?php
			}
		}
		?>
		</select>
		<?php
	}
}
