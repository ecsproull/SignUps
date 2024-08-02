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
	 * Review recent payments.
	 *
	 * @return void
	 */
	public function review_payments() {
		global $wpdb;
		$payments = $wpdb->get_results(
			'SELECT p.payments_signup_description,
				p.payments_amount_charged,
				p.payments_status,
				p.payments_attendee_badge,
				p.payments_start_time,
				m.member_firstname,
				m.member_lastname
			FROM wp_scw_payments AS p
			LEFT JOIN wp_scw_members AS m
			ON p.payments_attendee_badge = m.member_badge
			ORDER BY payments_start_time DESC',
			OBJECT
		);
		?>
		<div class="payment-items font-weight-bold mt-4 mb-1" >
			<div>First</div>
			<div>Last</div>
			<div>Badge</div>
			<div>Payment Initiated</div>
			<div>Description</div>
			<div>Cost</div>
			<div>Status</div>
		</div>
		<?php
		$count = 0;
		foreach ( $payments as $payment ) {
			?>
			<div class="payment-items font-weight-normal <?php echo $count % 2 ? 'bg-lightgray': ''; ?>">
				<div><?php echo esc_html( $payment->member_firstname ); ?></div>
				<div><?php echo esc_html( $payment->member_lastname ); ?></div>
				<div><?php echo esc_html( $payment->payments_attendee_badge ); ?></div>
				<div><?php echo esc_html( substr( $payment->payments_start_time, 0, strpos($payment->payments_start_time, '.') - 3 ) ); ?></div>
				<div><?php echo esc_html( $payment->payments_signup_description ); ?></div>
				<div><?php echo esc_html( $payment->payments_amount_charged ); ?></div>
				<div><?php echo esc_html( substr( $payment->payments_status, 0, 3 ) ); ?></div>
			</div>
			<?php
			$count++;
		}
		?>
		</div>
		<?php
	}
}
