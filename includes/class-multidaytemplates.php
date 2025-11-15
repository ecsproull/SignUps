<?php
/*
 * Summary
 * Database class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

/**
 * Form for editing multi day templates.
 * The templates are used bu classes that meet for more than one day.
 * It is accessed via the submenu item named Multi Day Templates.
 */
class MultiDayTemplates extends SignUpsBase {
    
    public function render_admin_page() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) && current_user_can( 'edit_plugins' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
			if ( isset( $post['submit_multiday'] ) ) {
                $this->submit_multiday_template( $post );
            } elseif ( isset( $post['delete_multiday'] ) ) {
                $this->delete_multiday_template( $post );
            } else {
                $this->load_signup_selection();
            }
        } elseif ( current_user_can( 'edit_plugins' ) ) {
            $this->render_page();
        }
    }

    /**
     * Render the admin page content
     */
    public function render_page() {
        $this->render_multiday_form( null );
    }

    public function delete_multiday_template( $post ) {
        global $wpdb;
        $where['multiday_signup_id'] = $signup_id;
        $wpdb->delete( SELF::MULTI_TEMPLATE_ITEMS_TABLE, $where );
    }

    public function submit_multiday_template( $post ) {
        global $wpdb;
        $days_after  = isset( $post['md_days_after'] ) ? $post['md_days_after'] : null;
        $start_times = isset( $post['md_time_of_day'] ) ? $post['md_time_of_day'] : null;
        $durations   = isset( $post['md_duration'] ) ? $post['md_duration']  : null;
        $signup_id   = $post['signup'];
        $max = $days_after ? count( $days_after ) : 0;

        $where['multiday_signup_id'] = $signup_id;
        $wpdb->delete( SELF::MULTI_TEMPLATE_ITEMS_TABLE, $where ); 
        
        for ($i = 0; $i < $max; $i++) {
            $data['multiday_signup_id']  = $signup_id;
            $data['multiday_days_after'] = $days_after[$i];
            $data['multiday_start_time'] = $start_times[$i];
            $data['multiday_duration']   = $durations[$i];

            $rows = $wpdb->insert( self::MULTI_TEMPLATE_ITEMS_TABLE, $data );
            if ( $rows !== 1 ) {
                throw( "Failed to insert row in the multiday template table." );
            }
        }

        $this->render_page();
    }
}
