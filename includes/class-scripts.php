<?php
/**
 * Summary
 * Client side scripts class.
 *
 * @package SignUps
 */

/**
 * Instantiate the scripts used by client side code.
 */
class Scripts {

	/**
	 * Script for getting a members info from their badge number.
	 *
	 * @return void
	 */
	public function get_member_script() {
		?>
		<script>
			function makeSignUpRequest(url, callback) {
				var request;
				if (window.XMLHttpRequest) {
					request = new XMLHttpRequest(); // IE7+, Firefox, Chrome, Opera, Safari
				} else {
					request = new ActiveXObject("Microsoft.XMLHTTP"); // IE6, IE5
				}
				request.onreadystatechange = function () {
					if (request.readyState == 4 && request.status == 200) {
						callback(request);
					} 
				}
				request.open("GET", url, true);
				request.setRequestHeader('X-WP-Nonce', <?php wp_create_nonce( 'wp_rest' ); ?> );
				request.send();
			}

			function lookupMemberByBadge() {
				var badge = document.getElementById('badge_input').value;
				var url = "http://localhost/wp/wp-json/scwmembers/v1/members?badge=" + badge;
				makeSignUpRequest(url, function(data) {
					alert(data);
					});
			}
		</script>
		<?php
	}
}
