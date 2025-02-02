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

		global $wpdb;
		$stripe_row = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::STRIPE_TABLE,
			),
			OBJECT
		);

		$sendgrid = new \SendGrid( $stripe_row[1]->stripe_api_key );

		try {
			$ret        = $sendgrid->send( $email );
			$ret_string = $ret ? 'Sent' : 'Failed';
			//$this->write_log( __FUNCTION__, basename( __FILE__ ), $ret_string . ' TO: ' . $email_address . ' Subject: ' . $subject . ' Msg: ' . $message );
			return $ret;
		} catch ( Exception $e ) {
			$this->write_log( __FUNCTION__, basename( __FILE__ ), 'Caught exception: ' . esc_html( $e->getMessage() ) . ' TO: ' . $email_address . ' Subject: ' . $subject . ' Msg: ' . $message );
				return false;
		}
	}
}
