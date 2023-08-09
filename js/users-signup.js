jQuery(document).ready(function($){
	var scw_submitting = 0;
	tinymce.init({
		selector: 'textarea.html-textarea',
		plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss',
		toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
		tinycomments_mode: 'embedded',
		tinycomments_author: 'Author name',
		mergetags_list: [
		  { value: 'First.Name', title: 'First Name' },
		  { value: 'Email', title: 'Email' },
		],
		ai_request: (request, respondWith) => respondWith.string(() => Promise.reject("See docs to implement AI Assistant"))
	});

	$("#get_member_button").click(function(){
		var req = $.ajax({
			url: wpApiSettings.root + 'scwmembers/v1/members',
			method: 'GET',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
			},
			data:{
				'badge' : $("#badge-input").val(),
				'user-groups' : $("#user_groups").val() ? $("#user_groups").val() : 'member'
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
				Cookies.set('signups_scw_badge', response[0].badge);
				$('.rolling-remove-chk').prop("hidden", true);
				$('badgeclass').prop("hidden", false);
			} else {
				alert('Badge number not found.')
			}
		}).error(function (response) {
			alert('Error: ' + req.status + ' Verify badge number is correct.');
		});
	});

	$("#badge-input").on('keydown', (e) => {
		if (e.code === 'Enter' || e.code === 'NumpadEnter') { 
			$("#get_member_button").trigger("click");
			e.preventDefault();
		}
	});

	$("#description_duration").on('keydown', (e) => {
		if(e.which === 8 || e.which === 46 || e.which === 37 || e.which === 39 ) {
			return;
		}

		var val = $("#description_duration").val(); 
		var len = val.length;
		if (len === 2 && !val.includes(':')) {
			$("#description_duration").val(val + ':');
		}

		if (val.includes(':') && e.which == 186) {
			e.preventDefault();
			return;
		}

		if(((e.which < 48 || e.which > 57) && e.which != 186) || len > 4){
			e.preventDefault();
		}
	});

	$(".signup_form").submit(function(e){
		e.preventDefault();
		var form = this;
		var selectedValues = $("input[name='time_slots[]']:checked:enabled").map(function() {
			return this.value;
		}).get();

		var deletedValues = $("input[name='remove_slots[]']:checked:enabled").map(function() {
			return this.value;
		}).get();

		if (selectedValues.length == 0 && deletedValues.length == 0 ) {
			alert("Please select a session!");
			return;
		}
		var selectedSessionsTable = "<table><tr class='text-center font-weight-bold'><td>Start</td><td>End</td><td>Item</td><td>Cost</td></tr>";
		selectedValues.forEach((item) => {
			var arr = item.split(',');
			var inputName = "comment-" + arr[3];

			if ($('input[name=' + inputName + ']').val()) {
				selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>" + $('input[name=' + inputName + ']').val() + "</td></tr>";
			} else {
				if (arr[4] && arr[4] != "0") {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>Cost: $" + arr[4] + "</td></tr>";
				} else {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>NA</td></tr>";
				}
			}

		});

		deletedValues.forEach((item) => {
			var arr = item.split(',');
				selectedSessionsTable += "<tr style='background-color:#FFCCCB;'><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>DELETE</td></tr>";
		});
		selectedSessionsTable += "</table>"

		scw_submitting = 1;
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
		var classname = '.' + arr[2].replace(' ', '');
		if (classname.indexOf("Machine") > 0) {
			var checked = $(".rolling-add-chk:checkbox:checked").length;
			if (checked == 0) {
				$(".rolling-add-chk").attr("disabled", false);
			} else if (checked == 1) {
				$(".rolling-add-chk").attr("disabled", true);
				$(classname).attr("disabled", false);
			}
		}
	});

	$(".back-button").click(function() {
		window.location.href = "https://" + location.hostname;
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

	$(window).on('beforeunload', function(){
		if (scw_submitting) {
			scw_submitting = 0;
			return;
		}
		if ($('.rolling-remove-chk:checkbox:checked').length > 0 ||
		$('.rolling-add-chk:checkbox:checked').length > 0) {
			return 'You have unsaved items, are you sure you want to leave the page?';
		}
	});

	$('.expand-button').click( function(event) {
		var data = $.parseJSON($(this).attr('data-button'));
		if ($(".expand-button").html() == "Show All") {
			$("." + data.session_id).prop("hidden", false);
			$(".expand-button").html("Hide");
		} else {
			$("." + data.session_id).prop("hidden", true);
			$(".expand-button").html("Show All");
		}
	});

	//$(table).find('tbody').append("<tr><td>aaaa</td></tr>");
	
	///// Sripe payment stuff below here. /////
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