<?php
/**
 * Summary
 * Stripe payment class.
 *
 * @package signups
 */

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 *
 * @package SignUps
 */
class SripePayments extends SignUpsBase {

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
		$stripe_row = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::STRIPE_TABLE,
			),
			OBJECT
		);

		\Stripe\Stripe::setApiKey( $stripe_row[0]->stripe_api_key );

		// Secret is at https://dashboard.stripe.com/webhooks.
		$endpoint_secret = $stripe_row[0]->stripe_endpoint_secret;

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

		if ( $endpoint_secret ) {
			/**
			 * Only verify the event if there is an endpoint secret defined
			 * Otherwise use the basic decoded event
			 */
			$sig_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) );
			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$endpoint_secret
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
				$wpdb->query(
					$wpdb->prepare(
						'LOCK TABLES %1s WRITE, %1s WRITE',
						self::ATTENDEES_TABLE,
						self::PAYMENTS_TABLE
					)
				);

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
					'payments_amount_charged'     => $payment_intent->metadata['cost'],
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

					if ( 'succeeded' === $payment_row[0]->payments_status ) {
						$update = array( 'attendee_balance_owed' => 0 );
						$where  = array( 'attendee_id' => $payment_intent->metadata['attendee_id'] );
						$wpdb->update( self::ATTENDEES_TABLE, $update, $where );
					}
				}

				$wpdb->query( 'UNLOCK TABLES' );
				break;

			case 'payment_intent.succeeded':
				$payment_intent = $event->data->object;
				$wpdb->query(
					$wpdb->prepare(
						'LOCK TABLES %1s WRITE, %1s WRITE',
						self::ATTENDEES_TABLE,
						self::PAYMENTS_TABLE
					)
				);

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

					if ( 'succeeded' === $payment_intent->status ) {
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

				$wpdb->query( 'UNLOCK TABLES' );
				break;
			case 'payment_method.attached':
				$payment_method = $event->data->object; // Contains a \Stripe\PaymentMethod.

				// Then define and call a method to handle the successful attachment of a PaymentMethod.
				// handlePaymentMethodAttached($paymentMethod);.
				break;
			default:
				// Unexpected event type.
				error_log( 'Received unknown event type' );
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
	 * @return void
	 */
	public function collect_money( $description, $price_id, $badge, $attendee_id, $cost ) {
		global $wpdb;
		$stripe_row = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::STRIPE_TABLE,
			),
			OBJECT
		);

		\Stripe\Stripe::setApiKey( $stripe_row[0]->stripe_api_secret );
		header( 'Content-Type: application/json' );
		$signup_domain    = $stripe_row[0]->stripe_root_url;
		$checkout_session = \Stripe\Checkout\Session::create(
			array(
				'metadata'    => array(
					'attendee_id' => $attendee_id,
					'badge'       => $badge,
					'cost'        => $cost,
					'price_id'    => $price_id,
					'description' => $description,
				),
				'line_items'  => array(
					array(
						'price'       => $price_id,
						'quantity'    => 1,
						'description' => $description,
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
		$attendee_id  = sanitize_text_field( get_query_var( 'attendee_id' ) );
		$badge_number = sanitize_text_field( get_query_var( 'badge' ) );
		$payment_row  = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT payments_signup_description, payments_status
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
		if ($payment_row) {
			if ( $payment_row->payments_status != 'succeeded' && $payment_row->payments_status != 'completed') {
				?>
				<meta http-equiv="Refresh" content="2">
				<?php
			}
			?>
			<h2>Status: <?php echo esc_html( $payment_row->payments_status ); ?></h2>
			<?php
		} else {
			?>
			<h2>Status: <?php echo esc_html( ' Unknown' ); ?></h2>
			<?php
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

	public function update_price( $price_id, $product_id , $new_cost ) {
		$new_cost = $new_cost . '00';
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/prices');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "unit_amount=" . $new_cost . "&currency=usd&recurring[interval]=month&product=prod_O3LkGgqLwkszx5");
		curl_setopt($ch, CURLOPT_USERPWD, 'sk_test_51LPCe7EVPTwIS1QJQp7Vd1X9RsslNrfWNaqetmC3v6DsF3ocQrYUgAfRrhcQkYZW77szXpwZ3RoWFn5y7SWU5ZN200ZDxPlBpk' . ':' . '');

		$headers = array();
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);
	}

	public function populate_products_table() {
		global $wpdb;
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, 'https://api.stripe.com/v1/products' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

		curl_setopt( $ch, CURLOPT_USERPWD, 'sk_test_51LPCe7EVPTwIS1QJQp7Vd1X9RsslNrfWNaqetmC3v6DsF3ocQrYUgAfRrhcQkYZW77szXpwZ3RoWFn5y7SWU5ZN200ZDxPlBpk' . ':' . '' );

		$result = curl_exec( $ch );
		if (curl_errno( $ch )) {
			echo 'Error:' . curl_error( $ch );
		} else {
			$products = json_decode( $result );
			curl_close( $ch );

			foreach( $products->data as $product ) {
				$product_row  = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE products_product_id = %s',
						self::STRIPE_PRODUCTS_TABLE,
						$product->id
					),
					OBJECT
				);

				if ( ! $product_row && $product->default_price ) {
					$ch = curl_init();

					curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/prices/' . $product->default_price);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
					
					curl_setopt($ch, CURLOPT_USERPWD, 'sk_test_51LPCe7EVPTwIS1QJQp7Vd1X9RsslNrfWNaqetmC3v6DsF3ocQrYUgAfRrhcQkYZW77szXpwZ3RoWFn5y7SWU5ZN200ZDxPlBpk' . ':' . '');
					
					$price_result = curl_exec($ch);
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					} else {
						$price = json_decode( $price_result );

						$product_new = Array();
						$product_new['products_product_id'] = $product->id;
						$product_new['products_product_description'] = $product->description ? $product->description : $product->name  ;
						$product_new['products_price_id'] = $product->default_price;
						$product_new['products_price'] = $price->unit_amount / 100;
						$product_new['products_product_name'] = $product->name;
						curl_close($ch);

						$rows = $wpdb->insert( self::STRIPE_PRODUCTS_TABLE, $product_new );
						
					}
				}
			}
		}
	}
}
