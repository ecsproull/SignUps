<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */


/**
 * Class for managing Stripe.com payments.
 * Used for creating new sessions to be added to the DB.
 */
class StripePayments extends SignUpsBase {

	/**
	 * Stripe API secret
	 *
	 *  @var string
	 */
	private $stripe_api_secret;

	/**
	 * Stripe API key.
	 *
	 *  @var string
	 */
	private $stripe_api_key;

	/**
	 * Stripe Endpoint Secret.
	 *
	 *  @var string
	 */
	private $stripe_endpoint_secret;

	/**
	 * Stripe root URL.
	 *
	 *  @var string
	 */
	private $stripe_root_url;


	/**
	 * Constructor
	 */
	public function __construct() {
		$options                      = get_option( 'signups_stripe' );
		$options                      = is_array( $options ) ? $options : array();
		$this->stripe_endpoint_secret = isset( $options['endpoint_secret'] ) ? $options['endpoint_secret'] : '';
		$this->stripe_api_key         = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$this->stripe_api_secret      = isset( $options['api_secret'] ) ? $options['api_secret'] : '';
		$this->stripe_root_url        = get_site_url();
	}

	/**
	 * Verification is done in the Payment Event handler.
	 */
	public function permissions_check() {
		return true;
	}

	/**
	 * Called by Stripe.com when a payment has been processed.
	 * There are several events but we only register for a few of them.
	 * Event registration is done on the Stripe.com web site when you
	 * set up the account.
	 *
	 * Events we handle
	 * checkout.session.completed
	 * checkout.session.expired
	 * charge.refunded
	 * payment_intent.canceled
	 * payment_intent.created
	 * payment_intent.payment_failed
	 * payment_intent.succeeded
	 */
	public function payment_event() {
		global $wpdb;
		\Stripe\Stripe::setApiKey( $this->stripe_api_key );

		$endpoint_secret = $this->stripe_endpoint_secret;
		$payload         = @file_get_contents( 'php://input' );
		$sig_header      = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
		$event           = null;

		if ( '' === $sig_header ) {
			$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Missing Stripe signature header.' );
			http_response_code( 400 );
			exit();
		}

		try {
			$event = \Stripe\Webhook::constructEvent(
				$payload,
				$sig_header,
				$endpoint_secret
			);
		} catch ( \UnexpectedValueException $e ) {
			$this->write_log( __FUNCTION__, basename( __FILE__ ), 'UnexpectedValueException Message: ' . $e->getMessage() );
			http_response_code( 400 );
			exit();
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			// $this->write_log( __FUNCTION__, basename( __FILE__ ), 'SignatureVerificationException Message: ' . $e->getMessage() );
			http_response_code( 400 );
			exit();
		}

		http_response_code( 200 );
		/**
		 * IMPORTANT: events have no order, NONE!!!!
		 * attendee may only be finalized once
		 * metadata is not used
		 * all Stripe events must be idempotent
		 * state must be accumulated until finalized
		 */
		switch ( $event->type ) {
			case 'payment_intent.succeeded':
				$this->payment_intent_succeeded( $event );
				break;
			case 'checkout.session.completed':
				$this->checkout_session_completed( $event );
				break;
			case 'charge.refunded':
				$this->charge_refunded( $event );
				break;
			case 'checkout.session.expired':
			case 'payment_intent.payment_failed':
			case 'payment_intent.canceled':
				$this->terminate_payment( $event );
				break;
			case 'payout.paid':
			case 'balance.available':
				break;
			default:
		}
	}

	/**
	 * Handle a refunded charge.
	 *
	 * Stripe sends `charge.refunded` when a charge is fully refunded.
	 * This handler is written to be idempotent: it sets the payment status to
	 * "refunded" and removes the attendee row if present.
	 *
	 * @param mixed $event The event contains the charge object.
	 * @return void
	 */
	private function charge_refunded( $event ) {
		global $wpdb;
		$charge = $event->data->object;

		$payment_intent_id = isset( $charge->payment_intent ) ? $charge->payment_intent : null;
		if ( ! $payment_intent_id ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Charge refunded but no payment_intent on charge id: ' . ( isset( $charge->id ) ? $charge->id : 'unknown' )
			);
			return;
		}

