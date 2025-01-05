<?php
/*
 * Summary
 * Shortcode class.
 *
 * @package SignUps
 */

ob_start();

/**
 * The ShortCodes class is the main class for generating the web pages that the user can see.
 * The default function create_select_signup creates the landing page for the plugin.
 */
class ShortCodes extends SignUpsBase {

	/**
	 * This is the entry function for the user side of the SignUp plugin.
	 * This function is also called in response to a Form's submit button. The Submit button
	 * for each form dictates which helper function is called to process the data that is input
	 * on a Form. For example when a users selects a class from the landing page the item selected
	 * is a actually a Submit button. That is received here and then passed to the helper function
	 * called  create_description_form that creates the description page for the class.
	 *
	 * This process of generating a Form, receiving user input and then navigating to the next Form
	 * to process that input is the basis of how the plugin works.
	 *
	 * An understanding of HTML forms is necessary to understanding this code. While the code is
	 * written PHP it generates HTML.
	 *
	 * @param  bool $admin_view When true it shows all signups whether they are approved or not.
	 * @return void
	 */
	public function user_signup( $admin_view = false ) {
		$post = wp_unslash( $_POST );
		if ( 0 === count( $post ) ) {
			$post = wp_unslash( $_GET );
		}
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			if ( isset( $post['signup_home'] ) ) {
				$this->create_select_signup( $admin_view );
			} elseif ( isset( $post['continue_signup'] ) ) {
				$this->create_signup_form( $post['continue_signup'], $post['secret'] );
			} elseif ( isset( $post['email_admin'] ) ) {
				$this->create_email_form( $post );
			} elseif ( isset( $post['email_session'] ) ) {
				$this->create_email_form( $post, false );
			} elseif ( isset( $post['send_email'] ) ) {
				$this->send_email( $post );
			} elseif ( isset( $post['rolling_days_new'] ) ) {
				$this->create_rolling_session( $post['add_attendee_session'], null, false, $post['rolling_days_new'] );
			} elseif ( isset( $post['add_attendee_session'] ) ) {
				if ( isset( $post['attendee_identifier'] ) && wp_verify_nonce( $post['attendee_identifier'], 'signups_attendee' ) ) { 
					$this->add_attendee_rolling( $post );
				} else {
					$sgm          = new SendGridMail();
					$ip_address   = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'No Ip Address';
					$body1        = '<br><br> Calling IP Address: ' . $ip_address . '<br>';
					$body1       .= 'Host Root : ' . get_site_url() . '<br>';
					$body1       .= '<pre>' . htmlspecialchars( wp_json_encode( $post, JSON_PRETTY_PRINT ), ENT_QUOTES, 'UTF-8' ) . '</pre>';
					$sgm->send_mail( 'ecsproull765@gmail.com', 'Attn HACKER: Woodshop Signup POST FAILED', $body1 );
				}
			} elseif ( isset( $post['move_me'] ) ) {
				$this->move_attendee_class( $post );
			} elseif ( isset( $post['add_attendee_class'] ) ) {
				$this->add_attendee_class( $post );
			} elseif ( isset( $post['home'] ) ) {
				$this->create_select_signup();
			} elseif ( isset( $post['signup_id'] ) ) {
				if ( '-1' === $post['signup_id'] ) {
					$this->create_select_signup();
				} else {
					$this->create_description_form( $post['signup_id'] );
				}
			}
		} elseif ( get_query_var( 'signup_id' ) ) {
			if ( get_query_var( 'secret' ) ) {
				$this->create_description_form( get_query_var( 'signup_id' ), get_query_var( 'secret' ) );
			} else {
				$this->create_description_form( get_query_var( 'signup_id' ) );
			}
		} elseif ( get_query_var( 'unsubscribe' ) ) {
			$key        = get_query_var( 'unsubscribe' );
			$badge      = get_query_var( 'badge' );
			$mail_group = get_query_var( 'mail_group' );
			$this->unsubscribe_nag_mailer( $key, $badge, $mail_group );
		} else {
			$this->create_select_signup( $admin_view );
		}
	}

	/**
	 * The information to be sent in an email is collected on a Form. That Form
	 * is created in the create_email_form function. Once the users fills out the Form
	 * and clicks the Send Email button, this function is called to send the email.
	 *
	 * @see ShortCodes::create_email_form()
	 *
	 * @param mixed $post Data to use to send the mail.
	 * @return void
	 */
	private function send_email( $post ) {
		$sgm         = new SendGridMail();
		$class_email = false;
		if ( isset( $post['class_email'] ) && $post['class_email'] ) {
			$class_email = true;
		}

		$email_status = $sgm->send_mail( $post['contact_email'], $post['subject'], 'Reply To: ' . $post['email'] . '<br><br>' . $post['body'], $class_email, $post['email'] );
		?>
		<form class="email_form" method="POST">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			<div class="text-center"><h2>Email Sent</h2</div>
			<div class="text-center submit-block mt-4">
				<div class="text-right">
					<button class="btn btn-primary" type="submit" name="continue_signup" 
						value="<?php echo esc_html( $post['signup_id'] ); ?>">Continue Signup</button>
				</div>
				<div>
				<button class="btn btn-danger" type="submit" name="signup_home" value="-1">Cancel</button>
				</div>
			</div>
		</form>
		<?php
	}


	/**
	 * Creates a section of HTML for a new user to identify themselves.
	 * A new user, typically a SCW resident signing up to be a member,
	 * would utilize this form to input their information.
	 *
	 * @return void
	 */
	protected function create_new_user_table() {
		?>
		<table id="new-member" class="mb-100px table table-bordered mr-auto ml-auto">
			<tr>
				<td class="text-right font-weight-bold">SCW Rec Number:</td>
				<td class="text-left"><input type="number" name="reccard" placeholder="123456" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">First Name:</td>
				<td class="text-left"><input type="text" name="firstname" placeholder="First Name" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Last Name:</td>
				<td class="text-left"><input type="text" name="lastname" placeholder="Last Name" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Phone Number:</td>
				<td class="text-left"><input  type="text" name="phone"
					placeholder="888-888-8888" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Email:</td>
				<td class="text-left"><input type="email" name="email" placeholder="Your email address" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Street Address 1:</td>
				<td class="text-left"><input type="text" name="address1"
					placeholder="Sun City West Street Address" required></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Street Address 2:</td>
				<td class="text-left"><input type="text" name="address2" placeholder="Unit 1234"></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">City:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="city" value="Sun City West" required readonly></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">State:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="state"	value="AZ"  required readonly></td>
			</tr>
			<tr>
				<td class="text-right font-weight-bold">Zip Code:</td>
				<td class="text-left"><input class=" bg-secondary text-white" type="text" name="zip"	value="85375"  required readonly></td>
			</tr>
		</table>
		<input id="user_groups" type="hidden" name="user_groups" value="none">
		<?php
	}

	/**
	 * Retrieves the the data required for the create_select_signup_form function to populate
	 * the SignUps landing page.
	 *
	 * @see ShortCodes::create_select_signup_form()
	 *
	 * @param boolean $admin_view Set to true when this  is displayed from the administrator view.
	 *
	 * @return void
	 */
	private function create_select_signup( $admin_view = false ) {
		global $wpdb;
		$admin_view = current_user_can( 'administrator' );
		$signups    = null;
		if ( $admin_view ) {
			$signups = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					'SELECT signup_id,
					signup_name,
					signup_category
					FROM %1s
					ORDER BY signup_order',
					self::SIGNUPS_TABLE
				),
				OBJECT
			);
		} else {
			$signups = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT signup_id,
					signup_name,
					signup_category
					FROM %1s
					WHERE signup_admin_approved = 1
					ORDER BY signup_order',
					self::SIGNUPS_TABLE
				),
				OBJECT
			);
		}

		$categories = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::SIGNUP_CATEGORY_TABLE
			),
			OBJECT
		);

		$this->create_select_signup_form( $signups, $categories );
	}

	/**
	 * Creates the signup form for an individual event or class.
	 * This function aggregates all of the data needed to display the signup form.
	 * The create_session_select_form function actually displays the form to sign up.
	 *
	 * @see ShortCodes::create_session_select_form()
	 *
	 * @param string $signup_id The id of the signup to create a form for.
	 * @param string $secret A members secret key used to identify the member.
	 * @return void
	 */
	private function create_signup_form( $signup_id, $secret = null ) {
		global $wpdb;
		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling             = $signups[0]->signup_rolling_template > 0;
		$signup_name         = $signups[0]->signup_name;
		$signup_email        = $signups[0]->signup_contact_email;
		$signup_contact_name = $signups[0]->signup_default_contact_name;

		if ( $rolling ) {
			$this->create_rolling_session( $signup_id, $secret );
		} else {
			$bad_debt = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT attendee_id,
					attendee_session_id, 
					attendee_payment_start,
					attendee_badge,
					attendee_payment_id,
					attendee_checkout_id,
					attendee_new_member_id
					FROM %1s
					WHERE 0 < attendee_balance_owed',
					self::ATTENDEES_TABLE
				),
				OBJECT
			);

			$dt_now       = new DateTime( 'now', $this->date_time_zone );
			$five_minutes = new DateInterval( 'PT5M' );
			$sgm          = new SendGridMail();
			$email_title  = 'Incomplete Class Payment';
			foreach ( $bad_debt as $bd ) {
				$dt_start = new DateTime( $bd->attendee_payment_start, $this->date_time_zone );
				$dt_start->add( $five_minutes );
				if ( $dt_start->format( 'U' ) < $dt_now->format( 'U' ) ) {
					$payments = new StripePayments();
					$payment_status = $payments->check_payment_intent( $bd->attendee_payment_id );

					if ( $payment_status ) {
						$email_title = 'Class payment succeeded with missed events';
						$data        = array( 'attendee_balance_owed' => 0 );
						$where       = array( 'attendee_id' => $bd->attendee_id );
						$wpdb->update( self::ATTENDEES_TABLE, $data, $where );
					} else {
						$payments->expire_checkout_session( $bd->attendee_checkout_id );
						if ( $bd->attendee_new_member_id > 0 ) {
							$where = array( 'new_member_id' => $bd->attendee_new_member_id );
							$wpdb->delete( self::NEW_MEMBER_TABLE, $where );
							$email_title = 'Incomplete Orientation Payment';
						}

						$where = array( 'attendee_id' => $bd->attendee_id );
						$wpdb->delete( self::ATTENDEES_TABLE, $where );
					}

					$sgm->send_mail( 'ecsproull765@gmail.com', $email_title, 'Badge:' . $bd->attendee_badge . ' Session ID:' . $bd->attendee_session_id );
				}
			}

			$signup_cost             = $signups[0]->signup_cost;
			$sessions                = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT session_id,
					session_start_formatted,
					session_end_formatted,
					session_start_time,
					session_slots,
					session_price_id,
					session_contact_name,
					session_contact_email
					FROM %1s
					WHERE session_signup_id = %s
					ORDER BY session_start_time',
					self::SESSIONS_TABLE,
					$signup_id
				),
				OBJECT
			);

			foreach ( $sessions as $session ) {
				$attendees[ $session->session_id ] = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE attendee_session_id = %s
						AND attendee_email != ""',
						self::ATTENDEES_TABLE,
						$session->session_id
					),
					OBJECT
				);
			}

			$this->create_session_select_form(
				$signup_name,
				$sessions,
				$attendees,
				$signup_cost,
				$signup_id,
				$signups[0]->signup_group,
				$signup_email,
				$signup_contact_name
			);
		}
	}

	/**
	 * Move a paid attendee to another session for the class.
	 * Members can only move themselves with the sessions of a class.
	 * They cannot move themselves from one class to another class
	 * because the individual classes, generally, have different prices.
	 *
	 * @param  mixed $post Data from the form where the move is requested.
	 * @return void
	 */
	private function move_attendee_class( $post ) {
		global $wpdb;
		if ( count( $post['time_slots'] ) === 1 ) {
			$parts          = explode( ',', $post['time_slots'][0] );
			$new_session_id = $parts[3];

			$result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1s 
					WHERE attendee_session_id = %s && attendee_badge = %s',
					self::ATTENDEES_TABLE,
					$new_session_id,
					$post['badge_number']
				)
			);

			if ( $result ) {
				?>
					<h1 class=" mt-3">Failed moving session, You are already signed up for that session.</h1>
				<?php
				return;
			}

			$where  = array( 'attendee_id' => $post['move_me'][0] );
			$data   = array( 'attendee_session_id' => $new_session_id );
			$result = $wpdb->update( self::ATTENDEES_TABLE, $data, $where );

			?>
			<form method="POST">
				<div class="text-center">
					<?php
					if ( 1 === $result ) {
						?>
						<h1 class=" mt-3">Successfully moved to session starting at <?php echo esc_html( $parts[0] ); ?></h1>
						<?php
						$attendee = $wpdb->get_row(
							$wpdb->prepare(
								'SELECT attendee_email
								FROM %1s
								WHERE attendee_id = %s',
								self::ATTENDEES_TABLE,
								$post['move_me'][0]
							),
							OBJECT
						);

						$body         = '<p>Your session has been changed.</p>';
						$body        .= $this->get_session_email_body( $new_session_id );
						$sgm          = new SendGridMail();
						$email_status = $sgm->send_mail( $attendee->attendee_email, 'Your session change.', $body, true );
					} else {
						?>
						<h1 class=" mt-3">Failed moving session, did you pick another session to move to?</h1>
						<?php
					}
					?>
					<button class="mt-3 mr-3 btn btn-primary signup-submit" type="submit" name="signup_id" 
						value="<?php echo esc_html( $post['session_signup_id'] ); ?>" >Return to Class</button>
					<button class="mt-3 ml-3 btn btn-primary signup-submit" type="submit" name="signup_id" 
						value="-1" >Class List</button>
				</div>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
			<?php
		}
	}

	/**
	 * Add attendee to a session of a class in response to the member selecting the session to attend.
	 * This function takes care all the bookwork involved, including collecting the money.
	 *
	 * If the $post array contains a remove_me field, that is handled at the top of the
	 * function and then the function returns. This is the case where a member removes
	 * themselves from a signup. This will only happens when the signup does not involve money.
	 *
	 * @see ShortCodes::create_session_select_form()
	 * @see StripePayments::collect_money()
	 *
	 * @param  mixed $post Data from the form.
	 * @return void
	 */
	private function add_attendee_class( $post ) {
		global $wpdb;

		if ( isset( $post['remove_me'] ) ) {
			$member_removed = false;
			foreach ( $post['remove_me'] as $attendee_id ) {
				$data                = array();
				$data['attendee_id'] = $attendee_id;
				$member_removed = $wpdb->delete( self::ATTENDEES_TABLE, $data );
			}

			if ( $member_removed ) {
				?>
				<h2>Member removed</h2>
				<?php
				return;
			}
		}

		$insert_id = 0;
		if ( isset( $post['new_member_rec_card'] ) ) {
			$new_member                        = array();
			$new_member['new_member_rec_card'] = $post['new_member_rec_card'];
			$new_member['new_member_first']    = $post['firstname'];
			$new_member['new_member_last']     = $post['lastname'];
			$new_member['new_member_phone']    = $post['phone'];
			$new_member['new_member_email']    = $post['email'];
			$new_member['new_member_street']   = $post['new_member_street'];

			$result = $wpdb->insert( self::NEW_MEMBER_TABLE, $new_member );
			if ( $result && $wpdb->insert_id ) {
				$insert_id            = $wpdb->insert_id;
				$post['badge_number'] = $insert_id;
			} else {
				$sgm  = new SendGridMail();
				$body = '';
				foreach ( $new_member as $key => $value ) {
					$body .= "Key: $key, Value: $value\n";
				}

				$email_status = $sgm->send_mail( 'ecsproull765@gmail.com', 'Failed New Member', 'Data: ' . $body );
			}
		}

		$qty                                    = 1;
		$now                                    = new DateTime( 'now', $this->date_time_zone );
		$slot_parts                             = explode( ',', $post['time_slots'][0] );
		$slot_start                             = new DateTime( $slot_parts[0], $this->date_time_zone );
		$slot_end                               = new DateTime( $slot_parts[1], $this->date_time_zone );
		$signup_name                            = $post['signup_name'];
		$cost                                   = $slot_parts[4];
		$new_attendee                           = array();
		$new_attendee['attendee_session_id']    = $slot_parts[3];
		$new_attendee['attendee_email']         = $post['email'];
		$new_attendee['attendee_phone']         = $post['phone'];
		$new_attendee['attendee_balance_owed']  = $cost;
		$new_attendee['attendee_lastname']      = $post['lastname'];
		$new_attendee['attendee_firstname']     = $post['firstname'];
		$new_attendee['attendee_item']          = $post['signup_name'];
		$new_attendee['attendee_badge']         = $post['badge_number'];
		$new_attendee['attendee_payment_start'] = $now->format( self::DATETIME_FORMAT );
		$new_attendee['attendee_plus_guest']    = isset( $post['attendee_plus_guest'] );
		$new_attendee['attendee_checkout_id']   = null;
		$new_attendee['attendee_payment_id']    = null;
		$new_attendee['attendee_new_member_id'] = $insert_id;

		if ( $new_attendee['attendee_plus_guest'] ) {
			$qty++;
		}
		?>
		<form method="POST">
			<table class="mb-100px mr-auto ml-auto">
				<tr class="attendee-row">
					<th>Date</th>
					<th>Time</th>
					<th>Name</th>
					<th>Item</th>
					<th>Status</th>
				</tr>
				<?php
				$current_session_attendees = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s WHERE attendee_session_id = %d AND attendee_item != "INSTRUCTOR"',
						self::ATTENDEES_TABLE,
						$new_attendee['attendee_session_id'],
					)
				);

				$available_slots = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT session_slots FROM %1s WHERE session_id = %d',
						self::SESSIONS_TABLE,
						$new_attendee['attendee_session_id']
					)
				);

				$signed_up_already   = false;
				$insert_return_value = false;
				$last_id             = 0;
				$wp_last_error       = '';
				if ( count( $current_session_attendees ) < $available_slots[0]->session_slots ) {
					if ( $new_attendee['attendee_badge'] ) {
						$signed_up_already = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT attendee_badge FROM %1s WHERE attendee_session_id = %d AND attendee_badge = %d',
								self::ATTENDEES_TABLE,
								$new_attendee['attendee_session_id'],
								$new_attendee['attendee_badge']
							)
						);
					} else {
						$signed_up_already = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT * FROM %1s WHERE attendee_session_id = %d AND 
									attendee_firstname = %s AND
									attendee_lastname  = %s AND
									attendee_phone     = %s',
								self::ATTENDEES_TABLE,
								$new_attendee['attendee_session_id'],
								$new_attendee['attendee_firstname'],
								$new_attendee['attendee_lastname'],
								$new_attendee['attendee_phone']
							)
						);
					}

					if ( ! $signed_up_already ) {
						$insert_return_value = $wpdb->insert( self::ATTENDEES_TABLE, $new_attendee );
						if ( ! $insert_return_value ) {
							$wp_last_error = $wpdb->last_error;
						}
						$last_id = $wpdb->insert_id;
					}
				}

				/**
				 * Four checks before we collect money.
				 * 1.) There is a balance owed which will be the full amount.
				 * 2.) The last inserted ID is valid.
				 * 3.) The insert didn't fail.
				 * 4.) Exactly one row is inserted. It can be 0 but never more than 1.
				 */
				if (
					0 !== (int) $new_attendee['attendee_balance_owed'] &&
					0 !== $last_id &&
					$insert_return_value
				) {
					$description = $signup_name . ' - ' . $slot_start->format( self::DATETIME_FORMAT );
					$payments    = new StripePayments();
					if ( ! $post['session_price_id'] ) {
						$signups = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT signup_product_id
								FROM %1s
								WHERE signup_id = %d',
								self::SIGNUPS_TABLE,
								$post['session_signup_id']
							),
							OBJECT
						);

						if ( ! $signups[0]->signup_product_id ) {
							$ret = $payments->create_product( $post['signup_name'], $cost );
							if ( $ret ) {
								$data                            = array();
								$data['signup_product_id']       = $ret['product_id'];
								$data['signup_default_price_id'] = $ret['price_id'];

								$where              = array();
								$where['signup_id'] = $post['session_signup_id'];
								$affected_row_count = $wpdb->update(
									'wp_scw_signups',
									$data,
									$where
								);

								if ( ! $affected_row_count ) {
									echo 'Failed to update signup with pricing and product info.';
									return;
								}

								$post['session_price_id'] = $ret['price_id'];

								$this->update_sessions_price_id( $where['signup_id'], $ret['price_id'] );
							} else {
								echo 'Failed to create stripe pricing and product info.';
								return;
							}
						}
					}

					$payments->collect_money( $description, $post['session_price_id'], $new_attendee['attendee_badge'], $last_id, $cost, $qty );
				}
				?>
				<tr class="attendee-row">
					<td><?php echo esc_html( $slot_start->format( self::DATE_FORMAT ) ); ?></td>
					<td><?php echo esc_html( $slot_start->format( self::TIME_FORMAT ) . ' - ' . $slot_end->format( self::TIME_FORMAT ) ); ?></td>
					<td><?php echo esc_html( $post['firstname'] . ' ' . $post['lastname'] ); ?></td>
					<td><?php echo esc_html( $slot_parts[2] ); ?></td>
					<?php
					if ( $signed_up_already ) {
						?>
						<td style="color:red"><b><i>Failed, Signed up alread</i></b></td>
						<?php
					} elseif ( ! $insert_return_value ) {
						?>
						<td style="color:red"><b><i>Failed DB Insert : <?php echo esc_html( $wp_last_error ); ?></i></b></td>
						<?php
					} else {
						?>
						<td>Success</td>
						<?php
					}
					?>
				</tr>
			</table>
		</form>
		<?php
	}

	/**
	 * Creates the form for selecting a signup. This is the landing page for members.
	 *
	 * @see ShortCodes::create_select_signup()
	 *
	 * @param  mixed $signups The results of a DB query for available classes.
	 * @param  mixed $categories the list of categories.
	 * @return void
	 */
	private function create_select_signup_form( $signups, $categories ) {
		?>
		<form method="GET">
			<div id="usercontent">
				<div id="signup-select" class="signup-category-list selection-font mb-100px mr-auto ml-auto mt-5">
					<?php
					$count = 0;
					foreach ( $categories as $category ) {
						?>
						<div class="text-center mb-4">
							<div class="border-top3 pt-2 bg-lightgray h-65px">
								<span class="category-text"><?php echo esc_html( $category->category_title ); ?></span>
							</div>
							<?php
							foreach ( $signups as $signup ) {
								if ( $signup->signup_category === $category->category_id ) {
									?>
									<button class="button-signup" type="submit" name="signup_id" value="<?php echo esc_html( $signup->signup_id ); ?>" >
										<i><u><?php echo esc_html( $signup->signup_name ); ?></u></i>
									</button>
									<?php
								}
							}
							?>
						</div>
						<?php
						$count++;
					}

					if ( $count % 4 > 0 ) {
						$remainder = 4 - ( $count % 4 );
						for ( $i = 0; $i < $remainder; $i++ ) {
							?>
							<div class="text-center mb-4">
								<div class="border-top3 pt-2 bg-lightgray h-65px">
								</div>
							</div>
							<?php
						}
					}
					?>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Creates a form that displays the sessions along with their attendees.
	 * This is the form where a member can select a session to attend.
	 * Once a session has been selected the information from this form
	 * will be passed to the add_attendee_class function. Although this form
	 * can be used to sign up for an event, it is usually used to signup for classes.
	 *
	 * @see ShortCodes::add_attendee_class()
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $cost The cost of the signup in dollars.
	 * @param  int    $signup_id The signup id.
	 * @param  string $user_group The group that defines who can signup.  CNC, Member...etc.
	 * @param  string $signup_email The email for the contact person for this signup.
	 * @param  string $signup_contact_name The name for the contact person for this signup.
	 * @return void
	 */
	private function create_session_select_form(
		$signup_name,
		$sessions,
		$attendees,
		$cost,
		$signup_id,
		$user_group,
		$signup_email,
		$signup_contact_name
	) {
		?>
			<div id="session_select" class="text-center mw-800px">
			<h1 class="mb-2"><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<form class="signup_form" method="GET">
					<div id="usercontent">
						<?php
						if ( 'none' === $user_group || 'residents' === $user_group ) {
							$user_badge = true;
							if ( 'residents' === $user_group ) {
								$this->create_new_member_form();
							} else {
								$this->create_new_user_table();
							}
						} else {
							$user_badge = $this->create_user_table( $user_group, $signup_id );
						}
						?>
						<table id="selection-table" class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-125px selection-font"
							<?php echo null === $user_badge ? 'hidden' : ''; ?> >
							<?php
							$sessions_displayed = 0;
							foreach ( $sessions as $session ) {
								$now = new DateTime( 'now', $this->date_time_zone );
								if ( $session->session_start_time < $now->format( 'U' ) ) {
									continue;
								}
								$sessions_displayed++;
								?>
								<tr class="submit-row">
									<td colspan='3'><button id=<?php echo esc_html( 'submit_' . $session->session_id ); ?>
												class="btn btn-md btn-primary mr-auto ml-auto mt-2 signup-submit"
												type="submit">Submit</button>
									</td>
								</tr>
								<?php
								$start_date = new DateTime( $session->session_start_formatted );
								$end_date   = new DateTime( $session->session_end_formatted );
								?>
								<tr id="submit-row" class="date-row">
									<td class="text-center" colspan="3"> 
										<?php
										echo esc_html(
											$start_date->format( self::DATE_FORMAT ) .
											' - ' . $start_date->format( self::TIME_FORMAT ) . ' - ' . $end_date->format( self::TIME_FORMAT )
										);
										?>
								</tr>
								<tr class="attendee-row bg-lg">
									<td>Session Contact</td>
									<td><?php $this->create_session_email_link( $signup_email, $signup_contact_name, $signup_name ); ?></td>
									<td>Select</td>
								</tr>
								<input type="hidden" name="add_attendee_class">
								<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
								<input type="hidden" name="session_price_id" value="<?php echo esc_html( $session->session_price_id ); ?>">
								<input type="hidden" name="session_signup_id" value="<?php echo esc_html( $signup_id ); ?>">
								<input type="hidden" name="paid" value=false>
								<?php
								wp_nonce_field( 'signups', 'mynonce' );

								$available_slots = $session->session_slots - count( $attendees[ $session->session_id ] );
								for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
									?>
									<tr class="attendee-row bg-lightgreen" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
										<td>Cost: $<?php echo esc_html( $cost ); ?></td>
										<td><?php echo esc_html( $signup_name ); ?></td>
										<td>
											<input class="ml-auto mr-auto addChk" type="radio" 
												name="time_slots[]" 
												value="<?php echo esc_html( $start_date->format( self::DATETIME_FORMAT ) . ',' . $end_date->format( self::DATETIME_FORMAT ) . ',' . $signup_name . ',' . $session->session_id . ',' . $cost ); ?>">
										</td>
									</tr>
									<?php
									break;
								}
								?>
								<tr class="attendee-row bg-lg">
									<td></td>
									<td><b><?php echo esc_html( $available_slots . ' slots open - ' . count( $attendees[ $session->session_id ] ) . ' filled' ); ?></b></td>
									<td></td>
								</tr>
								<?php
								$count = 0;
								if ( isset( $attendees[ $session->session_id ] ) ) {
									foreach ( $attendees[ $session->session_id ] as $attendee ) {
										++$count;
										?>
										<tr class="attendee-row <?php echo esc_html( $count > 3 ? $session->session_id : '' ); ?>" <?php echo $count > 3 ? 'hidden' : ''; ?> >
											<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
											<td><?php echo esc_html( $attendee->attendee_item ); ?></td>
											<?php
											if ( '0' === $attendee->attendee_balance_owed ) {
												$can_move = $attendee->attendee_badge === $user_badge;
												$paid     = '1' === $attendee->attendee_plus_guest ? 'Paid + Guest' : 'Paid';
												$action   = '0' === $cost ? 'Remove' : 'Move';
												?>
												<td class="move <?php echo esc_html( $attendee->attendee_badge ); ?>" <?php echo $can_move ? '' : 'hidden'; ?> ><?php echo esc_html( $action ); ?>
													<?php
													if ( '0' === $cost ) {
														?>
														<input class="remove-chk position-relative ml-1" 
															type="checkbox" name="remove_me[]" value='<?php echo esc_html( $attendee->attendee_id ); ?>' >
														<?php
													} else {
														?>
															<input class="move_me add-chk position-relative ml-1" 
																type="checkbox" name="move_me[]" value='<?php echo esc_html( $attendee->attendee_id ); ?>' >
														<?php
													}
													?>
												</td>

												<td class="paid <?php echo esc_html( $attendee->attendee_badge ); ?>" <?php echo $can_move ? 'hidden' : ''; ?> ><?php echo esc_html( $paid ); ?></td>
												<?php
											} else {
												?>
												<td><?php echo esc_html( 'Payment Pending' ); ?></td>
												<?php
											}
											?>
										</tr>
										<?php
									}
								}
								?>
								<tr class="attendee-row bg-dark">
									<td></td>
									<td></td>
									<td>
									<?php
									if ( $count > 3 ) {
										?>
										<button class="btn btn-sm bg-primary mr-auto ml-auto <?php echo esc_html( $session->session_id . '-expand-button' ); ?> expand-button" type='button' 
											data-button="<?php echo esc_html( $session->session_id ); ?>" >Show All</button>
										<?php
									}
									?>
									</td>
								</tr>
								<?php
							}

							if ( 0 === $sessions_displayed ) {
								?>
								<h2>There are currently no future sessions scheduled for this class.</h2>
								<h3>To suggest or request a session time contact <?php $this->create_session_email_link( $signup_email, $signup_contact_name, $signup_name ); ?></h3>
								<button type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="-1" name="signup_home" formnovalidate>Cancel</button>
								<?php
							} else {
								?>
								<tr class="footer-row">
									<td><button type="submit" class="btn bth-md mr-auto ml-auto mt-2 bg-primary" value="-1" name="signup_home" formnovalidate>Cancel</button></td>
									<?php
									for ( $i = 1; $i < 3; $i++ ) {
										?>
										<td></td>
										<?php
									}
									?>
								</tr>
								<?php
							}
							?>
							<div id="cancel"></div>
						</table>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Creates a signup description block.
	 * Once a user selected a class from the landing page they progress to this
	 * page where a detailed description is displayed. It is here that the have the option
	 * continue or cancel the signup process.
	 *
	 * It should be noted that this page is made up of a aggregation of several bits of data.
	 * Cost, Schedule, Contact and any preclass instructions are all part of this page. The
	 * Contacts is a link that a user can use to ask questions about the class.
	 *
	 * Clicking Continue transfers the data to the create_signup_form function which
	 * gets the data together so the session selection page can be shown.
	 *
	 * @see ShortCodes::create_signup_form()
	 *
	 * @param  mixed  $signup_id Id of the signup.
	 * @param  string $secret Members secret used to identify the member.
	 * @return void
	 */
	private function create_description_form( $signup_id, $secret = null ) {
		global $wpdb;
		$body           = 'Signup id : ' . $signup_id . ' Secret : ' . $secret;
		$pattern_secret = '/^[0-9a-f]{32}$/ms';
		if ( ! ctype_digit( $signup_id ) || ( $secret && ! preg_match( $pattern_secret, $secret ) ) ) {
			?>
			<h1 class="mt-3">Unknown signup</h1>
			<h2><a href="mailto:ecsproull765@gmail.com?subject=Failed Signup Parameters&body=<?php echo esc_html( $body ); ?>">Email Administrator</a><h2>
			<?php
			return;
		}

		$signups = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_name,
				signup_contact_email,
				signup_default_contact_name,
				signup_default_duration,
				signup_default_days_between_sessions,
				signup_default_day_of_month,
				signup_cost,
				signup_default_slots,
				signup_default_minimum,
				signup_schedule_desc,
				signup_rolling_template,
				signup_location
				FROM %1s
				WHERE signup_id = %s && signup_admin_approved = 1',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		if ( ! $signups ) {
			?>
			<h1 class="mt-3">Signup Not Found</h1>
			<!-- h2><a href="mailto:ecsproull765@gmail.com?subject=Failed Load Signup&body=<?php echo esc_html( $body ); ?>">Email Administrator</a><h2 -->
			<h2><button id="email-admin" class="btn btn-primary rounded" type="submit" name="email_admin" value="1">Email Administrator</button></h2>
			<input type="hidden" name="contact_email" value="ecsproull765@gmail.com" >
			<input type="hidden" name="contact_name" value="Signup Admin" >
			<?php
			return;
		}

		$signup             = $signups[0];
		$description_object = $this->get_signup_html( $signup_id );
		$schedule           = 'Schedule for this class has not been set';
		if ( $signup->signup_schedule_desc ) {
			$schedule = $signup->signup_schedule_desc;
		} elseif ( $signup->signup_default_duration ) {
			$dt_parts = explode( ':', $signup->signup_default_duration );

			if ( 1 === (int) $dt_parts[0] ) {
				$schedule = (int) $dt_parts[0] . ' hour';
			} else {
				$schedule = (int) $dt_parts[0] . ' hours';
			}

			if ( '00' !== $dt_parts[1] ) {
				$schedule .= ' & ' . $dt_parts[1] . ' minutes';
			}

			if ( $signup->signup_default_day_of_month ) {
				$schedule .= ', The ' . $signup->signup_default_day_of_month . ' of the month';
			} elseif ( $signup->signup_default_days_between_sessions ) {
				if ( 0 === (int) $signup->signup_default_days_between_sessions % 7 ) {
					$weeks = (int) $signup->signup_default_days_between_sessions / 7;
					if ( 1 === $weeks ) {
						$schedule .= ', Every week';
					} else {
						$schedule .= ', Every ' . $weeks . ' weeks';
					}
				} else {
					$schedule .= ', Every ' . $signup->signup_default_days_between_sessions . ' days';
				}
			}

			if ( $signup->signup_default_slots ) {
				$schedule .= '. Max ' . $signup->signup_default_slots . ' students';
			} else {
				$schedule .= '.';
			}

			if ( $signup->signup_default_minimum ) {
				$schedule .= '. Min ' . $signup->signup_default_minimum . ' students.';
			} else {
				$schedule .= '.';
			}
		}

		if ( ! $description_object || $signup->signup_rolling_template ) {
			$this->create_signup_form( $signup_id, $secret );
		} else {
			?>
			<div class="text-center"><h1 ><?php echo esc_html( $signup->signup_name ); ?></h1></div>
			<div class="description-box description-block">
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Cost: </div>
				<div><?php echo '$' . esc_html( $signup->signup_cost ) . '.00'; ?></div>
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Contact:</div>
				<div>
					<form method="POST">
						<?php $this->create_session_email_link( $signup->signup_contact_email, $signup->signup_default_contact_name, $signup->signup_name ); ?>
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
					</form>
				</div>
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Location: </div>
				<div><?php echo esc_html( $signup->signup_location ); ?></div>
				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Schedule: </div>
				<div><?php echo esc_html( $schedule ); ?></div>

				<?php
				if ( $description_object->description_prerequisite ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Prerequisite: </div>
					<div><?php echo esc_html( $description_object->description_prerequisite ); ?></div>
					<?php
				}

				if ( $description_object->description_materials ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-2">Materials: </div>
					<div><?php echo esc_html( $description_object->description_materials ); ?></div>
					<?php
				}

				if ( $description_object->description_instructions ) {
					?>
					<div class="text-right pr-2 font-weight-bold text-dark mb-3">Instructions: </div>
					<div class="mb-3 instruct"><?php echo html_entity_decode( $description_object->description_instructions ); ?></div>
					<?php
				}
				?>

				<div class="text-right pr-2 font-weight-bold text-dark mb-2">Description: </div>
				<div class="instruct"><?php echo html_entity_decode( $description_object->description_html ); ?></div>
			</div>
			<form class="ml-auto mr-auto" method="GET">
				<div class="submit-row-grid mt-4">
					<div>
						<button type="submit" class="btn btn-md bg-primary mr-2" value="-1" name="signup_home">Cancel</button>
					</div>
					<div class="text-left">
						<button id='accept_conditions' class="btn btn-primary" type='submit' value="<?php echo esc_html( $signup_id ); ?>" name="continue_signup">Continue</button>
					</div>
				</div>
				<input type="hidden" name="secret" value="<?php echo esc_html( $secret ); ?>">
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
			<?php
		}
	}

	/**
	 * Creates an email link that opens the email form with the proper parameters.
	 *
	 * @param  mixed $contact_email The person being emailed.
	 * @param  mixed $contact_name The name of the person being emailed.
	 * @param  mixed $signup_name The signup name.
	 * @return void
	 */
	private function create_session_email_link( $contact_email, $contact_name, $signup_name ) {
		?>
		<button class="email-button" type="submit" name="email_session" value="1" formnovalidate><?php echo esc_html( $contact_name ); ?></button>
		<input type="hidden" name="session_email" value="<?php echo esc_html( $contact_email ); ?>" >
		<input type="hidden" name="session_name" value="<?php echo esc_html( $contact_name ); ?>" >
		<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>" >
		<?php
	}

	/**
	 * Creates the form for sending the an email.
	 *
	 * @param mixed   $post  Data from the calling form.
	 * @param boolean $admin Set to true if this is called from the administrator side.
	 * @return void
	 */
	private function create_email_form( $post, $admin = true ) {
		$contact_email = $post['contact_email'];
		$contact_name  = $post['contact_name'];
		$class_email   = false;
		if ( ! $admin ) {
			$contact_email = $post['session_email'];
			$contact_name  = $post['session_name'];
			$class_email   = true;
		}
		?>
		<form class="email_form" method="POST">
			<?php
			$user_badge = null;
			wp_nonce_field( 'signups', 'mynonce' );
			$email = '';
			if ( isset( $post['email'] ) ) {
				$email = $post['email'];
			}

			$subject = '';
			if ( isset( $post['signup_name'] ) ) {
				$subject = $post['signup_name'];
			}
			?>
			<div id="send-email">
				<div class="text-right mt-2">
					<label for="email-to" class="mr-2">To:</label>
				</div>
				<div class="mt-2">
					<input id="email-to" class="w-100pr" type="text" name="contact_name"
						value="<?php echo esc_html( $contact_name ); ?>" disabled>
				</div>
				<div class="text-right mt-2">
					<label for="email-from" class="mr-2">From:</label>
				</div>
				<div class="mt-2">
					<input id="email-from" class="w-100pr" type="email" name="email"
						value="<?php echo esc_html( $email ); ?>" placeholder="Your email address" required>
				</div>
				<div class="text-right mt-2">
					<label for="email-subject" class="mr-2">Subject:</label>
				</div>
				<div class="mt-2">
					<input id="email-subject" class="w-100pr" type="text" name="subject" value="<?php echo esc_html( $subject ); ?>" required>
				</div>
				<div class="text-right mt2">
					<label for="email-body" class="mr-2">Message:</label>
				</div>
				<div class="mt-2">
					<textarea id="email-body" class="w-100pr" name="body" style="height:200px"
						value=""></textarea>
				</div>
			</div>
			<div class="text-center submit-block mt-4">
				<div class="text-right">
					<button class="btn btn-primary" type="submit" name="send_email" value="1">Send Email</button>
				</div>
				<div>
				<button type="submit" class="btn btn-md bg-primary mr-2" value="-1" name="home" formnovalidate>Cancel</button>
				</div>
			</div>
			<input type="hidden" name="contact_email" value="<?php echo esc_html( $contact_email ); ?>" >
			<input type="hidden" name="class_email" value="<?php echo esc_html( $class_email ); ?>" >
			<?php
			if ( isset( $post['add_attendee_session'] ) ) {
				?>
				<input type="hidden" name="signup_id" value="<?php echo esc_html( $post['add_attendee_session'] ); ?>" >
				<?php
			} elseif ( isset( $post['attendee_session_id'] ) ) {
				?>
				<input type="hidden" name="signup_id" value="<?php echo esc_html( $post['attendee_session_id'] ); ?>" >
				<?php
			} elseif ( isset( $post['session_signup_id'] ) ) {
				?>
				<input type="hidden" name="signup_id" value="<?php echo esc_html( $post['session_signup_id'] ); ?>" >
				<?php
			}
			?>
		</form>
		<?php
	}

	/**
	 * Called in response to a click on the Unsubscribe link in a generated monitor or class email.
	 * The request is stored in the unsubscribe table where the server can retrieve it and
	 * perform the unsubscribe on the server.
	 *
	 * @param string $key The key that identifies the member.
	 * @param string $badge The member's badge number.
	 * @param string $mail_group The email group.
	 * @return void
	 */
	private function unsubscribe_nag_mailer( $key, $badge, $mail_group ) {
		global $wpdb;
		$ip_address    = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'No Ip Address';
		$pattern_key   = '/^[0-9a-f]{32}$|^[0-9a-f]{14}\.[0-9]{8}$/ms';
		$pattern_badge = '/^[0-9]{4}$/ms';
		if ( ! preg_match( $pattern_key, $key ) || ! preg_match( $pattern_badge, $badge ) ) {
			$sgm = new SendGridMail();
			$sgm->send_mail( 'ecsproull765@gmail.com', 'Failed Validation', $key . ' ' . $badge . ' ip : ' . $ip_address );
			return;
		}

		$data                           = array();
		$data['unsubscribe_key']        = $key;
		$data['unsubscribe_complete']   = false;
		$data['unsubscribe_badge']      = $badge;
		$data['unsubscribe_mail_group'] = $mail_group;
		$member                         = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				where member_email_secret = %s',
				self::MEMBERS_TABLE,
				$key
			),
			OBJECT
		);

		$sgm = new SendGridMail();
		if ( $member && $member[0]->member_badge === $badge ) {
			$result = $wpdb->insert( self::UNSUBSCRIBE_TABLE, $data );
			if ( 1 === $result ) {
				?>
				<h1 class='ml-auto mr-auto mt-5'>Request queued and should be complete within 8 hours.</h1>
				<?php
				$sgm->send_mail( 'ecsproull765@gmail.com', 'Unsubscribe', $key . ' ' . $badge . ' ip : ' . $ip_address . ' group: ' . $mail_group );
			} else {
				?>
				<div class='ml-auto mr-auto mt-5'>
					<h1>Opps something failed.</h1>
					<h2><a href="mailto:ecsproull765@gmail.com?subject=Failed Unsubscribe&body=<?php echo esc_html( $key ); ?>">Email Administrator</a><h2>
				<?php
				$sgm->send_mail( 'ecsproull765@gmail.com', 'Unsubscribe Failed', $key . ' ' . $badge . ' ip : ' . $ip_address );
			}
		} else {
			?>
			<div class='ml-auto mr-auto mt-5'>
				<h1>Sorry, couldn't locate member. Please email the admin to get removed.</h1>
				<h2><a href="mailto:ecsproull765@gmail.com?subject=Failed Member Not found.&body=<?php echo esc_html( $key ); ?>">Email Administrator</a><h2>
			<?php
			$sgm->send_mail( 'ecsproull765@gmail.com', 'Unsubscribe Failed to Locate Member', $key . ' ' . $badge . ' ip : ' . $ip_address );
		}
	}
}
