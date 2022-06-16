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
	})
});