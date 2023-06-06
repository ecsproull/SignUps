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
				if (arr[4]) {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>Cost: $" + arr[4] + "</td></tr>";
				} else {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>NA</td></tr>";
				}
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

	$(".back-button").click(function() {
		window.location.href="http://localhost/wp/signups";
	});

	$("#selection-table input:radio").click (function(e) {
		var arr = e.currentTarget.value.split(',');
		var active_submit_id = "submit_" + arr[3];
		$("#selection-table :submit").each(function(i, s) {
			if (s.id != active_submit_id && !s.classList.contains('back-button')) {
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

	// show shipping address if different
	function showMe() {
		var box = document.getElementById('same');
		var vis = (box.checked) ? "block" : "none";
		document.getElementById('shipping-address').style.display = vis;
	}
	
	// close address section on "next" click
	function closeAddress() {
		var elems = document.querySelector('.collapsible');
		var instances = M.Collapsible.init(elems);
		instances.close(1);
	}
	// open submit section on "next" click
	function openSubmit() {
		var elems = document.querySelector('.collapsible');
		var instances = M.Collapsible.init(elems);
		instances.open(2);
	}
	// credit card iframe styling
	var custom_style = {
		'styles': {
			'base': {
				'color': 'grey',
				'border': '1px solid grey',
				'border-top': 'none',
				'border-right': 'none',
				'border-left': 'none',
				'font-weight': '200',
				'font-family': 'Arial',
				'padding': '0px',
				'margin-bottom': '5px',
				':focus': {
					'border': '2px solid #4db6ac',
					'border-top': 'none',
					'border-right': 'none',
					'border-left': 'none'
				},
				'::placeholder': {
					'text-transform': 'lowercase',
					'color': '#D3D3D3',
					'font-size': '17px'
				}
			},
			'invalid': {
				'color': '#CD5C5C',
				'border-color': '#CD5C5C'
			},
			'valid': {
				'color': '#4db6ac',
				'border-color': '#4db6ac'
			},
			'labels': {
				'base': {
					'color': 'gray',
					'font-family': 'Arial',
					'font-size': '13px',
					'font-weight': '1',
					'text-transform': 'lowercase',
					'padding': '0px',
					'padding-left': '0px'
				}
			},
			'errors': {
				'invalid': {
					'color': '#CD5C5C'
				}
			}
		}
	};

	var options = {
		custom_style: custom_style,
		show_labels: true,
		show_placeholders: true,
		show_error_messages: true,
		show_error_messages_when_unfocused: true
	};
});