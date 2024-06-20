<?php
/**
 * Summary
 * MemberRoster class.
 *
 * @package signups
 */

/**
 * Used to display limited information about the woodclub membership.
 * Intranet only.
 *
 * @package SignUps
 */
class MemberRoster extends SignUpsBase {

	/**
	 * Add the select class shortcode
	 */
	public function member_roster() {

		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
		} else {
			$this->display_roster();
		}
	}

	/**
	 * Displays the current roster.
	 *
	 * @return void
	 */
	private function display_roster() {
		$ip_address = $this->get_the_user_ip();
		if ( '68.10.34.196' === $ip_address ) {
			?>
			<h1>Member lookup coming soon</h1>
			<?php
		} else {
			?>
			<h1>Restricted Access</h1>
			<?php
		}
	}

	private function get_the_user_ip() {

		$ip = $_SERVER['REMOTE_ADDR'];
		return apply_filters( 'wpb_get_ip', $ip );
	}
}