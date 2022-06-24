jQuery( document ).ready( function($){
	$( "#get_member_button" ).click( function(){
		var req = $.ajax( {
			url: wpApiSettings.root + 'scwmembers/v1/members',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data:{
				'badge' : jQuery("#badge_input").val()
			}
		} ).done( function ( response ) {
			if ( response.length > 0) {
				$( '#firstname' ).val( response[0].firstname );
				$( '#lastname' ).val( response[0].lastname );
				$( '#phone' ).val( response[0].phone );
				$( '#email' ).val( response[0].email );
				$( '#badge' ).val( response[0].badge ); 
				checkAttendeeValid();
			} else {
				alert( 'Badge number not found.' )
			}
		} ).error( function ( response ) {
			alert( 'Error: ' + req.status + ' Verify badge number is correct.' );
		});
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
		var id = $( this ).data("textid");
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
		var to_slot = null;
		var from_slot = null;
		$( '.addChk,.selChk' ).each( function( index, element ) {
			if ( element.checked ) {
				var arr = element.value.split(',');
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
			var selector = "#move" + from_slot;
			var move_butt = $( selector );
			if (!move_butt.length) {
				move_butt = $( '#move' );
			}
			move_butt.prop('disabled', false);

			var selector2 = "#move_to" + from_slot;
			var move_to = $( selector2 );
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
		var arr =  evt.target.dataset['dragable'].split(',');
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
			var selector = "input[value='" + evt.originalEvent.dataTransfer.getData( "check_box_value" ) + "']";
			var origin_checkbox = $( selector );
			origin_checkbox.prop("checked", true);

			var from_slot = evt.originalEvent.dataTransfer.getData( "session_id" );
			var to_slot = evt.currentTarget.dataset['sessionId'];
			var selector2 = "#move_to" + from_slot;
			var move_to = $( selector2 );
			if ( !move_to.length ) {
				move_to = $( '#move_to' );
			}
			move_to.val( to_slot );

			var selector = "#move" + from_slot;
			var move_butt = $( selector );
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
});