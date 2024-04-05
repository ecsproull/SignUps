<?php
/**
 * Summary
 * Map settings.
 *
 * @package     Signups
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Summary
 * Admin page for the signups plubin. Containes the functions for editing the signups.
 *
 * @package SignUps
 */
class SignupSettings extends SignUpsBase {

	/**
	 * The main function of the Plugin.
	 * This delegates all the real work to helper functions.
	 * Loading the class selection is the default.
	 * All others are triggered by a form submission.
	 */
	public function signup_settings_page() {
		$post = wp_unslash( $_POST );
		if ( isset( $_POST['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'signups' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );
			if ( isset( $post['submit_class'] ) ) {
				$this->submit_class( $post );
			} elseif ( isset( $post['submit_session'] ) ) {
				$this->submit_session( $post );
			} elseif ( isset( $post['edit_class'] ) ) {
				$this->edit_class( $post );
			} elseif ( isset( $post['edit_class_move_up'] ) ) {
				$this->edit_class_move( $post, 'up' );
			} elseif ( isset( $post['edit_class_move_down'] ) ) {
				$this->edit_class_move( $post, 'down' );
			} elseif ( isset( $post['edit_session'] ) ) {
				$this->edit_session( $post );
			} elseif ( isset( $post['add_new_class'] ) ) {
				$this->create_signup_form( new ClassItem( null ) );
			} elseif ( isset( $post['add_new_session'] ) ) {
				$this->add_new_session_form( $post );
			} elseif ( isset( $post['delete_attendees'] ) ) {
				$this->delete_session_attendees( $post );
			} elseif ( isset( $post['move_attendees'] ) ) {
				$this->move_session_attendees( $post );
			} elseif ( isset( $post['add_attendee'] ) ) {
				$this->add_session_attendees( $post );
			} elseif ( isset( $post['submit_attendees'] ) ) {
				$this->submit_session_attendees( $post );
			} elseif ( isset( $post['edit_sessions_signup_id'] ) ) {
				$this->load_session_selection( $post );
			} elseif ( isset( $post['delete_session'] ) ) {
				$this->delete_session( $post );
			} elseif ( isset( $post['add_attendee_session'] ) ) {
				$this->add_attendee_rolling( $post );
			} elseif ( isset( $post['update_calendar'] ) ) {
				$this->update_calendar( $post );
				$repost = array( 'edit_sessions_signup_id' => $post['signup_id'] );
				$this->load_session_selection( $repost );
			} elseif ( isset( $post['delete_class'] ) ) {
				$this->confirm_class_delete( $post );
			} elseif ( isset( $post['confirm_delete_class'] ) ) {
				$this->delete_class( $post );
			} elseif ( isset( $post['session_add_slots'] ) ) {
				$this->add_session_slots( (object) $post, $post['signup_name'] );
			} elseif ( isset( $post['submit_description'] ) ) {
				$this->submit_description( $post );
			} elseif ( isset( $post['load_description_form'] ) ) {
				$this->load_description_form();
			} else {
				$this->load_signup_selection();
			}
		} else {
			$this->load_signup_selection();
		}
	}

	/**
	 * Moves the order of an item up or down in the listing view.
	 *
	 * @param array  $post The posted data from the form.
	 * @param string $direction The direction to move the item.
	 */
	private function edit_class_move( $post, $direction ) {
		global $wpdb;
		$signup_id = 0;
		$id_cat = array();
		if ( 'up' === $direction ) {
			$id_cat = explode( ',', $post['edit_class_move_up'] );
		} else {
			$id_cat = explode( ',', $signup_id = $post['edit_class_move_down'] );
		}

		$signup_id = $id_cat[0];
		$category  = $id_cat[1];

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_category = %d
				ORDER BY signup_order',
				self::SIGNUPS_TABLE,
				$category
			),
			OBJECT
		);

		$results_count = count( $results );
		for ( $i = 0; $i < $results_count; $i++ ) {
			if ( $results[ $i ]->signup_id === $signup_id ) {
				$update_id = 0;
				$temp = $results[ $i ]->signup_order;
				if ( 'up' === $direction ) {
					$results[ $i ]->signup_order     = $results[ $i - 1 ]->signup_order;
					$results[ $i - 1 ]->signup_order = $temp;
					$update_index                    = $i - 1;
				} else {
					$results[ $i ]->signup_order     = $results[ $i + 1 ]->signup_order;
					$results[ $i + 1 ]->signup_order = $temp;
					$update_index                    = $i + 1;
				}

				$where = array( 'signup_id' => $results[ $i ]->signup_id );
				$data  = array( 'signup_order' => $results[ $i ]->signup_order );
				$wpdb->update( self::SIGNUPS_TABLE, $data, $where );

				$where = array( 'signup_id' => $results[ $update_index ]->signup_id );
				$data  = array( 'signup_order' => $results[ $update_index ]->signup_order );
				$wpdb->update( self::SIGNUPS_TABLE, $data, $where );

				break;
			}
		}

		$this->load_signup_selection();
	}

