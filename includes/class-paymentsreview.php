<?php
/**
 * Summary
 * Payments review class.
 *
 * @package SignUps
 */

ob_start();

/**
 * PaymentsReview generates a list of Stripe Payments. 
 * This can be accessed via the submenu item named Payments.
 */
class PaymentsReview extends SignUpsBase {

	/**
	 * Revivew recent payments.
	 *
	 * @return void
	 */
	public function review_payments() {
		global $wpdb;
		$payments = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				ORDER BY payments_start_time DESC',
				self::PAYMENTS_TABLE
			),
			OBJECT
		);
		?>
		<div class="payment-items font-weight-bold text-center mt-4 mb-1" >
			<div>Payment Intent ID</div>
			<div>Payment Initiated</div>
			<div>Description</div>
			<div>Badge</div>
			<div>$$$$</div>
			<div>Status</div>
		</div>
		<?php
		$count = 0;
		foreach ( $payments as $payment ) {
			?>
			<div class="payment-items font-weight-normal <?php echo $count % 2 ? 'bg-lightgray': ''; ?>">
				<div><?php echo esc_html( $payment->payments_intent_id ); ?></div>
				<div><?php echo esc_html( $payment->payments_start_time ); ?></div>
				<div><?php echo esc_html( $payment->payments_signup_description ); ?></div>
				<div><?php echo esc_html( $payment->payments_attendee_badge ); ?></div>
				<div><?php echo esc_html( $payment->payments_amount_charged ); ?></div>
				<div><?php echo esc_html( $payment->payments_status ); ?></div>
			</div>
			<?php
			$count++;
		}
		?>
		</div>
		<?php
	}
}
