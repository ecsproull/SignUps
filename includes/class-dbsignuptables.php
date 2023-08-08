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

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_template"' ) !== 'wp_scw_template' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_template` (
					`template_id` int NOT NULL AUTO_INCREMENT,
					`template_name` varchar(60) NOT NULL,
					`template_columns` int NOT NULL,
					`template_rolling_days` int NOT NULL,
					PRIMARY KEY (`template_id`),
					UNIQUE KEY `template_id_UNIQUE` (`template_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_template_item"' ) !== 'wp_scw_template_item' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_template_item` (
					`template_item_id` int NOT NULL AUTO_INCREMENT,
					`template_item_template_id` int NOT NULL,
					`template_item_day_of_week` int NOT NULL,
					`template_item_title` varchar(45) NOT NULL,
					`template_item_slots` int NOT NULL,
					`template_item_start_time` time NOT NULL,
					`template_item_duration` time NOT NULL,
					`template_item_shifts` int NOT NULL,
					`template_item_group` char(1) NOT NULL,
					`template_item_column` int NOT NULL,
					PRIMARY KEY (`template_item_id`),
					UNIQUE KEY `template_item_id_UNIQUE` (`template_item_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_rolling_attendees"' ) !== 'wp_scw_rolling_attendees' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_rolling_attendees` (
					`attendee_id` int unsigned NOT NULL AUTO_INCREMENT,
					`attendee_session_id` int NOT NULL,
					`attendee_email` varchar(45) NOT NULL,
					`attendee_phone` varchar(15) NOT NULL,
					`attendee_balance_owed` int NOT NULL DEFAULT 0,
					`attendee_lastname` varchar(45) NOT NULL,
					`attendee_firstname` varchar(45) NOT NULL,
					`attendee_item` varchar(45) NOT NULL,
					`attendee_badge` varchar(8) DEFAULT NULL,
					`attendee_payment_start` varchar(45) DEFAULT NULL,
					`attendee_rec_number` varchar(45) DEFAULT NULL,
					`attendee_address1` varchar(100) DEFAULT NULL,
					`attendee_address2` varchar(45) DEFAULT NULL,
					PRIMARY KEY (`attendee_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=463 DEFAULT CHARSET=utf8;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_sessions"' ) !== 'wp_scw_sessions' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_sessions` (
					`session_id` int unsigned NOT NULL AUTO_INCREMENT,
					`session_signup_id` varchar(45) NOT NULL,
					`session_contact_name` varchar(45) DEFAULT NULL,
					`session_contact_email` varchar(45) NOT NULL,
					`session_start_time` int unsigned NOT NULL DEFAULT '0',
					`session_start_formatted` varchar(45) NOT NULL,
					`session_end_time` int unsigned NOT NULL DEFAULT '0',
					`session_end_formatted` varchar(45) NOT NULL,
					`session_slots` int unsigned NOT NULL DEFAULT '1',
					`session_location` varchar(45) NOT NULL,
					`session_item` varchar(45) NOT NULL DEFAULT '0',
					`session_price_id` varchar(45) DEFAULT '0',
					`session_calendar_id` int DEFAULT '0',
					`session_duration` time DEFAULT NULL,
					`session_days_between_sessions` tinyint DEFAULT NULL,
					`session_day_of_month` varchar(60) DEFAULT NULL,
					`session_time_of_day` time DEFAULT NULL,
					PRIMARY KEY (`session_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signups"' ) !== 'wp_scw_signups' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_signups` (
					`signup_id` int unsigned NOT NULL AUTO_INCREMENT,
					`signup_name` varchar(150) NOT NULL,
					`signup_contact_email` varchar(45) NOT NULL,
					`signup_location` varchar(45) NOT NULL,
					`signup_group` varchar(45) NOT NULL DEFAULT 'member',
					`signup_cost` int NOT NULL,
					`signup_thumbnail_url` varchar(255) DEFAULT NULL,
					`signup_default_slots` int DEFAULT NULL,
					`signup_rolling_template` tinyint DEFAULT NULL,
					`signup_default_price_id` varchar(45) DEFAULT '',
					`signup_product_id` varchar(45) DEFAULT NULL,
					`signup_admin_approved` tinyint NOT NULL DEFAULT '0',
					`signup_default_start_time` time DEFAULT NULL,
					`signup_default_duration` time DEFAULT NULL,
					`signup_default_days_between_sessions` tinyint DEFAULT NULL,
					`signup_default_day_of_month` varchar(45) DEFAULT NULL,
					`signup_default_contact_name` varchar(45) DEFAULT NULL,
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

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signup_descriptions"' ) !== 'wp_scw_signup_descriptions' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_signup_descriptions` (
					`description_id` int(11) NOT NULL AUTO_INCREMENT,
					`description_signup_id` int(10) unsigned NOT NULL,
					`description_html` mediumtext NOT NULL,
					PRIMARY KEY (`description_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_stripe_products"' ) !== 'wp_scw_stripe_products' ) {
			$wpdb->query(
				'CREATE TABLE `edswpdb`.`wp_scw_stripe_products` (
					`products_id` int NOT NULL AUTO_INCREMENT,
					`products_product_id` varchar(45) NOT NULL,
					`products_product_description` varchar(200) NOT NULL,
					`products_price_id` varchar(45) NOT NULL,
					`products_price` int NOT NULL,
					`products_product_name` varchar(45) NOT NULL,
					PRIMARY KEY (`products_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
			);
		}
	}
}
