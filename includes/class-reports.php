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
							GROUP BY CncSignUpHistory.Machine, 
									CncSignUpHistory.Badge, 
									CncSignUpHistory.Email, 
									MemberRoster.FirstName,  
									MemberRoster.LastName
							ORDER BY CncSignUpHistory.Machine';

	/**
	 * Usage report query with where parameter.
	 *
	 * @var mixed
	 */
	private $usage_report_where =
		'SELECT CncSignUpHistory.Machine,
			CncSignUpHistory.Badge,
			CncSignUpHistory.Email, 
			MemberRoster.FirstName, 
			MemberRoster.LastName, 
			COUNT( CncSignUpHistory.id) AS [Hours]
		FROM CncSignupHistory
		LEFT JOIN MemberRoster ON CncSignUpHistory.Email = MemberRoster.Email
		WHERE %s
		GROUP BY CncSignUpHistory.Machine, 
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
	private $users_report = 'SELECT * from CncSignUpHistory ORDER BY Machine, StartTime';

	/**
	 * Add the select class shortcode
	 */
	public function class_reports() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
			if ( isset( $post['submit_query'] ) ) {
				$this->submit_query( $post );
			}
		} else {
			$this->load_report(
				$this->get_user_items( $this->usage_report ),
				$this->get_user_items( $this->users_report ),
				$post
			);
		}
	}

	/**
	 * Submit a query with a where clause.
	 *
	 * @param  mixed $post
	 * @return void
	 */
	private function submit_query( $post ) {
		$where     = '';
		$where_all = '';
		if ( 'Both' !== $post['machine'] ) {
			$where     .= "CncSignUpHistory.Machine = '" . $post['machine'] . "'";
			$where_all .= " WHERE Machine = '" . $post['machine'] . "'";
		}

		if ( $post['start_date'] ) {
			if ( $where ) {
				$where     .= " AND CncSignUpHistory.StartTime >= '" . $post['start_date'] . "'";
				$where_all .= " AND StartTime >= '" . $post['start_date'] . "'";
			} else {
				$where     .= " CncSignUpHistory.StartTime >= '" . $post['start_date'] . "'";
				$where_all .= " WHERE StartTime >= '" . $post['start_date'] . "'";
			}
		}

		if ( $post['end_date'] ) {
			if ( $where ) {
				$where     .= " AND CncSignUpHistory.EndTime >= '" . $post['start_date'] . "'";
				$where_all .= " AND EndTime >= '" . $post['start_date'] . "'";
			} else {
				$where     .= " CncSignUpHistory.EndTime <= '" . $post['end_date'] . "'";
				$where_all .= " WHERE EndTime <= '" . $post['end_date'] . "'";
			}
		}

		$query     = '';
		$query_all = '';
		if ( $where ) {
			$query     = sprintf( $this->usage_report_where, $where );
			$query_all = $this->users_report .= $where_all;
		} else {
			$query     = $this->usage_report;
			$query_all = $this->users_report;
		}

		$items     = $this->get_user_items( $query );
		$all_items = $this->get_user_items( $query_all );
		$this->load_report( $items, $all_items, $post );
	}


	/**
	 * Loads the report
	 *
	 * @param  mixed $items Items for the summary report.
	 * @param  mixed $all_items All items in the db for the report period.
	 * @return void
	 */
	private function load_report( $items, $all_items, $post ) {
		?>
		<form class="ml-auto mr-auto" method="POST">
		<div class='cnc-query-params mb-3'>
			<div>
				<label for="machine">Machine</label>
				<select name="machine" id="machine" style="height: 30px">
					<option value="Both" <?php echo 'Both' === $post['machine'] ? 'selected' : ''; ?> >Both</option>
					<option value="2015" <?php echo '2015' === $post['machine'] ? 'selected' : ''; ?> >2015</option>
					<option value="2020" <?php echo '2020' === $post['machine'] ? 'selected' : ''; ?> >2020</option>
				</select>
			</div>
			<div>
				<label for="start_date">Start Date</label>
				<input type="date" id="start_date" name="start_date" value=<?php echo esc_html( $post['start_date'] ); ?>>
			</div>
			<div>
				<label for="end_date">End Date</label>
				<input type="date" id="end_date" name="end_date" value=<?php echo esc_html( $post['end_date'] ); ?>>
			</div>
			<div class="mt-2">
				<button id="toggle-view" type="button" class="btn btn-md bg-primary mr-auto ml-auto">All Slots</button>
			</div>
			<div>
				<button id="download" type="button" class="btn btn-md bg-primary">Download</button>
			</div>
			<div class="mt-2">
				<button type="submit" class="btn btn-md bg-primary mr-auto ml-auto" value="-1" name="submit_query">Update</button>
			</div>
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</div>
		</form>
		<div id="report-view">
			<?php
			if ( $items ) {
				?>
				<div class='cnc-report-list'>
					<div class = 'col'>Machine</div>
					<div class = 'col'>Badge</div>
					<div class = 'col'>Email</div>
					<div class = 'col'>First Name</div>
					<div class = 'col'>Last Name</div>
					<div class = 'col'>Hours</div>
				<?php
				$current_machine;
				$hours_count = 0;
				foreach ( $items as $slot ) {

					if ( ! $current_machine ) {
						$current_machine = $slot['Machine'];
					} elseif ( $current_machine !== $slot['Machine'] ) {
						?>
						<div></div>
						<div></div>
						<div></div>
						<div></div>
						<div><b>Total</b></div>
						<div><?php echo $hours_count; ?></div>
						<div></div>
						<div></div>
						<div></div>
						<div></div>
						<div></div>
						<div></div>
						<?php
						$current_machine = $slot['Machine'];
						$hours_count = 0;
					}
					$hours_count += (int)$slot['Hours'];
					?>
					<div class = 'col'><?php echo trim( esc_html( $slot['Machine'] ) ); ?></div>
					<div class = 'col'><?php echo trim( esc_html( $slot['Badge'] ) ); ?></div>
					<div class = 'col'><?php echo trim( esc_html( $slot['Email'] ) ); ?></div>
					<div class = 'col'><?php echo trim( esc_html( $slot['FirstName'] ) ); ?></div>
					<div class = 'col'><?php echo trim( esc_html( $slot['LastName'] ) ); ?></div>
					<div class = 'col'><?php echo trim( esc_html( $slot['Hours'] ) ); ?></div>
					<?php
				}
				?>
				<div></div>
				<div></div>
				<div></div>
				<div></div>
				<div><b>Total</b></div>
				<div><?php echo $hours_count; ?></div>
				<?php
			}
			?>
			</div>
		</div>
		<div id="all-items" style="display: none">
			<?php
			if ( $items ) {
				$csv_data = 'Machine,Badge,Email,First Name,Last Name,Start Time,End Time' . PHP_EOL;
				?>
				<div class='cnc-user-list'>
					<div class = 'col'>Machine</div>
					<div class = 'col'>Badge</div>
					<div class = 'col'>Email</div>
					<div class = 'col'>First Name</div>
					<div class = 'col'>Last Name</div>
					<div class = 'col'>Start Time</div>
					<div class = 'col mb-2'>End Time</div>
					<?php
					foreach ( $all_items as $slot ) {
						$csv_data .= trim( $slot['Machine'] ) . ',' . trim( $slot['Badge'] ) . ',' . trim( $slot['Email'] ) . ',' . trim( $slot['FirstName'] );
						$csv_data .= ',' . trim( $slot['LastName'] ) . ',' . trim( $slot['StartTime'] ) . ',' . trim( $slot['EndTime'] ) . PHP_EOL;
						?>
						<div class = 'col'><?php echo trim( esc_html( $slot['Machine'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['Badge'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['Email'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['FirstName'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['LastName'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['StartTime'] ) ); ?></div>
						<div class = 'col'><?php echo trim( esc_html( $slot['EndTime'] ) ); ?></div>
						<?php
					}
					?>
					<input type="hidden" id="csv_data" name="csv_dater" value="<?php echo esc_html( $csv_data ); ?>" >
				</div>
				<?php
			}
		?>
		</div>
		<?php
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
