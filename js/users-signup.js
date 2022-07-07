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
				$( '#first_name' ).val( response[0].firstname );
				$( '#last_name' ).val( response[0].lastname );
				$( '#email' ).val( response[0].email );
				$( '#phone' ).val( response[0].phone ); 
				$("#selection-table").prop("hidden", false);
			} else {
				alert( 'Badge number not found.' )
			}
		} ).error( function ( response ) {
			alert( 'Error: ' + req.status + ' Verify badge number is correct.' );
		});
	});

	$("#rolling_form").submit(function(e){
		e.preventDefault();
		var form = this;
		var selectedValues = $("input[name='time_slots[]']:checked:enabled",'#rolling_form').map(function() {
			return this.value;
		}).get();

		var selectedSessionsTable = "<table><tr class='text-center font-weight-bold'><td>Start</td><td>End</td><td>Item</td><td>Comment</td></tr>";
		selectedValues.forEach((item) => {
			var arr = item.split(',');
			var inputName = "comment-" + arr[3];
			$('input[name=' + inputName + ']').value
			selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>" + $('input[name=' + inputName + ']').val() + "</td></tr>";

		});
		selectedSessionsTable += "</table>"

		$('<div style="padding: 10px; max-width: 800px; word-wrap: break-word;">' + selectedSessionsTable + '</div>').dialog({
			draggable: true,
			modal: true,
			resizable: false,
			width: 'auto',
			title: 'Confirm Times',
			minHeight: 75,
			buttons: {
			  Submit: function () {
				form.submit();
				$(this).dialog('destroy');
			  },
			  Change: function () {
				$(this).dialog('destroy');
			  }
			}
		  });
	});

	$(".form-check-input").click(function(x) {
		var val = x.currentTarget.value;
		var arr = val.split(',');
		var inputName = "comment-" + arr[3];
		if (x.currentTarget.checked) {
			$('input[name=' + inputName + ']').get(0).type = 'text';
		} else {
			$('input[name=' + inputName + ']').get(0).type = 'hidden';
		}
		
	});
});