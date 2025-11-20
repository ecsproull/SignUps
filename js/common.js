jQuery(document).ready(function($){
	let isClicked = false;

	/**
	 * When the "Lookup" button is clicked on a signup form in order to 
	 * look up the member, this function retrieves the member's data from the server.
	 * Duplicate code is in signups.js
	 */
	$("#get_member_button").click(function(){
		var inputVal = $("#badge-input").val();
        var isValid = /^\d{4}$/.test(inputVal);
		if (!isValid || isClicked ) {
			return;
		}

		grecaptcha.execute($("#token_key").val(), {action: "homepage"}).then(function(token) {
			//console.log( "refreshed token:", token );
		
			isClicked = true;
			var req = $.ajax({
				url: wpApiSettings.root + "scwmembers/v1/members",
				method: "GET",
				beforeSend: function (xhr) {
					xhr.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
				},
				data:{
					"badge" : $("#badge-input").val(),
					"user-groups" : $("#user_groups").val(),
					"token" : token
				}
			}).done(function (response) {
				if (response.length > 0) {
					$(".member-first-name").each(function () { $(this).val(response[0].member_firstname); });
					$(".member-last-name").each(function () { $(this).val(response[0].member_lastname); });
					$(".member-badge").each(function () { $(this).val(response[0].member_badge);  });
					$("#selection-table").prop("hidden", false);
					$("button[type='submit']").each(function() {
						$(this).removeAttr("disabled");
					});

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

					if ($("#signups_attendee").length) {
						window.location.href = window.localStorage.href;
					}

					$("#logout-button").prop("hidden", false)
					$("#get_member_button").prop("hidden", true)

					if ( response[1] ) {
						Cookies.set("signups_scw_badge", response[0].member_badge);
					}

					$("#continue-to-signup").prop("hidden", false);
					isClicked = false;
				} else {
					alert("Badge number not found or Permission for signup denied.")
					isClicked = false;
				}
			}).error(function (response) {
				if (response.status == 400) {
					alert("Error: " + response.status + " Badge Number Not Found.");
				} else if (response.status == 401) {
					alert("Error: " + response.status + " Permission Denied.");
				} else {
					alert("Error: " + response.status + " Unknown Error.");
				}
				isClicked = false;
				window.location.href = window.location.href;
			});
		});
	});

    /**
	 * Stores a cookie with the users badge number.
	 * This functionality is on both the admin and user side.
	 * If an admin triggered this the admin
	 * would be logged out.
	 */
	$("#logout-button").click(function() {
		Cookies.remove("signups_scw_badge");
		location.reload();
	})
	
	const route = wpApiSettings.root + 'scwmembers/v1/get-photo?badge=';
	let openPhotoPopupId = null;
    $(document).on('click', '.member-photo-btn', async function(){
        const $btn    = $(this);
        const badge   = $btn.data('badge');
        const session = $btn.data('session');
        const popupId = $btn.data('popup-id');
        const $popup  = $('#' + popupId);
        if(!$popup.length) return;

         if (openPhotoPopupId && openPhotoPopupId !== popupId) {
			$('#' + openPhotoPopupId).prop('hidden', true).empty();
			openPhotoPopupId = null;
		}

		// Toggle same popup
		if(!$popup.prop('hidden')){
			$popup.prop('hidden', true).empty();
			openPhotoPopupId = null;
			return;
		}

        $popup.prop('hidden', false).html('<em>Loading...</em>');
		openPhotoPopupId = popupId;
        try {
            const resp = await fetch(route + encodeURIComponent(badge), {
                credentials: 'same-origin',
                headers: { 'X-WP-Nonce': (window.wpApiSettings && wpApiSettings.nonce) ? wpApiSettings.nonce : '' }
            });
            if(!resp.ok){ $popup.html('<span style="color:red;">Not found</span>'); return; }
            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);
            $popup.html('<img alt="Photo '+badge+'" src="'+url+'">');
        } catch(e){
            $popup.html('<span style="color:red;">Error</span>');
        }
    });

   $(document).on('click', function(e){
		if(!$(e.target).closest('.member-photo-btn, .member-photo-popup').length){
			if (openPhotoPopupId) {
				$('#' + openPhotoPopupId).prop('hidden', true).empty();
				openPhotoPopupId = null;
			}
		}
	});
});