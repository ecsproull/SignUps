<?php
/**
 * Summary
 * Map settings.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */


/**
 * SignUpsBase is the base class for most other classes. It contains the strings
 * for accessing the database plus code that is used in multiple places. It is used on
 * both the user and admin side of the code.
 */
class SignUpsBase {

	/**
	 * Database attendees table.
	 * Attendees belong to a session for a class or event.
	 *
	 * @var string
	 */
	protected const ATTENDEES_TABLE = 'wp_scw_attendees';

	/**
	 * Database signups table. 
	 * Signups include both rolling signups and class signups.
	 *
	 * @var string
	 */
	protected const SIGNUPS_TABLE = 'wp_scw_signups';

	/**
	 * Database sessions table.
	 * Sessions belong to a class or event signup.
	 *
	 * @var string
	 */
	protected const SESSIONS_TABLE = 'wp_scw_sessions';

	/**
	 * Database rolling attendees table.
	 * Rolling attendees belong to a rolling signup such as monitors.
	 * Rolling signups do not have sessions so the attendees are directly 
	 * associated with a signup slot. A slot is identified by a date and time.
	 *
	 * @var string
	 */
	protected const ATTENDEES_ROLLING_TABLE = 'wp_scw_rolling_attendees';

	/**
	 * Rolling signup table.
	 * This is the data used to define a rolling signup.
	 *
	 * @var string
	 */
	protected const ROLLING_TABLE = 'wp_scw_rolling';

	/**
	 * Date and time exception for rolling signups. e.g. Shop closures.
	 *
	 * @var string
	 */
	protected const ROLLING_EXCEPTIONS_TABLE = 'wp_scw_rolling_exceptions';

	/**
	 * Payments table.
	 * This is a record of payments received.
	 *
	 * @var string
	 */
	protected const PAYMENTS_TABLE = 'wp_scw_payments';

	/**
	 * Stripe settings table.
	 * This table has evolved into a settings table.
	 * It started as the settings for the Stripe.com api credentials
	 * but now holds additional data. Should be converted to a key/value pair table.
	 * 
	 * @var string
	 */
	protected const STRIPE_TABLE = 'wp_scw_stripe';

	/**
	 * Signup descriptions table.
	 * Descriptions have three parts. Descriptions, Calendar Description and Instructions.
	 * All three are held in this table.
	 *
	 * @var string
	 */
	protected const DESCRIPTIONS_TABLE = 'wp_scw_signup_descriptions';

	/**
	 * Rolling signup template table.
	 * To have a rolling signup such as Monitors requires a template to describe its behavior.
	 *
	 * @var string
	 */
	protected const SIGNUP_TEMPLATE_TABLE = 'wp_scw_template';

	/**
	 * Rolling signup template item table.
	 * A template may have many template items and they are held in this table.
	 *
	 * @var string
	 */
	protected const SIGNUP_TEMPLATE_ITEM_TABLE = 'wp_scw_template_item';

	/**
	 * Signup category table.
	 * Two examples of categories are Lathe, Ring Bowl and CNC.
	 * These are the labels that appear on the member landing page
	 * and each signup is assigned a category.
	 *
	 * @var string
	 */
	protected const SIGNUP_CATEGORY_TABLE = 'wp_scw_signup_categories';

	/**
	 * Spider Calendar Event table.
	 * The Signups Plugin uses the Spider Calendar Plugin to help 
	 * members visualize when signups are scheduled.
	 *
	 * @var string
	 */
	protected const SPIDER_CALENDAR_EVENT_TABLE = 'wp_spidercalendar_event';

	/**
	 * Data about received text messages.
	 * This has nothing to do with signups but a endpoint was needed to record
	 * text messages that were replies to texts sent via Twillo.
	 *
	 * @var string
	 */
	protected const TEXT_TABLE = 'wp_scw_text_messages';

	/**
	 * Members table.
	 * The list of currently active members of the woodshop.
	 * It is updated nightly via one of the RestFul APIs. 
	 *
	 * @var string
	 */
	protected const MEMBERS_TABLE = 'wp_scw_members';

	/**
	 * Machine permissions table.
	 * Members can have multiple permissions. This table holds the list
	 * of permissions for each member.
	 *
	 * @var string
	 */
	protected const MACHINE_PERMISSIONS_TABLE = 'wp_scw_machine_permissions';

	/**
	 * Unsubscribe table.
	 * When a member unsubscribes from the classes or monitor emails their
	 * information goes into this table. During the night the list is retrieved
	 * via a RestFul API. Then the data is used on the server to update the members
	 * contact information.
	 *
	 * @var string
	 */
	protected const UNSUBSCRIBE_TABLE = 'wp_scw_unsubscribe';

	/**
	 * Instructors table.
	 * Instructors can teach multiple classes and classes can have multiple instructors.
	 * This table holds the list of instructors.
	 *
	 * @var string
	 */
	protected const INSTRUCTORS_TABLE = 'wp_scw_instructors';

	/**
	 * Session instructors table.
	 * While classes can have multiple instructors, a subset of them are
	 * selected to teach each individual session. They are held in this table.
	 *
	 * @var string
	 */
	protected const SESSION_INSTRUCTORS_TABLE = 'wp_scw_session_instructors';

	/**
	 * New members table.
	 * New members are held here until they complete orientation.
	 * After orientation they added to the members table on the server.
	 * They are then pushed to the server. 
	 *
	 * @var string
	 */
	protected const NEW_MEMBER_TABLE = 'wp_scw_new_member';

	/**
	 * Log table.
	 * Although not heavily used, there are a set of logging functions
	 * that write to this table. This is mostly used for debugging purposes.
	 *
	 * @var mixed
	 */
	protected const LOG_TABLE = 'wp_scw_logs';

	/**
	 * Format DateTime as 2020-08-13 6:00 am.
	 *
	 * @var string
	 */
	protected const DATETIME_FORMAT = 'Y-m-d g:i A';

	/**
	 * Format DateTime as 2020-08-13T6:00 am.
	 * Acceptable for HTML Date Input
	 *
	 * @var string
	 */
	protected const DATETIME_FORMAT_INPUT = 'Y-m-d\TH:i';

	/**
	 * Format Date as 2020-08-13.
	 * Acceptable for HTML Date Input
	 *
	 * @var string
	 */
	protected const DATE_FORMAT3 = 'Y-m-d';

	/**
	 * Format Date as Mon 08-13-2020.
	 *
	 * @var string
	 */
	protected const DATE_FORMAT = 'D m-d-Y';

	/**
	 * Format Date as 08-13-2020.
	 *
	 * @var string
	 */
	protected const DATE_FORMAT2 = 'm-d-Y';

	/**
	 * Format Date as 2020-08-13.
	 *
	 * @var string
	 */
	protected const TIME_FORMAT = 'g:iA';

