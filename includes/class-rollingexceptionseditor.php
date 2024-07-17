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
 * RollingExceptionsEditor is used to edit the Rolling Exceptions
 * It is accessd via the submenu item named Exceptions.
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
			$this->submit_exceptions( $post );
			
		} else {
			$this->load_exceptions_form();
		}
	}

	/**
	 * Submit_exceptions to the database.
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	private function submit_exceptions( $post ) {
		global $wpdb;
		$length = count( $post['exc_id'] );
		for ( $i = 0; $i < $length; $i++ ) {
			$data                    = array();
			$data['exc_template_id'] = $post['exc_template_id'][ $i ];
			$data['exc_start']       = $post['exc_start'][ $i ];
			$data['exc_end']         = $post['exc_end'][ $i ];
			$data['exc_reason']      = $post['exc_reason'][ $i ];

			if ( ! $data['exc_template_id'] ) {
				$data['exc_template_id'] = '0';
			}

			if ( '-1' === $post['exc_id'][ $i ] && $post['exc_start'][ $i ] && $post['exc_end'][ $i ] ) {
				$wpdb->insert( self::ROLLING_EXCEPTIONS_TABLE, $data );
			} elseif ( '-1' !== $post['exc_id'][ $i ] ) {
				$where           = array();
				$where['exc_id'] = $post['exc_id'][ $i ];
				$wpdb->update( self::ROLLING_EXCEPTIONS_TABLE, $data, $where );
			}
		}

		if ( isset( $post['exc_delete'] ) ) {
			foreach( $post['exc_delete'] as $item ) {
				$where = array( 'exc_id' => $item );
				$wpdb->delete( self::ROLLING_EXCEPTIONS_TABLE, $where );
			}
		}

		$this->load_exceptions_form();
	}

	/**
	 * Loads the exceptions form.
	 *
	 * @return void
	 */
	private function load_exceptions_form() {
		global $wpdb;
		$exceptions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::ROLLING_EXCEPTIONS_TABLE,
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
			<div>Delete</div>
			<?php
			$count = 1;
			foreach ( $exceptions as $exc ) {
				?>
				<div><?php $this->load_template_selection( $exc->exc_template_id, false, 'exc_template_id', 'exception_select_id', 'All' ); ?></div>
				<div><input class="datetime-picker-start" type='datetime-local' key="<?php echo esc_html( $count ); ?>"
					name="exc_start[]" value='<?php echo esc_html( $exc->exc_start ); ?>' required></div>
				<div><input class="datetime-picker-end" type='datetime-local' key="<?php echo esc_html( $count++ ); ?>"
					name="exc_end[]" value='<?php echo esc_html( $exc->exc_end ); ?>' required ></div>
				<div><input type='text' name="exc_reason[]" value='<?php echo esc_html( $exc->exc_reason ); ?>' required ></div>
				<div><input type='checkbox' name="exc_delete[]" value='<?php echo esc_html( $exc->exc_id ); ?>' ></div>
				<input type="hidden" name="exc_id[]" value='<?php echo esc_html( $exc->exc_id ); ?>' >
				<?php
			}
			?>
			<div><?php $this->load_template_selection( 0, false, 'exc_template_id', 'exception_select_id', 'All' ); ?></div>
			<div><input class="datetime-picker-start" type='datetime-local' name="exc_start[]" value='' key="<?php echo esc_html( $count ); ?>"></div>
			<div><input class="datetime-picker-end" type='datetime-local' name="exc_end[]" value='' key="<?php echo esc_html( $count++ ); ?>"></div>
			<div><input type='text' name="exc_reason[]" value='Shop Closed'></div>
			<div></div>
			<input type="hidden" name = "exc_id[]" value = "-1">
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
