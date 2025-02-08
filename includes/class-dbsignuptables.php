<?php
/*
 * Summary
 * Signup Database table creation.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

/**
 * Create the database tables on activation.
 */
class DbSignUpTables {

	/**
	 * The only function and it creates the DB tables when the the plugin is activated.
	 * Each table is checked to see if it exists. If not, it is created.
	 */
	public function create_db_tables() {
		global $wpdb;
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_attendees"' ) !== 'wp_scw_attendees' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_attendees` (
				`attendee_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`attendee_session_id` int(11) NOT NULL,
				`attendee_email` varchar(45) NOT NULL,
				`attendee_phone` varchar(15) NOT NULL,
				`attendee_balance_owed` int(11) NOT NULL DEFAULT 0,
				`attendee_lastname` varchar(45) NOT NULL,
				`attendee_firstname` varchar(45) NOT NULL,
				`attendee_item` varchar(150) NOT NULL,
				`attendee_badge` varchar(8) NOT NULL,
				`attendee_payment_start` varchar(45) DEFAULT NULL,
				`attendee_plus_guest` tinyint(4) DEFAULT 0,
				PRIMARY KEY (`attendee_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=323 DEFAULT CHARSET=utf8mb4;"
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
				) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_template"' ) !== 'wp_scw_template' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_template` (
				`template_id` int(11) NOT NULL AUTO_INCREMENT,
				`template_name` varchar(60) NOT NULL,
				`template_columns` int(11) NOT NULL,
				`template_rolling_days` int(11) NOT NULL,
				`template_days_to_cancel` int(11) NOT NULL DEFAULT -1,
				PRIMARY KEY (`template_id`),
				UNIQUE KEY `template_id_UNIQUE` (`template_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_template_item"' ) !== 'wp_scw_template_item' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_template_item` (
				`template_item_id` int(11) NOT NULL AUTO_INCREMENT,
				`template_item_template_id` int(11) NOT NULL,
				`template_item_day_of_week` varchar(45) NOT NULL,
				`template_item_title` varchar(45) NOT NULL,
				`template_item_slots` int(11) NOT NULL,
				`template_item_start_time` time NOT NULL,
				`template_item_duration` time NOT NULL,
				`template_item_shifts` int(11) NOT NULL,
				`template_item_group` char(1) NOT NULL,
				`template_item_column` int(11) NOT NULL,
				PRIMARY KEY (`template_item_id`),
				UNIQUE KEY `template_item_id_UNIQUE` (`template_item_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_rolling_attendees"' ) !== 'wp_scw_rolling_attendees' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_rolling_attendees` (
				`attendee_id` int(11) NOT NULL AUTO_INCREMENT,
				`attendee_signup_id` int(11) NOT NULL DEFAULT 1,
				`attendee_email` varchar(45) DEFAULT NULL,
				`attendee_phone` varchar(15) DEFAULT NULL,
				`attendee_lastname` varchar(45) NOT NULL,
				`attendee_firstname` varchar(45) NOT NULL,
				`attendee_item` varchar(45) NOT NULL,
				`attendee_badge` varchar(8) NOT NULL,
				`attendee_start_time` int(10) unsigned NOT NULL DEFAULT 0,
				`attendee_start_formatted` varchar(45) NOT NULL,
				`attendee_end_time` int(10) unsigned NOT NULL DEFAULT 0,
				`attendee_end_formatted` varchar(45) NOT NULL,
				`attendee_comment` text DEFAULT NULL,
				`attendee_secret` varchar(45) DEFAULT NULL,
				PRIMARY KEY (`attendee_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=2999 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_sessions"' ) !== 'wp_scw_sessions' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_sessions` (
				`session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`session_signup_id` varchar(45) NOT NULL,
				`session_contact_name` varchar(45) DEFAULT NULL,
				`session_contact_email` varchar(45) NOT NULL,
				`session_start_time` int(10) unsigned NOT NULL DEFAULT 0,
				`session_start_formatted` varchar(45) NOT NULL,
				`session_end_time` int(10) unsigned NOT NULL DEFAULT 0,
				`session_end_formatted` varchar(45) NOT NULL,
				`session_slots` int(10) unsigned NOT NULL DEFAULT 1,
				`session_location` varchar(45) NOT NULL,
				`session_item` varchar(45) NOT NULL DEFAULT '0',
				`session_price_id` varchar(45) DEFAULT '0',
				`session_calendar_id` int(11) NOT NULL DEFAULT 0,
				`session_duration` time DEFAULT NULL,
				`session_days_between_sessions` tinyint(4) DEFAULT NULL,
				`session_day_of_month` varchar(60) DEFAULT NULL,
				`session_time_of_day` time DEFAULT NULL,
				`session_multiple_days` tinyint(4) DEFAULT NULL,
				`session_preclass_email_date` varchar(48) DEFAULT NULL,
				PRIMARY KEY (`session_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signups"' ) !== 'wp_scw_signups' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_signups` (
				`signup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`signup_order` int(11) DEFAULT NULL,
				`signup_name` varchar(150) NOT NULL,
				`signup_contact_email` varchar(45) NOT NULL,
				`signup_contact_firstname` varchar(48) NOT NULL,
				`signup_contact_lastname` varchar(48) NOT NULL,
				`signup_contact_badge` varchar(6) NOT NULL,
				`signup_contact_phone` varchar(45) NOT NULL,
				`signup_location` varchar(45) NOT NULL,
				`signup_group` varchar(45) NOT NULL DEFAULT 'member',
				`signup_cost` int(11) NOT NULL,
				`signup_default_slots` int(11) DEFAULT NULL,
				`signup_rolling_template` tinyint(4) DEFAULT NULL,
				`signup_product_id` varchar(45) DEFAULT NULL,
				`signup_default_price_id` varchar(45) DEFAULT '',
				`signup_admin_approved` tinyint(4) NOT NULL DEFAULT 0,
				`signup_multiple_days` tinyint(4) NOT NULL DEFAULT 1,
				`signup_default_start_time` time DEFAULT NULL,
				`signup_default_duration` time DEFAULT NULL,
				`signup_default_days_between_sessions` tinyint(4) DEFAULT NULL,
				`signup_default_day_of_month` varchar(45) DEFAULT NULL,
				`signup_default_contact_name` varchar(45) DEFAULT NULL,
				`signup_category` tinyint(4) DEFAULT NULL,
				`signup_default_minimum` tinyint(4) DEFAULT 1,
				`signup_schedule_desc` varchar(150) DEFAULT NULL,
				`signup_preclass_email` int(11) NOT NULL DEFAULT 1,
				`signup_guests_allowed` tinyint(4) DEFAULT 0,
				PRIMARY KEY (`signup_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signup_descriptions"' ) !== 'wp_scw_signup_descriptions' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_signup_descriptions` (
				`description_id` int(11) NOT NULL AUTO_INCREMENT,
				`description_signup_id` int(10) unsigned NOT NULL,
				`description_html` mediumtext NOT NULL,
				`description_html_short` mediumtext DEFAULT NULL,
				`description_prerequisite` mediumtext DEFAULT NULL,
				`description_materials` mediumtext DEFAULT NULL,
				`description_instructions` mediumtext DEFAULT NULL,
				`description_instructors` varchar(150) DEFAULT NULL,
				PRIMARY KEY (`description_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_signup_categories"' ) !== 'wp_scw_signup_categories' ) {
			$wpdb->query(
				'CREATE TABLE `wp_scw_signup_categories` (
					`category_id` int unsigned NOT NULL AUTO_INCREMENT,
					`category_title` varchar(150) NOT NULL,
					PRIMARY KEY (`category_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;'
			);

			$wpdb->query(
				"INSERT INTO `wp_scw_signup_categories`
				(`category_title`)
				VALUES
				('Lathe'),
				('Ring Bowl'),
				('CNC'),
				('Fundamentals'),
				('Lathe Projects'),
				('Club Classes'),
				('Project Classes'),
				('Miscellaneous')"
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
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_rolling_exceptions"' ) !== 'wp_scw_rolling_exceptions' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_rolling_exceptions` (
					`exc_id` int unsigned NOT NULL AUTO_INCREMENT,
					`exc_template_id` int NOT NULL DEFAULT '0',
					`exc_start` datetime NOT NULL,
					`exc_end` datetime NOT NULL,
					`exc_reason` varchar(45) NOT NULL DEFAULT 'Shop Closed',
					PRIMARY KEY (`exc_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_members"' ) !== 'wp_scw_members' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_new_member` (
				`new_member_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`new_member_rec_card` varchar(45) NOT NULL,
				`new_member_first` varchar(45) NOT NULL,
				`new_member_last` varchar(45) NOT NULL,
				`new_member_phone` varchar(45) NOT NULL,
				`new_member_email` varchar(64) NOT NULL,
				`new_member_street` varchar(64) NOT NULL,
				PRIMARY KEY (`new_member_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_machine_permissions"' ) !== 'wp_scw_machine_permissions' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_machine_permissions` (
				`permission_ID` int(11) NOT NULL AUTO_INCREMENT,
				`permission_machine_name` varchar(16) NOT NULL,
				`permission_badge` varchar(12) NOT NULL,
				PRIMARY KEY (`permission_ID`),
				UNIQUE KEY `permission_ID` (`permission_ID`)
				) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_text_messages"' ) !== 'wp_scw_text_messages' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_text_messages` (
					`text_id` int unsigned NOT NULL AUTO_INCREMENT,
					`text_body` text,
					`text_from_phone` varchar(45) DEFAULT NULL,
					`text_date_time` varchar(30) DEFAULT NULL,
					PRIMARY KEY (`text_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_unsubscribe"' ) !== 'wp_scw_unsubscribe' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_unsubscribe` (
				`unsubscribe_id` int(11) NOT NULL AUTO_INCREMENT,
				`unsubscribe_key` varchar(45) NOT NULL,
				`unsubscribe_complete` tinyint(4) NOT NULL DEFAULT 0,
				`unsubscribe_badge` varchar(6) NOT NULL,
				`unsubscribe_mail_group` varchar(32) NOT NULL DEFAULT 'monitor',
				PRIMARY KEY (`unsubscribe_id`),
				UNIQUE KEY `unsubscribe_id_UNIQUE` (`unsubscribe_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4;"
			);
		}
		
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_logs"' ) !== 'wp_scw_logs' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_logs` (
					`logs_id` int unsigned NOT NULL AUTO_INCREMENT,
					`logs_text` mediumtext NOT NULL,
					PRIMARY KEY (`logs_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_session_instructors"' ) !== 'wp_scw_session_instructors' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_session_instructors` (
					`si_id` int unsigned NOT NULL AUTO_INCREMENT,
					`si_signup_id` int NOT NULL,
					`si_session_id` int NOT NULL,
					`si_instructor_id` int NOT NULL,
					PRIMARY KEY (`si_id`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_instructors"' ) !== 'wp_scw_instructors' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_instructors` (
					`instructors_id` int unsigned NOT NULL AUTO_INCREMENT,
					`instructors_badge` varchar(6) NOT NULL,
					`instructors_name` varchar(45) NOT NULL,
					`instructors_email` varchar(45) NOT NULL,
					`instructors_phone` varchar(12) NOT NULL,
					`instructors_class_id` int NOT NULL,
					`instructors_class_title` varchar(64) NOT NULL,
					PRIMARY KEY (`instructors_id`)
				  ) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;"
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "wp_scw_members"' ) !== 'wp_scw_members' ) {
			$wpdb->query(
				"CREATE TABLE `wp_scw_members` (
				`member_ID` int(11) NOT NULL AUTO_INCREMENT,
				`member_badge` varchar(6) NOT NULL,
				`member_firstname` varchar(24) NOT NULL,
				`member_lastname` varchar(24) NOT NULL,
				`member_secret` varchar(45) NOT NULL,
				`member_email` varchar(45) DEFAULT NULL,
				`member_phone` varchar(45) DEFAULT NULL,
				`member_email_secret` varchar(48) NOT NULL,
				UNIQUE KEY `member_ID` (`member_ID`)
				) ENGINE=InnoDB AUTO_INCREMENT=690 DEFAULT CHARSET=utf8mb4;"
			);
		}
	}
}
