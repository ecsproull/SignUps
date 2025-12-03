<?php
/*
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
        <table id="payments_table" class="striped">
            <thead>
                <tr>
                    <th>First</th>
                    <th>Last</th>
                    <th>Badge</th>
                    <th>Payment Initiated</th>
                    <th>Description</th>
                    <th>Cost</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
        <?php
        foreach ( $payments as $payment ) {

            if ( ! $payment->member_lastname && ! $payment->member_firstname && $payment->payments_attendee_badge ) {
                $new_member = $wpdb->get_row(
                    $wpdb->prepare(
                        'SELECT new_member_first,new_member_last FROM ' . self::NEW_MEMBER_TABLE . ' WHERE new_member_id = %s',
                        $payment->payments_attendee_badge
                    ),
                    OBJECT
                );
                if ( $new_member ) {
                    $payment->member_firstname = $new_member->new_member_first;
                    $payment->member_lastname  = $new_member->new_member_last;
                }
            }

            // Safe time (strip fractional seconds)
            $raw_time = $payment->payments_start_time ?? '';
            if ( is_string( $raw_time ) && $raw_time !== '' ) {
                $dotPos = strpos( $raw_time, '.' );
                $raw_time = $dotPos !== false ? substr( $raw_time, 0, $dotPos ) : $raw_time;
            }
            ?>
            <tr>
                <td><?php echo esc_html( $payment->member_firstname ); ?></td>
                <td><?php echo esc_html( $payment->member_lastname ); ?></td>
                <td><?php echo esc_html( $payment->payments_attendee_badge ); ?></td>
                <td><?php echo esc_html( $raw_time ); ?></td>
                <td><?php echo esc_html( $payment->payments_signup_description ); ?></td>
                <td><?php echo esc_html( $payment->payments_amount_charged ); ?></td>
                <td><?php echo esc_html( substr( $payment->payments_status, 0, 3 ) ); ?></td>
            </tr>
            <?php
        }
        ?>
            </tbody>
        </table>
        <button type="button" id="copy_csv_btn" class="button">Copy CSV</button>
        <script>
        jQuery(function($){
            $('#copy_csv_btn').on('click', function(){
                const rows = [];
                $('#payments_table tbody tr').each(function(){
                    const cells = [];
                    $(this).find('td').each(function(){
                        let t = $(this).text().trim();
                        // Escape double quotes for CSV
                        if (t.indexOf('"') !== -1 || t.indexOf(',') !== -1 || t.indexOf('\n') !== -1) {
                            t = '"' + t.replace(/"/g,'""') + '"';
                        }
                        cells.push(t);
                    });
                    rows.push(cells.join(','));
                });
                const csv = rows.join('\r\n');
                navigator.clipboard.writeText(csv).then(()=> {
                    alert('CSV copied to clipboard.');
                }).catch(()=> {
                    alert('Failed to copy.');
                });
            });
        });
        </script>
        <?php
	}
}
