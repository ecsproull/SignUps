<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

use Twilio\TwiML\Messaging\Message;

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
		global $wpdb;
		$stripe_row = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::STRIPE_TABLE,
			),
			OBJECT
		);

		$this->stripe_endpoint_secret = $stripe_row[0]->stripe_endpoint_secret;
		$this->stripe_api_key         = $stripe_row[0]->stripe_api_key;
		$this->stripe_api_secret      = $stripe_row[0]->stripe_api_secret;
		$this->stripe_root_url        = $stripe_row[0]->stripe_root_url;
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
		$sig_header      = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event           = null;

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
			$this->write_log( __FUNCTION__, basename( __FILE__ ), 'SignatureVerificationException Message: ' . $e->getMessage() );
			http_response_code( 400 );
			exit();
		}

		http_response_code( 200 );
		$payment_intent = $event->data->object;
		$log_text       = 'Payment Event: ' . $event->type . ' Attendee_id: ' . $payment_intent->metadata['attendee_id'];
		$log_text      .= ' Badge: ' . $payment_intent->metadata['badge'] . ' Status: ' . $payment_intent->status;
		$log_text      .= ' Desc: ' . $payment_intent->metadata['description'];
		$this->write_log(
			__FUNCTION__,
			basename( __FILE__ ),
			$log_text
		);

		switch ( $event->type ) {
			case 'payment_intent.created':
				$data  = array( 'attendee_payment_id' => $payment_intent->id );
				$where = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
				$wpdb->update( self::ATTENDEES_TABLE, $data, $where );
				break;
			case 'checkout.session.completed':
				$data  = array(
					'attendee_checkout_id' => $payment_intent->id,
					'attendee_payment_id' => $payment_intent->payment_intent,
				);
				$where = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
				$wpdb->update( self::ATTENDEES_TABLE, $data, $where );
				$this->update_database( $payment_intent->payment_intent, $payment_intent->metadata, $payment_intent->status );

				if ( 'complete' === $payment_intent->status ) {
					$update = array( 'attendee_balance_owed' => 0 );
					$where  = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
					$rows   = $wpdb->update( self::ATTENDEES_TABLE, $update, $where );
					if ( 1 === $rows ) {
						$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Attendee update, ID: ' . $payment_intent->metadata['attendee_id'] );
					} else {
						$this->write_log( __FUNCTION__, basename( __FILE__ ), 'FAILED, Attendee update, ID: ' . $payment_intent->metadata['attendee_id'] );
					}
				}
				break;
			case 'checkout.session.expired':
			case 'payment_intent.payment_failed':
			case 'payment_intent.canceled':
				$payment_intent = $event->data->object;
				$bad_debt = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT	attendee_payment_id,
						attendee_new_member_id
						FROM %1s
						WHERE 0 < attendee_balance_owed && attendee_id = %1s',
						self::ATTENDEES_TABLE,
						$payment_intent->metadata['attendee_id']
					),
					OBJECT
				);

				if ( $bad_debt ) {
					$where = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
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
				break;
			default:
		}
	}

	/**
	 * Updates the database during an event callback.
	 *
	 * @param  mixed $payment_intent_id The payment intent id.
	 * @param  mixed $metadata The metadata associated with this event.
	 * @param  mixed $status The current status.
	 * @return void
	 */
	private function update_database( $payment_intent_id, $metadata, $status ) {
		global $wpdb;
		if ( ! isset( $metadata['attendee_id'] ) ) {
			$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Called without attendee_id' );
			return;
		}

		$dt_now      = new DateTime( 'now', $this->date_time_zone );
		$new_payment = array(
			'payments_attendee_id'        => $metadata['attendee_id'],
			'payments_amount_charged'     => (string) ( (int) $metadata['cost'] * $metadata['qty'] ),
			'payments_signup_description' => $metadata['description'],
			'payments_last_access_time'   => $dt_now->format( 'Y-m-d H:i:s.u' ),
			'payments_attendee_badge'     => $metadata['badge'],
			'payments_price_id'           => $metadata['price_id'],
			'payments_status'             => $status,
			'payments_intent_status_time' => $dt_now->format( 'Y-m-d H:i:s.u' ),
			'payments_intent_id'          => $payment_intent_id,
			'payments_start_time'         => $dt_now->format( 'Y-m-d H:i:s.u' ),
		);

		$wpdb->insert( self::PAYMENTS_TABLE, $new_payment );
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

		$stripe = new \Stripe\StripeClient( $this->stripe_api_secret );
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

		$stripe = new \Stripe\StripeClient( $this->stripe_api_secret );
		$checkout_session = $stripe->checkout->sessions->retrieve( $checkout_session_id, array() );
		if ( 'open' === $checkout_session->status ) {
			$stripe->checkout->sessions->expire( $checkout_session_id, array() );
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
	 * @return void
	 */
	public function collect_money( $description, $price_id, $badge, $attendee_id, $cost, $qty, $signup_id ) {
		global $wpdb;
		\Stripe\Stripe::setApiKey( $this->stripe_api_secret );
		header( 'Content-Type: application/json' );
		$signup_domain    = $this->stripe_root_url;
		$current_time     = time();
		$expire_time      = $current_time + ( 31 * 60 );
		$checkout_session = \Stripe\Checkout\Session::create(
			array(
				'metadata'            => array(
					'attendee_id' => $attendee_id,
					'badge'       => $badge,
					'cost'        => $cost,
					'price_id'    => $price_id,
					'description' => $description,
					'qty'         => $qty,
				),
				'line_items'          => array(
					array(
						'price'    => $price_id,
						'quantity' => $qty,
					),
				),
				'payment_intent_data' => array(
					'metadata' => array(
						'attendee_id' => $attendee_id,
						'badge'       => $badge,
						'cost'        => $cost,
						'price_id'    => $price_id,
						'description' => $description,
						'qty'         => $qty,
					),
				),
				'mode'                => 'payment',
				'expires_at'          => $expire_time,
				'success_url'         => $signup_domain . '/signups?payment_success=1&attendee_id=' . $attendee_id . '&signup_id=' . $signup_id . '&badge=' . $badge . '&mynonce=' . wp_create_nonce( 'signups' ),
				'cancel_url'          => $signup_domain . '/signups?payment_canceled=1&attendee_id=' . $attendee_id . '&signup_id=' . $signup_id . '&mynonce=' . wp_create_nonce( 'signups' ),
			)
		);

		header( 'HTTP/1.1 303 See Other' );
		header( 'Location: ' . $checkout_session->url );

		$where = array( 'attendee_id' => $attendee_id );
		$data  = array( 'attendee_checkout_id' => $checkout_session->id );
		$wpdb->update( self::ATTENDEES_TABLE, $data, $where );

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
	 * @return void
	 */
	public function payment_success( $post ) {
		global $wpdb;
		$payment_complete = false;
		$attendee_id      = $post['attendee_id'];
		$badge_number     = $post['badge'];
		$signup_id        = $post['signup_id'];
		$payment_row      = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT payments_id, payments_intent_id, payments_signup_description, payments_status, payments_attendee_id, payments_email_sent
				FROM %1s
				WHERE payments_attendee_id = %s',
				self::PAYMENTS_TABLE,
				$attendee_id
			),
			OBJECT
		)
		?>
		<h2>Payment for badge: <?php echo esc_html( $badge_number ); ?></h2>
		<?php
		if ( $payment_row ) {
			if ( 'succeeded' !== $payment_row->payments_status && 'complete' !== $payment_row->payments_status ) {
				?>
				<meta http-equiv="Refresh" content="2">
				<?php
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

		if ( $payment_complete && ! $payment_row->payments_email_sent ) {
			$email = null;
			if ( (int) $badge_number > 1000 ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s
						WHERE member_badge = %s',
						self::MEMBERS_TABLE,
						$badge_number
					),
					OBJECT
				);

				$email = $results[0]->member_email;
			} else {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %1s
						WHERE new_member_id = %s',
						self::NEW_MEMBER_TABLE,
						$badge_number
					),
					OBJECT
				);

				$email = $results[0]->new_member_email;
			}

			$attendee = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT attendee_session_id FROM %1s
					WHERE attendee_id = %s',
					self::ATTENDEES_TABLE,
					$payment_row->payments_attendee_id
				),
				OBJECT
			);

			if ( $email ) {
				$signup_parts = explode( '-', $payment_row->payments_signup_description );
				$body         = '<p>Your payment id is : ' . $payment_row->payments_intent_id . '</p>';
				$body        .= $this->get_session_email_body( $attendee->attendee_session_id );
				$sgm          = new SendGridMail();
				$email_status = $sgm->send_mail( $email, 'You are signed up for ' . $signup_parts[0], $body, true );
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
		$payment_id = $post['attendee_id'];
		$signup_id        = $post['signup_id'];
		if ( $payment_id ) {
			$bad_debt = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT	attendee_checkout_id
					FROM %1s
					WHERE 0 < attendee_balance_owed && attendee_id = %1s',
					self::ATTENDEES_TABLE,
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
