<?php
/**
 * Summary
 * Reportscode class.
 *
 * @package signups
 */

/**
 * Used for creating class reports.
 *
 * @package SignUps
 */
class Reports extends SignUpsBase {

	/**
	 * Usage report query
	 *
	 * @var mixed
	 */
	private $usage_report = 'SELECT CncSignUpHistory.Machine,
								CncSignUpHistory.Badge,
								CncSignUpHistory.Email, 
								MemberRoster.FirstName, 
								MemberRoster.LastName, 
								COUNT( CncSignUpHistory.id) AS [Hours]
							FROM CncSignupHistory
							LEFT JOIN MemberRoster ON CncSignUpHistory.Email = MemberRoster.Email
							GROUP BY	CncSignUpHistory.Machine, 
									CncSignUpHistory.Badge, 
									CncSignUpHistory.Email, 
									MemberRoster.FirstName,  
									MemberRoster.LastName
							ORDER BY CncSignUpHistory.Machine';

	/**
	 * All usage qyery.
	 *
	 * @var mixed
	 */
	private $users_report = 'SELECT * from CncSignUpHistory';

	/**
	 * Add the select class shortcode
	 */
	public function class_reports() {
		$all = $this->get_user_items( $this->usage_report );
		if ( $all ) {
			?>
			<div class='cnc-report-list'>
				<div class = 'col'>Machine</div>
				<div class = 'col'>Badge</div>
				<div class = 'col'>Email</div>
				<div class = 'col'>First Name</div>
				<div class = 'col'>Last Name</div>
				<div class = 'col'>Hours</div>
			<?php
			foreach ( $all as $slot ) {
				?>
				<div class = 'col'><?php echo trim( esc_html( $slot['Machine'] ) ); ?></div>
				<div class = 'col'><?php echo trim( esc_html( $slot['Badge'] ) ); ?></div>
				<div class = 'col'><?php echo trim( esc_html( $slot['Email'] ) ); ?></div>
				<div class = 'col'><?php echo trim( esc_html( $slot['FirstName'] ) ); ?></div>
				<div class = 'col'><?php echo trim( esc_html( $slot['LastName'] ) ); ?></div>
				<div class = 'col'><?php echo trim( esc_html( $slot['Hours'] ) ); ?></div>
				<?php
			}
		}
	}

	/**
	 * Queries the local database.
	 *
	 * @param  mixed $query The query to execute.
	 * @return The list.
	 */
	private function get_user_items( $query ) {
		$server   = 'WC_SERVER\\SQLEXPRESS';
		$database = 'WoodClub';
		$username = 'memberapp';
		$password = 'member';
		$list     = array();
		$handle   = new PDO( "sqlsrv:Server=$server;Database=$database;", $username, $password );
		$handle->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$statement = $handle->prepare( $query );
		$result    = $statement->execute();
		if ( $result ) {
			$all = $statement->fetchAll( PDO::FETCH_ASSOC );
			if ( $all ) {
				$list = $all;
			}
		}

		$handle = null;
		return $list;
	}
}
