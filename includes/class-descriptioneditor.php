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
 * Manages creating signup descriptions.
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
	 * load_products_editor
	 *
	 * @return void
	 */
	public function load_description_editor() {

		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
            if ( isset( $post['submit_description'] ) ) {
				$this->submit_description( $post );
			} elseif ( isset( $post['description_id'] ) ) { 
                $this->load_description_form( $post['description_id'] );
            }else {
				$this->load_description_form( 1 );
			}
		} else {
			$this->load_description_form( 1 );
		}
	}

    private function load_description_form( $description_id ) {
        ?>
        <form method="POST" name="template_form" >
        <div class="description-box mt-4">
            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_title">Title:</label>
            </div>
            <div>
                <input type="text" id="description_title" class="mt-2 w-100" 
                    value="" placeholder="Description Title" name="description_title">
            </div>

            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_instructors">Instructors:</label>
            </div>
            <div>
                <input type="text" id="description_instructors" class="mt-2 w-100" 
                    value="" placeholder="Instructors Names" name="description_instructors">
            </div>
            
            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_enrollment">Enrollment:</label>
            </div>
            <div>
                <input type="text" id="description_enrollment" class="mt-2 w-100" 
                    value="" placeholder="Number of students maximum and minimum." name="description_enrollment">
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
                <label class="label-margin-top mr-2" for="description_instructions">Preclass Instructions:</label>
            </div>
            <div>
                <input type="text" id="description_instructions" class="mt-2 w-100" 
                    value="" placeholder="Glue wood in layers..." name="description_instructions">
            </div>

            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_cost">Cost:</label>
            </div>
            <div>
                <input type="text" id="description_cost" class="mt-2 w-100" 
                    value="" placeholder="30.00" name="description_costs">
            </div>

            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_start">Start Day & Time:</label>
            </div>
            <div>
                <input type="datetime-local" id="description_start" class="mt-2 w-100" 
                    value="" placeholder="" name="description_start">
            </div>

            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_duration">Duration:</label>
            </div>
            <div>
                <input type="time" id="description_duration" class="mt-2 w-100 without_ampm"
                    value="" placeholder="" name="description_duration">
            </div>

            <div class="text-right">
                <label class="label-margin-top mr-2" for="description_description">Description:</label>
            </div>
            
            <div>
                <textarea type="text" id="description_description" class="mt-2 w-100 html-textarea" 
                    value="" placeholder name="description_description">
                    Complete description of the class. It is recommended creating this in a word processor and then pasting it here.
                </textarea>
            </div>
        </div>
        </form>
        <?php
    }

    private function submit_description( $description_id ) {
        
    }
}