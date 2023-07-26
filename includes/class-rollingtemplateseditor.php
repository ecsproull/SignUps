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
            if ( isset( $post['cancel_template'] ) ) {
				$this->load_template_form( $post['cancel_template'] );
			} elseif ( isset( $post['submit_template'] ) ) {
				$this->submit_template( $post );
			} elseif ( isset( $post['template_id'] ) ) { 
                $this->load_template_form( $post['template_id'] );
            }else {
				$this->load_template_form( 1 );
			}
		} else {
			$this->load_template_form( 1 );
		}
	}

    private function submit_template( $post ) {
        global $wpdb;
        $data = Array();
        $data['template_name']         = $post['template_name'];
        $data['template_rolling_days'] = $post['template_rolling_days'];
        $data['template_columns']      = $post['template_columns'];
        
        if ( $post['submit_template'] == -1 ) {
            $rows = $wpdb->insert( self::SIGNUP_TEMPLATE_TABLE, $data );
            if ( $rows == 1 ) {
                $post['template_item_id'] = $wpdb->insert_id;
            }

        } else {
            $where = Array();
            $where['template_id'] = $post['submit_template'];
            $rows = $wpdb->update( self::SIGNUP_TEMPLATE_TABLE, $data, $where );
        }
        
        if ( $rows != 1 && $wpdb->last_error != '' ) {
            echo "<p>Error updating template table: " . $wpdb->last_error . "<br><br>";
        }

        for( $i = 0; $i < count ( $post['template_item_id'] ); $i++ ) {
            if ( array_key_exists( 'template_item_delete', $post) && in_array( $post['template_item_id'][$i] , $post['template_item_delete'] ) ) {
                $template_items = $wpdb->get_results(
                    $wpdb->prepare(
                        'DELETE
                        FROM %1s
                        WHERE template_item_id = %d',
                        self::SIGNUP_TEMPLATE_ITEM_TABLE,
                        $post['template_item_id'][$i]
                    ),
                    OBJECT
                );
                continue;
            }

            $data = Array();
            $data['template_item_day_of_week'] = $post['template_item_day_of_week'][$i];
            $data['template_item_title']        = $post['template_item_title'][$i];
            $data['template_item_slots']        = $post['template_item_slots'][$i];
            $data['template_item_start_time']   = $post['template_item_start_time'][$i];
            $data['template_item_duration']     = $post['template_item_duration'][$i];
            $data['template_item_shifts']       = $post['template_item_shifts'][$i];
            $data['template_item_column']       = $post['template_item_column'][$i];
            $data['template_item_group']        = $post['template_item_group'][$i];

            if ( $post['template_item_id'][$i] > 0 ) {
                $where                     = Array();
                $where['template_item_id'] = $post['template_item_id'][$i];
                $rows                      = $wpdb->update( self::SIGNUP_TEMPLATE_ITEM_TABLE, $data, $where );

                if ( $rows != 1 && $wpdb->last_error != '' ) {
                    echo "<p>Error updating template item ". $i . " failed : " . $wpdb->last_error . "<br><br>";
                }
            } else {
                $data['template_item_template_id'] = $post['submit_template'];
                $rows  = $wpdb->insert( self::SIGNUP_TEMPLATE_ITEM_TABLE, $data );

                if ( $rows != 1 && $wpdb->last_error != '' ) {
                    echo "<p>Error insert failed : " . $wpdb->last_error . "<br><br>";
                }
            }
        }

        ?>
        <form method="POST" name="template_form" >
            <button type="submit" class="btn bth-md bg-primary ml-auto mr-auto mt-5" 
                value=<?php echo $post['submit_template']; ?> name="cancel_template">Return</button>
        </form>
        <?php
    }

    private function load_template_form( $template_id ) {
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

        $template_items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
                WHERE template_item_template_id = %d',
				self::SIGNUP_TEMPLATE_ITEM_TABLE,
                $template_id
			),
			OBJECT
		);
        
        
        ?>
        <form method="POST" name="template_form" >
        <?php
        $this->load_template_selection( $template_id, true );
        ?>
        <div class="box mt-4">
            <div class="text-right">
                <label class="mt-2 mr-2" for="rolling-name">Name:</label>
            </div>
            <div>
                <input type="text" id="rolling-name" class="mt-2 w-250px" 
                    value="<?php echo $templates[0]->template_name; ?>" name="template_name">
            </div>

            <div class="text-right">
                <label class="mt-2 mr-2" for="rolling-days">Rolling Days:</label>
            </div>
            <div>
                <input type="number" id="rolling-days" class="mt-2 w-75px" 
                    value="<?php echo $templates[0]->template_rolling_days; ?>" name="template_rolling_days">
            </div>

            <div class="text-right">
                <label class="mt-2 mr-2" for="rolling-columns">Column Count:</label>
            </div>
            <div>
                <input type="number" for="rolling-columns" class="mt-2 w-75px" 
                    value="<?php echo $templates[0]->template_columns; ?>" name="template_columns">
            </div>
        </div>
        <table class="mt-3 table mr-auto d-flex template-table">
            <tr>
                <th>Days</th>
                <th>Item</th>
                <th>Slots</th>
                <th>Start Time</th>
                <th>Duration</th>
                <th>Shifts</th>
                <th>Column</th>
                <th>Group</th>
                <th>Delete</th>
            </tr>
            <?php
            foreach ( $template_items as $item ) {
                ?>
                <tr>
                    <td ><input class="w-125px" type='text' name="template_item_day_of_week[]" value=<?php echo $item->template_item_day_of_week; ?> required ></td>
                    <td><input type='text' name="template_item_title[]" value='<?php echo $item->template_item_title; ?>' required ></td>
                    <td><input class="w-75px" type="number" name="template_item_slots[]" value=<?php echo $item->template_item_slots; ?> required ></td>
                    <td><input class="w-125px" type='text' name="template_item_start_time[]" value=<?php echo $item->template_item_start_time; ?> required ></td>
                    <td><input class="w-125px" type='text' name="template_item_duration[]" value=<?php echo $item->template_item_duration; ?> required ></td>
                    <td><input class="w-75px" type='number' name="template_item_shifts[]" value=<?php echo $item->template_item_shifts; ?> required ></td>
                    <td><input class="w-75px" type='number' name="template_item_column[]" value=<?php echo $item->template_item_column; ?> required ></td>
                    <td><input class="w-75px" type='text' name="template_item_group[]" value=<?php echo $item->template_item_group; ?> required ></td>
                    <td><div class="bg-danger"><input class="w-75px ml-3" type='checkbox' name="template_item_delete[]" value=<?php echo $item->template_item_id; ?> ></div></td>
                    <input class="w-75px" type='hidden' name="template_item_id[]" value=<?php echo $item->template_item_id; ?>  >
                </tr>
            <?php
            }
            ?>
        </table>
        <div class="row" style="width: 1000px">
            <button type="submit" class="btn bth-md bg-primary ml-auto" value=<?php echo esc_html( $template_id ); ?> name="submit_template">Submit</button>
            <button type="submit" class="btn bth-md bg-primary ml-5" value=<?php echo esc_html( $template_id ); ?> name="cancel_template">Cancel</button>
            <button type="button" class="btn bth-md bg-success mr-auto ml-5 add-template-row">Add Row</button>
        </div>
        <?php
        wp_nonce_field( 'signups', 'mynonce' );
        ?>
        </form>
        <?php
    }
}
