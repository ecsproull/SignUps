<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

use \SendGrid\Mail\Mail;

/**
 * Helper class for sending email via Twilio SendGrid
 */
class SendGridMail extends SignUpsBase {

	/**
	 * Used to send an email.
	 *
	 * @param  mixed   $email_address Email address of the recipient.
	 * @param  mixed   $subject subject for the email.
	 * @param  mixed   $message Message body for the email.
	 * @param  boolean $class_email Used to get the correct From email address.
	 * @param  mixed   $reply_to Used to set the reply to field.
	 * @return True on success, false on failure.
	 */
	public function send_mail( $email_address, $subject, $message, $class_email = false, $reply_to = null ) {
		$email = new Mail();

		if ( $class_email ) {
			$email->setFrom(
				'classes@scwwoodshop.com',
				'SCW WoodClub Classes'
			);
		} else {
			$email->setFrom(
				'monitors@scwwoodshop.com',
				'SCW WoodClub Signups'
			);
		}

		if ( $reply_to ) {
			$email->setReplyTo( $reply_to );
		}

		$email->setSubject( $subject );
		$email->addTo( $email_address );

		$email->addContent(
			'text/html',
			$message
		);

		$sendgrid_api_key = get_option( 'signups_sendgrid' ) ? get_option( 'signups_sendgrid' )['sendgrid_api_key'] : '';

		$sendgrid = new \SendGrid( $sendgrid_api_key );


		try {
			$response = $sendgrid->send($email);
			$status   = (int) $response->statusCode();
			$body     = (string) $response->body();

			if ($status !== 202) {
				// Extract SendGrid error details (if JSON)
				$errors = $body;
				$json   = json_decode($body, true);
				if (json_last_error() === JSON_ERROR_NONE && isset($json['errors'])) {
					$errors = implode('; ', array_map(function ($e) {
						$parts = [];
						if (!empty($e['message'])) $parts[] = $e['message'];
						if (!empty($e['field']))   $parts[] = 'field: '.$e['field'];
						if (!empty($e['help']))    $parts[] = 'help: '.$e['help'];
						return implode(' | ', $parts);
					}, $json['errors']));
				}

				$this->write_log(
					__FUNCTION__,
					basename(__FILE__),
					"SendGrid error status {$status}. TO: {$email_address} Subject: {$subject} Errors: {$errors}"
				);
				return false;
			}

			return true;
		} catch (\Throwable $e) {
			$this->write_log(__FUNCTION__, basename(__FILE__), 'Exception sending mail: '.$e->getMessage());
			return false;
		}
	}
}
