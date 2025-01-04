jQuery(document).ready(function($){
	var scw_submitting = 0;

	/**
	 * When the "Lookup" button is clicked on a signup form in order to 
	 * look up the member, this function retrieves the member's data from the server.
	 * Duplicate code is in signups.js
	 */
	$("#get_member_button").click(function(){
		var req = $.ajax({
			url: wpApiSettings.root + "scwmembers/v1/members",
			method: "GET",
			beforeSend: function (xhr) {
				xhr.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
			},
			data:{
				"badge" : $("#badge-input").val(),
				"user-groups" : $("#user_groups").val()
			}
		}).done(function (response) {
			if (response.length > 0) {
				$(".member-first-name").each(function () { $(this).val(response[0].member_firstname); });
				$(".member-last-name").each(function () { $(this).val(response[0].member_lastname); });
				$(".member-email").each(function () { $(this).val(response[0].member_email); });
				$(".member-phone").each(function () { $(this).val(response[0].member_phone);  });
				$(".member-badge").each(function () { $(this).val(response[0].member_badge);  });
				$("#user-secret").val(response[0].member_secret);
				$("#selection-table").prop("hidden", false);
				$("button[type='submit']").each(function() {
					$(this).removeAttr("disabled");
				});

				if ($("#remember_me").is(":checked")){
					Cookies.set("signups_scw_badge", response[0].member_badge);
					SetServerCookie(response[0].member_badge);
				} else {
					SetServerCookie("");
					Cookies.set("signups_scw_badge");
				}

				$(".rolling-remove-chk").prop("hidden", true);
				$(".move").prop("hidden", true);
				$(".paid").prop("hidden", false);
				$(".paid." + response[0].member_badge).prop("hidden", true);
				$(".move." + response[0].member_badge).prop("hidden", false);
				
				var daysToCancel = $("#template_days_to_cancel").val();
				var removeChkClass = ".rolling-remove-chk." + response[0].member_badge;
				var currentDate = new Date();
				var newDate = new Date(currentDate);
				newDate.setDate(currentDate.getDate() + Number(daysToCancel));
				$(removeChkClass).each(function() {
					var items = $(this).val().trim().split(",");
					var slotDate = Date.parse(items[0].substr(0,10));
					var cutoffDate = Date.parse(newDate.toDateString());
					if (slotDate > cutoffDate) {
						$(this).prop("hidden", false);
					}
				});

				$("badgeclass").prop("hidden", false);
			} else {
				alert("Badge number not found or Permission for signup denied.")
			}
		}).error(function (response) {
			if (response.status == 400) {
				alert("Error: " + response.status + " Badge Number Not Found.");
			} else if (response.status == 401) {
				alert("Error: " + response.status + " Permission Denied.");
			} else {
				alert("Error: " + response.status + " Unknown Error.");
			}
		});
	});

	/**
	 * In the private Reports page there is a button to email the class.
	 * This handles that click and copies the class emails to the clipboard.
	 */
	$(".instructors-email-class").click( function(e) {
		var email_elements = $("." + $(document.activeElement).val());
		var email_list = "";
		email_elements.each((i, ele) => {
			email_list += ele.innerText + ";";
		});
		navigator.clipboard.writeText(email_list);
		alert ("Email addresses were copied to the clipboard.");
	})

	/**
	 * If the badge input TextBox looses focus it attempts to look up what is
	 * in the edit box. 
	 */
	$("#badge-input").on("blur", (e) => {
		$("#get_member_button").trigger("click");
	});

	/**
	 * Pressing the enter key in the badge input TextBox triggers the lookup action.
	 */
	$("#badge-input").on("keydown", (e) => {
		if (e.code === "Enter" || e.code === "NumpadEnter") { 
			$("#member_button").trigger("click");
			e.preventDefault();
		}
	});

	/**
	 * Currently unused. All users have a unique ID assigned to them
	 * and this id was originally sent to them in an email an allowed
	 * then to edit their signups. This has been relaxed and a user only 
	 * needs their badge number to edit their signups. If more security is 
	 * needed this code can still be used.
	 */
	$("#user-edit-id").on("input", function() {
		var len = $(this).val().length;
		if(len === 32) {
			$("#update-butt").removeAttr("disabled");
			$("#update-butt").removeAttr("hidden");
		} else {
			$("#update-butt").attr("disabled", true);
			$("#update-butt").attr("hidden", true);
		}

	});

	/**
	 * When the users changes the number of rolling days to be shown
	 * in a rolling signup this causes the form to update with the newly 
	 * selected number of days showing.
	 */
	$("#rolling-days").on("change", function(e) {
		e.preventDefault();
		$("<input />").attr("type", "hidden")
				.attr("name", "rolling_days_new")
				.attr("value", this.value)
				.attr("id", "rolling-days-id")
				.appendTo(".signup_form");

		$(".signup_form").submit();
	});

	/**
	 * The submit button on signup forms (Class and Rolling) is handled here to  display
	 * a confirmation popup before the actual submit is done. There are several special 
	 * cases that need to be handled and they are at the top of the function.
	 * In some cases it is one of the email buttons being clicked
	 * and those are just pass through. 
	 */
	$(".signup_form").submit(function(e){
		//debugger;
		e.preventDefault();
		var form = this;
		if (document.activeElement.getAttribute('name') == 'login' ||
	        document.activeElement.getAttribute('name') == 'badge_number') {
			$("#email").append('<input type="hidden" name="login" value="1" />');
			$("<input />").attr("type", "hidden")
				.attr("name", "continue_signup")
				.attr("value", $("#update-butt").val())
				.appendTo(".signup_form");
			form.submit();
			return;
		}

		if (document.activeElement.getAttribute('name') == 'logout') {
			$("#email").append('<input type="hidden" name="logout" value="1" />');
			form.submit();
			return;
		}

		if (document.activeElement.getAttribute('name') == 'email_admin') {
			$("#email").append('<input type="hidden" name="email_admin" value="1" />');
			form.submit();
			return;
		}
		
		if (document.activeElement.getAttribute('name') == 'email_session') {
			$("#email").append('<input type="hidden" name="email_session" value="1" />');
			form.submit();
			return;
		}

		if (document.activeElement.getAttribute('name') == 'signup_home') {
			$("#cancel").append('<input type="hidden" name="signup_home" value="1" />');
			form.submit();
			return;
		}

		if ($("#rolling-days-id").val()) {
			form.submit();
			return;
		}

		if ($('.remove-chk:checkbox:checked').length > 0 ) {
			form.submit();
			return;
		}

		if ($("#update-butt").attr("clicked")) {
			$("#update-butt").removeAttr("clicked")
			$("<input />").attr("type", "hidden")
				.attr("name", "continue_signup")
				.attr("value", $("#update-butt").val())
				.appendTo(".signup_form");
		    form.submit();
			return;
		}
		
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
			var arr = item.split(",");
			var inputName = "comment-" + arr[3];

			if ($("input[name=" + inputName + "]").val()) {
				selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>" + $("input[name=" + inputName + "]").val() + "</td></tr>";
			} else {
				if (arr[4] && arr[4] != "0") {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>Cost: $" + arr[4] + "</td></tr>";
				} else {
					selectedSessionsTable += "<tr><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>NA</td></tr>";
				}
			}

		});

		deletedValues.forEach((item) => {
			var arr = item.split(",");
				selectedSessionsTable += "<tr style='background-color:#FFCCCB;'><td>" + arr[0] + "</td><td>" + arr[1] + "</td><td>" + arr[2] + "</td><td>DELETE</td></tr>";
		});
		selectedSessionsTable += "</table>"

		scw_submitting = 1;
		$("<div style='padding: 10px; max-width: 800px; word-wrap: break-word;'>" + selectedSessionsTable + "</div>").dialog({
			draggable: true,
			modal: true,
			resizable: false,
			width: "auto",
			title: "Confirm Times",
			minHeight: 75,
			buttons: {
			  Submit: function () {
				form.submit();
				$(this).dialog("destroy");
			  },
			  Change: function () {
				$(this).dialog("destroy");
			  }
			}
		  });
	});

	/**
	 * Stores a cookie with the users badge number.
	 * This functionality is on both the admin and user side.
	 * The also contacts the server to store that information there also.
	 */
	$("#remember_me").click(function() {
		var badgeToSet = "";
		if ($("#remember_me").is(":checked")){
			if ($("#badge-input").val()) {
				Cookies.set("signups_scw_badge", $("#badge-input").val());
				badgeToSet = $("#badge-input").val();
			}
		} else {
			Cookies.remove("signups_scw_badge");
		}

		SetServerCookie( badgeToSet );
	})

	/**
	 * Helper function to set a cookie on the server.
	 * @param {*} badgeToSet Badge to set.
	 */
	function SetServerCookie( badgeToSet ) {
		$.ajax({
			url: wpApiSettings.root + "scwmembers/v1/cookies",
			method: "GET",
			beforeSend: function (xhr) {
				xhr.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
			},
			data:{
				"badge" : badgeToSet
			}
		});
	}

	/**
	 * This was meant to enforce rules for signing for various machines.
	 * The goal was to limit someone from hogging a machine by signing up
	 * all of the slots in one day. That plan in currently on hold but the code
	 * remains done and test it we want to enable it again.
	 */
	$(".rolling-add-chk").click(function(x) {
		var val = x.currentTarget.value;
		if (val) {
			var arr = val.split(",");
			var classname = "." + arr[2].replace(" ", "");
			if (classname.indexOf("Machine") > 0) {
				var checked = $(".rolling-add-chk:checkbox:checked").length;
				if (checked > 8) {
					//$(".rolling-add-chk:checkbox:not(:checked)").attr("disabled", true);
					//alert("Only 4 sessions can be selected at a time.");
				} else if (checked == 0) {
					$(".rolling-add-chk").attr("disabled", false);
				} else if (checked > 0 && checked < 4) {
					//$(".rolling-add-chk").attr("disabled", true);
					//$(classname).attr("disabled", false);
				}

				var badge = $("#badge-input").val();
				$(".attendee-row").each(function(i, e) {
					var badgeClass = "." + badge;
					var chillen = $(this).find(badgeClass);
					if (chillen.length === 1) {
						var chk = $(this).find(".form-check-input:visible");
						if (chk.length === 1) {
							//chk.attr("disabled", true);
						}
					}
				});

				//checkUsersEdits();
			} else if (classname.indexOf("Laser") > 0) {
			
				var checked = $(".rolling-add-chk:checkbox:checked").length;
				if (checked > 8) {
					$(".rolling-add-chk:checkbox:not(:checked)").attr("disabled", true);
				} else {
					$(".rolling-add-chk:checkbox:not(:checked)").attr("disabled", false);
				}
			} else {
				var arr = val.split(",");
				var checked = x.currentTarget.checked;
				$(".rolling-add-chk").each(function(i, e) {
					if ($(this).val() === val) {
						return true;
					}
					var x = $(this).val().split(",");
					if (x[0] == arr[0]) {
						if (checked) {
							$(this).attr("disabled", true);
						} else {
							$(this).attr("disabled", false);
						}
					}
				});
			}
		}
	});

	/**
	 * Returns the user to https://scwwoodshop.com
	 */
	$(".back-button").click(function() {
		window.location.href = "https://" + location.hostname;
	});

	/**
	 * Hides or shows the Submit buttons based on session selection.
	 * In reality any of the Submit buttons would submit the form
	 * correctly. When a session selection is made we reduce the confusion
	 * and only show the Submit button for the session selected.
	 */
	$("#selection-table input:radio").click (function(e) {
		var arr = e.currentTarget.value.split(",");
		var active_submit_id = "submit_" + arr[3];
		$("#selection-table :submit").each(function(i, s) {
			if (s.id != active_submit_id && !s.classList.contains("back-button")) {
				s.style.display = "none";
			} else {
				s.style.display = "block";
			}
		})

		$("#selection-table :radio").each(function(i, s) {
			if (s.value != e.currentTarget.value) {
				s.checked = false;
			}
		})

		$(".custom-alert").hide();
	});

	/**
	 * Presents the user with a warning when navigating away from
	 * a Rolling selection form with un-submitted work.
	 */
	$(window).on("beforeunload", function(){
		if (scw_submitting) {
			scw_submitting = 0;
			return;
		}
		if ($(".rolling-remove-chk:checkbox:checked").length > 0 ||
		$(".rolling-add-chk:checkbox:checked").length > 0) {
			return "You have unsaved items, are you sure you want to leave the page?";
		}
	});

	/**
	 * When there are more than 3 members signed up for a class session all but
	 * the first 3 are hidden and a Show All button is displayed. Clicking 
	 * the button toggles the view to show all or hide the extras.
	 */
	$(".expand-button").click( function(event) {
		var data = $(this).attr("data-button");
		if ($("." + data + "-expand-button").html() == "Show All") {
			$("." + data).prop("hidden", false);
			$("." + data + "-expand-button").html("Hide");
		} else {
			$("." + data).prop("hidden", true);
			$("." + data + "-expand-button").html("Show All");
		}
	});

	/**
	 * Currently unused.
	 */
	$("#download").click(function(e){
		download($("#csv_data").val() ,"CncUsers.csv", "text/csv;charset=utf-8;")
	})

	/**
	 * Prevents an attempt to move more than one session at a time.
	 * Also displays a help message to help member move themselves.
	 * 2-b-clear, a member may move themselves between the sessions 
	 * of a class. They can't move to another class as that involves money.
	 */
	$(".move_me").click(function(e) {
		var count = $("input[name='move_me[]']:checked").length;
		if (count > 1) {
			alert("You can only move one session.");
			$(".move_me").prop("checked", false);
			$(".custom-alert").hide();
			return;
		}

		if (1 === count && $("#selection-table input:radio:checked").length === 0) {
			const message = "Select session to move to.";
			const customAlert = document.createElement("div");
			customAlert.className = "custom-alert";
			customAlert.textContent = message;
			document.body.appendChild(customAlert);
		} else {
			$(".custom-alert").hide();
		}
	})

	/**
	 * Currently unused.
	 */
	function download(data, filename, type) {
		var file = new Blob([data], {type: type});
		if (window.navigator.msSaveOrOpenBlob) // IE10+
		  window.navigator.msSaveOrOpenBlob(file, filename);
		else { // Others
		  var a = document.createElement("a"),
			  url = URL.createObjectURL(file);
		  a.href = url;
		  a.download = filename;
		  document.body.appendChild(a);
		  a.click();
		  setTimeout(function() {
			document.body.removeChild(a);
			window.URL.revokeObjectURL(url);  
		  }, 0); 
		}
	}

	/**
	 * Stripe payment stuff below here. This has to do with the IFrame that
	 * is displayed during the payment process.
	 */
	// show shipping address if different
	function showMe() {
		var box = document.getElementById("same");
		var vis = (box.checked) ? "block" : "none";
		document.getElementById("shipping-address").style.display = vis;
	}
	
	// close address section on "next" click
	function closeAddress() {
		var elems = document.querySelector(".collapsible");
		var instances = M.Collapsible.init(elems);
		instances.close(1);
	}
	// open submit section on "next" click
	function openSubmit() {
		var elems = document.querySelector(".collapsible");
		var instances = M.Collapsible.init(elems);
		instances.open(2);
	}
	// credit card iframe styling
	var custom_style = {
		"styles": {
			"base": {
				"color": "grey",
				"border": "1px solid grey",
				"border-top": "none",
				"border-right": "none",
				"border-left": "none",
				"font-weight": "200",
				"font-family": "Arial",
				"padding": "0px",
				"margin-bottom": "5px",
				":focus": {
					"border": "2px solid #4db6ac",
					"border-top": "none",
					"border-right": "none",
					"border-left": "none"
				},
				"::placeholder": {
					"text-transform": "lowercase",
					"color": "#D3D3D3",
					"font-size": "17px"
				}
			},
			"invalid": {
				"color": "#CD5C5C",
				"border-color": "#CD5C5C"
			},
			"valid": {
				"color": "#4db6ac",
				"border-color": "#4db6ac"
			},
			"labels": {
				"base": {
					"color": "gray",
					"font-family": "Arial",
					"font-size": "13px",
					"font-weight": "1",
					"text-transform": "lowercase",
					"padding": "0px",
					"padding-left": "0px"
				}
			},
			"errors": {
				"invalid": {
					"color": "#CD5C5C"
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