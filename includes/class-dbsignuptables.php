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
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_attendees"' ) !== 'wp_scw_attendees' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_attendees` (
					`attendee_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`attendee_session_id` int(12) NOT NULL,
					`attendee_email` varchar(45) NOT NULL,
					`attendee_phone` varchar(15) NOT NULL,
					`attendee_balance_owed` int(11) NOT NULL DEFAULT 0,
					`attendee_lastname` varchar(45) NOT NULL,
					`attendee_firstname` varchar(45) NOT NULL,
					`attendee_item` varchar(45) NOT NULL,
					`attendee_badge` varchar(8) NOT NULL,
					`attendee_payment_start` varchar(45) DEFAULT NULL,
					PRIMARY KEY (`attendee_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_payments"' ) !== 'wp_scw_payments' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_payments` (
					`payments_id` int(11) NOT NULL AUTO_INCREMENT,
					`payments_intent_id` varchar(45) NOT NULL,
					`payments_customer_id` varchar(45) DEFAULT NULL,
					`payments_attendee_id` int(11) DEFAULT NULL,
					`payments_price_id` varchar(45) DEFAULT NULL,
					`payments_start_time` varchar(45) NOT NULL,
					`payments_signup_description` varchar(80) DEFAULT NULL,
					`payments_attendee_badge` varchar(45) DEFAULT NULL,
					`payments_amount_charged` int(11) DEFAULT NULL,
					`payments_intent_status_time` varchar(45) DEFAULT NULL,
					`payments_last_access_time` varchar(45) NOT NULL,
					`payments_status` varchar(45) DEFAULT 'FAILED',
					PRIMARY KEY (`payments_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_rolling"' ) !== 'wp_scw_rolling' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_rolling` (
					`rolling_id` int(11) NOT NULL AUTO_INCREMENT,
					`rolling_start_time` time NOT NULL DEFAULT '08:00:00',
					`rolling_session_length` time NOT NULL DEFAULT '01:00:00',
					`rolling_days_week` varchar(45) NOT NULL DEFAULT '1,2,3,4,5',
					`rolling_template_name` varchar(45) NOT NULL DEFAULT 'Daily 8 sessions',
					`rolling_slot_items` varchar(90) NOT NULL DEFAULT 'Attendee',
					`rolling_slots` int(11) NOT NULL DEFAULT 1,
					`rolling_days` int(11) NOT NULL DEFAULT 30,
					PRIMARY KEY (`rolling_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_rolling_attendees"' ) !== 'wp_scw_rolling_attendees' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_rolling_attendees` (
					`attendee_id` int(11) NOT NULL AUTO_INCREMENT,
					`attendee_signup_id` int(11) NOT NULL DEFAULT 1,
					`attendee_email` varchar(45) DEFAULT NULL,
					`attendee_phone` varchar(15) DEFAULT NULL,
					`attendee_lastname` varchar(45) NOT NULL,
					`attendee_firstname` varchar(45) NOT NULL,
					`attendee_item` varchar(45) NOT NULL,
					`attendee_badge` varchar(8) NOT NULL,
					`attendee_start_time` int(12) unsigned NOT NULL DEFAULT 0,
					`attendee_start_formatted` varchar(45) NOT NULL,
					`attendee_end_time` int(12) unsigned NOT NULL DEFAULT 0,
					`attendee_end_formatted` varchar(45) NOT NULL,
					`attendee_comment` text DEFAULT NULL,
					PRIMARY KEY (`attendee_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=463 DEFAULT CHARSET=utf8;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_sessions"' ) !== 'wp_scw_sessions' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_sessions` (
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
					`session_item` varchar(45) NOT NULL DEFAULT '0',
					`session_price_id` varchar(45) DEFAULT '0',
					PRIMARY KEY (`session_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signups"' ) !== 'wp_scw_signups' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_signups` (
					`signup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`signup_name` varchar(150) NOT NULL,
					`signup_contact_email` varchar(45) NOT NULL,
					`signup_location` varchar(45) NOT NULL,
					`signup_description_url` varchar(45) NOT NULL,
					`signup_cost` int(11) NOT NULL,
					`signup_thumbnail_url` varchar(255) DEFAULT NULL,
					`signup_default_slots` int(11) DEFAULT NULL,
					`signup_rolling_template` tinyint(4) DEFAULT NULL,
					`signup_default_price_id` varchar(45) DEFAULT '',
					`signup_sig_id` int(11) DEFAULT NULL,
					PRIMARY KEY (`signup_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_stripe"' ) !== 'wp_scw_stripe' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_stripe` (
					`stripe_id` int(11) NOT NULL DEFAULT 1,
					`stripe_api_key` varchar(100) NOT NULL,
					`stripe_api_secret` varchar(100) NOT NULL,
					PRIMARY KEY (`stripe_id`)
				  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
			);
		}
	}
}
