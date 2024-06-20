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
class InstructorsEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Instructors editor.
	 */
	public function instructors_editor() {
		$post = wp_unslash( $_POST );
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			if ( isset( $post['instructors_submit'] ) ) {
				$this->instructors_submit( $post);
			} else {
				$this->instructors_input_form( $post );
			}
		} else {
			$this->instructors_input_form( null );
		}
	}

	/**
	 * Instructors submit.
	 *
	 * @param mixed $post Post data to submit.
	 */
	public function instructors_submit( $post ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT signup_name
				FROM %1s
				WHERE signup_id = %d",
				self::SIGNUPS_TABLE,
				$post['selected_class']
			),
			OBJECT
		);

		$count_instructors = count( $post['instructors_badge'] );
		for ( $i = 0; $i < $count_instructors; $i++ ) {
			$data                           = array();
			$data['instructors_badge']       = $post['instructors_badge'][ $i ];
			$data['instructors_name']        = $post['instructors_name'][ $i ];
			$data['instructors_email']       = $post['instructors_email'][ $i ];
			$data['instructors_phone']       = $post['instructors_phone'][ $i ];
			$data['instructors_class_id']    = (int) $post['selected_class'];
			$data['instructors_class_title'] = $signups[0]->signup_name;

			if ( $post['instructors_id'][ $i ] ) {
				$where                   = array();
				$where['instructors_id'] = $post['instructors_id'][ $i ];
				$wpdb->update( self::INSTRUCTORS_TABLE, $data, $where );
			} else {
				$ret = $wpdb->insert( self::INSTRUCTORS_TABLE, $data );
			}
		}

		if ( isset( $post['instructors_remove'] ) ) {
			foreach( $post['instructors_remove'] as $inst_id ) {
				$wpdb->get_results(
					$wpdb->prepare(
						'DELETE
						FROM %1s
						WHERE instructors_id = %d',
						self::INSTRUCTORS_TABLE,
						$inst_id
					)
				);

				$wpdb->query(
					$wpdb->prepare(
						'DELETE
						FROM %1s
						WHERE si_instructor_id = %d && si_signup_id = %d',
						self::SESSION_INSTRUCTORS_TABLE,
						$inst_id,
						$post['selected_class']
					)
				);
			}
		}

		$this->instructors_input_form( $post );
	}

	/**
	 * Instructors editor form.
	 *
	 * @param mixed $post Post data to build form.
	 */
	public function instructors_input_form( $post ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT signup_id, signup_name
				FROM %1s
				WHERE signup_rolling_template = '0'
				ORDER BY signup_name",
				self::SIGNUPS_TABLE
			),
			OBJECT
		);

		$selected_class_id = $signups[0]->signup_id;
		$class_instructors = array();
		if ( isset( $post['selected_class'] ) ) {
			$selected_class_id = $post['selected_class'];
		}

		$class_instructors = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE instructors_class_id = %d',
				self::INSTRUCTORS_TABLE,
				$selected_class_id
			),
			OBJECT
		);
		?>
		<form id="instructors-form" method="POST">
			<div class="text-center mt-4">
				<select id="select_class" class="select-lg" name="selected_class">
					<?php
					foreach ( $signups as $signup ) {
						if ( $selected_class_id === $signup->signup_id ) {
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
			</div>
			<?php
			$this->create_lookup_member_table( true );
			?>
			<input id="first-name" class="member-badge" type="hidden" name="firstname" value="">
			<div class="text-center mt-4">
				<input type="button" id="add-instructor" class="btn btn-primary rounded" value='Add Instructor To List'>
			</div>
			<div id="inst-list" class="instructor-list mt-3 ml-auto mr-auto">
				<div>Badge</div>
				<div>Name</div>
				<div>Email</div>
				<div>Phone</div>
				<div>Remove</div>
				<?php
				foreach ( $class_instructors as $instructor ) {
					?>
					<div><input class="w-99" type="text" name="instructors_badge[]" value="<?php echo esc_html( $instructor->instructors_badge ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_name[]" value="<?php echo esc_html( $instructor->instructors_name ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_email[]" value="<?php echo esc_html( $instructor->instructors_email ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_phone[]" value="<?php echo esc_html( $instructor->instructors_phone ); ?>"></div>
					<div><input class="form-check-input ml-2 remove-chk mt-2" type="checkbox" name="instructors_remove[]" 
						value="<?php echo esc_html( $instructor->instructors_id ); ?>"></div>
					<input class="w-99" type="hidden" name="instructors_id[]" value="<?php echo esc_html( $instructor->instructors_id ); ?>">
					<?php
				}
				?>
			</div>
			<input id="reload" type="hidden" name="reload" value="0">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			<div class="text-center mt-5">
				<button class="btn btn-primary" type="submit" name="instructors_submit" 
						value="instructors" >Submit</button>
			</div>
		</form>
		<?php
	}
}