	/**
	 * Date timezone.
	 *
	 * @var string
	 */
	protected $date_time_zone;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->date_time_zone = new DateTimeZone( 'America/Phoenix' );
	}

	/**
	 * Writes a string to the log table in the database.
	 *
	 * @param  mixed $log_text The string to write to the log.
	 * @return void
	 */
	protected function write_log( $log_text ) {
		global $wpdb;
		$log_data['logs_text'] = $log_text;
		$wpdb->insert( self::LOG_TABLE, $log_data );
	}

	/**
	 * Formats a string to be passed back in form data.
	 *
	 * @param int $attendee_id Attendee ID.
	 * @param int $session_id Session ID.
	 */
	protected function session_attendee_string( $attendee_id, $session_id ) {
		echo esc_html( $attendee_id . ',' . $session_id );
	}

	/**
	 * Format a date
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted date as 12 hour time.
	 */
	protected function format_date( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( self::DATETIME_FORMAT );
	}

	/**
	 * Format a date
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted time only.
	 */
	protected function format_time_only( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( 'g:iA' );
	}

	/**
	 * Format a date string from a DateTime object.
	 *
	 * @param mixed $formatted_time Formatted as 24 hour time.
	 * @return string Formatted date only.
	 */
	protected function format_date_only( $formatted_time ) {
		$dt = new DateTime( $formatted_time );
		return $dt->format( 'Y-m-d' );
	}
	
	/**
	 * When an administrator makes changes to a signup the change will
	 * not appear immediately unless you clear the cache. That is, the cache
	 * holds on to the old data. You can only clear the cache at plugin load/reload.
	 * Upon load the plugin looks at the entry to decide if the cache needs cleared.
	 * 
	 * @param  int $value
	 * @return void
	 */
	protected function set_clear_cache( $value ) {
		global $wpdb;
		$where = array( 'stripe_api_key' => 'cache');
		$data  = array( 'stripe_api_secret' => $value );
		$wpdb->update( self::STRIPE_TABLE, $data, $where );
	}

	/**
	 * Helper function for registering RestFul API routes.
	 * The routes are the URL used to call the API.
	 *
	 * @param  string $namespace The namespace.
	 * @param  string $route End of the route.
	 * @param  object $func The endpoint function.
	 * @param  string $class_inst Instance of the class containing the function.
	 * @param  array  $args Arguments to the api call.
	 * @param  string $method POST, GET....etc.
	 * @return void
	 */
	protected function register_route( $namespace, $route, $func, $class_inst, $args, $method ) {
		$basic_args = array(
			'methods'             => $method,
			'callback'            => array( $class_inst, $func ),
			'permission_callback' => array( $class_inst, 'permissions_check' ),
		);

		array_merge( $basic_args, $args );

		register_rest_route(
			$namespace,
			$route,
			$basic_args
		);
	}

	/**
	 * Returns the HTML description for a signup.
	 *
	 * @param int $signup_id The signup id.
	 * @return array
	 */
	protected function get_signup_html( $signup_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE description_signup_id = %1s',
				self::DESCRIPTIONS_TABLE,
				(int) $signup_id
			),
			OBJECT
		);

		if ( $results ) {
			return $results[0];
		} else {
			return null;
		}
	}

	/**
	 * Is this a rolling signup is the question this function answers.
	 * The function simply looks to see if a rolling template is assigned
	 * to the signup.
	 *
	 * @param  mixed $signup_id The ID of the signup.
	 * @return boolean True for rolling, else false.
	 */
	private function is_rolling_signup( $signup_id ) {
		global $wpdb;
		$signup = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		return $signup[0]->signup_rolling_template > '0';
	}

	/**
	 * Creates a form for new users to apply for membership.
	 * New member don't exist in our database so we need to collect their
	 * information and this is the form for doing that.
	 *
	 * @return void
	 */
	protected function create_new_member_form() {
		?>
		<div class='new-member-form mb-3'>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="rec_card">Rec Card Number:</label>
			</div>
			<div class="text-left">
				<input type="number" id="rec_card" class="mt-2 w-100" pattern="[0-9]{5-10}" max="9999995"
					value="" placeholder="123456" name="new_member_rec_card" required>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="first_name">First Name:</label>
			</div>
			<div class="text-left">
				<input type="text" id="first_name" class="mt-2 w-100" maxlength="42"
					value="" placeholder="John" name="firstname" required>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="last_name">Last Name:</label>
			</div>
			<div class="text-left">
				<input type="text" id="last_name" class="mt-2 w-100" maxlength="42"
					value="" placeholder="Doe" name="lastname" required>
			</div>
			<div></div>
			<div class="text-left">
				<h3 class="mt-1 mb-1 text-danger">Phone number format xxx-xxx-xxxx.</h3>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="phone">Phone:</label>
			</div>
			<div class="text-left">
				<input id="phone" class="member-phone" type="text" name="phone" maxlength="13"
					value="" placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="first_name">Email:</label>
			</div>
			<div class="text-left">
				<input type="email" id="first_name" class="mt-2 w-100"  maxlength="60"
					value="" placeholder="john@doe.com" name="email" required>
			</div>
			<div></div>
			<div class="text-left">
				<h3 class="mt-1 mb-1 text-danger">Sun City West Address.</h3>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="first_name">Street:</label>
			</div>
			<div class="text-left">
				<input type="text" id="first_name" class="mt-2 w-100" maxlength="60"
					value="" placeholder="1234 N RH Johnson" name="new_member_street" required>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="city-state-zip">City, State, Zip:</label>
			</div>
			<div class="text-left">
				<input type="text" id="city-state-zip" class="mt-2 w-100" maxlength="60"
					value="Sun City West AZ  85375" name="new_member_street" readonly disabled>
			</div>
		</div>
		<div class="text-center">
			<h3 style="text-align: center;">
				<button class="btn btn-primary rounded" 
					type="submit" name="email_admin" value="ecsproull765@gmail.com" formnovalidate>Email Administrator</button>
				<input type="hidden" name="contact_email" value="ecsproull765@gmail.com" >
				<input type="hidden" name="contact_name" value="Signup Admin" >
				<div id="email" style="height:0px"></div>
			</h3>
		</div>
		<?php
	}

	/**
	 * Create a member search box used in administrative pages.
	 * An admin often needs to find a member by name. This form 
	 * allows for searching on part of a name, email address or phone number.
	 * 
	 *
	 * @param  mixed $center Should the control be centered.
	 * @param  mixed $badge Badge number.
	 * @param  mixed $firstname First name.
	 * @param  mixed $lastname Last name.
	 * @param  mixed $email Members email.
	 * @param  mixed $phone Members phone.
	 * @return void
	 */
	protected function create_lookup_member_table( $center = false, $badge = '', $firstname = '', $lastname = '', $email = '', $phone = '' ) {
		?>
		<table id="lookup-member" class="mb-2 mt-4 table table-bordered <?php echo $center ? 'ml-auto mr-auto' : ''; ?>" style="width:0;">
			<tr>
				<td class="text-right">
					<input id="search-input" class="member-badge" type="text" placeholder="Enter 3+ character string"
						name="signup_contact_badge" value="<?php echo esc_html( $badge ); ?>" required>
				</td>
				<td class="text-left"><input type="button" id="search_button" class="btn btn-primary rounded ml-4" value='Search'></td>
			</tr>
			<tr>
				<td class="text-right">
					<input id="first-name" class=" member-first-name" type="text" placeholder="First Name"
						name="signup_contact_firstname" value="<?php echo esc_html( $firstname ); ?>" required>
				</td>
				<td  class="text-left">
					<input id="last-name" class="member-last-name" type="text" placeholder="Last Name" required
						name="signup_contact_lastname" value="<?php echo esc_html( $lastname ); ?>"></td>
			</tr>
			<tr>
				<td class="text-right">
					<input id="phone" class="member-phone" type="text" required name="signup_contact_phone"
						value="<?php echo esc_html( $phone ); ?>" placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}">
				</td>
				<td>
					<input id="email" class="member-email" type="email" name="signup_contact_email" placeholder="Your email address."
					value="<?php echo esc_html( $email ); ?>" required>
				</td>
			</tr>
		</table>
		<div id="search-results" class="ml-auto mr-auto"></div>
		<?php
	}

	/**
	 * Creates a section of HTML for the user to identify themselves using their badge number.
	 * A badge number is the only allowed input.
	 * This is used at the top of all member signups where a member must
	 * identify themselves before signing up. By retrieving a
	 * members data from the database we can avoid having member input
	 * incorrect data about themselves.
	 *
	 * @param string $user_group The required group for this signup. Normally "member".
	 * @param string $signup_id The id for the signup.
	 * @param string $secret Secret supplied by member to edit their signups.
	 * @return boolean
	 */
	protected function create_user_table( $user_group, $signup_id, $secret = null ) {
		global $wpdb;
		$return_val  = null;
		$results     = array( 1 );
		$remember_me = false;
		$user_secret = null;

		$rolling_signup = $this->is_rolling_signup( $signup_id );

		$signup = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT signup_guests_allowed
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		if ( $secret ) {
			$attendees_rolling = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE attendee_secret = %s',
					self::ATTENDEES_ROLLING_TABLE,
					$secret
				),
				OBJECT
			);

			if ( $attendees_rolling ) {
				$badge   = $attendees_rolling[0]->attendee_badge;
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE member_badge = %s',
						self::MEMBERS_TABLE,
						$badge
					),
					OBJECT
				);

				$user_secret = $results[0]->member_secret;
				$return_val  = $badge;
			}
			?>
			<?php
		}

		if ( ! $return_val && isset( $_COOKIE['signups_scw_badge'] ) && sanitize_key( $_COOKIE['signups_scw_badge'] ) !== 'undefined' ) {
			$remember_me = true;
			$cookie      = wp_unslash( $_COOKIE );
			$badge       = sanitize_key( $_COOKIE['signups_scw_badge'] );
			$results     = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *
					FROM %1s
					WHERE member_badge = %s',
					self::MEMBERS_TABLE,
					$badge
				),
				OBJECT
			);

			$permission = true;
			if ( $results && $user_group ) {
				$permission = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s
						WHERE permission_badge = %s && permission_machine_name = %s',
						self::MACHINE_PERMISSIONS_TABLE,
						$badge,
						$user_group
					),
					OBJECT
				);
			}

			if ( $results && $permission ) {
				$return_val = $results[0]->member_badge;
			} else {
				$return_val = null;
				$results    = array( 1 );
			}

			$user_secret = $results[0]->member_secret;
		} else {
			$remember_me = isset( $_COOKIE['signups_scw_badge'] ) && sanitize_key( $_COOKIE['signups_scw_badge'] ) !== 'undefined';
		}
		?>

		<table id="lookup-member" class="mb-2 table table-bordered mr-auto ml-auto selection-font">
			<tr>
				<td class="text-right">Enter Badge#</td>
				<td class="text-left"><input id="badge-input" class="member-badge" type="number" name="badge_number" 
					value="<?php echo $return_val ? esc_html( $results[0]->member_badge ) : ''; ?>">
				<button type="button" id="get_member_button" class="btn btn-primary rounded">Lookup</button></td>
				<td></td>
			</tr>
			<tr>
				<td class="text-right"><input id="first-name" class=" member-first-name" type="text" name="firstname" value=<?php echo $return_val ? esc_html( $results[0]->member_firstname ) : 'First'; ?> required readonly></td>
				<td  class="text-left"><input id="last-name" class="member-last-name" type="text" name="lastname" value=<?php echo $return_val ? esc_html( $results[0]->member_lastname ) : 'Last'; ?> required readonly></td>
				<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id" hidden>Cancel</button></td>
			</tr>
			<tr>
				<td class="text-center" colspan="2">
					<span class="mr-1">Remember Badge</span>
					<input id="remember_me" class="position-relative remember-me-chk mr-1" 
						type="checkbox" name="remember_me" value='' <?php echo $remember_me ? 'checked' : ''; ?>></td>
				<td class="text-left"><input id="user-edit-id" class="user-edit-id" 
					type="text" name="secret" value="<?php echo esc_html( $secret ); ?>" placeholder="Enter secret to edit"
					<?php echo $rolling_signup ? 'hidden' : 'hidden'; ?>></td>
				<td><button id="update-butt" type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" 
					value=<?php echo esc_html( $signup_id ); ?> name="continue_signup" disabled
					<?php echo $rolling_signup ? 'hidden' : 'hidden'; ?> >Reload</button></td>
			</tr>
			<tr>
				<td colspan=3>
					<h3 style="text-align: center;">
						<button class="btn btn-primary rounded" type="submit" name="email_admin" value="ecsproull765@gmail.com">Email Administrator</button>
						<input type="hidden" name="contact_email" value="ecsproull765@gmail.com" >
						<input type="hidden" name="contact_name" value="Signup Admin" >
					</h3>
				</td>
			</tr>
			<tr hidden>
				<td><input id="phone" class="member-phone" type="text" name="phone"
					value=<?php echo $return_val ? esc_html( $results[0]->member_phone ) : '888-888-8888'; ?> placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required readonly></td>
				<td><input id="email" class="member-email" type="email" name="email"
					value="<?php echo $return_val ? esc_html( $results[0]->member_email ) : ''; ?>" placeholder="foo@bar.com" required readonly></td>
				<td></td>
			</tr>
			<?php
			if ( $signup->signup_guests_allowed ) {
				?>
				<tr>
					<td colspan=3><h1 style="color:red;">Will you bring a Guest
						<input id="guest" class="remember-me-chk ml-2" type="checkbox" name="attendee_plus_guest" value=""></h1>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<div id="email"></div>
		<input id="user_groups" type="hidden" name="user_groups" value="<?php echo esc_html( $user_group ); ?>">
		<input id="user-secret" type="hidden" name="user_secret" value="<?php echo esc_html( $user_secret ); ?>">
		<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		<?php

		return $return_val;
	}

	/**
	 * Creates a rolling signup based on a template
	 *
	 * @param  int    $rolling_signup_id The id for the signup.
	 * @param  string $secret A secret key used to identify a user. Obsolete.
	 * @param  bool   $admin Set to true if an admin is using this function.
	 * @param  int    $rolling_days The number of rolling days to create.
	 *
	 * @return void
	 */
	protected function create_rolling_session( $rolling_signup_id, $secret, $admin = false, $rolling_days = null ) {
		global $wpdb;
		$today = new DateTime( 'now', $this->date_time_zone );
		$today->SetTime( 5, 0 );
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
					signup_name,
					signup_rolling_template,
					signup_default_price_id,
					signup_group
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$rolling_signup_id
			),
			OBJECT
		);

		if ( $rolling_days && 'Past' === $rolling_days ) {
			$date_interval         = new DateInterval( 'P30D' );
			$date_interval->invert = 1;
			$today->add( $date_interval );
		}

		$attendees_rolling = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE attendee_signup_id = %s AND attendee_start_time >= %d
				ORDER BY attendee_start_time',
				self::ATTENDEES_ROLLING_TABLE,
				$signups[0]->signup_id,
				$today->format( 'U' )
			),
			OBJECT
		);

		$template = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE template_id = %s',
				self::SIGNUP_TEMPLATE_TABLE,
				$signups[0]->signup_rolling_template
			),
			OBJECT
		);

		$template_items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT template_item_day_of_week,
				template_item_title,
				template_item_slots,
				template_item_start_time,
				template_item_duration,
				template_item_shifts,
				template_item_group,
				template_item_column
				FROM %1s
				WHERE template_item_template_id = %s',
				self::SIGNUP_TEMPLATE_ITEM_TABLE,
				$template[0]->template_id
			),
			OBJECT
		);

		$template2 = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE template_id = %s',
				self::SIGNUP_TEMPLATE_TABLE,
				'1'
			),
			OBJECT
		);

		$template_items2 = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT template_item_day_of_week,
				template_item_title,
				template_item_slots,
				template_item_start_time,
				template_item_duration,
				template_item_shifts,
				template_item_group,
				template_item_column
				FROM %1s
				WHERE template_item_template_id = %s',
				self::SIGNUP_TEMPLATE_ITEM_TABLE,
				$template2[0]->template_id
			),
			OBJECT
		);

		$description_html = null;
		$description = $this->get_signup_html( $rolling_signup_id );
		if ( $description ) {
			$description_html = $description->description_html;
		}

		$this->create_rolling_session_select_form2(
			$signups[0]->signup_name,
			$attendees_rolling,
			$rolling_signup_id,
			$template[0],
			$template_items,
			$signups[0]->signup_group,
			$admin,
			$secret,
			$description_html,
			$template_items2,
			$rolling_days
		);
	}

	/**
	 * Get the attendees for a rolling signup slot. Based on start time.
	 *
	 * @param  mixed $attendees Attendees for this signup.
	 * @param  mixed $start_date When to look look for attendees that match the start date.
	 * @param  mixed $template_item_title The title of the signup.
	 * @return array
	 */
	private function get_slot_attendees( $attendees, $start_date, $template_item_title ) {
		$slot_attendees = array();
		foreach ( $attendees as $attendee ) {
			if ( $attendee->attendee_start_time === $start_date->format( 'U' ) &&
				$template_item_title === $attendee->attendee_item ) {
				$slot_attendees[] = $attendee;
			}
		}

		return $slot_attendees;
	}

	/**
	 * Get the attendees based on start time. Used to check if an attendee is double booking.
	 *
	 * @param  mixed $attendees Attendees for this signup.
	 * @param  mixed $start_date When to look look for attendees that match the start date.
	 * @param  mixed $badge Current users badge.
	 * @return array
	 */
	private function is_attendee_free( $attendees, $start_date, $badge ) {
		$slot_attendees = array();
		foreach ( $attendees as $attendee ) {
			if ( $attendee->attendee_start_time === $start_date->format( 'U' ) &&
				$badge === $attendee->attendee_badge ) {
				return true;
			}
		}

		return true;
	}

	/**
	 * Retrieves a list of meeting exceptions between a start and end date.
	 *
	 * @param  mixed $start_date Date to start creating from.
	 * @param  mixed $end_date Date to end creating exceptions.
	 * @return array
	 */
	public function create_meeting_exceptions( $start_date, $end_date ) {
		global $wpdb;
		$exceptions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE exc_start >= %s AND exc_start <= %s',
				self::ROLLING_EXCEPTIONS_TABLE,
				$start_date->format( 'Y-m-d' ),
				$end_date->format( 'Y-m-d' )
			),
			OBJECT
		);

		$time_exceptions = array();
		foreach ( $exceptions as $exc ) {
			$dte               = new TimeException();
			$dte->template     = $exc->exc_template_id;
			$dte->begin        = new DateTime( $exc->exc_start, $this->date_time_zone );
			$dte->end          = new DateTime( $exc->exc_end, $this->date_time_zone );
			$dte->reason       = $exc->exc_reason;
			$time_exceptions[] = $dte;
		}

		return $time_exceptions;
	}

	/**
	 * Creates a form that displays the rolling sessions along with their attendees.
	 * This is a long function and studying the code is the only way to fully understand it.
	 * It allows members to select slots and also remove themselves from a previously selected slot.
	 * Some of the signups, such as monitors are not allowed to remove themselves within 2 days of their
	 * duty time. By default the user can see the default number of days that the signup specifies. Usually 
	 * 30 days. There is a drop down where 60 and 90 days can be selected. The dropdown also allows to see
	 * the past 30 days to look at the history.
	 * 
	 * There is a lot of logic intertwined with the HTML that is being generated which makes this a hard
	 * function to follow. Good luck.
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @param  object $template The template for the rolling class.
	 * @param  array  $template_items Each one describe a signup for that day.
	 * @param  string $user_group The group that is allowed to sign up.
	 * @param  bool   $admin This being accessed by an administrator.
	 * @param  string $secret Unique id for a member.
	 * @param  string $description Description of the signup.
	 * @param  mixed  $template_items2 The template items for template2. Used when a template is changed at a predetermined date.
	 * @param  mixed  $rolling_days Overrides the standard number of rolling days.
	 * @return void
	 */
	protected function create_rolling_session_select_form2(
		$signup_name,
		$attendees,
		$signup_id,
		$template,
		$template_items,
		$user_group,
		$admin,
		$secret,
		$description,
		$template_items2,
		$rolling_days = null
	) {
		$start_date = new DateTime( 'now', $this->date_time_zone );
		$end_date   = new DateTime( 'now', $this->date_time_zone );

		if ( ! $rolling_days && 'Past' !== $rolling_days ) {
			$rolling_days = $template->template_rolling_days;
		}

		if ( 'Past' !== $rolling_days ) {
			$end_date->add( new DateInterval( 'P' . $rolling_days . 'D' ) );
		} else {
			$date_interval = new DateInterval( 'P30D' );
			$end_date->add( $date_interval );
			$date_interval->invert = 1;
			$start_date->add( $date_interval );
		}
		$one_day_interval = new DateInterval( 'P1D' );
		$time_exceptions  = $this->create_meeting_exceptions( $start_date, $end_date );

		if ( ! $secret && get_query_var( 'secret' ) ) {
			$secret = get_query_var( 'secret' );
		}
		?>
		<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><b><?php echo esc_html( $signup_name ); ?></b></h1>
			<div>
				<div>
					<form class="signup_form" method="POST">
						<?php
						$user_badge = null;
						wp_nonce_field( 'signups', 'mynonce' );
						if ( ! $admin ) {
							$user_badge = $this->create_user_table( $user_group, $signup_id, $secret );
							?>
							<div class="text-left mb-2 html-font"><?php echo html_entity_decode( $description ); ?></div>
							<?php
						}
						?>

						<div class="rolling-days-select">
							<label for="rolling-days-sel">Days</label>
							<select id="rolling-days" name="rolling_days">
								<option value="30" <?php echo '30' === $rolling_days ? 'selected' : ''; ?>>30</option>
								<option value="60" <?php echo '60' === $rolling_days ? 'selected' : ''; ?>>60</option>
								<option value="90" <?php echo '90' === $rolling_days ? 'selected' : ''; ?>>90</option>
								<option value="Past" <?php echo 'Past' === $rolling_days ? 'selected' : ''; ?>>Past</option>
							</select>
						</div>

						<table id="selection-table" class="table-bordered mr-auto ml-auto selection-font"
							<?php echo null === $user_badge && ! $admin ? 'hidden' : ''; ?> >
							<?php
							$current_day    = '2000-07-01';
							$comment_index  = 0;
							$comment_name   = 'comment-';
							$comment_row_id = 'comment-row-';
							while ( $start_date <= $end_date ) {
								$datetime = new DateTime( '09/23/2024 12:00 AM' );
								if ( $start_date > $datetime && '1' === $signup_id ) {
									$template_items = $template_items2;
								}

								$day_of_week = $start_date->format( 'N' );
								$day_items   = array_filter(
									$template_items,
									function( $value, $key ) use ( $day_of_week ) {
										return str_contains( $value->template_item_day_of_week, $day_of_week );
									},
									ARRAY_FILTER_USE_BOTH
								);
								usort(
									$day_items,
									function ( $a, $b ) {
										$st_time1 = strtotime( $a->template_item_start_time );
										$st_time2 = strtotime( $b->template_item_start_time );
										if ( $st_time1 < $st_time2 ) {
											return 1;
										} else {
											return 0;
										}
										//return $st_time1 < $st_time2;
									}
								);

								$slot_titles = array();
								$group_items = array();
								foreach ( $day_items as $day_item ) {
									$slot_titles[ $day_item->template_item_title ] = $day_item->template_item_title;
									if ( ! array_key_exists( $day_item->template_item_group, $group_items ) ) {
										$group_items[ $day_item->template_item_group ] = array();
									}

									$group_items[ $day_item->template_item_group ][] = $day_item;
								}

								usort(
									$group_items,
									function ( $a, $b ) {
										if ( $a[0]->template_item_start_time > $b[0]->template_item_start_time ) {
											return 1;
										} else {
											return 0;
										}
										//return $a[0]->template_item_start_time > $b[0]->template_item_start_time;
									}
								);

								$current_day = $start_date->format( self::DATE_FORMAT );
								$count       = 0;
								$col_span    = $template->template_columns + 1;
								?>
								<tr class="submit-row" colspan='<?php echo esc_html( $col_span ); ?>'>
								<td colspan='<?php echo esc_html( $col_span ); ?>'><button type="submit" class="btn btn-md mr-auto ml-auto bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
								</tr>
								<tr class="date-row">
									<td colspan='<?php echo esc_html( $col_span ); ?>'><span class='mt-3'><?php echo esc_html( $current_day ); ?></span></td>
								</tr>
								<?php
								foreach ( $group_items as $group_item ) {
									$start_time_parts = explode( ':', $group_item[0]->template_item_start_time );
									$duration_parts   = explode( ':', $group_item[0]->template_item_duration );
									$start_hour       = $start_time_parts[0];
									$start_minutes    = $start_time_parts[1];
									$start_date       = $start_date->SetTime( $start_hour, $start_minutes );
									$duration         = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
									$temp_end_date    = new DateTime( $start_date->format( self::DATETIME_FORMAT ), $this->date_time_zone );
									$temp_end_date->Add( $duration );

									for ( $s = 0; $s < $group_item[0]->template_item_shifts; $s++ ) {
										?>
										<tr  class="attendee-row"  style=<?php echo $s % 2 ? 'background:#cfcfcf;' : 'background:#efefef;'; ?> >
											<td><?php echo esc_html( $start_date->format( self::TIME_FORMAT ) . ' - ' . $temp_end_date->format( self::TIME_FORMAT ) ); ?></td>
										<?php

										$current_column = 0;
										foreach ( $group_item as $item ) {
											for ( ; $current_column < $item->template_item_column; $current_column++ ) {
												?>
												<td></td>
												<?php
											}
											$slot_attendees = $this->get_slot_attendees( $attendees, $start_date, $item->template_item_title );
											if ( $slot_attendees ) {
												if ( '1' === $item->template_item_slots ) {
													$attendee = $slot_attendees[0];
													?>
													<td>
														<span class='text-primary'><i><?php echo esc_html( $item->template_item_title ); ?> </i></span><br>
														<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
														<input class="form-check-input ml-2 rolling-remove-chk mt-2 <?php echo esc_html( $attendee->attendee_badge ); ?>" 
															type="checkbox" name="remove_slots[]" 
															<?php echo $this->add_remove_chk( $start_date, $user_badge, $attendee, $secret, $template ) || $admin ? '' : 'hidden'; ?>
															value="
															<?php
															echo esc_html(
																$start_date->format( self::DATETIME_FORMAT ) . ',' .
																$temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $item->template_item_title . ',' .
																$comment_index . ',' . $attendee->attendee_id
															);
															?>
															"
															>
													</td>
													<?php
												} else {
													?>
													<td>
													<?php
													if ( count( $slot_attendees ) < $item->template_item_slots ) {
														?>
														<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo esc_html( str_replace( ' ', '', $item->template_item_title ) ); ?>" 
														type="checkbox" name="time_slots[]" 
														value="
														<?php
														echo esc_html(
															$start_date->format( self::DATETIME_FORMAT ) . ',' . $temp_end_date->format( self::DATETIME_FORMAT ) .
															',' . $item->template_item_title . ',' . $comment_index . ',0,' . $item->template_item_slots
														);
														?>
														" <?php echo $this->is_attendee_free( $attendees, $start_date, $user_badge ) ? '' : 'disabled'; ?> ><br>
														<?php
													}

													if ( count( $slot_attendees ) === (int) $item->template_item_slots ) {
														echo "<span class='text-primary'><i>All " . esc_html( $item->template_item_slots . ' ' . $item->template_item_title ) . ' Filled</i></span><br>';
													} else {
														echo "<span class='text-primary'><i>" . count( $slot_attendees ) . ' of ' . esc_html( $item->template_item_slots ) . ' Filled </i></span><br>';
													}

													$count         = 1;
													$random_number = wp_rand();
													foreach ( $slot_attendees as $attendee ) {
														?>
														<div class="<?php echo $count > 3 ? esc_html( $random_number ) : ''; ?>" <?php echo $count > 3 ? 'hidden' : ''; ?> >
															<?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?>
															<input class="form-check-input ml-2 rolling-remove-chk mt-2 <?php echo esc_html( $attendee->attendee_badge ); ?>" 
																type="checkbox" name="remove_slots[]" 
																<?php echo $this->add_remove_chk( $start_date, $user_badge, $attendee, $secret, $template ) || $admin ? '' : 'hidden'; ?>
																value="
																<?php
																echo esc_html(
																	$start_date->format( self::DATETIME_FORMAT ) . ',' .
																	$temp_end_date->format( self::DATETIME_FORMAT ) . ',' . $item->template_item_title . ',' .
																	$comment_index . ',' . $attendee->attendee_id
																);
																?>
																">
															<br>
														</div>
														<?php
														$count++;
													}

													if ( $count > 3 ) {
														?>
														<button class="btn btn-sm bg-primary mr-auto ml-auto expand-button" type='button' 
															data-button='{"session_id": <?php echo esc_html( $random_number ); ?>}' >Show All</button>
														<?php
													}
													?>
													</td>
													<?php
												}
											} else {
												$skip_time_slot = false;
												$reason         = '';
												foreach ( $time_exceptions as $exception ) {
													if ( $start_date >= $exception->begin &&
														$start_date < $exception->end &&
														( $exception->template === $template ||
														'0' === $exception->template ) ) {
														$reason         = $exception->reason;
														$skip_time_slot = true;
														break;
													}
												}

												if ( $skip_time_slot ) {
													?>
													<td><?php echo esc_html( $reason ); ?></td>
													<?php
												} else {
													$com_name = $comment_name . $comment_index;
													$value    = $start_date->format( self::DATETIME_FORMAT ) . ',';
													$value   .= $temp_end_date->format( self::DATETIME_FORMAT ) . ',';
													$value   .= $item->template_item_title . ',' . $comment_index . ',0,' . $item->template_item_slots;
													?>
													<td class="text-center">
														<span class="mr-2"><?php echo esc_html( $item->template_item_title ); ?></span> 
														<input class="form-check-input position-relative rolling-add-chk ml-auto <?php echo esc_html( str_replace( ' ', '', $item->template_item_title ) ); ?>" 
															type="checkbox" name="time_slots[]" 
															value="<?php echo esc_html( $value ); ?>"
															<?php echo $this->is_attendee_free( $attendees, $start_date, $user_badge ) ? '' : 'disabled'; ?> >
															<?php
															if ( $item->template_item_slots > '1' && count( $slot_attendees ) === 0 ) {
																echo "<br><span class='text-primary'><i>" . count( $slot_attendees ) . ' of ' . esc_html( $item->template_item_slots ) . ' Filled </i></span><br>';
															}
															?>
													</td>
													<?php
												}
											}

											$current_column++;
										}

										for ( ; $current_column < $template->template_columns; $current_column++ ) {
											?>
											<td></td>
											<?php
										}
										?>
										</tr>
										<?php
										$start_date->add( $duration );
										$temp_end_date->Add( $duration );
									}
								}
								$start_date->add( $one_day_interval );
							}
							?>
							<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
							<input type="hidden" name="add_attendee_session" value="<?php echo esc_html( $signup_id ); ?>">
							<input id="template_days_to_cancel" type="hidden" name="template_days_to_cancel" 
								value="<?php echo esc_html( $template->template_days_to_cancel ); ?>">
							<?php
							if ( $admin ) {
								?>
								<input type="hidden" name="is_admin" value="true">
								<?php
							}
							?>
							<tr class="footer-row">
								<td><button type="button" class="btn bth-md mr-auto ml-auto mt-2 bg-primary back-button" value="-1" name="signup_id">Cancel</button></td>
								<?php
								$current_column = 1;
								for ( ; $current_column < $template->template_columns; $current_column++ ) {
									?>
									<td></td>
									<?php
								}
								?>
								<td><button type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="<?php echo esc_html( $signup_id ); ?>" name="add_attendee_session">Submit</button></td>
							</tr>
						</form>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Determines if the remove checkbox should be added.
	 * This is a helper function for creating rolling forms.
	 *
	 * @param  mixed $start_date The date of the signup.
	 * @param  mixed $user_badge The users badge.
	 * @param  mixed $attendee Current attendee for the slot.
	 * @param  mixed $secret Current users secret to be able to unsubscribe.
	 * @param  mixed $template The template for the signup.
	 * @return boolean True if it is ok to cancel the session, false if not.
	 */
	private function add_remove_chk( $start_date, $user_badge, $attendee, $secret, $template ) {
		$sd  = clone $start_date;
		$now = date_create( 'now' );
		date_sub( $sd, date_interval_create_from_date_string( $template->template_days_to_cancel . 'days' ) );
		$ret_val = $user_badge === $attendee->attendee_badge && $now < $sd;
		return $ret_val;
	}

	/**
	 * Add attendee for the selected spots.
	 * This is a helper function used by members and admins.
	 * Members can moved themselves and admins often need to move
	 * a member.
	 *
	 * @param  array|mixed $post Data from the form.
	 * @return void
	 */
	protected function add_attendee_rolling( $post ) {
		global $wpdb;
		$send_mail = false;
		$body      = '<h2>' . $post['signup_name'] . '</h2><br>';
		$body     .= '<b><pre>      Date           Time           Name             Item         Status</pre></b>';
		?>
		<table class="mb-100px mr-auto ml-auto">
			<tr class="attendee-row">
				<th>Date</th>
				<th>Time</th>
				<th>Name</th>
				<th>Item</th>
				<th>Status</th>
			</tr>
		<?php

		if ( isset( $post['remove_slots'] ) ) {
			foreach ( $post['remove_slots'] as $slot ) {
				$slot_parts           = explode( ',', $slot );
				$slot_start           = new DateTime( $slot_parts[0], $this->date_time_zone );
				$slot_end             = new DateTime( $slot_parts[1], $this->date_time_zone );
				$slot_id              = $slot_parts[4];
				$where                = array();
				$where['attendee_id'] = $slot_id;

				/* $wpdb->query(
					$wpdb->prepare(
						'LOCK TABLES %1s WRITE',
						self::ATTENDEES_ROLLING_TABLE
					)
				); */

				$delete_return_value = $wpdb->delete( self::ATTENDEES_ROLLING_TABLE, $where );
				//$wpdb->query( 'UNLOCK TABLES' );
				?>
				<tr class="attendee-row" style="background-color:#FFCCCB;">
					<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
					<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
					<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
					<td><?php echo esc_html( $slot_parts[2] ); ?></td>
					<?php
					$body     .= '<pre>' . $slot_start->format( self::DATE_FORMAT ) . ' ' . $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT );
					$body     .= '  ' . $post['firstname'] . ' ' . $post['lastname'] . '    ' . $slot_parts[2];
					$send_mail = true;
					if ( ! $delete_return_value ) {
						?>
						<td style="color:red"><b><i>Failed</i></b></td>
						<?php
						$body .= '  Removed Failed </pre>';
					} else {
						?>
						<td>Success</td>
						<?php
						$body .= '  Removed Success </pre>';
					}
					?>
				</tr>
				<?php
			}
		}

		if ( isset( $post['is_admin'] ) ) {
			?>
			</table>
			<?php
			$current_user = wp_get_current_user();
			$sgm          = new SendGridMail();
			$ip_address   = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'No Ip Address';
			$url          = get_site_url();
			$link         = "<a href='$url/signups/?signup_id=" . $post['add_attendee_session'] . '&secret=' . $post['user_secret'] . "'>Edit Signup</a>";
			$body        .= '<br><br> IP Address: ' . $ip_address . '<br>' . $link . '<br>' . esc_html( $current_user->user_login ) . '<br>' . esc_html( $current_user->user_email ) . '<br>';
			$sgm->send_mail( 'ecsproull765@gmail.com', 'ADMIN Woodshop Signup', $body );
			return;
		}

		$badge = $post['badge_number'];
		if ( strlen( $badge ) !== 4 || ! preg_match( '~[0-9]+~', $badge ) ) {
			?>
			<h2>Data verification failed. Error:0x80420</h2>
			<?php
			return;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE member_badge = %s',
				self::MEMBERS_TABLE,
				$post['badge_number']
			),
			OBJECT
		);

		if ( ! $results ) {
			?>
			<h2>Data verification failed. Error:0x80421</h2>
			<?php
			return;
		}

		$new_attendee                       = array();
		$new_attendee['attendee_signup_id'] = $post['add_attendee_session'];
		$new_attendee['attendee_email']     = $post['email'];
		$new_attendee['attendee_phone']     = $post['phone'];
		$new_attendee['attendee_lastname']  = $post['lastname'];
		$new_attendee['attendee_firstname'] = $post['firstname'];
		$new_attendee['attendee_badge']     = $post['badge_number'];
		$new_attendee['attendee_secret']    = $post['user_secret'];
		$insert_return_value                = false;
		?>
		<div class="container">
			<form method="POST">
				<?php
				if ( isset( $post['time_slots'] ) ) {
					foreach ( $post['time_slots'] as $slot ) {
						$slot_parts                               = explode( ',', $slot );
						$slot_start                               = new DateTime( $slot_parts[0], $this->date_time_zone );
						$new_attendee['attendee_start_time']      = $slot_start->format( 'U' );
						$new_attendee['attendee_start_formatted'] = $slot_start->format( self::DATETIME_FORMAT );
						$slot_end                                 = new DateTime( $slot_parts[1], $this->date_time_zone );
						$new_attendee['attendee_end_time']        = $slot_end->format( 'U' );
						$new_attendee['attendee_end_formatted']   = $slot_end->format( self::DATETIME_FORMAT );
						$new_attendee['attendee_item']            = trim( $slot_parts[2] );
						$comment_name                             = 'comment-' . $slot_parts[3];
						$slot_count                               = $slot_parts[5];

						/* $wpdb->query(
							$wpdb->prepare(
								'LOCK TABLES %1s WRITE',
								self::ATTENDEES_ROLLING_TABLE
							)
						); */

						$dup_rows = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT * FROM %1s WHERE attendee_start_time = %d AND attendee_signup_id = %d AND attendee_item = %s',
								self::ATTENDEES_ROLLING_TABLE,
								$new_attendee['attendee_start_time'],
								$new_attendee['attendee_signup_id'],
								$new_attendee['attendee_item']
							)
						);

						$insert_id = -1;
						if ( count( $dup_rows ) < $slot_count ) {
							$insert_return_value = $wpdb->insert( self::ATTENDEES_ROLLING_TABLE, $new_attendee );
							if ( $insert_return_value ) {
								$insert_id = $wpdb->insert_id;
							}
						} else {
							?>
							<h2 class="text-danger">Timeslot is already taken, refresh page and reselect another time.</h2>
							<?php
						}

						//$wpdb->query( 'UNLOCK TABLES' );
						?>
						<tr class="attendee-row">
							<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
							<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
							<td><?php echo esc_html( $slot_parts[2] ); ?></td>
							<?php
							$body .= '<pre>' . $slot_start->format( self::DATE_FORMAT ) . ' ' . $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT );
							$body .= '  ' . $post['firstname'] . ' ' . $post['lastname'] . '    ' . $slot_parts[2];
							if ( ! $insert_return_value || count( $dup_rows ) > $slot_count ) {
								?>
								<td style="color:red"><b><i>Failed</i></b></td>
								<?php
								$body .= '   ' . "<b style='color:red'><i>   Failed </pre></i></b></pre>";
							} else {
								$send_mail = true;
								?>
								<td>Success</td>
								<?php
								$body .= '   Success </pre>';
							}
							?>
						</tr>
						<?php
					}
				}

				?>
				<tr class="attendee-row">
					<td></td>
					<td class="text-center">
						<button class="btn btn-primary signup-submit" type="submit" name="signup_id" value="<?php echo esc_html( $post['add_attendee_session'] ); ?>" >Return</button>
					</td>
					<td></td>
					<td class="text-center"><button class="btn btn-primary back-button" type="button" name="signup_id" value="-1" >Cancel</button></td>
					<td></td>
				</tr>
				</table>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
			<h2>Signup complete</h2>
			<br>
			<div class="fs-3 text-dark">
				<a href="<?php echo esc_html( get_site_url() ); ?>/signups/?signup_id=<?php echo esc_html( $post['add_attendee_session'] ); ?>&secret=<?php echo esc_html( $post['user_secret'] ); ?>" >Change Signup</a><br>
				<br>
				<!--<p>Your key to edit this signup is: &emsp; &emsp; <?php echo esc_html( $post['user_secret'] ); ?> </p> -->
				<p>ALSO: An email has been sent to <b><i><?php echo esc_html( $post['email'] ); ?></i></b> with a link to edit this signup.<p>
			</div>
			
		</div>
		<?php
		if ( $send_mail ) {
			$sgm   = new SendGridMail();
			$url   = get_site_url();
			$link  = "<a href='$url/signups/?signup_id=" . $post['add_attendee_session'] . '&secret=' . $post['user_secret'] . "'>Edit Signup</a>";
			$body .= '<br><br>' . $link . '<br>';
			$sgm->send_mail( $post['email'], 'Woodshop Signup', $body );
		}

		clean_post_cache( $post );
	}

	/**
	 * Loads the template selection dropdown list.
	 *
	 * @param  int     $template_id The id of the selected template.
	 * @param  boolean $add_new Adds an option to add a new template.
	 * @param  string  $template_id_name The name of the template.
	 * @param  string  $select_id The name of the selected template.
	 * @param  boolean $default_title Default title.
	 * @return void
	 */
	protected function load_template_selection(
		$template_id,
		$add_new,
		$template_id_name,
		$select_id = 'template-select',
		$default_title = 'None'
		) {
		global $wpdb;
		$templates = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT template_id, template_name
				FROM %1s',
				self::SIGNUP_TEMPLATE_TABLE
			),
			OBJECT
		);

		?>
		<select id="<?php echo esc_html( $select_id ); ?>" name="<?php echo esc_html( $template_id_name ); ?>">
		<option value="0"><?php echo esc_html( $default_title ); ?></option>
		<?php
		foreach ( $templates as $result ) {
			if ( $template_id === $result->template_id ) {
				?>
				<option value="<?php echo esc_html( $result->template_id ); ?>" selected><?php echo esc_html( $result->template_name ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_html( $result->template_id ); ?>"><?php echo esc_html( $result->template_name ); ?></option>
				<?php
			}
		}

		if ( $add_new ) {
			?>
			<option value="-1">New Template</option>
			<?php
		}
		?>
		<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</select>
		<?php
	}

	/**
	 * Updates the price id of all sessions for a signup.
	 *
	 * @param  mixed $signup_id The id of the parent signup to the sessions.
	 * @param  mixed $new_price_id The new price id.
	 * @return void
	 */
	protected function update_sessions_price_id( $signup_id, $new_price_id ) {
		global $wpdb;
		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$signup_id
			),
			ARRAY_A
		);

		foreach ( $sessions as $session ) {
			$where = array( 'session_id' => $session['session_id'] );
			$data  = array( 'session_price_id' => $new_price_id );
			$wpdb->update( self::SESSIONS_TABLE, $data, $where );
		}
	}

	/**
	 * Creates the description, short description and instructions input block.
	 * JS is used to convert the textareas to the HTML editor, CKEditor.
	 * See SignUps.js.
	 *
	 * @param mixed $description_object Object that holds the description, instructions and calendar description.
	 * @return void
	 */
	protected function create_description_section( $description_object ) {
		?>
		<div class="description-box">
			<div></div>
			<div>
				<ul class="nav mt-2 border bg-light mb-2">
					<li class="nav-item border" style="background-color: #BEBEBE">
						<b><a class="nav-link long-desc active" aria-current="page" href="#">Description</a></b>
					</li>
					<li class="nav-item border">
						<b><a class="nav-link short-desc" href="#">Calendar</a></b>
					</li>
					<li class="nav-item border">
						<b><a class="nav-link inst" href="#">Instructions</a></b>
					</li>
				</ul>
				<div id="html-signup-description">
				<textarea id="description_long"
					name='description_html'><?php echo $description_object ? html_entity_decode( $description_object->description_html ) : ''; ?>
				</textarea>
				</div>
				<div id="html-signup-description-short" style="display: none;">
					<textarea id="description_short" 
						name='description_html_short'><?php echo $description_object ? html_entity_decode( $description_object->description_html_short ) : ''; ?>
					</textarea>
				</div>
				<div id="html-signup-instructions" style="display: none;">
					<textarea id="description_instructions" 
						name='description_instructions'><?php echo $description_object ? html_entity_decode( $description_object->description_instructions ) : ''; ?>
					</textarea>
				</div>
			<div>
		</div>
		<?php
	}

	/**
	 * Removes items from the calendar in response to removing admin approval from a signup.
	 * The calendar is another plugin called the Spider Calendar. We are just inputting rows
	 * into their database.
	 *
	 * @param  int $signup_id The signup id that owns the sessions.
	 * @param  string $signup_name The sighup name.
	 * @param  bool $add To add or remove from the calendar.
	 * @return void
	 */
	protected function add_remove_from_calendar( $signup_id, $signup_name, $add ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$signup_id
			),
			OBJECT
		);

		if ( $results ) {
			foreach ( $results as $session ) {
				if ( $session->session_calendar_id || ( ! $session->session_calendar_id && $add ) ) {
					$mini_post = array(
						'signup_id'           => $session->session_signup_id,
						'session_id'          => $session->session_id,
						'signup_name'         => $signup_name,
						'session_calendar_id' => $session->session_calendar_id,
					);

					if ( $add ) {
						$mini_post['update'] = true;
						$this->update_calendar( $mini_post );
					} else {
						$this->update_calendar( $mini_post );
						$where = array( 'session_id' => $session->session_id );
						$data  = array( 'session_calendar_id' => '' );
						$wpdb->update( self::SESSIONS_TABLE, $data, $where );
					}
				}
			}
		}
	}

	/**
	 * Updates the clubs calendar.
	 *
	 * @param int $post The posted data from the form.
	 */
	protected function update_calendar( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_id = %s',
				self::SESSIONS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$description = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE description_signup_id = %s',
				self::DESCRIPTIONS_TABLE,
				$post['signup_id']
			),
			OBJECT
		);

		$session         = $results[0];
		$new_calendar_id = 0;
		if ( $session->session_calendar_id > 0 && ! isset( $post['update'] ) ) {
			$where_session = array( 'id' => $session->session_calendar_id );
			$wpdb->delete( self::SPIDER_CALENDAR_EVENT_TABLE, $where_session );
		} else {
			$datetime   = new DateTime( $session->session_start_formatted );
			$date       = $datetime->format( 'Y-m-d' );
			$start_time = $datetime->format( 'g:iA' );
			$datetime   = new DateTime( $session->session_end_formatted );
			$end_time   = $datetime->format( 'g:iA' );
			$signup_url = get_site_url() . '/signups?signup_id=' . $post['signup_id'];

			$text_for_date;
			if ( $description ) {
				if ( $description[0]->description_html_short ) {
					$text_for_date = html_entity_decode( $description[0]->description_html_short );
				} else {
					$text_for_date = html_entity_decode( $description[0]->description_html );
				}
			}
			$text_for_date .= '<br><br><a href=' . $signup_url . " target='_blank' rel='noopener' >More Info</a>.";

			$data                  = array();
			$data['calendar']      = 1;
			$data['date']          = $date;
			$data['date_end']      = $date;
			$data['title']         = $post['signup_name'];
			$data['category']      = 7;
			$data['time']          = $start_time . '-' . $end_time;
			$data['text_for_date'] = $text_for_date;
			$data['userID']        = '';
			$data['repeat_method'] = 'no_repeat';
			$data['repeat']        = '1';
			$data['week']          = '';
			$data['month']         = '';
			$data['month_type']    = '1';
			$data['monthly_list']  = '';
			$data['month_week']    = '';
			$data['year_month']    = '1';
			$data['published']     = 1;

			if ( isset( $post['session_calendar_id'] ) && $post['session_calendar_id'] > 0 ) {
				$where = array( 'id' => $post['session_calendar_id'] );
				$rows  = $wpdb->update( self::SPIDER_CALENDAR_EVENT_TABLE, $data, $where );
				if ( false === $rows ) {
					echo '<h1>Failed to update Calendar id: </h1)' . esc_html( $post['session_calendar_id'] . ' with error : ' . $wpdb->last_error );
				}
				return;

			} else {
				$rows            = $wpdb->insert( self::SPIDER_CALENDAR_EVENT_TABLE, $data );
				$new_calendar_id = $wpdb->insert_id;
			}
		}

		$where                         = array();
		$update                        = array();
		$where['session_id']           = $post['session_id'];
		$update['session_calendar_id'] = $new_calendar_id;
		$affected_row_count            = $wpdb->update(
			'wp_scw_sessions',
			$update,
			$where
		);
	}

	/**
	 * Retrieves the data to create a pre-class email.
	 *
	 * @param int $session_id If requested a particular session.
	 */
	protected function get_session_email_data( $session_id = null ) {
		global $wpdb;
		$sessions = null;

		if ( ! $session_id ) {
			$dt       = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
			$today    = $dt->format( self::DATE_FORMAT2 );
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wp_scw_sessions.session_id,
						wp_scw_sessions.session_signup_id,
						wp_scw_sessions.session_start_formatted,
						wp_scw_sessions.session_location,
						wp_scw_sessions.session_slots
					FROM %1s
					LEFT JOIN wp_scw_signups ON wp_scw_signups.signup_id = wp_scw_sessions.session_signup_id
					WHERE wp_scw_sessions.session_preclass_email_date = %s AND wp_scw_signups.signup_admin_approved = 1',
					self::SESSIONS_TABLE,
					$today
				),
				OBJECT
			);
		} else {
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wp_scw_sessions.session_id,
						wp_scw_sessions.session_signup_id,
						wp_scw_sessions.session_start_formatted,
						wp_scw_sessions.session_location,
						wp_scw_sessions.session_slots
					FROM %1s
					LEFT JOIN wp_scw_signups ON wp_scw_signups.signup_id = wp_scw_sessions.session_signup_id
					WHERE wp_scw_sessions.session_id = %s AND wp_scw_signups.signup_admin_approved = 1',
					self::SESSIONS_TABLE,
					$session_id
				),
				OBJECT
			);
		}

		$return_values = array();
		foreach ( $sessions as $session ) {
			$data = new SessionEmailData();
			$signup = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT signup_name, 
						signup_contact_firstname,
						signup_contact_email,
						signup_contact_lastname,
						signup_default_minimum
					FROM %1s
					WHERE signup_id = %d',
					self::SIGNUPS_TABLE,
					$session->session_signup_id
				),
				OBJECT
			);

			$signup_instructions = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT description_instructions, description_materials 
					FROM %1s
					WHERE description_signup_id = %d',
					self::DESCRIPTIONS_TABLE,
					$session->session_signup_id
				),
				OBJECT
			);

			if ( $signup_instructions[0] && $signup_instructions[0]->description_instructions ) {
				$data->class_instructions = html_entity_decode( $signup_instructions[0]->description_instructions );
			} else {
				$data->class_instructions = 'None';
			}

			if ( $signup_instructions[0] && $signup_instructions[0]->description_materials ) {
				$data->class_materials = html_entity_decode( $signup_instructions[0]->description_materials );
			} else {
				$data->class_materials = 'None';
			}

			$data->class_title             = $signup[0]->signup_name;
			$data->class_location          = $session->session_location;
			$data->date_time_formatted     = $session->session_start_formatted;
			$data->class_slots             = $session->session_slots;
			$data->class_signup_id         = $session->session_signup_id;
			$data->class_contact_firstname = $signup[0]->signup_contact_firstname;
			$data->class_contact_lastname  = $signup[0]->signup_contact_lastname;
			$data->class_contact_email     = $signup[0]->signup_contact_email;
			$data->class_minimum           = $signup[0]->signup_default_minimum;
			$data->instructors             = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT wp_scw_instructors.instructors_name,
						wp_scw_instructors.instructors_email
					FROM wp_scw_instructors
					LEFT JOIN wp_scw_session_instructors
					ON wp_scw_instructors.instructors_id = wp_scw_session_instructors.si_instructor_id
					WHERE wp_scw_session_instructors.si_session_id = %d',
					$session->session_id
				),
				OBJECT
			);

			$attendees = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT attendee_email
					FROM %1s
					WHERE attendee_session_id = %d',
					self::ATTENDEES_TABLE,
					$session->session_id
				),
				OBJECT
			);

			$data->attendees = array();
			foreach ( $attendees as $attendee ) {
				$data->attendees[] = $attendee->attendee_email;
			}

			$return_values[] = $data;
		}

		return $return_values;
	}

	/**
	 * Formats the body of a session email to an attendee.
	 *
	 * @param  mixed $session_id The id of the session to send.
	 * @return Body for the email.
	 */
	protected function get_session_email_body( $session_id ) {
		$sessions = $this->get_session_email_data( $session_id );
		if ( ! $sessions ) {
			return;
		}

		$session          = $sessions[0];
		$body             = '<p>We look forward to seeing you for ' . $session->class_title . ' on ' . $session->date_time_formatted . ' which is scheduled to meet at ' . $session->class_location . '.</p>';
		$body            .= '<p>The instructor(s) for the class will be ';
		$index            = 1;
		$instructor_count = count( $session->instructors );
		if ( $instructor_count > 0 ) {
			$add_comma = false;
			foreach ( $session->instructors as $instructor ) {
				if ( $add_comma ) {
					if ( $index === $instructor_count ) {
						$body .= ' & ' . $instructor->instructors_name;
					} else {
						$body .= ', ' . $instructor->instructors_name;
					}
				} else {
					$add_comma = true;
					$body     .= ' ' . $instructor->instructors_name;
				}

				$index++;
			}
		} else {
			$body .= ' ' . $session->class_contact_firstname . ' ' . $session->class_contact_lastname;
		}

		$body .= '</p>';
		if ( $session->class_materials && 'None' !== substr( $session->class_materials, 0, 4 ) ) {
			$body .= '<p><b>These are the materials that you need to bring with you.</b><br>';
			$body .= '<p>' . $session->class_materials . '</p>';
			$body .= '</p>';
		} else {
			$body .= '<p>The materials for this class will be supplied.</p>';
		}

		if ( $session->class_instructions && 'None' !== substr( $session->class_instructions, 0, 4 ) ) {
			$body .= '<b>To be prepared for this class please follow these pre-class instructions.</b>';
			$body .= '<p>' . $session->class_instructions . '</p>';
			$body .= '</p>';
		} else {
			$body .= '<p>There are no pre-class instructions for this class. Just show up ready to learn.</p>';
		}

		$body .= '<p>If you need to reschedule you may do it yourself on the original signup. ';
		$body .= 'Sign in with your badge number and select the square checkbox next to your name. ';
		$body .= 'Then select the session you wish to attend and then use the Submit button to update your selection.</p>';
		$body .= '<p>For general questions about the class: <a href="mailto:' . $session->class_contact_email . '">' . $session->class_contact_firstname . ' ' . $session->class_contact_lastname . '</a></p>';
		$body .= '<p>For technical questions about the signup website: <a href=\"mailto:ecsproull765@gmail.com\">Ed Sproull</a></p>';

		return $body;
	}
}
