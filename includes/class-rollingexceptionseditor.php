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
class RollingExceptionsEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Load the exceptions editor
	 *
	 * @return void
	 */
	public function load_exceptions_editor() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
		} else {
			$this->load_exceptions_form();
		}
	}

	private function submit_exceptions( $post ) {
		global $wpdb;
		$data                          = array();
		$data['template_name']         = $post['template_name'];
		$data['template_rolling_days'] = $post['template_rolling_days'];
		$data['template_columns']      = $post['template_columns'];

	}

	private function load_exceptions_form() {
		global $wpdb;
		$templates = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE template_id = %d',
				self::SIGNUP_TEMPLATE_TABLE,
				$template_id
			),
			OBJECT
		);

		$exceptions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE template_item_template_id = %d',
				self::ROLLING_EXCEPTIONS_TABLE,
				$template_id
			),
			OBJECT
		);

		?>
		<h1>Set Time Exceptions</h1>
		<h3>These will be set to even hours</h3>
		<form method="POST" name="template_form" class="mt-4" >
		<div class="rolling-exception-list">
			<div>Template</div>
			<div>Start</div>
			<div>End</Div>
			<div>Description</Div>
			<?php
			foreach ( $exceptions as $exc ) {
				?>
				<div><?php $this->load_template_selection( $exc->exc_template_id, true ); ?></div>
				<div><input class="datetime-picker" type='datetime-local' name="exe_start[]" value='<?php echo $exc->exc_start; ?>' required></div>
				<div><input class="datetime-picker" type='datetime-local' name="exe_end[]" value='<?php echo $exc->exc_end; ?>' required ></div>
				<div><input type='text' name="exe_reason[]" value='<?php echo esc_html( $exc->exc_reason ); ?>' required ></div>
				<?php
			}
			?>
			<div><?php $this->load_template_selection( 0, true ); ?></div>
			<div><input class="datetime-picker"type='datetime-local' name="exe_start[]" value='' required ></div>
			<div><input class="datetime-picker"type='datetime-local' name="exe_end[]" value='' required ></div>
			<div><input type='text' name="exe_reason[]" value='Shop Closed' required ></div>
		</div>
		<div class="row" style="width: 1000px">
			<button type="submit" class="btn bth-md bg-primary ml-auto mr-auto mt-3" value='' name="submit_exception">Submit</button>
		</div>
		<?php
		wp_nonce_field( 'signups', 'mynonce' );
		?>
		</form>
		<?php
	}
}
