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
class RollingTemplatesEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * load_products_editor
	 *
	 * @return void
	 */
	public function load_templates_editor() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			if ( isset( $post['submit_template'] ) ) {
				$this->submit_template( $post );
			} elseif ( isset( $post['template_id'] ) ) { 
                $this->load_template_form( $post['template_id'] );
            }else {
				$this->load_template_form( -1 );
			}
		} else {
			$this->load_template_form( -1 );
		}
	}

    private function submit_template( $post ) {
        global $wpdb;
        $data = Array();
        $data['rolling_start_time']     = $post['start_time'];
        $data['rolling_session_length'] = $post['duration'];
        $data['rolling_slots']          = $post['slots'];
        $data['rolling_days']           = $post['rolling_days'];
        $data['rolling_template_name']  = $post['name'];
        $data['rolling_slot_items']     = $post['items'];

        $day_template;
        for( $i = 1; $i < 8; $i++ ) {
            if ( $post['days'][$i] ) {
                if ( $day_template ) {
                    $day_template .= ',';
                }

                $day_template .= $i . '-' . $post['days_sessions'][$i];
            }
        }

        $data['rolling_days_week'] = $day_template;
        if ( $post['submit_template'] == -1 ) {
            $insert_return_value = $wpdb->insert( self::ROLLING_TABLE, $data );
            if ( $insert_return_value != 1 ) {
                echo "<p>Error inserting new table: " . $wpdb->last_error;
            } else {
                $this->load_template_form( $wpdb->insert_id );
                return;
            }
        } else {
            $where = Array();
            $where['rolling_id'] = $post['submit_template'];
            $rows = $wpdb->update( self::ROLLING_TABLE, $data, $where );
            if ( $rows != 1 ) {
                echo "<p>Error updating rolling table: " . $wpdb->last_error;
            } else {
                $this->load_template_form( $post['submit_template'] );
                return;
            }
        }
    }

    private function load_template_form( $template_id ) {
        global $wpdb;
        $results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
                WHERE rolling_id = %d',
				self::ROLLING_TABLE,
                $template_id
			),
			OBJECT
		);
        
        ?>
        <form method="POST" name="template_form" >
        <?php
        $this->load_template_selection( $template_id );
        if ( -1 !== $template_id ) {
            $days = Array();
            $days_sessions_raw = explode( ',', $results[0]->rolling_days_week );
            $days_sessions     = array();
		    foreach ( $days_sessions_raw as $dsr ) {
                $x                      = explode( '-', $dsr );
                $days_sessions[ $x[0] ] = $x[1];
            }
            ?>
            <div>
                <label class="mr-4 mt-3" for="rolling-name">Name:</label>
                <input type="text" class="mt-2 w-250px" id="rolling-name" value="<?php echo $results[0]->rolling_template_name; ?>" name="name">
            </div>
            <div>
                <label for="start-time">Start time:</label>
                <input type="time" class="mt-2" id="start-time" value=<?php echo $results[0]->rolling_start_time; ?> name="start_time">
            </div>
            <div>
                <label class="mr-1" for="duration">Duration:</label>
                <input type="time" class="mt-2 without_ampm" id="duration" value=<?php echo $results[0]->rolling_session_length; ?> name="duration">
            </div>
            <div>
                <label class="mr-4 mt-2" for="rolling-days">Days:</label>
                <input type="number" class="mt-2 ml-2 w-75px" id="rolling-days" value=<?php echo $results[0]->rolling_days; ?> name="rolling_days">
            </div>
            <div>
                <label class="mr-3 mt-2" for="rolling-slots">Slots:</label>
                <input type="number" class="mt-2 w-75px" id="rolling-slots" value=<?php echo $results[0]->rolling_slots; ?> name="slots">
            </div>
            <div>
                <label class="mr-4 mt-2" for="rolling-items">Items:</label>
                <input type="text" class="mt-2 w-250px" id="rolling-items" value="<?php echo $results[0]->rolling_slot_items; ?>" name="items">
            </div>
            <table class="mt-3 table table-bordered mr-auto w-300px">
                <tr>
                    <th>Day</th>
                    <th>Select</th>
                    <th>Sessions</th>
                    <th>Extra Items</th>
                    <th>Extra Item Slots</th>
                </tr>
                <tr>
                    <td>Monday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[1]' <?php if ( array_key_exists( 1, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[1]' value=<?php echo $days_sessions[1]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[1]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[1]"></td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[2]' <?php if ( array_key_exists( 2, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[2]' value=<?php echo $days_sessions[2]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[2]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[2]"></td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[3]' <?php if ( array_key_exists( 3, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[3]' value=<?php echo $days_sessions[3]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[3]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[3]"></td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[4]' <?php if ( array_key_exists( 4, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[4]' value=<?php echo $days_sessions[4]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[4]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[4]"></td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[5]' <?php if ( array_key_exists( 5, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[5]' value=<?php echo $days_sessions[5]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[5]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[5]"></td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[6]' <?php if ( array_key_exists( 6, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[6]' value=<?php echo $days_sessions[6]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[6]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[6]"></td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td><input class="mr-auto ml-auto" type='checkbox' name='days[7]' <?php if ( array_key_exists( 7, $days_sessions ) ) { echo 'checked'; } ?> /></td>
                    <td><input style="width:75px" type='number' name='days_sessions[7]' value=<?php echo $days_sessions[7]; ?> /></td>
                    <td><input type="text" class="mt-2 w-250px" value="" name="extra_items[7]"></td>
                    <td><input type="number" class="mt-2 w-75px" value=1 name="extra_slots[7]"></td>
                </tr>
            </table>
            <div class="row w-300px">
                <button type="submit" class="btn bth-md bg-primary mr-3 ml-auto"value=<?php echo esc_html( $template_id ); ?> name="submit_template">Submit</button>
                <button type="submit" class="btn bth-md bg-primary mr-auto ml-3"value="-1" name="submit_template">Cancel</button>
            </div>
            <?php
            wp_nonce_field( 'signups', 'mynonce' );
        }
        ?>
        </form>
        <?php
    }

    private function load_template_selection( $template_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT rolling_id,
				rolling_template_name
				FROM %1s',
				self::ROLLING_TABLE
			),
			OBJECT
		);

		?>
	    <label for="templates" class="block-label mt-50px mb-10px">Select a Template</label>
		<select id="template-select" name="template_id" id="templates">
		<?php
		foreach ( $results as $result ) {
			if ( $template_id == $result->rolling_id ) {
				?>
				<option value="<?php echo esc_html( $result->rolling_id ); ?>" selected><?php echo esc_html( $result->rolling_template_name ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_html( $result->rolling_id ); ?>"><?php echo esc_html( $result->rolling_template_name ); ?></option>
				<?php
			}
		}
		?>
        <?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</select>
		<?php
	}
}
