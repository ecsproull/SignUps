jQuery( document ).ready( function($){
	$("#get_member_button").click(function(){
		var req = $.ajax({
			url: wpApiSettings.root + 'scwmembers/v1/members',
			method: 'GET',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
			},
			data:{
				'badge' : $("#badge-input").val()
			}
		}).done(function (response) {
			if (response.length > 0) {
				$('.member-first-name').each(function () { $(this).val(response[0].firstname); });
				$('.member-last-name').each(function () { $(this).val(response[0].lastname); });
				$('.member-email').each(function () { $(this).val(response[0].email); });
				$('.member-phone').each(function () { $(this).val(response[0].phone);  });
				$('.member-badge').each(function () { $(this).val(response[0].badge);  });
				$("#selection-table").prop("hidden", false);
				$('button[type="submit"]').each(function() {
					$(this).removeAttr('disabled');
				});
				var badgeclass = '.' + response[0].badge;
				//Cookies.set('signups_scw_badge', response[0].badge);
				$('.rolling-remove-chk').prop("hidden", true);
				$(badgeclass).prop("hidden", false);
				$('#submit_attendees').prop("disabled", false);
			} else {
				alert('Badge number not found.')
			}
		}).error(function (response) {
			alert('Error: ' + req.status + ' Verify badge number is correct.');
		});
	});

	$("#badge-input").on('keyup', (e) => { 
		if (e.code === 'Enter' || e.code === 'NumpadEnter') { 
			$("#get_member_button").trigger("click");
		}
	});

	var openPopup = null;
	function closePopup() {
		if ( openPopup ) {
			openPopup.classList.toggle( "show" );
			openPopup = null;
		}
	}

	$( ".popup" ).click( function( e ){
		if (openPopup) {
			closePopup();
			return;
		}
		let id = $( this ).data("textid");
		openPopup = document.getElementById( id );
		openPopup.classList.toggle( "show" );
		e.stopPropagation();
	});

	$( "#session_select").click( function() {
		closePopup();
	});

	$( "#thumbnail" ).change(function( e ){
		$( "#displayThumb" ).attr( "src", e.currentTarget.value );
	});

	$( '#firstname,#lastname,#phone,#email,#badge' ).each( function() {
		$(this).change( function() {
			checkAttendeeValid();
		});
	});

	function checkAttendeeValid() {
		if ( $( '#firstname').val() != '' &&
			$( '#lastname' ).val() != '' &&
			$( '#badge' ).val() != '' &&
			( $( '#phone' ).val() != '' || $( '#email' ).val() != '') ) {
			$( '#submit_attendees' ).prop('disabled', false);
		} else {
			$( '#submit_attendees' ).prop('disabled', true);
		}
	}

	$( '.addChk,.selChk' ).change( function( e ) {
		let to_slot = null;
		let from_slot = null;
		$( '.addChk,.selChk' ).each( function( index, element ) {
			if ( element.checked ) {
				let arr = element.value.split(',');
				if (arr[0] == '-1') {
					if (to_slot || arr[1] == from_slot) {
						to_slot = null;
						disableMoveButton();
						return false;
					} else {
						to_slot = arr[1];
					}
				} else {
					if (from_slot || arr[1] == to_slot) {
						from_slot = null;
						disableMoveButton();
						return false;
					} else {
						from_slot = arr[1]; 
					}
				}
			}
		});

		
		if ( !to_slot || !from_slot ) {
			disableMoveButton();
		} else {
			let selector = "#move" + from_slot;
			let move_butt = $( selector );
			if (!move_butt.length) {
				move_butt = $( '#move' );
			}
			move_butt.prop('disabled', false);

			let selector2 = "#move_to" + from_slot;
			let move_to = $( selector2 );
			if ( !move_to.length ) {
				move_to = $( '#move_to' );
			}
			move_to.val( to_slot );
		}
	});

	function disableMoveButton() {
		$( "input[name=move_attendees]" ).each( function( index, element ) {
			element.disabled = true;
		});
	}

	var lastDragSessionId = -1;
	$( ".drag-row").on( 'dragstart', function(evt) {
		let arr =  evt.target.dataset['dragable'].split(',');
		evt.originalEvent.dataTransfer.setData( "attendee_id", arr[0] );
		evt.originalEvent.dataTransfer.setData( "session_id", arr[1] );
		evt.originalEvent.dataTransfer.setData( "check_box_value",  evt.target.dataset['dragable'] );
		lastDragSessionId = arr[1];
	})

	$( ".add-attendee-row").on( 'dragover', function(evt) {
		if (evt.currentTarget.dataset['sessionId'] != lastDragSessionId ) {
			evt.originalEvent.preventDefault();
		}
	})

	$( ".add-attendee-row").on( 'drop', function(evt) {
		if (confirm( "Confirm Attendee Move") ) {
			$(":checkbox").prop( "checked", false );
			evt.currentTarget.querySelector("input").checked = true;
			let selector = "input[value='" + evt.originalEvent.dataTransfer.getData( "check_box_value" ) + "']";
			let origin_checkbox = $( selector );
			origin_checkbox.prop("checked", true);

			let from_slot = evt.originalEvent.dataTransfer.getData( "session_id" );
			let to_slot = evt.currentTarget.dataset['sessionId'];
			let selector2 = "#move_to" + from_slot;
			let move_to = $( selector2 );
			if ( !move_to.length ) {
				move_to = $( '#move_to' );
			}
			move_to.val( to_slot );

			selector = "#move" + from_slot;
			let move_butt = $( selector );
			if (!move_butt.length) {
				move_butt = $( '#move' );
			}
			move_butt.prop('disabled', false);
			move_butt.trigger('click');
		}

	})

	// Add rolling information to the create class form.
	$( "#rolling-signup" ).change( function( e ) {
		alert(e.currentTarget.value);
	});

	$( "button#add-time-slot" ).click( function( event ) {
		let val_str = $( "button#add-time-slot" ).val();
		$('#session-table').append(
			'<tr>' +
				'<td class="text-right mr-2"><label>Start Time:</label></td>' +
					'<td><input id="start-time' + val_str + '" class="w-250px start-time" type="datetime-local" name="session_start_formatted[]" value="<?php echo esc_html( $start ); ?>" /> </td>' +
				'</tr>' +
				'<tr>' +
					'<td class="text-right mr-2"><label>End Time:</label></td>' +
					'<td><input id="end-time' + val_str + '" class="w-250px" type="datetime-local" name="session_end_formatted[]" value="<?php echo esc_html( $end ); ?>" /> </td>' +
			'</tr>'
		);

		let val_int = parseInt(val_str);
		$( "button#add-time-slot" ).val(++val_int);
	});

	$("#session-table").on("change", ".start-time", function( event ) {
		let minutes = $("#default-minutes").val();
		let start_id = event.target.id;
		let end_id = '#' + start_id.replace(/start/g, "end")
		start_id = '#' + start_id;

		let date = new Date($(start_id).val());
		date.setTime(date.getTime() + parseInt(minutes) * 60 * 1000);
		date.setTime(date.getTime() + -7 * 60 * 60 * 1000);
		let end_date = date.toISOString();
		let pos1 = end_date.indexOf(":");
		let pos2 = end_date.indexOf(":", pos1 + 1);
		end_date = end_date.substring(0, pos2);
		$(end_id).val(end_date);
	});

	$( "#display-html" ).click( function( event ) {
		$("#html-description-display").html($("#html-signup-description").val());
	});

	$("#signup-select").on("change", function (e) {
		document.html_form.submit();
	});

	$("#template-select").on("change", function (e) {
		if (document.template_form) {
			document.template_form.submit();
		}
	});
	
});