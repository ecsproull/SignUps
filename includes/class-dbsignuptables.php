<?php
/**
 * Summary
 * Place class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Create the database tables on activation.
 */
class DbSignUpTables {

	/**
	 * Creates the DB tables when the the plugin is activated.
	 */
	public function create_db_tables() {
		global $wpdb;
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signups"' ) !== 'wp_scw_signups' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_classes` (
					`signup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`signup_name` varchar(150) NOT NULL,
					`signup_contact_email` varchar(45) NOT NULL,
					`signup_location` varchar(45) NOT NULL,
					`signup_description_url` varchar(255) NOT NULL,
					`signup_cost` int(11) NOT NULL,
					`signup_sig_id` int(11) DEFAULT NULL,
					`signup_thumbnail_url` varchar(255) DEFAULT NULL,
					`signup_default_slots` int(11) DEFAULT NULL,
					`signup_rolling_template` int(11) DEFAULT 0,
					PRIMARY KEY (`signup_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_sessions"' ) !== 'wp_scw_sessions' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_sessions` (
					`session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`session_signup_id` varchar(45) NOT NULL,
					`session_contact_name` varchar(45) DEFAULT NULL,
					`session_contact_email` varchar(45) NOT NULL,
					`session_start_time` int(12) unsigned NOT NULL DEFAULT 0,
					`session_start_formatted` varchar(45) NOT NULL,
					`session_end_time` int(12) unsigned NOT NULL DEFAULT 0,
					`session_end_formatted` varchar(45) NOT NULL,
					`session_slots` int(10) unsigned NOT NULL DEFAULT 1,
					`session_location` varchar(45) NOT NULL,
					`session_sig_slotitemid` int(12) unsigned NOT NULL DEFAULT 0,
					PRIMARY KEY (`session_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=454 DEFAULT CHARSET=utf8;;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_attendees"' ) !== 'wp_scw_attendees' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_attendees` (
					`attendee_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`attendee_session_id` int(12) NOT NULL,
					`attendee_email` varchar(45) NOT NULL,
					`attendee_phone` varchar(15) NOT NULL,
					`attendee_paid_amount` int(11) NOT NULL DEFAULT 0,
					`attendee_lastname` varchar(45) NOT NULL,
					`attendee_firstname` varchar(45) NOT NULL,
					`attendee_item` varchar(45) NOT NULL,
					PRIMARY KEY (`attendee_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=689 DEFAULT CHARSET=utf8;'
			);
		}

	}
}
