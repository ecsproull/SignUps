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
			}
		} else {
			?>
			<form method="POST" >
				<?php
				$this->load_signup_selection();
				?>
				<div>
					<label for="html" class="block-label mt-25px mb-10px">Past HTML Here</label>
					<textarea id="html-signup-description" name="html" style="width: 600px; height: 400px;"></textarea>
				</div>
				<div class="mt-2">
					<button type="button" id="display-html" class="btn bt-md btn-primary mr-auto ml-auto mt-2">Preview</button>
					<input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit" name="submit_html">
				</div> 
				<div id="html-description-display" class="mt-25px;"></div>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
			<?php
		}
	}

	private function submit_html( $post ) {
		$foo = htmlentities($post['html']);
		echo $foo;
		echo "<br><br>";
		echo html_entity_decode($foo);
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

		$this->create_signup_dropdown_list( $results );
	}

	private function create_signup_dropdown_list( $results ) {
		?>
	    <label for="signup" class="block-label mt-50px mb-10px">Select a Signup</label>
		<select name="signup" id="signup">
		<?php
		foreach ( $results as $result ) {
			?>
			<option value="<?php echo esc_html( $result->signup_id ); ?>"><?php echo esc_html( $result->signup_name ); ?></option>
			<?php
		}
		?>
		</select>
		<?php
	}
}
