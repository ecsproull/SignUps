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
				$this->load_description_form( 1 );
			}
		} else {
			$this->load_description_form( 1 );
		}
	}

	/**
	 * Load the form to create class descriptions.
	 *
	 * @param  mixed $description_id The id of the description to load.
	 * @return void
	 */
	private function load_description_form( $description_id ) {
		?>
		<form method="POST" name="template_form" >
		<div class="class-description-box mt-4">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_title">Title:</label>
			</div>
			<div>
				<input type="text" id="description_title" class="mt-2 w-100" 
					value="" placeholder="Description Title" name="description_title" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_name">Contact Name:</label>
			</div>
			<div>
				<input type="text" id="description_contact_name" class="mt-2 w-100" 
					value="" placeholder="Contact Names" name="description_contact_name" required>
			</div>
			
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_slots">Slots:</label>
			</div>
			<div>
				<input type="number" id="description_slots" class="mt-2 w-100" 
					value="" placeholder="Maximum Number of attendees." name="description_slots" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_email">Contact Email:</label>
			</div>
			<div>
				<input type="text" id="description_contact_email" class="mt-2 w-100" 
					value="" placeholder="Contact Email" name="description_contact_email" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_cost">Cost:</label>
			</div>
			<div>
				<input type="text" id="description_cost" class="mt-2 w-100" 
					value="" placeholder="30.00" name="description_cost" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_start">Start Day & Time:</label>
			</div>
			<div>
				<input type="datetime-local" id="description_start" class="mt-2 w-100" 
					value="" placeholder="" name="description_start" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_duration">Duration:</label>
			</div>
			<div>
				<input type="time" id="description_duration" class="mt-2 w-100 without_ampm"
					value="" placeholder="3:00" name="description_duration" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_location">Location:</label>
			</div>
			<div>
				<input type="text" id="description_location" class="mt-2 w-100 without_ampm"
					value="" placeholder="Woodshop, library..." name="description_location" required>
			</div>
		</div>
		
		<div class="description-box">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_prerequisite">Prerequisite:</label>
			</div>
		
			<div>
				<input type="text" id="description_prerequisite" class="mt-2 w-100" 
					value="" placeholder="Prerequisites or none" name="description_prerequisite" required>
			</div>
			
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_materials">Student Materials:</label>
			</div>
			<div>
				<input type="text" id="description_materials" class="mt-2 w-100" 
					value="" placeholder="Wood, glue, ..." name="description_materials" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_instructions">Preclass Instructions:</label>
			</div>
			<div>
				<input type="text" id="description_instructions" class="mt-2 w-100" 
					value="" placeholder="Glue wood in layers..." name="description_instructions" required>
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
		$new_signup = array();
		$new_signup['signup_name']             = $post['description_title'];
		$new_signup['signup_contact_email']    = $post['description_contact_email'];
		$new_signup['signup_location']         = $post['description_location'];
		$new_signup['signup_cost']             = $post['description_cost'];
		$new_signup['signup_default_slots']    = $post['description_slots'];
		$new_signup['signup_rolling_template'] = 0;
		$new_signup['signup_admin_approved']   = 0;
		$new_signup['signup_group']            = 'member';

		$affected_row_count = $wpdb->insert( self::SIGNUPS_TABLE, $new_signup );
		if ( 1 === $affected_row_count ) {
			$new_session = array();
			$start_date                         = new DateTime( $post['description_start'], $this->date_time_zone );
			$duration                           = new Datetime( $post['description_duration'], $this->date_time_zone );
			$duration_hours                     = date_format( $duration, 'h' );
			$duration_minutes                   = date_format( $duration, 'i' );
			$end_date                           = new DateTime( $end, $this->date_time_zone );
			$session['session_start_time']      = $start_date->format( 'U' );
			$session['session_end_time']        = $end_date->format( 'U' );
			$session['session_start_formatted'] = $start_date->format( self::DATETIME_FORMAT );
			$session['session_end_formatted']   = $end_date->format( self::DATETIME_FORMAT );

		} else {
			echo 'Failed to Create Signup : ' . esc_html( $wpdb->last_error );
		}
	}
}
