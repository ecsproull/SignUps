<?php
/**
 * Summary
 * Discription submission class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages creating a signup with one session and a description..
 */
class DescriptionEditor extends SignUpsBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Load the description editor.
	 *
	 * @param boolean $admin Is the user an admin.
	 * @return void
	 */
	public function load_description_editor( $admin = false ) {

		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			if ( isset( $post['submit_description'] ) ) {
				$this->submit_description( $post );
			} elseif ( isset( $post['description_id'] ) ) {
				$this->load_description_form( $post['description_id'] );
			} else {
				$this->load_description_form();
			}
		} else {
			$this->load_description_form();
		}
	}

	/**
	 * Load the form to create class descriptions.
	 *
	 * @return void
	 */
	private function load_description_form() {
		?>
		<form method="POST" name="template_form" >
		<div class="description-box mt-4">
		<div class="text-right">
				<label class="label-margin-top mr-2" for="description_title">Title:</label>
			</div>
			<div>
				<input type="text" id="description_title" class="mt-2 w-100" 
					value="" placeholder="Description Title" name="description_title" required>
			</div>
		</div>
		<div class="class-description-box">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_name">Contact Name:</label>
			</div>
			<div>
				<input type="text" id="description_contact_name" class="mt-2 w-100" 
					value="" placeholder="Contact Names" name="description_contact_name" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_email">Contact Email:</label>
			</div>
			<div>
				<input type="text" id="description_contact_email" class="mt-2 w-100" 
					value="" placeholder="Contact Email" name="description_contact_email" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_location">Location:</label>
			</div>
			<div>
				<input type="text" id="description_location" class="mt-2 w-100 without_ampm"
					value="SCW Woodclub" placeholder="Woodshop, library..." name="description_location" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_slots">Slots:</label>
			</div>
			<div>
				<input type="number" id="description_slots" class="mt-2 w-100 h-2rem" 
					value="" placeholder="Maximum Number of attendees." name="description_slots" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_default_minimum">Minimum Attendees:</label>
			</div>
			<div>
				<input type="number" id="signup_default_minimum" class="mt-2 w-100" 
					value="1" name="signup_default_minimum" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_start">Start Day & Time:</label>
			</div>
			<div>
				<input type="datetime-local" id="description_start" class="mt-2 w-100 h-2rem" 
					value="" placeholder="" name="description_start" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_cost">Cost:</label>
			</div>
			<div>
				<input type="number" id="description_cost" class="mt-2 w-100 h-2rem" 
					value="0" name="description_cost" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_duration">Duration:</label>
			</div>
			<div>
				<input type="text" id="description_duration" class="mt-2 w-100 without_ampm h-2rem"
					value="" placeholder="--:--" pattern="[0-9]{1,2}:[0-9]{2}" name="description_duration">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_Repeat">Repeat:</label>
			</div>
			<div>
				<select id="signup_Repeat" class="mt-2 w-100 h-2rem" name="description_repeat">
					<option value="7">Weekly</option>
					<option value="14">Two Weeks</option>
					<option value="21">Three Weeks</option>
					<option value="31">Monthly</option>
					<option value="0">TBD</option>
				</select>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_group">User Group:</label>
			</div>
			<div>
				<select id="signup_group" class="mt-2 w-100 h-2rem" name="description_group">
					<option value="member">Members</option>
					<option value="cnc">Cnc Users</option>
				</select>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_multiple_days">Multi Day:</label>
			</div>
			<div>
				<input id="signup_multiple_days" class="mt-2 w-100" name="signup_multiple_days" 
					value="1" >
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_end_repeat">End Repeat:</label>
			</div>
			<div>
				<input type="date" id="description_end_repeat" class="mt-2 w-100 h-2rem" 
					value="" placeholder="" name="description_end_repeat">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_admin_approved">Admin Approved:</label>
			</div>
			<div class="text-left ml-2 pt-2"><input type="checkbox" id="signup_admin_approved" class="mt-2"  
				name="signup_admin_approved" checked /> 
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_add_cal">Add to Calendar:</label>
			</div>
			<div class="text-left ml-2 pt-2"><input type="checkbox" id="description_add_cal" class="mt-2"  
				name="description_add_cal" checked /> 
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_repeat_count">Repeat Count:</label>
			</div>
			<div>
				<input type="number" id="description_repeat_count" class="mt-2 w-100" 
					value="1" name="description_repeat_count" required>
			</div>
		</div>

		<div class="description-box">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_schedule_desc">Schedule:</label>
			</div>

			<div>
				<input type="text" id="signup_schedule_desc" class="mt-2 w-100" 
					value="" placeholder="Leave blank unless the schedule is TBD" name="signup_schedule_desc">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_prerequisite">Prerequisite:</label>
			</div>

			<div>
				<input type="text" id="description_prerequisite" class="mt-2 w-100" 
					value="" placeholder="Prerequisites or none" name="description_prerequisite">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_materials">Student Materials:</label>
			</div>
			<div>
				<input type="text" id="description_materials" class="mt-2 w-100" 
					value="" placeholder="Wood, glue, ..." name="description_materials">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_instructions">Preclass:</label>
			</div>
			<div>
				<textarea type="text" id="description_instructions" class="mt-2 w-100" rows="4" cols="120"
					value="" placeholder="Glue wood in layers..." name="description_instructions"></textarea>
			</div>

			<div class="text-right mt-5">
				<label class="label-margin-top mr-2" for="description_description">Description:</label>
			</div>

			<div class="mt-2">
				<textarea type="text" id="description_description" class=" w-100 html-textarea" 
					value="" placeholder name="description_description" required>
					Complete description of the class. It is recommended creating this in a word processor and then pasting it here.
				</textarea>
			</div>

			<div></div>
			<div><button type="submit" class="btn btn-md bg-primary mr-auto ml-auto" value="-1" name="submit_description">Submit</button></div>
		</div>
		<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Submit the description to the database.
	 *
	 * @param  mixed $post Data posted from the create form.
	 * @return void
	 */
	private function submit_description( $post ) {
		global $wpdb;
		$new_signup                                = array();
		$new_signup['signup_name']                 = $post['description_title'];
		$new_signup['signup_contact_email']        = $post['description_contact_email'];
		$new_signup['signup_default_contact_name'] = $post['description_contact_name'];
		$new_signup['signup_location']             = $post['description_location'];
		$new_signup['signup_cost']                 = $post['description_cost'];
		$new_signup['signup_default_slots']        = $post['description_slots'];
		$new_signup['signup_rolling_template']     = 0;
		$new_signup['signup_admin_approved']       = 0;
		$new_signup['signup_group']                = $post['description_group'];
		$new_signup['signup_schedule_desc']        = $post['signup_schedule_desc'];
		$new_signup['signup_default_minimum']      = $post['signup_default_minimum'];

		$start_date                              = new DateTime( $post['description_start'], $this->date_time_zone );
		$new_signup['signup_default_start_time'] = date_format( $start_date, 'H:i' );

		if ( '31' !== $post['description_repeat'] ) {
			$new_signup['signup_default_days_between_sessions'] = $post['description_repeat'];
		} else {
			$new_signup['signup_default_days_between_sessions'] = 0;
			$day  = $start_date->format( 'l' );
			$date = $start_date->format( 'j' );
			$week = intdiv( $date, 7 );
			switch ( $week ) {
				case 0:
					$new_signup['signup_default_day_of_month'] = 'First ' . $day;
					break;
				case 1:
					$new_signup['signup_default_day_of_month'] = 'Second ' . $day;
					break;
				case 2:
					$new_signup['signup_default_day_of_month'] = 'Third ' . $day;
					break;
				case 3:
					$new_signup['signup_default_day_of_month'] = 'Fourth ' . $day;
					break;
				default:
					$new_signup['signup_default_day_of_month'] = 'Last ' . $day;
					break;
			}
		}

		$duration         = new Datetime( $post['description_duration'], $this->date_time_zone );
		$duration_hours   = date_format( $duration, 'h' );
		$duration_minutes = date_format( $duration, 'i' );

		if ( (int) $duration_hours > 12 ) {
			$duration_hours = $duration - 12;
			$duration->modify( '-12 hours' );
		}

		$duration_total_minutes                = $duration_hours * 60 + $duration_minutes;
		$new_signup['signup_default_duration'] = date_format( $duration, 'H:i' );

		$affected_row_count = $wpdb->insert( self::SIGNUPS_TABLE, $new_signup );
		if ( 1 === $affected_row_count ) {
			$signup_id                              = $wpdb->insert_id;
			$new_session                            = array();
			$new_session['session_start_time']      = $start_date->format( 'U' );
			$new_session['session_start_formatted'] = $start_date->format( self::DATETIME_FORMAT );
			$start_date->modify( '+' . $duration_total_minutes . ' minutes' );
			$new_session['session_end_time']              = $start_date->format( 'U' );
			$new_session['session_end_formatted']         = $start_date->format( self::DATETIME_FORMAT );
			$new_session['session_contact_email']         = $post['description_contact_email'];
			$new_session['session_contact_name']          = $post['description_contact_name'];
			$new_session['session_duration']              = date_format( $duration, 'H:i' );
			$new_session['session_slots']                 = $post['description_slots'];
			$new_session['session_item']                  = 'attendee';
			$new_session['session_days_between_sessions'] = $new_signup['signup_default_days_between_sessions'];
			$new_session['session_day_of_month']          = $new_signup['signup_default_day_of_month'];
			$new_session['session_time_of_day']           = $new_signup['signup_default_start_time'];
			$new_session['session_signup_id']             = $signup_id;

			$affected_row_count = $wpdb->insert( self::SESSIONS_TABLE, $new_session );
			if ( 1 !== $affected_row_count ) {
				echo '<h1>Failed to Create Initial Session : ' . esc_html( $wpdb->last_error ) . '</h1>';
				$where              = array( 'signup_id' => $signup_id );
				$affected_row_count = $wpdb->delete( self::SIGNUPS_TABLE, $where );
			} else {
				$new_description = array(
					'description_signup_id'    => $signup_id,
					'description_html'         => htmlentities( $post['description_description'] ),
					'description_materials'    => $post['description_materials'],
					'description_prerequisite' => $post['description_prerequisite'],
					'description_instructions' => $post['description_instructions'],
				);

				$affected_row_count = $wpdb->insert( self::DESCRIPTIONS_TABLE, $new_description );
				if ( 1 !== $affected_row_count ) {
					echo '<h1>Failed to Create Description : ' . esc_html( $wpdb->last_error ) . '</h1>';
				} else {
					echo '<h1>Success, check with the system admin to get this approved</h1>';
				}
			}
		} else {
			echo '<h1>Failed to Create Signup : ' . esc_html( $wpdb->last_error ) . '</h1>';
		}
	}
}
