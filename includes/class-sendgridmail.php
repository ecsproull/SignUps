<?php
/**
 * Summary
 * Place class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

use \SendGrid\Mail\Mail;

/**
 * Mirror of the database Session object.
 * Used for creating new sessions to be added to the DB.
 *
 * @package SignUps
 */
class SendGridMail extends SignUpsBase {

	/**
	 * Used to send an email.
	 *
	 * @param  mixed $email_address Email address of the receipient.
	 * @param  mixed $subject subject for the email.
	 * @param  mixed $message Message body for the email.
	 * @return void
	 */
	public function send_mail( $email_address, $subject, $message ) {
		$email = new Mail();
		$email->setFrom(
			'scwwoodclubmonitors@outlook.com',
			'SCW WoodClub Signups'
		);

		$email->setSubject( $subject );
		$email->addTo( $email_address );

		$email->addContent(
			'text/html',
			$message
		);

		$sendgrid = new \SendGrid( getenv( 'SendGrid' ) );

		try {
			$response = $sendgrid->send( $email );
		} catch ( Exception $e ) {
				echo 'Caught exception: ' . esc_html( $e->getMessage() ) . "\n";
		}
	}
}
