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
		var id = $( this ).data("textid");
		closePopup();
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
			$( selector ).prop('disabled', false);

			var selector2 = "#move_to" + from_slot;
			$( selector2 ).val( to_slot );
		}
	});

	function disableMoveButton() {
		$( "input[name=move_attendees]" ).each( function( index, element ) {
			element.disabled = true;
		});
	}
});