		$payment_row = $this->get_payment_row( null, $payment_intent_id );
		$this->write_event_log( $event, $payment_row, $charge );

		if ( ! $payment_row ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Charge refunded but no payment row found for intent id: ' . $payment_intent_id
			);
			return;
		}

		$wpdb->update(
			self::PAYMENTS_TABLE,
			array(
				'payments_status' => 'refunded',
			),
			array(
				'payments_intent_id' => $payment_intent_id,
			)
		);

		if ( isset( $payment_row->payments_attendee_id ) && $payment_row->payments_attendee_id ) {
			$wpdb->delete(
				self::ATTENDEES_TABLE,
				array(
					'attendee_id' => (int) $payment_row->payments_attendee_id,
				)
			);

			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Attendee was removed from ' . $payment_row->payments_signup_description . ', Payment ID: ' . $payment_row->payments_intent_id
			);
		}
	}

	/**
	 * Handle a successful payment intent.
	 *
	 * @param  mixed $event The event contains the payment intent object.
	 * @return void
	 */
	private function payment_intent_succeeded( $event ) {
		global $wpdb;
		$event_data = $event->data->object;

		$payment_row = $this->get_payment_row( null, $event_data->id );

		if ( $event_data->amount ) {
			$event_data->amount = $event_data->amount / 100;
		}

		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . self::PAYMENT_INTENTS_TABLE . '
				(intent_id, intent_status, intent_succeeded_at, intent_amount)
				VALUES (%s, %s, %s, %d)
				ON DUPLICATE KEY UPDATE intent_status = VALUES(intent_status), intent_succeeded_at = VALUES(intent_succeeded_at), intent_amount = VALUES(intent_amount)',
				$event_data->id,
				$event_data->status,
				current_time( 'mysql' ),
				$event_data->amount
			)
		);

		// If we already have a record of this intent then checkout has copleted.
		if ( $payment_row ) {
			$this->write_event_log( $event, $payment_row, $event_data );
			$this->finalize_success( $payment_row->payments_checkout_id );
		} else {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Payment Intent Succeeded: No payment row found for intent id: ' . $event_data->id
			);
		}
	}

	/**
	 * Handle a checkout session completed.
	 *
	 * @param  mixed $event The event contains the payment intent object.
	 * @return void
	 */
	private function checkout_session_completed( $event ) {
		global $wpdb;
		$event_data = $event->data->object;

		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::PAYMENTS_TABLE . ' AS p
                LEFT JOIN ' . self::ATTENDEES_TABLE . ' AS a
                ON a.attendee_checkout_id = p.payments_checkout_id
                SET p.payments_intent_id = %s,
                    a.attendee_payment_id = %s
                WHERE p.payments_checkout_id = %s
                    OR a.attendee_checkout_id = %s',
				$event_data->payment_intent,
				$event_data->payment_intent,
				$event_data->id,
				$event_data->id
			)
		);

		if ( false === $result ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Checkout session update failed for checkout id: ' . $event_data->id . ' Error: ' . $wpdb->last_error
			);
		} elseif ( 2 === $result ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Checkout session: Updated attendee and payments rows for checkout id: ' . $event_data->id
			);
		}

		$intent_row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT *
				FROM ' . self::PAYMENT_INTENTS_TABLE . '
				WHERE intent_id = %s',
				$event_data->payment_intent
			),
			OBJECT
		);

		if ( false === $intent_row && $wpdb->last_error ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Failed to load payment intent row for intent id: ' . $event_data->payment_intent . ' Error: ' . $wpdb->last_error
			);
			$intent_row = null;
		}

		if ( $intent_row && 'succeeded' === $intent_row->intent_status ) {
			$this->finalize_success( $event_data->id );
		} else {
			$wpdb->insert(
				self::PAYMENT_INTENTS_TABLE,
				array(
					'intent_id'     => $event_data->payment_intent,
					'intent_status' => 'pending',
				)
			);

			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Checkout session completed but no payment row found for checkout id: ' . $event_data->id
			);
		}
	}

	/**
	 * Handle a terminated payment intent.
	 *
	 * For testing this user these test cards:
	 * Generic decline  4000 0000 0000 9995
	 * Insufficient funds   4000 0000 0000 9995
	 * 3DS authentication failure   4000 0027 6000 3184
	 * CVC failure  4000 0000 0000 0127
	 *
	 * @param  mixed $event The event contains the payment intent object.
	 * @return void
	 */
	private function terminate_payment( $event ) {
		global $wpdb;
		$pi_id       = null;
		$co_id       = null;
		$payment_row = null;
		if ( 'checkout.session.expired' === $event->type ) {
			$payment_row = $this->get_payment_row( $event->data->object->id, null );

		} else {
			// Nothing to do, record it and move on.
			$this->write_event_log( $event, $payment_row, $event->data->object );
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO ' . self::PAYMENT_INTENTS_TABLE . '
					(intent_id, intent_status)
					VALUES (%s, %s)
					ON DUPLICATE KEY UPDATE intent_status = VALUES(intent_status)',
					$event->data->id,
					$event->data->status
				)
			);
			return;
		}

		$bad_debt = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT	attendee_payment_id,
				attendee_new_member_id
				FROM ' . self::ATTENDEES_TABLE . '
				WHERE 0 < attendee_balance_owed AND attendee_id = %d',
				$payment_row->payments_attendee_id
			),
			OBJECT
		);

		if ( $bad_debt ) {
			$where = array( 'attendee_id' => $payment_row->payments_attendee_id );
			if ( $bad_debt->attendee_payment_id && $this->check_payment_intent( $bad_debt->attendee_payment_id ) ) {
				$data = array( 'attendee_balance_owed' => 0 );
				$wpdb->update( self::ATTENDEES_TABLE, $data, $where );
			} else {
				if ( $bad_debt->attendee_new_member_id > 0 ) {
					$where_new_member = array( 'new_member_id' => $bad_debt->attendee_new_member_id );
					$wpdb->delete( self::NEW_MEMBER_TABLE, $where_new_member );
				}
				$wpdb->delete( self::ATTENDEES_TABLE, $where );
			}
		}
	}

	/**
	 * Finalize a successful payment.
	 *
	 * @param  mixed $checkout_id The checkout session id.
	 * @return void
	 */
	private function finalize_success( $checkout_id ) {
		global $wpdb;
		$attendee = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT	*
				FROM ' . self::ATTENDEES_TABLE . '
				WHERE attendee_checkout_id = %s',
				$checkout_id
			),
			OBJECT
		);

		if ( null === $checkout_id || null === $attendee ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'No attendee found for checkout id: ' . $checkout_id
			);
			return;
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::ATTENDEES_TABLE . ' AS a
				JOIN ' . self::PAYMENTS_TABLE . '  AS p
				ON p.payments_checkout_id = a.attendee_checkout_id
				SET a.attendee_balance_owed = 0,
					p.payments_status = %s
				WHERE a.attendee_checkout_id = %s && a.attendee_balance_owed > 0',
				'succeeded',
				$checkout_id
			)
		);

		if ( false === $updated ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Failed to finalize payment for checkout id: ' . $checkout_id . ' attendee id: ' . $attendee->attendee_id
			);
		} elseif ( $updated > 0 ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Finalized payment for checkout id: ' . $checkout_id . ' attendee id: ' . $attendee->attendee_id
			);
		}
	}


	// Helper functions /////.
	/**
	 * Retrieve a payment row by its id.
	 *
	 * @param  mixed $checkout_id The checkout session id.
	 * @param  mixed $payment_intent_id The payment intent id.
	 * @return object The payment row.
	 */
	private function get_payment_row( $checkout_id, $payment_intent_id ) {
		global $wpdb;

		$payment_row = null;
		if ( $checkout_id ) {
			$payment_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT *
					FROM ' . self::PAYMENTS_TABLE . '
					WHERE payments_checkout_id = %s',
					$checkout_id,
				),
				OBJECT
			);
		} elseif ( $payment_intent_id ) {
			$payment_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT *
					FROM ' . self::PAYMENTS_TABLE . '
					WHERE payments_intent_id = %s',
					$payment_intent_id,
				),
				OBJECT
			);
		}
		return $payment_row;
	}

	/**
	 * To be used before deleting a pending payment.
	 *
	 * @param  mixed $payment_intent_id The Payment ID.
	 * @return boolean Succeeded or failed.
	 */
	public function check_payment_intent( $payment_intent_id ) {
		if ( ! $payment_intent_id ) {
			return false;
		}

		$stripe         = new \Stripe\StripeClient( $this->stripe_api_secret );
		$payment_intent = $stripe->paymentIntents->retrieve( $payment_intent_id, array() );
		return 'succeeded' === $payment_intent->status;
	}

	/**
	 * Expires a checkout session. After this no payment can be processed.
	 *
	 * @param  mixed $checkout_session_id The id of the session to expire.
	 * @return void
	 */
	public function expire_checkout_session( $checkout_session_id ) {
		if ( ! $checkout_session_id ) {
			return;
		}

		$stripe           = new \Stripe\StripeClient( $this->stripe_api_secret );
		$checkout_session = $stripe->checkout->sessions->retrieve( $checkout_session_id, array() );
		if ( 'open' === $checkout_session->status ) {
			$stripe->checkout->sessions->expire( $checkout_session_id, array() );
		}
	}

	/**
	 * Adds the initial row to the payments table for a particular payment.
	 *
	 * @param  mixed $attendee_id The id of the entry in the attendees table.
	 * @param  mixed $amount The amount to be charged.
	 * @param  mixed $description Description of what is being paid for.
	 * @param  mixed $badge Attendee's badge number.
	 * @param  mixed $price_id The price ID to be charged to.
	 * @param  mixed $checkout_session_id The checkout session id.
	 * @return int|null The new payment id or null on failure.
	 */
	private function add_payment_data( $attendee_id, $amount, $description, $badge, $price_id, $checkout_session_id ) {
		global $wpdb;
		$dt_now      = new DateTime( 'now', $this->date_time_zone );
		$new_payment = array(
			'payments_attendee_id'        => $attendee_id,
			'payments_amount_charged'     => $amount,
			'payments_signup_description' => $description,
			'payments_last_access_time'   => $dt_now->format( 'Y-m-d H:i:s.u' ),
			'payments_attendee_badge'     => $badge,
			'payments_price_id'           => $price_id,
			'payments_status'             => 'started',
			'payments_intent_status_time' => $dt_now->format( 'Y-m-d H:i:s.u' ),
			'payments_start_time'         => $dt_now->format( 'Y-m-d H:i:s.u' ),
			'payments_checkout_id'        => $checkout_session_id,
		);

		if ( $wpdb->insert( self::PAYMENTS_TABLE, $new_payment ) ) {
			return $wpdb->insert_id;
		} else {
			return null;
		}
	}

	/**
	 * When money is to be collected we transfer control back to Stripe.com and they do the credit
	 * card processing. As that process proceeds, Stripe sends events back to us to let us know
	 * about the progress.
	 *
	 * @param string $description Description of what is bing paid for..
	 * @param int    $price_id    The price ID to be charged to.
	 * @param int    $badge       Attendee's badge number.
	 * @param int    $attendee_id The id fo the entry in the attendees table.
	 * @param int    $cost        The dollar cost of the signup.
	 * @param int    $qty         The quantity being purchased.
	 * @param int    $signup_id   The signup id associated with the payment.
	 * @return void
	 */
	public function collect_money( $description, $price_id, $badge, $attendee_id, $cost, $qty, $signup_id ) {
		global $wpdb;
		\Stripe\Stripe::setApiKey( $this->stripe_api_secret );
		header( 'Content-Type: application/json' );
		$signup_domain = $this->stripe_root_url;
		$current_time  = time();
		$expire_time   = $current_time + ( 31 * 60 );

		$insert_id = $this->add_payment_data( $attendee_id, (int) $cost * (int) $qty, $description, $badge, $price_id, null );
		if ( ! $insert_id ) {
			?>
			<h2>Unable to create payment record. Please contact support.</h2>
			<h3><a href="mailto:ecsproull765@gmail.com">Contact support</a></h3>
			<?php
			return;
		}

		$checkout_session = \Stripe\Checkout\Session::create(
			array(
				'line_items'  => array(
					array(
						'price'    => $price_id,
						'quantity' => $qty,
					),
				),
				'mode'        => 'payment',
				'expires_at'  => $expire_time,
				'success_url' => $signup_domain . '/signups?payment_success=1&attendee_id=' . $attendee_id . '&signup_id=' . $signup_id . '&badge=' . $badge . '&mynonce=' . wp_create_nonce( 'signups' ),
				'cancel_url'  => $signup_domain . '/signups?payment_canceled=1&attendee_id=' . $attendee_id . '&signup_id=' . $signup_id . '&mynonce=' . wp_create_nonce( 'signups' ),
			)
		);

		$where = array( 'payments_id' => $insert_id );
		$data  = array( 'payments_checkout_id' => $checkout_session->id );
		if ( ! $wpdb->update( self::PAYMENTS_TABLE, $data, $where ) ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Failed to update payments table with checkout session id for payments_id: ' . $insert_id
			);
			// TODO: Handle failed update. Possibly delete the payment record and terminate the checkout session.
		}

		$where = array( 'attendee_id' => $attendee_id );
		$data  = array( 'attendee_checkout_id' => $checkout_session->id );
		if ( ! $wpdb->update( self::ATTENDEES_TABLE, $data, $where ) ) {
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				'Failed to update attendees table with checkout session id for attendee_id: ' . $attendee_id
			);
			// TODO: Handle failed update. Possibly delete the payment record and terminate the checkout session.
		}

		header( 'HTTP/1.1 303 See Other' );
		header( 'Location: ' . $checkout_session->url );

		$log_text  = ' Attendee_id: ' . $wpdb->insert_id;
		$log_text .= ' Badge: ' . $badge;
		$log_text .= ' Desc: ' . $description;
		$this->write_log(
			__FUNCTION__,
			basename( __FILE__ ),
			$log_text
		);
	}

	/**
	 * Shortcode for the Payment Success page. This is what we show when a payment succeeds.
	 * WordPress pages have to added that implement this shortcode. A link to that page is
	 * passed in the collect_money function.
	 *
	 * @param mixed $post Url parameters.
	 * @return void
	 */
	public function payment_success( $post ) {
		global $wpdb;
		$payment_complete = false;

		if ( ! isset( $post['mynonce'] ) || ! wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			?>
			<h2>Security verification failed.</h2>
			<?php
			return;
		}

		$attendee_id   = sanitize_text_field( $post['attendee_id'] );
		$badge_number  = sanitize_text_field( $post['badge'] );
		$signup_id     = sanitize_text_field( $post['signup_id'] );
		$refresh_count = isset( $post['refresh_count'] ) ? (int) $post['refresh_count'] : 0;

		$payment_row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT payments_id, payments_intent_id, payments_signup_description, payments_status, payments_attendee_id, payments_email_sent
				FROM ' . self::PAYMENTS_TABLE . '
				WHERE payments_attendee_id = %s',
				$attendee_id
			),
			OBJECT
		)
		?>
		<h2>Payment for badge: <?php echo esc_html( $badge_number ); ?></h2>
		<?php
		if ( $payment_row ) {
			if ( 'succeeded' !== $payment_row->payments_status && 'complete' !== $payment_row->payments_status ) {
				if ( $refresh_count < 5 ) {
					$next_count  = $refresh_count + 1;
					$refresh_url = add_query_arg(
						array(
							'payment_success' => '1',
							'attendee_id'     => $attendee_id,
							'signup_id'       => $signup_id,
							'badge'           => $badge_number,
							'refresh_count'   => $next_count,
							'mynonce'         => wp_create_nonce( 'signups' ),
						),
						home_url( '/signups' )
					);
					?>
					<meta http-equiv="Refresh" content="10;url=<?php echo esc_url( $refresh_url ); ?>">
					<p>Checking payment status... (Attempt <?php echo esc_html( $next_count ); ?> of 5)</p>
					<?php
				}
			} else {
				$payment_complete = true;
			}
			?>
			<h2>Status: <?php echo esc_html( $payment_row->payments_status ); ?></h2>
			<?php
		} else {
			?>
			<h2>Status: <?php echo esc_html( ' Unknown' ); ?></h2>
			<?php
		}

		?>
		<?php
		$admin_subject    = rawurlencode( 'Payment Question' );
		$admin_body_lines = array(
			'Badge: ' . $badge_number,
		);

		if ( $payment_row ) {
			$payment_row_data = get_object_vars( $payment_row );
			foreach ( $payment_row_data as $key => $value ) {
				if ( is_scalar( $value ) || null === $value ) {
					$admin_body_lines[] = $key . ': ' . ( null === $value ? 'null' : (string) $value );
				} else {
					$admin_body_lines[] = $key . ': ' . wp_json_encode( $value );
				}
			}
		} else {
			$admin_body_lines[] = 'No payment record found.';
		}

		$admin_body   = rawurlencode( implode( "\n", $admin_body_lines ) );
		$admin_mailto = 'mailto:ecsproull765@gmail.com?subject=' . $admin_subject . '&body=' . $admin_body;
		?>
		<h3><a class="email-button" href="<?php echo esc_url( $admin_mailto ); ?>">Email Administrator</a></h3>
		<?php

		if ( $payment_complete && ! $payment_row->payments_email_sent ) {
			$email = null;
			if ( (int) $badge_number > 1000 ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM ' . self::MEMBERS_TABLE . '
						WHERE member_badge = %s',
						$badge_number
					),
					OBJECT
				);

				$email = $results[0]->member_email;
			} else {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM ' . self::NEW_MEMBER_TABLE . '
						WHERE new_member_id = %s',
						$badge_number
					),
					OBJECT
				);

				$email = $results[0]->new_member_email;
			}

			$attendee = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT attendee_session_id FROM ' . self::ATTENDEES_TABLE . '
					WHERE attendee_id = %s',
					$payment_row->payments_attendee_id
				),
				OBJECT
			);

			if ( $email ) {
				$signup_parts = explode( '-', $payment_row->payments_signup_description );
				$signup_name  = $signup_parts[0];
				$body         = '<p>Your payment id is : ' . $payment_row->payments_intent_id . '</p>';
				$body        .= $this->get_session_email_body( $attendee->attendee_session_id );
				$sgm          = new SendGridMail();
				$email_status = $sgm->send_mail( $email, 'You are signed up for ' . $signup_name, $body, true );

				$instructors_email = $this->get_session_instructors( $attendee->attendee_session_id );
				$instructor_body   = $this->get_session_instructors_email_body( $attendee->attendee_session_id );
				foreach ( $instructors_email as $instructor ) {
					$sgm->send_mail( $instructor->instructors_email, 'New Signup for your class: ' . $signup_name, $instructor_body, true );
				}

				if ( $email_status ) {
					$data  = array( 'payments_email_sent' => 1 );
					$where = array( 'payments_id' => $payment_row->payments_id );
					$wpdb->update( self::PAYMENTS_TABLE, $data, $where );
					?>
					<h2>A confirmation email was set to <?php echo esc_html( $email ); ?></h2>
					<h2>Your payment id is : <?php echo esc_html( $payment_row->payments_intent_id ); ?></h2>
					<?php
					$this->create_done_or_logout( $signup_id );
				}
			}
		}
	}

	/**
	 * Payment canceled message.
	 *
	 * @param  mixed $post Url parameters.
	 * @return void
	 */
	public function payment_canceled( $post ) {
		global $wpdb;

		// Verify nonce for security.
		if ( ! isset( $post['mynonce'] ) || ! wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			?>
			<h2>Security verification failed.</h2>
			<?php
			return;
		}

		$payment_id = sanitize_text_field( $post['attendee_id'] );
		$signup_id  = sanitize_text_field( $post['signup_id'] );
		if ( $payment_id ) {
			$bad_debt = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT	attendee_checkout_id
					FROM ' . self::ATTENDEES_TABLE . '
					WHERE 0 < attendee_balance_owed && attendee_id = %s',
					$payment_id
				),
				OBJECT
			);

			if ( $bad_debt ) {
				$stripe = new \Stripe\StripeClient( $this->stripe_api_secret );
				$stripe->checkout->sessions->expire( $bad_debt->attendee_checkout_id, array() );
			}

			?>
			<h2>Payment Cancelation Processed</h2>
			<?php
		} else {
			?>
			<h2>Invalid Attempt</h2>
			<?php
			$this->create_done_or_logout( $signup_id );
		}
	}

	/**
	 * Write an event log entry.
	 *
	 * @param  mixed $event The event from Stripe.
	 * @param  mixed $payment_row The payment row from the payments table.
	 * @param  mixed $event_data The event data object.
	 * @return void
	 */
	private function write_event_log( $event, $payment_row, $event_data ) {
		if ( ! $payment_row ) {
			$log_text = 'Payment Event: ' . $event->type . '; Payment row was null; ';
			$this->write_log(
				__FUNCTION__,
				basename( __FILE__ ),
				$log_text
			);
			return;
		}

		$log_text  = 'Payment Event: ' . $event->type . '; Attendee_id: ' . $payment_row->payments_attendee_id . '; ';
		$log_text .= ' Badge: ' . $payment_row->payments_attendee_badge . '; Status: ' . $event_data->status . '; ';
		$log_text .= ' Desc: ' . $payment_row->payments_signup_description;
		$this->write_log(
			__FUNCTION__,
			basename( __FILE__ ),
			$log_text
		);
	}

	// Stripe.com product and price management functions /////.
	/**
	 * Update the price for a signup on Stripe.com.
	 * The price is registered with Stipe.Com and this is a
	 * helper function to update the price of an existing item.
	 *
	 * @param  mixed $product_id Product Id.
	 * @param  mixed $new_cost New cost.
	 * @return The new price id.
	 */
	public function update_price( $product_id, $new_cost ) {
		$result = null;
		try {
			$stripe = new \Stripe\StripeClient( $this->stripe_api_secret );
			$result = $stripe->prices->create(
				array(
					'unit_amount' => (int) ( $new_cost . '00' ),
					'currency'    => 'usd',
					'product'     => $product_id,
				)
			);

			if ( 200 === (int) $result->code ) {
				return $result->id;
			}
		} catch ( Exception $e ) {
			?>
			<h2>Exception updating price id. <?php echo esc_html( $e->getMessage() ); ?></h2>
			<?php
		}

		return $result->id;
	}

	/**
	 * Create the product id and price id for a signup.
	 * These registered with Stipe.Com and updated via their API.
	 *
	 * @param string $name Name of the signup.
	 * @param int    $cost The cost of the signup.
	 * @return The new product and price id as an array.
	 */
	public function create_product( $name, $cost ) {

		$stripe = new \Stripe\StripeClient( $this->stripe_api_secret );
		$result = $stripe->products->create(
			array(
				'name'               => $name,
				'default_price_data' => array(
					'currency'    => 'usd',
					'unit_amount' => (int) $cost * 100,
				),
			)
		);

		if ( $result ) {
			$ret               = array();
			$ret['product_id'] = $result->id;
			$ret['price_id']   = $result->default_price;
			return $ret;
		} else {
			return null;
		}
	}
}
