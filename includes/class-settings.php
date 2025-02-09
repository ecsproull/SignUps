<?php
/*
 * Settings Editor class.
 */

/**
 * Settings Editor
 */
class Settings {
	/**
	 * Sets up the input fields on the settings page.
	 *
	 * @return void
	 */
	public function signups_plugin_option_page() {
		?>
		<div class="wrap">
			<h2>Signups Plugin</h2>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'signups_stripe' );
				do_settings_sections( 'signups_stripe_settings_editor' );
				submit_button( 'Save Stripe Changes', 'primary' );
				?>
			</form>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'signups_captcha' );
				do_settings_sections( 'signups_captcha_settings_editor' );
				submit_button( 'Save Capatcha Changes', 'primary' );
				?>
			</form>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'signups_sendgrid' );
				do_settings_sections( 'signups_sendgrid_settings_editor' );
				submit_button( 'Save SendGrid Changes', 'primary' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register the settins.
	 *
	 * @return void
	 */
	public function signups_register_settings() {
		$args = array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'signups_validate_options' ),
			'default'           => null,
		);

		register_setting( 'signups_stripe', 'signups_stripe', $args );
		register_setting( 'signups_captcha', 'signups_captcha', $args );
		register_setting( 'signups_sendgrid', 'signups_sendgrid', $args );

		add_settings_section(
			'signups_plugin_main',
			'Signups Plugin Settings',
			array( $this, 'signups_stripe_section_text' ),
			'signups_stripe_settings_editor'
		);

		add_settings_field(
			'signups_plugin_api_key',
			'Stripe API Key',
			array( $this, 'signups_stripe_api_key' ),
			'signups_stripe_settings_editor',
			'signups_plugin_main'
		);

		add_settings_field(
			'signups_plugin_api_secret',
			'Stripe API Secret',
			array( $this, 'signups_stripe_api_secret' ),
			'signups_stripe_settings_editor',
			'signups_plugin_main'
		);

		add_settings_field(
			'signups_plugin_endpoint_secret',
			'Stripe API Endpoint Secret',
			array( $this, 'signups_stripe_endpoint_secret' ),
			'signups_stripe_settings_editor',
			'signups_plugin_main'
		);

		add_settings_section(
			'signups_plugin_captcha',
			'Signups reCaptcha Settings',
			array( $this, 'signups_captcha_section_text' ),
			'signups_captcha_settings_editor'
		);

		add_settings_field(
			'signups_captcha_api_key',
			'reCapatcha API Key',
			array( $this, 'signups_captcha_api_key' ),
			'signups_captcha_settings_editor',
			'signups_plugin_captcha'
		);

		add_settings_field(
			'signups_captcha_api_secret',
			'reCapatcha API Secret',
			array( $this, 'signups_captcha_api_secret' ),
			'signups_captcha_settings_editor',
			'signups_plugin_captcha'
		);

		add_settings_section(
			'signups_plugin_sendgrid',
			'Signups Sendgrid Settings',
			array( $this, 'signups_sendgrid_section_text' ),
			'signups_sendgrid_settings_editor'
		);

		add_settings_field(
			'signups_sendgrid_api_key',
			'SendGrid API Key',
			array( $this, 'signups_sendgrid_api_key' ),
			'signups_sendgrid_settings_editor',
			'signups_plugin_sendgrid'
		);
	}

	/**
	 * Create the Stripe settings section text.
	 *
	 * @return void
	 */
	public function signups_stripe_section_text() {
		echo '<p>Enter the Stripe API keys here.</p>';
	}

	/**
	 * Create the reCaptcha settings section text.
	 *
	 * @return void
	 */
	public function signups_captcha_section_text() {
		echo '<p>Enter the reCapatcha API keys here.</p>';
	}

	/**
	 * Create the Sendgrid settings section text.
	 *
	 * @return void
	 */
	public function signups_sendgrid_section_text() {
		echo '<p>Enter the SendGrid API key here.</p>';
	}

	/**
	 * Generate the Stripe api key setting.
	 *
	 * @return void
	 */
	public function signups_stripe_api_key() {
		$options = get_option( 'signups_stripe' );
		if ( isset( $options['api_key'] ) ) {
			$name = $options['api_key'];
		} else {
			$name = '';
		}

		echo "<input id='api_key' class='key_name' name='signups_stripe[api_key]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * Generate the Stripe api secret setting.
	 *
	 * @return void
	 */
	public function signups_stripe_api_secret() {
		$options = get_option( 'signups_stripe' );
		if ( isset( $options['api_secret'] ) ) {
			$name = $options['api_secret'];
		} else {
			$name = '';
		}

		echo "<input id='api_secret' class='key_name' name='signups_stripe[api_secret]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * Generate the Stripe endpoint secret setting.
	 *
	 * @return void
	 */
	public function signups_stripe_endpoint_secret() {
		$options = get_option( 'signups_stripe' );
		if ( isset ( $options['endpoint_secret'] ) ) {
			$name = $options['endpoint_secret'];
		} else {
			$name = '';
		}

		// echo the field
		echo "<input id='endpoint_secret' class='key_name' name='signups_stripe[endpoint_secret]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * Generate the reCaptcha api key setting.
	 *
	 * @return void
	 */
	public function signups_captcha_api_key() {
		$options = get_option( 'signups_captcha' );
		if ( isset( $options['captcha_api_key'] ) ) {
			$name = $options['captcha_api_key'];
		} else {
			$name = '';
		}

		echo "<input id='captcha_api_key' class='key_name' name='signups_captcha[captcha_api_key]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * Generate the reCaptcha api secret setting.
	 *
	 * @return void
	 */
	public function signups_captcha_api_secret() {
		$options = get_option( 'signups_captcha' );
		if ( isset( $options['captcha_api_secret'] ) ) {
			$name = $options['captcha_api_secret'];
		} else {
			$name = '';
		}

		// echo the field
		echo "<input id='captcha_api_secret' class='key_name' name='signups_captcha[captcha_api_secret]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * Generate the SendGrid api key setting.
	 *
	 * @return void
	 */
	public function signups_sendgrid_api_key() {
		$options = get_option( 'signups_sendgrid' );
		if ( isset( $options['sendgrid_api_key'] ) ) {
			$name = $options['sendgrid_api_key'];
		} else {
			$name = '';
		}

		echo "<input id='sendgrid_api_key' class='key_name' name='signups_sendgrid[sendgrid_api_key]'
			type='text' value='" . esc_attr( $name ) . "' />";
	}

	/**
	 * These are entered by an admin so we aren't validating them.
	 *
	 * @param  mixed $input The input.
	 * @return string Returing the input as is.
	 */
	public function signups_validate_options( $input ) {
		return $input;
	}
}