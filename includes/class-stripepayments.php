<?php
/**
 * Summary
 * Place class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 *
 * @package SignUps
 */
class StripePayments extends SignUpsBase {

	/**
	 * Stripe API secret
	 *
	 *  mixed
	 */
	private $stripe_api_secret;

	/**
	 * Stripe API key.
	 *
	 *  mixed
	 */
	private $stripe_api_key;

	/**
	 * Stripe Endpoint Secret.
	 *
	 *  mixed
	 */
	private $stripe_endpoint_secret;

	/**
	 * Stripe root URL.
	 *
	 *  mixed
	 */
	private $stripe_root_url;


	/**
	 * __construct
	 *
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
	 */
	public function payment_event() {
		global $wpdb;
		\Stripe\Stripe::setApiKey( $this->stripe_api_key );

		$payload = @file_get_contents( 'php://input' );
		$event   = null;

		try {
			$event = \Stripe\Event::constructFrom(
				json_decode( $payload, true )
			);
		} catch ( \UnexpectedValueException $e ) {
			echo '⚠️  Webhook error while parsing basic request.';
			http_response_code( 400 );
			exit();
		}

		if ( $stripe_endpoint_secret ) {
			/**
			 * Only verify the event if there is an endpoint secret defined
			 * Otherwise use the basic decoded event
			 */
			if ( isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) {
				$sig_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) );
			}

			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$stripe_endpoint_secret
				);
			} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
				echo '⚠️  Webhook error while validating signature.';
				http_response_code( 400 );
				exit();
			}
		}

		http_response_code( 200 );
		switch ( $event->type ) {
			case 'checkout.session.completed':
				$payment_intent = $event->data->object;
				/* $wpdb->query(
					$wpdb->prepare(
						'LOCK TABLES %1s WRITE, %1s WRITE',
						self::ATTENDEES_TABLE,
						self::PAYMENTS_TABLE
					)
				); */

				$payment_row = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT payments_status
						FROM %1s
						WHERE payments_intent_id = %s',
						self::PAYMENTS_TABLE,
						$payment_intent->payment_intent
					),
					OBJECT
				);

				$dt_now      = new DateTime( 'now', $this->date_time_zone );
				$new_payment = array(
					'payments_attendee_id'        => $payment_intent->metadata['attendee_id'],
					'payments_amount_charged'     => (string) ( (int) $payment_intent->metadata['cost'] * $payment_intent->metadata['qty'] ),
					'payments_signup_description' => $payment_intent->metadata['description'],
					'payments_last_access_time'   => $dt_now->format( 'Y-m-d H:i:s.u' ),
					'payments_attendee_badge'     => $payment_intent->metadata['badge'],
					'payments_price_id'           => $payment_intent->metadata['price_id'],
					'payments_status'             => $payment_intent->status,
					'payments_intent_status_time' => $dt_now->format( 'Y-m-d H:i:s.u' ),
				);

				if ( ! $payment_row ) {
					$new_payment['payments_intent_id']  = $payment_intent->payment_intent;
					$new_payment['payments_start_time'] = $dt_now->format( 'Y-m-d H:i:s.u' );
					$wpdb->insert( self::PAYMENTS_TABLE, $new_payment );
				} else {
					$where = array( 'payments_intent_id' => $payment_intent->payment_intent );
					$wpdb->update( self::PAYMENTS_TABLE, $new_payment, $where );

					if ( 'succeeded' === $payment_row[0]->payments_status || 'complete' === $payment_row[0]->payments_status ) {
						$update = array( 'attendee_balance_owed' => 0 );
						$where  = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
						$wpdb->update( self::ATTENDEES_TABLE, $update, $where );
					}
				}

				//$wpdb->query( 'UNLOCK TABLES' );
				break;

			case 'payment_intent.succeeded':
				$payment_intent = $event->data->object;
				/* $wpdb->query(
					$wpdb->prepare(
						'LOCK TABLES %1s WRITE, %1s WRITE',
						self::ATTENDEES_TABLE,
						self::PAYMENTS_TABLE
					)
				); */

				$payment_row = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT payments_attendee_id
						FROM %1s
						WHERE payments_intent_id = %s',
						self::PAYMENTS_TABLE,
						$payment_intent->id
					),
					OBJECT
				);

				$dt_now = new DateTime( 'now', $this->date_time_zone );
				$update = array(
					'payments_customer_id'        => $payment_intent->customer,
					'payments_status'             => $payment_intent->status,
					'payments_last_access_time'   => $dt_now->format( 'Y-m-d H:i:s.u' ),
					'payments_intent_status_time' => $dt_now->format( 'Y-m-d H:i:s.u' ),
				);

				if ( $payment_row ) {
					$where = array(
						'payments_intent_id' => $payment_intent->id,
					);

					$wpdb->update( self::PAYMENTS_TABLE, $update, $where );

					if ( 'succeeded' === $payment_intent->status || 'complete' === $payment_intent->status ) {
						$update = array(
							'attendee_balance_owed' => 0,
						);

						$where = array(
							'attendee_id' => $payment_row[0]->payments_attendee_id,
						);

						$wpdb->update( self::ATTENDEES_TABLE, $update, $where );
					} else {
						echo 'WTF';
					}
				} else {
					$update['payments_intent_id']  = $payment_intent->id;
					$update['payments_start_time'] = $dt_now->format( 'Y-m-d H:i:s.u' );
					$wpdb->insert( self::PAYMENTS_TABLE, $update );
				}

				//$wpdb->query( 'UNLOCK TABLES' );
				break;
			case 'payment_method.attached':
				$payment_method = $event->data->object; // Contains a \Stripe\PaymentMethod.;.
				break;
			case 'checkout.session.expired':
			case 'payment_intent.payment_failed':
			case 'payment_intent.canceled':
				break;
			default:
		}
	}

	/**
	 * Payment form.
	 *
	 * @param string $description Description of what is bing paid for..
	 * @param int    $price_id    The price ID to be charged to.
	 * @param int    $badge       Attendee's badge number.
	 * @param int    $attendee_id The id fo the entry in the attendees table.
	 * @param int    $cost        The dollar cost of the signup.
	 * @param int    $qty         The quantity being purchased.
	 * @return void
	 */
	public function collect_money( $description, $price_id, $badge, $attendee_id, $cost, $qty ) {
		\Stripe\Stripe::setApiKey( $this->stripe_api_secret );
		header( 'Content-Type: application/json' );
		$signup_domain    = $this->stripe_root_url;
		$checkout_session = \Stripe\Checkout\Session::create(
			array(
				'metadata'    => array(
					'attendee_id' => $attendee_id,
					'badge'       => $badge,
					'cost'        => $cost,
					'price_id'    => $price_id,
					'description' => $description,
					'qty'         => $qty
				),
				'line_items'  => array(
					array(
						'price'    => $price_id,
						'quantity' => $qty,
					),
				),
				'mode'        => 'payment',
				'success_url' => $signup_domain . '/payment-success?attendee_id=' . $attendee_id . '&badge=' . $badge,
				'cancel_url'  => $signup_domain . '/payment-canceled?attendee_id=' . $attendee_id . '&badge=' . $badge,
			)
		);

		header( 'HTTP/1.1 303 See Other' );
		header( 'Location: ' . $checkout_session->url );
	}

	/**
	 * Shortcode for the Payment Success page
	 *
	 * @return void
	 */
	public function payment_success() {
		global $wpdb;
		$payment_complete = false;
		$attendee_id      = sanitize_text_field( get_query_var( 'attendee_id' ) );
		$badge_number     = sanitize_text_field( get_query_var( 'badge' ) );
		$payment_row      = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT payments_intent_id, payments_signup_description, payments_status, payments_attendee_id
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

		if ( $payment_complete ) {
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
					?>
					<h2>An email was set to <?php echo esc_html( $email ); ?> with your payment id : <?php echo esc_html( $payment_row->payments_intent_id ); ?></h2>
					<?php
				}
			}
		}
	}

	/**
	 * Shortcode for the Payment failure page
	 *
	 * @return void
	 */
	public function payment_failure() {
		$payment_id   = sanitize_text_field( get_query_var( 'paymentid' ) );
		$badge_number = sanitize_text_field( get_query_var( 'badge' ) );
		?>
		<P>Payment Failed</P>
		<?php
	}

	/**
	 * Update the price for a signup.
	 * The price is registered with Stipe.Com.
	 *
	 * @param  mixed $price_id Original price id.
	 * @param  mixed $product_id Product Id.
	 * @param  mixed $new_cost New cost.
	 * @return The new price id.
	 */
	public function update_price( $price_id, $product_id, $new_cost ) {
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

		}

		return $result->id;
	}

	/**
	 * Create the product id and price id for a signup.
	 * These registered with Stipe.Com and updated via their API.
	 *
	 * @param string $name Name of the signup.
	 * @param int    $cost The cost of the signup.
	 * @return The new product and price id as a comma separated string.
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
