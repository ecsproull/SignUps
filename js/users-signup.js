jQuery( document ).ready( function($){
	$( "#get_member_button" ).click( function(){
		var req = $.ajax( {
			url: wpApiSettings.root + 'scwmembers/v1/members',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data:{
				'badge' : jQuery("#badge-input").val()
			}
		} ).done( function ( response ) {
			if ( response.length > 0) {
				$( '.member-first-name' ).each( function () { $(this).val( response[0].firstname ); });
				$( '.member-last-name' ).each( function () { $(this).val( response[0].lastname ); });
				$( '.member-email' ).each( function () { $(this).val( response[0].email ); });
				$( '.member-phone' ).each( function () { $(this).val( response[0].phone );  });
				$( '.member-badge' ).each( function () { $(this).val( response[0].badge );  });
				$("#selection-table").prop("hidden", false);
			} else {
				alert( 'Badge number not found.' )
			}
		} ).error( function ( response ) {
			alert( 'Error: ' + req.status + ' Verify badge number is correct.' );
		});
	});

	$(".signup_form").submit(function(e){
		e.preventDefault();
		var form = this;
		var selectedValues = $("input[name='time_slots[]']:checked:enabled").map(function() {
			return this.value;
		}).get();

		var selectedSessionsTable = "<table><tr class='text-center font-weight-bold'><td>Start</td><td>End</td><td>Item</td><td>Comment</td></tr>";
		selectedValues.forEach((item) => {
			var arr = item.split(',');
			var inputName = "comment-" + arr[3];

			if ($('input[name=' + inputName + ']').val()) {
				selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>" + $('input[name=' + inputName + ']').val() + "</td></tr>";
			} else {
				selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>NA</td></tr>";
			}

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

	$(".rolling-add-chk").click(function(x) {
		var val = x.currentTarget.value;
		var arr = val.split(',');
		var inputName = "comment-" + arr[3];
		if (x.currentTarget.checked) {
			$('input[name=' + inputName + ']').get(0).type = 'text';
		} else {
			$('input[name=' + inputName + ']').get(0).type = 'hidden';
		}
		
	});

	$("#back-button").click(function() {
		window.location.href="http://localhost/wp/signups";
	});

	$("#selection-table input:radio").click (function(e) {
		var arr = e.currentTarget.value.split(',');
		var active_submit_id = "submit_" + arr[3];
		$("#selection-table :submit").each(function(i, s) {
			if (s.id != active_submit_id && s.id != "back-button") {
				s.style.display = 'none';
			} else {
				s.style.display = 'block';
			}
		})

		$("#selection-table :radio").each(function(i, s) {
			if (s.value != e.currentTarget.value) {
				s.checked = false;
			}
		})
	});
});