	/**
	 * Updates the clubs calendar.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function update_calendar( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_id = %s',
				self::SESSIONS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$description = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE description_signup_id = %s',
				self::DESCRIPTIONS_TABLE,
				$post['signup_id']
			),
			OBJECT
		);

		$session         = $results[0];
		$new_calendar_id = 0;
		if ( $session->session_calendar_id > 0 && ! isset( $post['update'] ) ) {
			$where_session = array( 'id' => $session->session_calendar_id );
			$wpdb->delete( self::SPIDER_CALENDAR_EVENT_TABLE, $where_session );
		} else {
			$datetime   = new DateTime( $session->session_start_formatted );
			$date       = $datetime->format( 'Y-m-d' );
			$start_time = $datetime->format( 'g:iA' );
			$datetime   = new DateTime( $session->session_end_formatted );
			$end_time   = $datetime->format( 'g:iA' );
			$signup_url = get_site_url() . '/signups?signup_id=' . $post['signup_id'];

			$text_for_date;
			if ( $description ) {
				$text_for_date = html_entity_decode( $description[0]->description_html_short );
			}
			$text_for_date .= '<br><br><a href=' . $signup_url . " target='_blank' rel='noopener' >Signup</a>.";

			$data                  = array();
			$data['calendar']      = 1;
			$data['date']          = $date;
			$data['date_end']      = $date;
			$data['title']         = $post['signup_name'];
			$data['category']      = 7;
			$data['time']          = $start_time . '-' . $end_time;
			$data['text_for_date'] = $text_for_date;
			$data['userID']        = '';
			$data['repeat_method'] = 'no_repeat';
			$data['repeat']        = '1';
			$data['week']          = '';
			$data['month']         = '';
			$data['month_type']    = '1';
			$data['monthly_list']  = '';
			$data['month_week']    = '';
			$data['year_month']    = '1';
			$data['published']     = 1;

			if ( isset( $post['session_calendar_id'] ) && $post['session_calendar_id'] > 0 ) {
				$where = array( 'id' => $post['session_calendar_id'] );
				$rows  = $wpdb->update( self::SPIDER_CALENDAR_EVENT_TABLE, $data, $where );
				if ( $rows === false ) {
					echo '<h1>Failed to update Calendar id: </h1)' . esc_html( $post['session_calendar_id'] . ' with error : ' . $wpdb->last_error );
				}
				return;

			} else {
				$rows            = $wpdb->insert( self::SPIDER_CALENDAR_EVENT_TABLE, $data );
				$new_calendar_id = $wpdb->insert_id;
			}
		}

		$where                         = array();
		$update                        = array();
		$where['session_id']           = $post['session_id'];
		$update['session_calendar_id'] = $new_calendar_id;
		$affected_row_count            = $wpdb->update(
			'wp_scw_sessions',
			$update,
			$where
		);
	}

	/**
	 * Submit class to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_class( $post ) {
		global $wpdb;
		$where                   = array();
		$where['signup_id']      = (int) $post['id'];
		$original_cost           = $post['original_cost'];
		$signup_default_price_id = $post['signup_default_price_id'];
		$signup_product_id       = $post['signup_product_id'];
		unset( $post['submit_class'] );
		unset( $post['id'] );
		unset( $post['original_cost'] );

		$post['signup_cost']             = (int) $post['signup_cost'];
		$post['signup_default_slots']    = (int) $post['signup_default_slots'];
		$post['signup_rolling_template'] = (int) $post['signup_rolling_template'];
		$post['signup_group']            = 'member' === $post['signup_group'] ? '' : $post['signup_group'];
		//unset( $post['template_id'] );

		$duration_parts = explode( ':', $post['signup_default_duration'] );
		if ( $duration_parts[0] > 12 ) {
			$duration_parts[0]               = $duration_parts[0] - 12;
			$post['signup_default_duration'] = $duration_parts[0] . ':' . $duration_parts[1] . ':' . $duration_parts[2];
		}

		if ( isset( $post['signup_admin_approved'] ) ) {
			$post['signup_admin_approved'] = 1;
			if ( $where['signup_id'] ) {
				$this->add_remove_from_calendar( $where['signup_id'], $post['signup_name'], true );
			}
		} else {
			$post['signup_admin_approved'] = 0;
			if ( $where['signup_id'] ) {
				$this->add_remove_from_calendar( $where['signup_id'], $post['signup_name'], false );
			}
		}

		$affected_row_count = 0;
		$stripe             = new StripePayments();
		if ( $where['signup_id'] ) {
			if ( $original_cost !== $post['signup_cost'] && $signup_product_id ) {
				$new_price_id = $stripe->update_price( $signup_default_price_id, $signup_product_id, $post['signup_cost'] );
				if ( $new_price_id ) {
					$post['signup_default_price_id'] = $new_price_id;
					$this->update_sessions_price_id( $where['signup_id'], $new_price_id );
				} else {
					$post['signup_product_id'] = '';
					$post['signup_default_price_id'] = '0';
				}
			}

			$affected_row_count = $wpdb->update(
				'wp_scw_signups',
				$post,
				$where
			);
		} else {
			$affected_row_count = $wpdb->insert( self::SIGNUPS_TABLE, $post );
			if ( 1 === $affected_row_count ) {
				$data               = array( 'signup_order' => $wpdb->insert_id );
				$where              = array( 'signup_id' => $wpdb->insert_id );
				$affected_row_count = $wpdb->update(
					'wp_scw_signups',
					$data,
					$where
				);
			}
		}

		$this->update_message( $affected_row_count, $wpdb->last_error );
	}

	/**
	 * Removes items from the calendar in respose to removing Admin Approved
	 *
	 * @param  mixed $signup_id The signup id that owns the sessions.
	 * @param  mixed $signup_name The sighup name.
	 * @param  mixed $add To add or remove from the calendar.
	 * @return void
	 */
	private function add_remove_from_calendar( $signup_id, $signup_name, $add ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$signup_id
			),
			OBJECT
		);

		if ( $results ) {
			foreach ( $results as $session ) {
				if ( $session->session_calendar_id || ( ! $session->session_calendar_id && $add ) ) {
					$mini_post = array(
						'signup_id'           => $session->session_signup_id,
						'session_id'          => $session->session_id,
						'signup_name'         => $signup_name,
						'session_calendar_id' => $session->session_calendar_id,
					);

					if ( $add ) {
						$mini_post['update'] = true;
						$this->update_calendar( $mini_post );
					} else {
						$this->update_calendar( $mini_post );
						$where = array( 'session_id' => $session->session_id );
						$data  = array( 'session_calendar_id' => '' );
						$wpdb->update( self::SESSIONS_TABLE, $data, $where );
					}
				}
			}
		}
	}

	/**
	 * Submit session to databse.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_session( $post ) {
		global $wpdb;
		$signup = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_default_price_id
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['session_signup_id']
			),
			OBJECT
		);

		$wpdb->get_results(
			$wpdb->prepare(
				'DELETE
				FROM %1s
				WHERE si_session_id = %s',
				self::SESSION_INSTRUCTORS_TABLE,
				$post['id']
			)
		);

		$count_instructors = 0;
		if ( isset( $post['instructors'] ) ) {
			foreach ( $post['instructors'] as $instructor_id ) {
				$data                     = array();
				$data['si_signup_id']     = (int) $post['session_signup_id'];
				$data['si_session_id']   = (int) $post['id'];
				$data['si_instructor_id'] = (int) $instructor_id;
				$wpdb->insert( self::SESSION_INSTRUCTORS_TABLE, $data );
				$count_instructors++;
			}

			unset( $post['instructors'] );
		}

		?>
		<h2 class="text-center"><?php echo esc_html( $count_instructors . ' instructors were updated.' ); ?></h2>
		<?php
		unset( $post['instructors_id'] );
		unset( $post['instructors_badge'] );
		unset( $post['instructors_name'] );
		unset( $post['instructors_email'] );
		unset( $post['instructors_phone'] );

		$add_to_calendar = isset( $post['add_to_calendar'] );
		unset( $post['add_to_calendar'] );
		array_map(
			function( $start, $end, $keys ) use ( $post, $signup, $add_to_calendar ) {
				global $wpdb;
				if ( $start && $end ) {
					$session = $post;
					$where   = array();

					if ( isset( $session['id'] ) ) {
						$where['session_id'] = $session['id'];
						unset( $session['id'] );
					}

					$signup_name = $session['signup_name'];
					unset( $session['submit_session'] );
					unset( $session['session_add_slots_count'] );
					unset( $session['signup_name'] );
					unset( $session['session_end_repeat'] );
					$rows_updated                       = 0;
					$start_date                         = new DateTime( $start, $this->date_time_zone );
					$end_date                           = new DateTime( $end, $this->date_time_zone );
					$session['session_start_time']      = $start_date->format( 'U' );
					$session['session_start_formatted'] = $start_date->format( self::DATETIME_FORMAT );
					$session['session_end_formatted']   = $end_date->format( self::DATETIME_FORMAT );

					if ( $signup[0]->signup_default_price_id ) {
						$session['session_price_id'] = $signup[0]->signup_default_price_id;
					} else {
						unset( $session['session_price_id'] );
					}

					if ( $where['session_id'] && 0 === $keys ) {
						$rows_updated = $wpdb->update( 'wp_scw_sessions', $session, $where );
						if ( 1 === $rows_updated && $post['session_calendar_id'] ) {
							$mini_post = array(
								'signup_id'           => $post['session_signup_id'],
								'session_id'          => $where['session_id'],
								'signup_name'         => $signup_name,
								'session_calendar_id' => $post['session_calendar_id'],
								'update'              => true,
							);

							$this->update_calendar( $mini_post );
						}
					} else {
						$rows_updated = $wpdb->insert( 'wp_scw_sessions', $session );
						if ( 1 === $rows_updated && $add_to_calendar ) {
							$mini_post = array(
								'signup_id'   => $post['session_signup_id'],
								'session_id'  => $wpdb->insert_id,
								'signup_name' => $signup_name,
							);

							$this->update_calendar( $mini_post );
						}
					}

					$this->update_message( $rows_updated, $wpdb->last_error );
					$first_item = false;
				}
			},
			$post['session_start_formatted'],
			$post['session_end_formatted'],
			array_keys( $post['session_start_formatted'] )
		);
	}

	/**
	 * Edit a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function edit_class( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['edit_class']
			),
			OBJECT
		);
		$this->create_signup_form( $results[0] );
	}

	/**
	 * Confirm a class deletion.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function confirm_class_delete( $post ) {
		global $wpdb;
		$class = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['delete_class']
			),
			OBJECT
		);

		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$post['delete_class']
			),
			OBJECT
		);

		?>
		<div class="container ml-3 mt-3">
			<h1>Delete:  <?php echo esc_html( $class[0]->signup_name ); ?></h1>
			<h2>These sessions will also be deleted.</h2>
			<table class="mb-100px mt-4 table table-striped mr-auto ml-auto">
				<?php
				foreach ( $sessions as $session ) {
					?>
					<tr>
						<td class="w-25"><?php echo esc_html( $class[0]->signup_name ); ?></td>
						<td class="w-25"><?php echo esc_html( $session->session_start_formatted ); ?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<form method="POST">
				<div class="mt-2">
					<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="window.history.go( -0 );" value="Cancel">
					<button class="btn btn-success" type="submit" name="confirm_delete_class" value=<?php echo esc_html( $post['delete_class'] ); ?>>Confirm</button>
				</div>
				<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Delete class and sessions.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_class( $post ) {
		global $wpdb;
		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_signup_id = %s',
				self::SESSIONS_TABLE,
				$post['confirm_delete_class']
			),
			ARRAY_A
		);

		foreach ( $sessions as $session ) {
			$this->delete_session( $session, false );
		}

		$where = array( 'signup_id' => $post['confirm_delete_class'] );
		$wpdb->delete( self::SIGNUPS_TABLE, $where );

		$this->load_signup_selection();
	}

	/**
	 * Add a new session to a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_new_session_form( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$post['add_new_session']
			),
			OBJECT
		);

		$start = new DateTime( 'now' );
		$end   = new DateTime( 'now' );

		if ( $results[0]->signup_default_day_of_month ) {
			$start_date_string = sprintf(
				'%s of %s %s',
				$results[0]->signup_default_day_of_month,
				$start->format( 'F' ),
				$start->format( 'Y' )
			);

			$test_start = strtotime( $start_date_string );
			if ( $test_start ) {
				$start = new DateTime(
					$start_date_string,
					$this->date_time_zone
				);

				$end = new DateTime(
					sprintf(
						'%s of %s %s',
						$results[0]->signup_default_day_of_month,
						$end->format( 'F' ),
						$end->format( 'Y' )
					),
					$this->date_time_zone
				);
			} else {
				?>
				<h1 style="color:red;">Default day of month is not valid: <?php echo esc_html( $results[0]->signup_default_day_of_month ); ?> "</h1>"
				<?php
			}
		}

		$start_time_parts = explode( ':', $results[0]->signup_default_start_time );
		$start->setTime( $start_time_parts[0], $start_time_parts[1] );
		$end->setTime( $start_time_parts[0], $start_time_parts[1] );
		$duration_parts = explode( ':', $results[0]->signup_default_duration );
		$interval       = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
		$end->add( $interval );

		$start_time = array();
		$end_time   = array();

		for ( $i = 0; $i < $results[0]->signup_multiple_days; $i++ ) {
			$start_time[] = $start->format( self::DATETIME_FORMAT_INPUT );
			$end_time[]   = $end->format( self::DATETIME_FORMAT_INPUT );
			$start->modify( '+1 day' );
			$end->modify( '+1 day' );
		}

		$session_item                                = new SessionItem( $post['add_new_session'] );
		$session_item->session_slots                 = $results[0]->signup_default_slots;
		$session_item->session_duration              = $results[0]->signup_default_duration;
		$session_item->session_days_between_sessions = $results[0]->signup_default_days_between_sessions;
		$session_item->session_day_of_month          = $results[0]->signup_default_day_of_month;
		$session_item->session_multiple_days         = $results[0]->signup_multiple_days;
		$session_item->session_contact_name          = $results[0]->signup_default_contact_name;
		$session_item->session_contact_email         = $results[0]->signup_contact_email;
		$session_item->session_location              = $results[0]->signup_location;
		$session_item->session_time_of_day           = $results[0]->signup_default_start_time;
		$session_item->session_start_formatted       = $start_time;
		$session_item->session_end_formatted         = $end_time;
		$session_item->session_signup_id             = $results[0]->signup_id;

		$this->create_session_form( $session_item, $post['signup_name'], null, null );
	}

	/**
	 * Adds slots for a session
	 *
	 * @param  mixed $session_item The session being edited.
	 * @param  mixed $signup_name The name of the signup.
	 * @return void
	 */
	private function add_session_slots( $session_item, $signup_name ) {
		$start_dates      = array();
		$end_dates        = array();
		$duration_parts   = explode( ':', $session_item->session_duration );
		$interval         = new DateInterval( 'PT' . $duration_parts[0] . 'H' . $duration_parts[1] . 'M' );
		$start_time_parts = explode( ':', $session_item->session_time_of_day );
		$today            = new DateTime( 'now', $this->date_time_zone );

		if ( $session_item->session_start_formatted[0] ) {
			$today = new DateTime( $session_item->session_start_formatted[0], $this->date_time_zone );
		}

		$now             = new DateTime( 'now', $this->date_time_zone );
		$end_repeat_date = $session_item->session_end_repeat ? new DateTime( $session_item->session_end_repeat ) : null;

		if ( $session_item->session_end_repeat ) {
			$session_item->session_add_slots_count = 25;
		}

		if ( $session_item->session_day_of_month ) {
			for ( $i = 0; $i < $session_item->session_add_slots_count; $i++ ) {
				$start_date_string = sprintf(
					'%s of %s %s',
					$session_item->session_day_of_month,
					$today->format( 'F' ),
					$today->format( 'Y' )
				);

				$test_start = strtotime( $start_date_string );
				if ( $test_start ) {
					$start = new DateTime(
						$start_date_string,
						$this->date_time_zone
					);

					if ( $start < $now ) {
						$today = $today->modify( '+1 month' );
						$start_date_string = sprintf(
							'%s of %s %s',
							$session_item->session_day_of_month,
							$today->format( 'F' ),
							$today->format( 'Y' )
						);

						$start = new DateTime(
							$start_date_string,
							$this->date_time_zone
						);
					}
				} else {
					?>
					<h1 style="color:red;">Default day of month is not valid: <?php echo esc_html( $results[0]->signup_default_day_of_month ); ?> "</h1>"
					<?php
				}

				$end = clone $start;
				$end->setTime( $start_time_parts[0], $start_time_parts[1] );
				$start->setTime( $start_time_parts[0], $start_time_parts[1] );
				$end->add( $interval );

				if ( $session_item->session_end_repeat && ( $end_repeat_date < $start ) ) {
					break;
				}
				for ( $j = 0; $j < $session_item->session_multiple_days; $j++ ) {
					$start_dates[] = $start->format( self::DATETIME_FORMAT_INPUT );
					$end_dates[]   = $end->format( self::DATETIME_FORMAT_INPUT );
					$start->modify( '+1 day' );
					$end->modify( '+1 day' );
				}

				$today = $today->modify( '+1 month' );
			}
		} else {
			$start_date = new DateTime( $session_item->session_start_formatted[0] );
			$end_date   = new DateTime( $session_item->session_end_formatted[0] );

			for ( $i = 0; $i < $session_item->session_add_slots_count; $i++ ) {
				$start = clone $start_date;
				$end   = clone $end_date;

				if ( $session_item->session_end_repeat && $end_repeat_date < $start ) {
					break;
				}

				for ( $j = 0; $j < $session_item->session_multiple_days; $j++ ) {
					$start_dates[] = $start->format( self::DATETIME_FORMAT_INPUT );
					$end_dates[]   = $end->format( self::DATETIME_FORMAT_INPUT );
					$start->modify( '+1 day' );
					$end->modify( '+1 day' );
				}

				$start_date->modify(
					sprintf(
						'+%s days',
						$session_item->session_days_between_sessions
					)
				);

				$end_date->modify(
					sprintf(
						'+%s days',
						$session_item->session_days_between_sessions
					)
				);
			}
		}

		$session_item->session_start_formatted = $start_dates;
		$session_item->session_end_formatted   = $end_dates;
		$this->create_session_form( $session_item, $signup_name, null, null );
	}

	/**
	 * Edit a session of a class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function edit_session( $post ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE session_id = %s',
				self::SESSIONS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$class_instructors = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE instructors_class_id = %d',
				self::INSTRUCTORS_TABLE,
				$post['signup_id']
			),
			OBJECT
		);

		$session_instructors = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				WHERE si_session_id = %d',
				self::SESSION_INSTRUCTORS_TABLE,
				$post['session_id']
			),
			OBJECT
		);

		$start_date                          = array();
		$dt_start                            = new DateTime( $results[0]->session_start_formatted );
		$start_date[]                        = $dt_start->format( self::DATETIME_FORMAT_INPUT );
		$results[0]->session_start_formatted = $start_date;

		$end_date                          = array();
		$dt_end                            = new DateTime( $results[0]->session_end_formatted );
		$end_date[]                        = $dt_end->format( self::DATETIME_FORMAT_INPUT );
		$results[0]->session_end_formatted = $end_date;

		$this->create_session_form( $results[0], $post['signup_name'], $class_instructors, $session_instructors );
	}

	/**
	 * Delete a session of a class.
	 *
	 * @param int $post The posted data from the form.
	 * @param int $repost This is being reposted.
	 */
	private function delete_session( $post, $repost = true ) {
		global $wpdb;
		$rows_updated = 0;
		$last_errors  = '';
		if ( isset( $post['session_calendar_id'] ) && $post['session_calendar_id'] > 0 ) {
			$where_calendar = array( 'id' => $post['session_calendar_id'] );
			$rows = $wpdb->delete( self::SPIDER_CALENDAR_EVENT_TABLE, $where_calendar );
			if ( $rows ) {
				$rows_updated += $rows;
			} else {
				echo '<h1>Failed to delete calendar entry, id: ' . esc_html( $post['session_calendar_id'] ) . '</h1><br>';
				$last_errors .= $wpdb->last_error;
			}
		}

		$where_session = array( 'session_id' => $post['session_id'] );
		$rows          = $wpdb->delete( self::SESSIONS_TABLE, $where_session );
		if ( $rows ) {
			$rows_updated += $rows;
		} else {
			echo '<h1>Failed to delete session, id: ' . esc_html( $post['session_id'] ) . '</h1><br>';
			$last_errors .= $wpdb->last_error;
		}

		$where_attendees = array( 'attendee_session_id' => $post['session_id'] );
		$rows            = $wpdb->delete( self::ATTENDEES_TABLE, $where_attendees );
		if ( $rows || ( 0 === $rows && '' === $wpdb->last_error ) ) {
			$rows_updated += $rows;
		} else {
			echo '<h1>Failed to delete session, id: ' . esc_html( $post['session_id'] ) . '</h1>';
			$last_errors .= $wpdb->last_error;
		}

		if ( $last_errors ) {
			$this->update_message( $rows_updated, $last_errors );
		} elseif ( $repost ) {
			$repost = array(
				'edit_sessions_signup_id' => $post['signup_id'],
			);
			$this->load_session_selection( $repost );
		}
	}

	/**
	 * Add attendees to a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function add_session_attendees( $post ) {
		global $wpdb;
		$query = '';
		if ( isset( $post['session_id'] ) ) {
			$query = 'SELECT * FROM ' . self::SESSIONS_TABLE . ' WHERE session_id = ' . $post['session_id'];
		} else {
			foreach ( $post['addedAttendee'] as $attendee_session ) {
				$parts = explode( ',', $attendee_session );
				if ( '' === $query ) {
					$query = 'SELECT * FROM ' . self::SESSIONS_TABLE . ' WHERE session_id = ' . $parts[1];
				} else {
					$query .= ' OR session_id = ' . $parts[1];
				}
			}
		}

		$sessions = null;
		if ( '' != $query ) {
			$sessions = $wpdb->get_results( $wpdb->prepare( '%1s', $query ), OBJECT );
		}

		$this->create_attendee_select_form( $post['signup_name'], $post['signup_id'], $sessions );
	}

	/**
	 * Submit attendees to a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function submit_session_attendees( $post ) {
		global $wpdb;
		$sessions = unserialize( $post['sessions'] );
		foreach ( $sessions as $session ) {

			$new_attendee = array(
				'attendee_session_id'   => (int) $session->session_id,
				'attendee_email'        => $post['email'],
				'attendee_firstname'    => $post['firstname'],
				'attendee_lastname'     => $post['lastname'],
				'attendee_phone'        => $post['phone'],
				'attendee_badge'        => $post['badge_number'],
				'attendee_balance_owed' => 0,
				'attendee_item'         => $session->session_item,
			);

			$wpdb->insert( self::ATTENDEES_TABLE, $new_attendee );
		}

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id'],
			$post['signup_id']        => $post['signup_name'],
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Delete attendees from a class session.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function delete_session_attendees( $post ) {
		global $wpdb;
		foreach ( $post['selectedAttendee'] as $attendee ) {
			$attendee_id = explode( ',', $attendee )[0];
			$wpdb->delete( self::ATTENDEES_TABLE, array( 'attendee_id' => $attendee_id ) );
		}

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id'],
		);
		$this->load_session_selection( $repost );
	}

	/**
	 * Move attendees from one class session to another session in the same class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function move_session_attendees( $post ) {
		global $wpdb;
		$ids   = explode( ',', $post['selectedAttendee'][0] );
		$data  = array( 'attendee_session_id' => $post['move_to'] );
		$where = array( 'attendee_id' => $ids[0] );
		$wpdb->update( self::ATTENDEES_TABLE, $data, $where );

		$repost = array(
			'edit_sessions_signup_id' => $post['signup_id'],
		);

		//TODO: Send attendee update email?
		$this->load_session_selection( $repost );
	}

	/**
	 * Load the class selection.
	 */
	private function load_signup_selection() {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_id,
				signup_name,
				signup_category
				FROM %1s
				ORDER BY signup_category, signup_order',
				self::SIGNUPS_TABLE
			),
			OBJECT
		);

		$this->create_signup_select_form( $results );
	}

	/**
	 * Load the session selection form. The session belong to one class.
	 *
	 * @param int $post The posted data from the form.
	 */
	private function load_session_selection( $post ) {
		global $wpdb;
		$signup_id   = $post['edit_sessions_signup_id'];
		$attendees   = array();
		$instructors = array();
		$class       = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT signup_rolling_template, signup_name
				FROM %1s
				WHERE signup_id = %s',
				self::SIGNUPS_TABLE,
				$signup_id
			),
			OBJECT
		);

		$rolling     = $class[0]->signup_rolling_template > 0;
		$signup_name = $class[0]->signup_name;

		if ( $rolling ) {
			$this->create_rolling_session( $signup_id, null, true );
		} else {
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT session_id,
					session_start_formatted,
					session_start_time,
					session_slots,
					session_calendar_id
					FROM %1s
					WHERE session_signup_id = %s
					ORDER BY session_start_time',
					self::SESSIONS_TABLE,
					$post['edit_sessions_signup_id']
				),
				OBJECT
			);

			foreach ( $sessions as $session ) {
				$attendees[ $session->session_id ] = array();
				$session_list                      = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT *
						FROM %1s
						WHERE attendee_session_id = %s
						AND attendee_email != ""',
						self::ATTENDEES_TABLE,
						$session->session_id
					),
					OBJECT
				);

				$session_instructors = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT wp_scw_instructors.instructors_name,
						wp_scw_instructors.instructors_email,
						wp_scw_session_instructors.si_session_id
						FROM wp_scw_session_instructors
						LEFT JOIN wp_scw_instructors ON wp_scw_session_instructors.si_instructor_id = wp_scw_instructors.instructors_id
						WHERE  wp_scw_session_instructors.si_session_id = %d',
						$session->session_id
					),
					OBJECT
				);

				$instructors[ $session->session_id ] = $session_instructors;

				foreach ( $session_list as $attendee ) {
					$attendees[ $session->session_id ][] = $attendee;
				}
			}

			$this->create_session_select_form(
				$signup_name,
				$sessions,
				$attendees,
				$post['edit_sessions_signup_id'],
				$instructors
			);
		}
	}

	/**
	 * Create_attendee_select_form.
	 *
	 * @param mixed  $signup_name The name of the signup.
	 * @param number $signup_id Signup Id.
	 * @param array  $sessions An array fo sessions.
	 * @return void
	 */
	private function create_attendee_select_form( $signup_name, $signup_id, $sessions ) {
		?>
		<form method="POST">
			<div class="text-center mt-5">
				<h1><?php echo esc_html( $signup_name ); ?></h1> <br>
				<h2>Add Attendee</h2>
				<div id="content" class="container">
					<table class="mb-100px mt-4 table table-striped mr-auto ml-auto">
						<?php
						foreach ( $sessions as $session ) {
							?>
							<tr>
								<td class="w-25"><?php echo esc_html( $session->session_item ); ?></td>
								<td class="w-25"><?php echo esc_html( $session->session_start_formatted ); ?></td>
								<td class="w-25"><?php echo esc_html( $signup_name ); ?></td>
								<td class="w-25"></td>
								<td class='w-75px'></td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
					$this->create_user_table( '', $signup_id );
					?>
					<input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back"></td>
					<input id="submit_attendees" class="btn btn-primary mt-2" type="submit" value="Complete Add" name="submit_attendees"><td>
					<input type='hidden' name='sessions' value="<?php echo esc_html( htmlentities( serialize( $sessions ) ) ); ?>" />
					<input type='hidden' name='signup_id' value="<?php echo esc_html( $signup_id ); ?>" />
					<input type='hidden' name='signup_name' value="<?php echo esc_html( $signup_name ); ?>" />
					<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Formats the message to display after an upate to the DB has been made.
	 *
	 * @param  mixed  $rows_updated How many rows were updated in the database.
	 * @param  string $last_error The last db error if it exists.
	 * @return void
	 */
	private function update_message( $rows_updated, $last_error ) {
		if ( $rows_updated >= 1 ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated </h2>
				<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			</div>
			<?php
		} elseif ( '' === $last_error ) {
			?>
			<div class="text-center mt-5">
				<h2> Session Updated, No Change </h2>
			</div>
			<?php
		} else {
			?>
			<div class="text-center mt-5">
				<h2> Error: <?php echo esc_html( $last_error ); ?> </h2>
				<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			</div>
			<?php
		}
		?>
		<div class="text-center mr-2">
			<input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Create the form to select a class to update.
	 *
	 * @param  array $results The class results from teh DB to list on the form.
	 * @return void
	 */
	private function create_signup_select_form( $results ) {
		global $wpdb;
		?>
		<form method="POST">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
			<div id="content" class="container">
				<table class="mb-100px table mr-auto ml-auto mt-5">
					<tr>
						<td>Add SignUp</td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td> <input class="submitbutton addItem" type="submit" name="add_new_class" value=""></td>
					</tr>
					<tr>
						<td>Add SignUp, Sessions and Description</td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td> <input class="submitbutton addItem" type="submit" name="load_description_form" value=""></td>
					</tr>
					<?php
					$category_counts = array();
					foreach ( $results as $result ) {
						if ( array_key_exists( $result->signup_category, $category_counts ) ) {
							$category_counts[ $result->signup_category ]++;
						} else {
							$category_counts[ $result->signup_category ] = 1;
						}
					}

					$count    = 0;
					$category = '';
					foreach ( $results as $result ) {
						if ( $result->signup_category !== $category ) {
							$category   = $result->signup_category;
							$categories = $wpdb->get_results(
								$wpdb->prepare(
									'SELECT *
									FROM %1s
									WHERE category_id = %d',
									self::SIGNUP_CATEGORY_TABLE,
									$category
								),
								OBJECT
							);
							$count = 0;
							?>
							<tr class="bg-lg">
								<td><h4><?php echo esc_html( $categories[0]->category_title ); ?></h4></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td> <?php echo esc_html( $result->signup_name ); ?></td>
							<?php
							if ( $count > 0 ) {
								?>
								<td> <input class="submitbutton upImage" type="submit" name="edit_class_move_up" 
									value="<?php echo esc_html( $result->signup_id . ',' . $result->signup_category ); ?>"> </td>
								<?php
							} else {
								?>
								<td></td>
								<?php
							}

							if ( ( $category_counts[ $result->signup_category ] - 1 ) === $count ) {
								echo '<td></td>';
							} else {
								?>
								<td> <input class="submitbutton downImage mr-3" type="submit" name="edit_class_move_down" 
									value="<?php echo esc_html( $result->signup_id . ',' . $result->signup_category ); ?>"> </td>
								<?php
							}
							?>
							<td> <input class="submitbutton editImage" type="submit" name="edit_class" value="<?php echo esc_html( $result->signup_id ); ?>"> </td>
							<td> <input class="submitbutton sessionsImage mr-3" type="submit" name="edit_sessions_signup_id" value="<?php echo esc_html( $result->signup_id ); ?>"> </td>
							<td> <input class="submitbutton deleteImage" type="submit" name="delete_class" value="<?php echo esc_html( $result->signup_id ); ?>">
								<input type="hidden" name="<?php echo esc_html( $result->signup_id ); ?>" value="<?php echo esc_html( $result->signup_name ); ?>" >
								<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
							</td>
						</tr>
						<?php
						$count++;
					}
					?>
				</table>
			</div>
		</form>
		<?php
	}

	/**
	 * Creates a form that displays the sessions along with their attenees
	 *
	 * @param  string $signup_name The class name.
	 * @param  array  $sessions The list of sessions for the class.
	 * @param  array  $attendees The list of attendees for the class.
	 * @param  int    $signup_id The ID of the class.
	 * @param  array  $instructors An array of instructors for each session.
	 * @return void
	 */
	private function create_session_select_form( $signup_name, $sessions, $attendees, $signup_id, $instructors ) {
		?>
		<div id="session_select" class="text-center mt-2">
			<h1><?php echo esc_html( $signup_name ); ?></h1>
			<div>
				<div id="content" class="container">
					<table id="select_table" class="mb-100px table table-bordered mr-auto ml-auto w-90 mt-25px">
						<form method="POST">
						<tr style="background-color: lightyellow;">
							<td class="text-left" >
								<button class="ml-2 border-0 bg-transparent" type="submit" name="add_new_session" value="<?php echo esc_html( $signup_id ); ?>">
									<b>
										<i>
											<u>Add Session</u>
										</i>
									</b>
								</button>
							</td>
							<td style="width: 200px;"></td>
							<td></td>
							<td></td>
						</tr>
						<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
						<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
						</form>
						<?php
						foreach ( $sessions as $session ) {
							$session_date_time = esc_html( $this->format_date( $session->session_start_formatted ) );
							$email_id          = 'email-session-' . $session->session_id;
							?>
							<form method="POST">
							<tr>
								<td class="text-left"> 
									<?php echo esc_html( $session_date_time ); ?></td>
								<td></td>
								<td class="text-right pr-2"><?php echo $session->session_calendar_id > 0 ? '&#128197' : ''; ?></td>
								<td>
									<div class="popup" data-textid=<?php echo esc_html( 'sessionid' . $session->session_id ); ?> ><b><i><u>Actions</u></i></b>
										<span class="popuptext" id=<?php echo esc_html( 'sessionid' . $session->session_id ); ?> >
											<input class="btn btn-primary w-90 mb-1" 
												type="submit"
												name="edit_session"
												value="Edit Session"> 
											<input class="btn btn-danger w-90 mb-1" 
												type="submit"
												name="delete_session"
												value="Delete Session" 
												onclick="return confirm('Confirm Session Delete')">
											<?php
											if ( count( $attendees[ $session->session_id ] ) < $session->session_slots ) {
												?>
												<input class="btn btn-success w-90" type="submit" name="add_attendee" value="Add Attendee">
												<?php
											}
											?>
											<input  id=<?php echo esc_html( 'move' . $session->session_id ); ?>
												class="btn btn-primary w-90 mb-1 mt-2"
												type="submit"
												name="move_attendees"
												value="Move Selected"
												disabled="true">
											<input class="btn btn-danger w-90 mb-1" 
												type="submit"
												name="delete_attendees"
												value="Delete Selected"
												onclick="return confirm('Confirm Attendee Delete')" >
											<?php
											if ( $session->session_calendar_id > 0 ) {
												?>
												<input class="btn btn-danger w-90 mb-1" type="submit" name="update_calendar" value="Remove From Cal">
												<?php
											} else {
												?>
												<input class="btn btn-success w-90 mb-1" type="submit" name="update_calendar" value="Add To Cal">
												<?php
											}
											?>
											<button class="btn btn-primary w-90 mb-1 email-button" 
												type="button"
												name="email_session"
												value="<?php echo esc_html( $email_id ); ?>">Email Session</button> 
										</span>
									</div>
								</td>
							</tr>
							<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
							<input type="hidden" name="signup_id" value="<?php echo esc_html( $signup_id ); ?>">
							<input type="hidden" name="session_id" value="<?php echo esc_html( $session->session_id ); ?>">
							<input type="hidden" name="session_calendar_id" value="<?php echo esc_html( $session->session_calendar_id ); ?>">
							<input  id=<?php echo esc_html( 'move_to' . $session->session_id ); ?> type="hidden" name="move_to" value="0">
							<?php
							wp_nonce_field( 'signups', 'mynonce' );
							
							foreach ( $attendees[ $session->session_id ] as $attendee ) {
								?>
								<tr class="drag-row" draggable="true" data-dragable="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>" >
									<td> <?php echo esc_html( $attendee->attendee_firstname . ' ' . $attendee->attendee_lastname ); ?></td>
									<td>Attendee</td>
									<td><span class="<?php echo esc_html( $email_id ); ?>"><?php echo esc_html( $attendee->attendee_email ); ?></span></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative selChk" type="checkbox" name="selectedAttendee[]"
										value="<?php $this->session_attendee_string( $attendee->attendee_id, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}

							for ( $i = count( $attendees[ $session->session_id ] ); $i < $session->session_slots; $i++ ) {
								?>
								<tr class="add-attendee-row" data-session-id="<?php echo esc_html( $session->session_id ); ?>" >
									<td class='addAtt'> Add Attendee</td>
									<td><?php echo esc_html( $this->format_date( $session->session_start_formatted ) ); ?></td>
									<td></td>
									<td class="centerCheckBox"> <input class="form-check-input position-relative addChk" type="checkbox" name="addedAttendee[]" value="<?php $this->session_attendee_string( -1, $session->session_id ); ?>"> </td>
								</tr>
								<?php
							}

							if ( $instructors ) {
								foreach ( $instructors[ $session->session_id ] as $instructor ) {
									?>
									<tr class="drag-row bk-lg fw-bold instructor">
										<td class="fw-bold"><?php echo esc_html( $instructor->instructors_name ); ?></td>
										<td>Instructor</td>
										<td><span class="<?php echo esc_html( $email_id ); ?>"><?php echo esc_html( $instructor->instructors_email ); ?></span></td>
										<td></td>
									</tr>
									<?php
								}
							}
							?>
						</form>
							<?php
						}
						?>
				</table>
			</div>
		</div>
		<input class="btn btn-danger mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back">
		</div>
		<?php
	}

	/**
	 * Creates a form used to create a class.
	 *
	 * @param  class $data Raw data retrieved from the data base or an empty class if a new class is being created.
	 * @return void
	 */
	private function create_signup_form( $data ) {
		global $wpdb;
		$url        = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : 'No Ip Address';
		$signup_url  = $url . '/signups/?signup_id=' . $data->signup_id;
		$categories = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM %1s',
				self::SIGNUP_CATEGORY_TABLE
			),
			OBJECT
		);
		?>
		<div class="text-center mb-4">
			<h1><?php echo esc_html( $data->signup_name ); ?> </h1>
		</div>
		<form method="POST" >
			<table class="table table-striped mr-auto ml-auto">
				<tr>
					<td class="text-right">
						<button type="button" id="copy-signup-link" class="btn btn-secondary mr-2 mt-2">Copy URL</button>
					</td>
					<td id="signup-url">
						<?php
						echo esc_html( $signup_url );
						?>
					</td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Class Name:</label></td>
					<td><input class="w-250px" type="text" name="signup_name" value="<?php echo esc_html( $data->signup_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Category:</label></td>
					<td><select name="signup_category">
						<?php
						foreach ( $categories as $category ) {
							?>
							<option value=<?php	echo esc_html( $category->category_id ); ?> 
								<?php echo $category->category_id === $data->signup_category ? 'selected' : ''; ?>
								><?php echo esc_html( $category->category_title ); ?></option>
							<?php
						}
						?>
					</select></td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Email:</label></td>
					<td><input class="w-250px" type="email" name="signup_contact_email" value="<?php echo esc_html( $data->signup_contact_email ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Name:</label></td>
					<td><input class="w-250px" type="text" name="signup_default_contact_name" value="<?php echo esc_html( $data->signup_default_contact_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Location:</label></td>
					<td><input class="w-250px" type="text" name="signup_location" value="<?php echo esc_html( $data->signup_location ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>User Group:</label></td>
					<td><select name="signup_group">
						<option value="">Members</option>
						<option value="cnc" <?php echo 'cnc' === $data->signup_group ? 'selected' : ''; ?> >Cnc Users</option>
						<option value="laser" <?php echo 'laser' === $data->signup_group ? 'selected' : ''; ?> >Laser Users</option>
					</select> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Cost:</label></td>
					<td><input class="w-75px" type="number" name="signup_cost" 
						value="<?php echo esc_html( $data->signup_cost ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Slots:</label></td>
					<td><input class="w-75px" type="number" name="signup_default_slots" 
						value="<?php echo esc_html( $data->signup_default_slots ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Minimum:</label></td>
					<td><input class="w-75px" type="number" name="signup_default_minimum" 
						value="<?php echo esc_html( $data->signup_default_minimum ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Multiple Days per Class:</label></td>
					<td><input class="w-75px" type="number" name="signup_multiple_days" 
						value="<?php echo esc_html( $data->signup_multiple_days ); ?>" min="1" /> </td>
				</tr>

				<tr>
					<td class="text-right mr-2"><label>Default Start Time of Day:</label></td>
					<td><input class="w-125px" type="time" name="signup_default_start_time" placeholder="12:00 AM" 
						value="<?php echo esc_html( $data->signup_default_start_time ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Class Duration:</label></td>
					<td><input id="signup_duration" class="w-125px" type="text" name="signup_default_duration" 
						value="<?php echo esc_html( substr( $data->signup_default_duration, 0, 5 ) ); ?>"
						placeholder="--:--" pattern="[0-9]{1,2}:[0-9]{2}" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Days Between Classes:</label></td>
					<td><input class="w-75px" type="number" name="signup_default_days_between_sessions" 
						value="<?php echo esc_html( $data->signup_default_days_between_sessions ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Default Day of Month:</label></td>
					<td><input class="w-250px" type="text" name="signup_default_day_of_month" 
						value="<?php echo esc_html( $data->signup_default_day_of_month ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Schedule Description:</label></td>
					<td><input class="w-250px" type="text" name="signup_schedule_desc"
						value="<?php echo esc_html( $data->signup_schedule_desc ); ?>"
						placeholder="Leave Blank unless the class doesn't meet regularly" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Rolling Template:</label></td>
					<td>
					<?php
						$this->load_template_selection( $data->signup_rolling_template, false, 'signup_rolling_template' );
					?>
					</td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Admin Approved:</label></td>
					<td><input class="w-75px" type="checkbox" name="signup_admin_approved" value="" 
						<?php echo esc_html( $data->signup_admin_approved ) == '1' ? 'checked ' : ''; ?> /> </td>

				</tr>
				<tr>
					<td class="text-right mr-2"><input class="btn bt-md btn-danger mt-2" style="cursor:pointer;" type="button" onclick="   window.history.go( -0 );" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto mt-2" type="submit" value="Submit" name="submit_class"></td>
				</tr>
			</table>
			<input type="hidden" name="id" value="<?php echo esc_html( $data->signup_id ); ?>">
			<input type="hidden" name="original_cost" value="<?php echo esc_html( $data->signup_cost ); ?>">
			<input type="hidden" name="signup_default_price_id" value="<?php echo esc_html( $data->signup_default_price_id ); ?>">
			<input type="hidden" name="signup_product_id" value="<?php echo esc_html( $data->signup_product_id ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Creates a form used to create a class session.
	 *
	 * @param class  $data Either an empty class or the data that represents the session being updated.
	 * @param string $signup_name The name of the class the session belongs to.
	 * @param mixed  $class_instructors All instructors that teach this class.
	 * @param mixed  $session_instructors Instructors assigned to teach this class.
	 *
	 * @return void
	 */
	private function create_session_form( $data, $signup_name, $class_instructors, $session_instructors ) {
		?>
		<div class="text-center mb-4 mr-100px">
			<h1><?php echo esc_html( $signup_name ); ?></h1>
		</div>
		<form method="POST">
			<table id="session-table" class="table table-striped mr-auto ml-auto">
				<tr>
					<td class="text-right mr-2"><label>Contact Name:</label></td>
					<td><input class="w-250px" type="text" name="session_contact_name" 
						value="<?php echo esc_html( $data->session_contact_name ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Contact Email:</label></td>
					<td><input class="w-250px" type="email" name="session_contact_email" 
						value="<?php echo esc_html( $data->session_contact_email ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Location:</label></td>
					<td><input class="w-250px" type="text" name="session_location" 
						value="<?php echo esc_html( $data->session_location ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Session Item:</label></td>
					<td><input class="w-250px" type="text" name="session_item" 
						value="<?php echo esc_html( $data->session_item ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Slots:</label></td>
					<td><input class="w-250px" type="number" name="session_slots" 
						value="<?php echo esc_html( $data->session_slots ); ?>" /> </td>
				</tr>
				<tr>
					<td class="text-right mr-2"><label>Multiple Days:</label></td>
					<td><input class="w-250px" type="number" name="session_multiple_days" 
						value="<?php echo esc_html( $data->session_multiple_days ); ?>" /> </td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>Start Time: </label></td>
					<td><input id="default-minutes" class="w-250px" type="time" name="session_time_of_day" 
						value="<?php echo esc_html( $data->session_time_of_day ); ?>" 
						<?php echo $data->session_id ? 'disabled' : ''; ?> /> </td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>Duration: </label></td>
					<td><input id="default-minutes" class="w-250px without_ampm" type="time" name="session_duration" 
						value="<?php echo esc_html( $data->session_duration ); ?>" 
						<?php echo $data->session_id ? 'disabled' : ''; ?> /> </td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>Days Between Classes:</label></td>
					<td><input class="w-250px" type="number" name="session_days_between_sessions" 
						value="<?php echo esc_html( $data->session_days_between_sessions ); ?>"
						<?php echo $data->session_id ? 'disabled' : ''; ?> /> </td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>Day of Month:</label></td>
					<td><input class="w-250px" type="text" name="session_day_of_month" 
						value="<?php echo esc_html( $data->session_day_of_month ); ?>" 
						<?php echo $data->session_id ? 'disabled' : ''; ?> /> </td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>End Repeat Date:</label>
					<td><input type="date" class="w-250px" name="session_end_repeat"
						value="<?php echo esc_html( $data->session_end_repeat ); ?>"></td>
				</tr>
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-right mr-2"><label>Add to Calendar:</label></td>
					<td><input class="w-250px mb-2" type="checkbox" name="add_to_calendar" /></td>
				</tr>
				<?php
				$data_items_count = count( $data->session_start_formatted );
				for ( $i = 0; $i < $data_items_count; $i++ ) {
					?>
					<tr>
						<td class="text-right mr-2"><label>Start Time:</label></td>
						<td><input id="start-time" class="w-250px start-time" type="datetime-local" name="session_start_formatted[]" 
							value="<?php echo esc_html( $data->session_start_formatted[ $i ] ); ?>" /> </td>
					</tr>
					<tr>
						<td class="text-right mr-2"><label>End Time:</label></td>
						<td><input id="end-time" class="w-250px" type="datetime-local" name="session_end_formatted[]" 
							value="<?php echo esc_html( $data->session_end_formatted[ $i ] ); ?>" /></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
			if ( $data->session_id && $class_instructors ) {
				?>
				<div id="inst-list" class="instructor-list mt-3 ml-auto mr-auto">
				<div>Badge</div>
				<div>Name</div>
				<div>Email</div>
				<div>Phone</div>
				<div>Add</div>
				<?php
				foreach ( $class_instructors as $instructor ) {
					?>
					<div><input class="w-99" type="text" name="instructors_badge[]" value="<?php echo esc_html( $instructor->instructors_badge ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_name[]" value="<?php echo esc_html( $instructor->instructors_name ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_email[]" value="<?php echo esc_html( $instructor->instructors_email ); ?>"></div>
					<div><input class="w-99" type="text" name="instructors_phone[]" value="<?php echo esc_html( $instructor->instructors_phone ); ?>"></div>
					<?php
					$si_checked = false;
					foreach ( $session_instructors as $si ) {
						if ( $si->si_instructor_id === $instructor->instructors_id ) {
							$si_checked = true;
						}
					}6
					?>
					<div><input class="form-check-input ml-2 add-chk mt-2" type="checkbox" name="instructors[]" 
						value="<?php echo esc_html( $instructor->instructors_id ); ?>" <?php echo $si_checked ? 'checked' : ''; ?>></div>
					<input class="w-99" type="hidden" name="instructors_id[]" value="<?php echo esc_html( $instructor->instructors_id ); ?>">
					<?php
				}
				?>
				</div>
				<?php
			}
			?>
			<table class="mr-auto ml-auto">
				<tr <?php echo $data->session_id ? 'hidden' : ''; ?>>
					<td class="text-center"><button class="btn btn-primary" name="session_add_slots" type="submit" value="1"><b><i>Update Sessions</i></b></button></td>
					<td class="text-right mr-2"><label class="ml-3">Session Count:</label>
						<input class="w-75px" type="number" name="session_add_slots_count" value="1" />
					</td>
				</tr>
				<tr>
					<td>___________________________________</td>
					<td>___________________________________</td>
					<td>___________________________________</td>
				</tr>
				<tr>
					<td colspan='3' class="text-center"><h2>When everthing above apears correct, then submit to the database.</h2></td>
					<td></td>
					<td></td>
				</tr>
				<tr>
					<td class="text-right"><button class="btn bt-md btn-danger mt-2 mr-5" style="cursor:pointer;" type="submit" name="edit_sessions_signup_id"
					value="<?php echo esc_html( $data->session_signup_id ); ?>">Back</button></td>
					<td class="text-left"><input class="btn bt-md btn-success mr-auto ml-auto ml-2" type="submit" value="Submit Session" name="submit_session"></td>
				</tr>
			</table>
			<input type="hidden" name="session_signup_id" value="<?php echo esc_html( $data->session_signup_id ); ?>">
			<input type="hidden" name="session_calendar_id" value="<?php echo esc_html( $data->session_calendar_id ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_html( $data->session_id ); ?>">
			<input type="hidden" name="signup_name" value="<?php echo esc_html( $signup_name ); ?>">
			<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Load the form to create class descriptions.
	 *
	 * @return void
	 */
	private function load_description_form() {
		?>
		<form method="POST" name="template_form" >
		<div class="title-category-box mt-4">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_title">*Title:</label>
			</div>
			<div>
				<input type="text" id="description_title" class="mt-2 w-100" 
					value="" placeholder="Description Title" name="description_title" required>
			</div>
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_title">Category:</label>
			</div>
				<div><select class="mt-2" name="signup_category">
					<?php
					global $wpdb;
					$categories = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT *
							FROM %1s',
							self::SIGNUP_CATEGORY_TABLE
						),
						OBJECT
					);

					foreach ( $categories as $category ) {
						?>
						<option value=<?php	echo esc_html( $category->category_id ); ?> 
							<?php echo $category->category_id === $data->signup_category ? 'selected' : ''; ?>
							><?php echo esc_html( $category->category_title ); ?></option>
						<?php
					}
					?>
				</select>
			</div>
		</div>
		<div class="class-description-box">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_name">*Contact Name:</label>
			</div>
			<div>
				<input type="text" id="description_contact_name" class="mt-2 w-100" 
					value="" placeholder="Contact Names" name="description_contact_name" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_contact_email">*Contact Email:</label>
			</div>
			<div>
				<input type="text" id="description_contact_email" class="mt-2 w-100" 
					value="" placeholder="Contact Email" name="description_contact_email" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_location">*Location:</label>
			</div>
			<div>
				<input type="text" id="description_location" class="mt-2 w-100 without_ampm"
					value="SCW Woodclub" placeholder="Woodshop, library..." name="description_location" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_slots">*Slots:</label>
			</div>
			<div>
				<input type="number" id="description_slots" class="mt-2 w-100 h-2rem" 
					value="3" name="description_slots" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_default_minimum">*Min Attendees:</label>
			</div>
			<div>
				<input type="number" id="signup_default_minimum" class="mt-2 w-100" 
					value="1" name="signup_default_minimum" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_start">*First Session:</label>
			</div>
			<div>
				<input type="datetime-local" id="description_start" class="mt-2 w-100 h-2rem" 
					value="" name="description_start" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_cost">*Cost:</label>
			</div>
			<div>
				<input type="number" id="description_cost" class="mt-2 w-100 h-2rem" 
					value="0" name="description_cost" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_duration">*Duration:</label>
			</div>
			<div>
				<input type="text" id="description_duration" class="mt-2 w-100 without_ampm h-2rem"
					value="2:00" placeholder="--:--" pattern="[0-9]{1,2}:[0-9]{2}" name="description_duration" required>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_Repeat">Repeat:</label>
			</div>
			<div>
				<select id="signup_Repeat" class="mt-2 w-100 h-2rem" name="description_repeat">
					<option value="7">Weekly</option>
					<option value="14">Two Weeks</option>
					<option value="21">Three Weeks</option>
					<option value="31">Monthly</option>
					<option value="0">TBD</option>
				</select>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_group">User Group:</label>
			</div>
			<div>
				<select id="signup_group" class="mt-2 w-100 h-2rem" name="description_group">
					<option value="">Members</option>
					<option value="cnc">Cnc Users</option>
					<option value="laser">Laser Users</option>
				</select>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_multiple_days">Multi Day:</label>
			</div>
			<div>
				<input id="signup_multiple_days" class="mt-2 w-100" name="signup_multiple_days" 
					value="1" >
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="session_end_repeat">End Repeat:</label>
			</div>
			<div>
				<input type="date" id="session_end_repeat" class="mt-2 w-100 h-2rem" 
					value="" placeholder="" name="session_end_repeat">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_admin_approved">Admin Approved:</label>
			</div>
			<div class="text-left ml-2 pt-2"><input type="checkbox" id="signup_admin_approved" class="mt-2"  
				name="signup_admin_approved" /> 
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_add_cal">Add to Calendar:</label>
			</div>
			<div class="text-left ml-2 pt-2"><input type="checkbox" id="description_add_cal" class="mt-2"  
				name="description_add_cal" /> 
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="session_add_slots_count">Repeat Count:</label>
			</div>
			<div>
				<input type="number" id="session_add_slots_count" class="mt-2 w-100" 
					value="1" name="session_add_slots_count" required>
			</div>
		</div>

		<div class="description-box">
			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_instructors">Instructors:</label>
			</div>
			<div>
				<input type="text" id="description_instructors" class="mt-2 w-100" 
					value="" placeholder="Tom, Dick and Harry" name="description_instructors">
			</div>	

			<div class="text-right">
				<label class="label-margin-top mr-2" for="signup_schedule_desc">Schedule:</label>
			</div>
			<div>
				<input type="text" id="signup_schedule_desc" class="mt-2 w-100" 
					value="" placeholder="Leave blank unless the schedule is TBD" name="signup_schedule_desc">
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_prerequisite">Prerequisite:</label>
			</div>
			<div>
				<textarea type="text" id="description_prerequisite" class="mt-2 w-100" 
					value="" placeholder="Prerequisites or none" name="description_prerequisite"></textarea>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_materials">Student Materials:</label>
			</div>
			<div>
				<textarea type="text" id="description_materials" class="mt-2 w-100" 
					value="" placeholder="Wood, glue, ..." name="description_materials"></textarea>
			</div>

			<div class="text-right">
				<label class="label-margin-top mr-2" for="description_instructions">Preclass:</label>
			</div>
			<div>
				<textarea type="text" id="description_instructions" class="mt-2 w-100"
					value="" placeholder="Glue wood in layers..." name="description_instructions"></textarea>
			</div>

			<div class="text-right mt-5">
				<label class="label-margin-top mr-2" for="description_description">Description:</label>
			</div>
			<div class="mt-2">
				<?php
					$name      = 'description_description';
					$content   = 'Complete description of the class. It is recommended creating this in a word processor and then pasting it here.';
					$editor_id = 'description_description';
					$settings  = array(
						'textarea_name' => $name,
					);
					wp_editor( $content, $editor_id, $settings );
					?>
			</div>

			<div></div>
			<div><button type="submit" class="btn btn-md bg-primary mr-auto ml-auto" value="-1" name="submit_description">Submit</button></div>
		</div>
		<?php wp_nonce_field( 'signups', 'mynonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Submit the description to the database.
	 *
	 * @param  mixed $post Data posted from the create form.
	 * @return void
	 */
	private function submit_description( $post ) {
		global $wpdb;
		$new_signup                                = array();
		$new_signup['signup_name']                 = $post['description_title'];
		$new_signup['signup_contact_email']        = $post['description_contact_email'];
		$new_signup['signup_default_contact_name'] = $post['description_contact_name'];
		$new_signup['signup_location']             = $post['description_location'];
		$new_signup['signup_cost']                 = $post['description_cost'];
		$new_signup['signup_default_slots']        = $post['description_slots'];
		$new_signup['signup_rolling_template']     = 0;
		$new_signup['signup_admin_approved']       = isset( $post['signup_admin_approved'] );
		$new_signup['signup_group']                = $post['description_group'];
		$new_signup['signup_schedule_desc']        = $post['signup_schedule_desc'];
		$new_signup['signup_default_minimum']      = $post['signup_default_minimum'];
		$new_signup['signup_category']             = $post['signup_category'];
		$new_signup['signup_multiple_days']        = $post['signup_multiple_days'];

		$start_date                              = new DateTime( $post['description_start'], $this->date_time_zone );
		$new_signup['signup_default_start_time'] = date_format( $start_date, 'H:i' );

		if ( '31' !== $post['description_repeat'] ) {
			$new_signup['signup_default_days_between_sessions'] = $post['description_repeat'];
		} else {
			$new_signup['signup_default_days_between_sessions'] = 0;
			$day  = $start_date->format( 'l' );
			$date = $start_date->format( 'j' );
			$week = intdiv( $date, 7 );
			switch ( $week ) {
				case 0:
					$new_signup['signup_default_day_of_month'] = 'First ' . $day;
					break;
				case 1:
					$new_signup['signup_default_day_of_month'] = 'Second ' . $day;
					break;
				case 2:
					$new_signup['signup_default_day_of_month'] = 'Third ' . $day;
					break;
				case 3:
					$new_signup['signup_default_day_of_month'] = 'Fourth ' . $day;
					break;
				default:
					$new_signup['signup_default_day_of_month'] = 'Last ' . $day;
					break;
			}
		}

		$duration         = new Datetime( $post['description_duration'], $this->date_time_zone );
		$duration_hours   = date_format( $duration, 'h' );
		$duration_minutes = date_format( $duration, 'i' );

		if ( (int) $duration_hours > 12 ) {
			$duration_hours = $duration - 12;
			$duration->modify( '-12 hours' );
		}

		$duration_total_minutes                = $duration_hours * 60 + $duration_minutes;
		$new_signup['signup_default_duration'] = date_format( $duration, 'H:i' );

		if ( $new_signup['signup_cost'] > 0 ) 

		$affected_row_count = $wpdb->insert( self::SIGNUPS_TABLE, $new_signup );
		if ( 1 === $affected_row_count ) {
			$signup_id     = $wpdb->insert_id;
			$data          = null;
			if ( $new_signup['signup_cost'] > 0 ) {
				$stripe        = new StripePayments();
				$product_price = $stripe->create_product( $new_signup['signup_name'], $new_signup['signup_cost'] );
				if ( count( $product_price ) === 2 ) {
					$data = array(
						'signup_order'      => $signup_id,
						'signup_product_id' => $product_price['product_id'],
						'signup_default_price_id'   => $product_price['price_id'],
					);
				} else {
					$data  = array( 'signup_order' => $signup_id );
				}
			} else {
				$data  = array( 'signup_order' => $signup_id );
			}

			if ( $data ) {
				$where = array( 'signup_id' => $signup_id );
				$wpdb->update(
					self::SIGNUPS_TABLE,
					$data,
					$where
				);
			}

			$new_description = array(
				'description_signup_id'    => $signup_id,
				'description_html'         => htmlentities( $post['description_description'] ),
				'description_html_short'   => htmlentities( $post['description_description'] ),
				'description_materials'    => $post['description_materials'],
				'description_prerequisite' => $post['description_prerequisite'],
				'description_instructions' => $post['description_instructions'],
				'description_instructors'  => $post['description_instructors'],
			);

			$affected_row_count = $wpdb->insert( self::DESCRIPTIONS_TABLE, $new_description );
			if ( 1 !== $affected_row_count ) {
				echo '<h1>Failed to Create Description : ' . esc_html( $wpdb->last_error ) . '</h1>';
			} else {
				$new_session                            = array();
				$new_session['session_start_time']      = $start_date->format( 'U' );
				$new_session['session_start_formatted'] = $start_date->format( self::DATETIME_FORMAT );
				$start_date->modify( '+' . $duration_total_minutes . ' minutes' );
				$new_session['session_end_time']              = $start_date->format( 'U' );
				$new_session['session_end_formatted']         = $start_date->format( self::DATETIME_FORMAT );
				$new_session['session_contact_email']         = $post['description_contact_email'];
				$new_session['session_contact_name']          = $post['description_contact_name'];
				$new_session['session_duration']              = date_format( $duration, 'H:i' );
				$new_session['session_slots']                 = $post['description_slots'];
				$new_session['session_item']                  = 'attendee';
				$new_session['session_days_between_sessions'] = $new_signup['signup_default_days_between_sessions'];
				$new_session['session_day_of_month']          = $new_signup['signup_default_day_of_month'];
				$new_session['session_time_of_day']           = $new_signup['signup_default_start_time'];
				$new_session['session_signup_id']             = $signup_id;
				$new_session['session_location']              = $post['description_location'];

				if ( $post['session_add_slots_count'] > 1 || $post['session_end_repeat'] ) {
					$new_session['session_add_slots_count'] = $post['session_add_slots_count'];
					$new_session['session_start_formatted'] = array( 0 => $new_session['session_start_formatted'] );
					$new_session['session_end_formatted']   = array( 0 => $new_session['session_end_formatted'] );
					$new_session['session_multiple_days']   = $post['signup_multiple_days'];
					$new_session['session_end_repeat']      = $post['session_end_repeat'];
					$this->add_session_slots( (object) $new_session, $post['description_title'] );
					return;
				}

				if ( '1' === $post['session_add_slots_count'] ) {
					$affected_row_count = $wpdb->insert( self::SESSIONS_TABLE, $new_session );
					if ( 1 !== $affected_row_count ) {
						echo '<h1>Failed to Create Initial Session : ' . esc_html( $wpdb->last_error ) . '</h1>';
					} else {
						if ( isset( $post['description_add_cal'] ) ) {
							$mini_post = array(
								'signup_id'   => $signup_id,
								'session_id'  => $wpdb->insert_id,
								'signup_name' => $post['description_title'],
							);

							$this->update_calendar( $mini_post );
						}
					}
				}
			}
		}
	}
}
