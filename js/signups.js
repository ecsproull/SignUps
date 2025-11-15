/**
 * Administrator JS. Only loaded on the administrator side.
 * Some of the functions are duplicated on the user side.
 */
jQuery( document ).ready( function($){
	/**
	 * Fires when the short description is ready.
	 * After the Description and instruction editors are ready 
	 * they need to a have the HTML editor attached to them.
	 */
	$("#description_short").ready(function(){
		loadEditor($("#description_short")[0]);
	});

	$("#description_long").ready(function(){
		loadEditor($("#description_long")[0]);
	});

	$("#description_short").ready(function(){
		loadEditor($("#description_instructions")[0]);
	});

	/**
	 * Does the work of turning a TextArea into an HTML editor.
	 * @param {*} ele The element to attach to.
	 */
	function loadEditor(ele) {
		CKEDITOR.ClassicEditor.create(ele, {
			// https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
			toolbar: {
				items: [
					'exportPDF','exportWord', '|',
					'findAndReplace', 'selectAll', '|',
					'heading', '|',
					'bold', 'italic', 'strikethrough', 'underline', '|',
					'bulletedList', 'numberedList', 'todoList', '|',
					'outdent', 'indent', '|',
					'undo', 'redo',
					'-',
					'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
					'alignment', '|',
					'link', 'uploadImage', 'blockQuote', 'insertTable', 'htmlEmbed', '|',
					'specialCharacters', 'horizontalLine', 'pageBreak', '|',
					'sourceEditing'
				],
				shouldNotGroupWhenFull: true
			},
			// Changing the language of the interface requires loading the language file using the <script> tag.
			// language: 'es',
			list: {
				properties: {
					styles: true,
					startIndex: true,
					reversed: true
				}
			},
			// https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#configuration
			heading: {
				options: [
					{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
					{ model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
					{ model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
					{ model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
					{ model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
					{ model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
					{ model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
				]
			},
			// https://ckeditor.com/docs/ckeditor5/latest/features/editor-placeholder.html#using-the-editor-configuration
			placeholder: '',
			// https://ckeditor.com/docs/ckeditor5/latest/features/font.html#configuring-the-font-family-feature
			fontFamily: {
				options: [
					'default',
					'Arial, Helvetica, sans-serif',
					'Courier New, Courier, monospace',
					'Georgia, serif',
					'Lucida Sans Unicode, Lucida Grande, sans-serif',
					'Tahoma, Geneva, sans-serif',
					'Times New Roman, Times, serif',
					'Trebuchet MS, Helvetica, sans-serif',
					'Verdana, Geneva, sans-serif'
				],
				supportAllValues: true
			},
			// https://ckeditor.com/docs/ckeditor5/latest/features/font.html#configuring-the-font-size-feature
			fontSize: {
				options: [ 10, 12, 14, 'default', 18, 20, 22 ],
				supportAllValues: true
			},
			// Be careful with the setting below. It instructs CKEditor to accept ALL HTML markup.
			// https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#enabling-all-html-features
			htmlSupport: {
				allow: [
					{
						name: /.*/,
						attributes: true,
						classes: true,
						styles: true
					}
				]
			},
			// Be careful with enabling previews
			// https://ckeditor.com/docs/ckeditor5/latest/features/html-embed.html#content-previews
			htmlEmbed: {
				showPreviews: true
			},
			// https://ckeditor.com/docs/ckeditor5/latest/features/link.html#custom-link-attributes-decorators
			link: {
				decorators: {
					addTargetToExternalLinks: true,
					defaultProtocol: 'https://',
					toggleDownloadable: {
						mode: 'manual',
						label: 'Downloadable',
						attributes: {
							download: 'file'
						}
					}
				}
			},
			// The "superbuild" contains more premium features that require additional configuration, disable them below.
			// Do not turn them on unless you read the documentation and know how to configure them and setup the editor.
			removePlugins: [
				// These two are commercial, but you can try them out without registering to a trial.
				// 'ExportPdf',
				// 'ExportWord',
				'AIAssistant',
				//'CKBox',
				'CKFinder',
				//'EasyImage',
				// This sample uses the Base64UploadAdapter to handle image uploads as it requires no configuration.
				// https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/base64-upload-adapter.html
				// Storing images as Base64 is usually a very bad idea.
				// Replace it on production website with other solutions:
				// https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html
				// 'Base64UploadAdapter',
				'RealTimeCollaborativeComments',
				'RealTimeCollaborativeTrackChanges',
				'RealTimeCollaborativeRevisionHistory',
				'PresenceList',
				'Comments',
				'TrackChanges',
				'TrackChangesData',
				'RevisionHistory',
				'Pagination',
				'WProofreader',
				// Careful, with the Mathtype plugin CKEditor will not load when loading this sample
				// from a local file system (file://) - load this site via HTTP server if you enable MathType.
				//'MathType',
				// The following features are part of the Productivity Pack and require additional license.
				'SlashCommand',
				'Template',
				'DocumentOutline',
				'FormatPainter',
				'TableOfContents',
				'PasteFromOfficeEnhanced',
				'CaseChange'
			]
		})
		.catch(error => {
            //console.error(error);
        });

		CKEDITOR.editorConfig = function(config) {
			// Other configurations...
			config.extraPlugins = 'image';
			config.height = '500px';
		};
	}

	/**
	 * Prevents bogus data from being entered into the class duration EditBox.
	 */
	$("#description_duration").on("keydown", (e) => {
		if(e.which === 8 || e.which === 46 || e.which === 37 || e.which === 39 ) {
			return;
		}

		var val = $("#description_duration").val(); 
		var len = val.length;
		if (len === 2 && !val.includes(":")) {
			$("#description_duration").val(val + ":");
		}

		if (val.includes(":") && e.which == 186) {
			e.preventDefault();
			return;
		}

		if(((e.which < 48 || e.which > 57) && e.which != 186) || len > 4){
			e.preventDefault();
		}
	});

	/**
	 * When an administrator wishes to search for a member, this function 
	 * contacts the sever for a results set and then displays that set for
	 * the administrator to choose from. 
	 */
	$("#search_button").click(function(){

		if ($('#search-results').html()) {
			$('#search-results').html("");
		}

		if (!$("#search-input").val()) {
			return;
		}

		$.ajax({
			url: wpApiSettings.root + 'scwmembers/v1/search',
			method: 'GET',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
			},
			data:{
				'text' : $("#search-input").val(),
				'key' : '9523a157-8ee7-5401-9f91-abccea39fe2f'
			}
		}).done(function (response) {
			if (response.length === 1) {
				$('.member-first-name').each(function () { $(this).val(response[0].member_firstname); });
				$('.member-last-name').each(function () { $(this).val(response[0].member_lastname); });
				$('.member-email').each(function () { $(this).val(response[0].member_email); });
				$('.member-phone').each(function () { $(this).val(response[0].member_phone);  });
				$('.member-badge').each(function () { $(this).val(response[0].member_badge);  });
				$('#user-secret').val(response[0].member_secret);
				$("#selection-table").prop("hidden", false);
				$('button[type="submit"]').each(function() {
					$(this).removeAttr('disabled');
				});

	
				$('.rolling-remove-chk').prop("hidden", true);
				$('badgeclass').prop("hidden", false);
			} else if (response.length > 1) {
				response.forEach(function (r,i) {
					var val = r.member_badge + ',' + r.member_firstname + ',' + r.member_lastname + ',' + r.member_email + ',' + r.member_phone;
					$('#search-results').append(
					'<div><button class="btn btn-primary add-instructor-button"type="button" value="' + val + '">Add</button></div>' +
					'<div>' + r.member_badge + '</div>' +
					'<div>' + r.member_firstname + '</div>' +
					'<div>' + r.member_lastname + '</div>' +
					'<div>' + r.member_email + '</div>')
					'<div>' + r.member_phone + '</div>';
				});

				$(".add-instructor-button").click( function(e) {
					var data = $(this).val().split(',');
					if ($('#inst-list').length) {
						$('#inst-list').append(
							'<div><input class="w-99" type="text" name="instructors_badge[]" value="' + data[0] + '"></div>' +
							'<div><input class="w-99" type="text" name="instructors_name[]" value="' + data[1] + ' ' + data[2] + '"></div>' +
							'<div><input class="w-99" type="text" name="instructors_email[]" value="' + data[3] + '"></div>' +
							'<div><input class="w-99" type="text" name="instructors_phone[]" value="' + data[4] + '"></div>' +
							'<div><input class="form-check-input ml-2 remove-chk mt-2" type="checkbox" name="instructors_remove[]"' +
										'value="' + $('.member-badge').val() + '"></div>' +
							'<input type="hidden" name="instructors_id[]" value="">'
						);
					} else {
						$('.member-first-name').each(function () { $(this).val(data[1]); });
						$('.member-last-name').each(function () { $(this).val(data[2]); });
						$('.member-email').each(function () { $(this).val(data[3]); });
						$('.member-phone').each(function () { $(this).val(data[4]);  });
						$('.member-badge').each(function () { $(this).val(data[0]);  });
						$('#search-results').html("");
					}
				});
			} else {
				alert('Search string not found')
			}
		}).error(function (response) {
			if (response.status == 409) {
				alert('Error: ' + response.status + ' Search string must be at least 3 characters. \n Letters, numbers, the @ and a period will be accepted.');
			} else if (response.status == 401) {
				alert('Error: ' + response.status + ' Unauthorized Access.');
			} else {
				alert('Error: ' + response.status + ' Unknown Error.');
			}
		});
	});

	/**
	 * This captures the ENTER key within the badge input edit box
	 * and executes the search.
	 */
	$("#badge-input").on('keyup', (e) => { 
		if (e.code === 'Enter' || e.code === 'NumpadEnter') { 
			$("#get_member_button").trigger("click");
		}
	});

	/**
	 * When editing sessions for a class there is an "Actions"
	 * button. Click on the Actions button opens a popup with several 
	 * menu items. The next three function open and close that popup.
	 */
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

	/**
	 * Enables and disables buttons on the session editor's Actions menu
	 * based on what checkboxes are selected.
	 */
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

	/**
	 * Helper function to disable the Move button.
	 */
	function disableMoveButton() {
		$( "input[name=move_attendees]" ).each( function( index, element ) {
			element.disabled = true;
		});
	}

	/**
	 * Handles the drag start event when an admin drags a member from one session another session.
	 */
	var lastDragSessionId = -1;
	$( ".drag-row").on( 'dragstart', function(evt) {
		let arr =  evt.target.dataset['dragable'].split(',');
		evt.originalEvent.dataTransfer.setData( "attendee_id", arr[0] );
		evt.originalEvent.dataTransfer.setData( "session_id", arr[1] );
		evt.originalEvent.dataTransfer.setData( "check_box_value",  evt.target.dataset['dragable'] );
		lastDragSessionId = arr[1];
	})

	/**
	 * Handled the drag over event during a member drag operation.
	 */
	$( ".add-attendee-row").on( 'dragover', function(evt) {
		if (evt.currentTarget.dataset['sessionId'] != lastDragSessionId ) {
			evt.originalEvent.preventDefault();
		}
	})

	/**
	 * Handles the drop event when dragging and dropping a member into a new session of a class.
	 */
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
			let move_to = $(selector2);
			if (!move_to.length) {
				move_to = $('#move_to');
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

	});

	/**
	 * When adding sessions to a class and you alter any of the 
	 * settings used to auto-generate future sessions, this automatically 
	 * check the "Save Settings" checkbox. If the user doesn't want to save them
	 * they must uncheck this setting.
	 */
	$(".session-setting").on('change', function(e) {
		$(".save-settings").prop( "checked", true );
	});

	/**
	 * When adding sessions to a class and you set the signup repeat to 
	 * something like "2 weeks" this clears the day of the month. Having a
	 * day of the month set would override the "2 weeks" that you just set, so 
	 * we clear it. Id it is set back to Monthly then we enable the day of the month.
	 */
	$("#signup_Repeat").on("change", function(e) {
		if ($(e.target).val() !== '0') {
			$("#day-of-month").val('');
			$("#day-of-month").prop('disabled', true);
		} else {
			$("#day-of-month").prop('disabled', false);
		}
	});

	/**
	 * If you add a day of the month for the class this 
	 * clears the signup repeat.
	 */
	$("#day-of-month").on('change', function(e) {
		if($("#day-of-month").val()) {
			$("#signup_Repeat").val("0");
		}
	});
	

	/**
	 * At the top of each signup's property page there is
	 * a URL for that signup. There is a button that an admin
	 * can you to copy that link. The button click is handled here.
	 */
	$("#copy-signup-link").click( function(e) {
		var copyText = $("#signup-url").text();
		navigator.clipboard.writeText(copyText);
   });

	/**
	 * On the instructors editor form, if you change the class selection 
	 * this causes the form to reload with the correct instructors.
	 */
	$("#select_class").change(function(e) {
		$("#reload").val("1");
		$("#instructors-form").submit();
	});

	/**
	 * Appears this is dead code. TODO: Verify and remove.
	 */
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

	/**
	 * On the instructors editor form, after searching and loading an instructors information,
	 * clicking the Add Instructor button will fire this. The instructors information will be 
	 * added to the form for submission.
	 */
	$("#add-instructor").click( function(e) {
		$('#inst-list').append(
			'<div><input class="w-99" type="text" name="instructors_badge[]" value="' + $('.member-badge').val() + '"></div>' +
			'<div><input class="w-99" type="text" name="instructors_name[]" value="' + $('.member-first-name').val() + ' ' + $('.member-last-name').val() + '"></div>' +
			'<div><input class="w-99" type="text" name="instructors_email[]" value="' + $('.member-email').val() + '"></div>' +
			'<div><input class="w-99" type="text" name="instructors_phone[]" value="' + $('.member-phone').val() + '"></div>' +
			'<div><input class="form-check-input ml-2 remove-chk mt-2" type="checkbox" name="instructors_remove[]"' +
						'value="' + $('.member-badge').val() + '"></div>' +
			'<input type="hidden" name="instructors_id[]" value="">'
		);
	});

	/**
	 * In the edit session form there is a button to email the session
	 * attendees and instructors. Clicking that button will copy the list of
	 * email address to the clipboard for use in you favorite email app.
	 */
	$(".email-butt").click( function(e) {
		var emailClass = '.' + e.target.value;
		var emailId = '#' + e.target.value;
		var emailAddresses = '';
		$(emailClass).each( function() {
			emailAddresses += $(this).text() + ';';
		});

		const htmlContent = $(emailId).html();
		const blob = new Blob([htmlContent], { type: "text/html" });
		const clipboardItem = new ClipboardItem({ "text/html": blob });
		navigator.clipboard.write([clipboardItem])
		document.location.href = "mailto:" + emailAddresses + "?subject=" +  $("#signup_name").text();
	});

	/**
	 * When the signup selection is changed the form saves changes
	 * and then reloads with the data for the newly selected signup.
	 */
	$("#signup-select").on("change", function (e) {
		document.html_form.submit();
	});

	/**
	 * When a different template is selected in the Template Editor
	 * the form reloads with the data for the newly selected template.
	 */
	$("#template-select").on("change", function (e) {
		if (document.template_form) {
			document.template_form.submit();
		}
	});

	/**
	 * Adds a new row to the Template Editor.
	 */
	$('.add-template-row').click( function() {
		$('.template-table').find('tbody').append(
			"<tr>" +
				"<td ><input class='w-125px' type='text' name='template_item_day_of_week[]' value=''></td>" +
				"<td><input type='text' name='template_item_title[]' value=''></td>" +
				"<td><input class='w-75px' type='number' name='template_item_slots[]' value='1'></td>" +
				"<td><input class='w-125px' type='text' name='template_item_start_time[]' value=''></td>" +
				"<td><input class='w-125px' type='text' name='template_item_duration[]' value='00:00'></td>" +
				"<td><input class='w-75px' type='number' name='template_item_shifts[]' value='1'></td>" +
				"<td><input class='w-75px' type='number' name='template_item_column[]' value='1'></td>" +
				"<td><input class='w-75px' type='text' name='template_item_group[]' value='A'></td>" +
				"<input class='w-75px' type='hidden' name='template_item_id[]' value='-1'>" +
			"</tr>"
		);
	});

	/**
	 * Processes the click event when a different editor is selected in the 
	 * HTML Editor.
	 */
	$('.nav-link').click( function (e) {
		if (e.currentTarget.innerText == "Description") {
			$('#html-signup-description').show();
			$('#html-signup-description-short').hide();
			$('#html-signup-instructions').hide();
			$('.inst').css("background-color", "#F8F8F8");
			$('.short-desc').css("background-color", "#F8F8F8");
			$('.long-desc').css("background-color", "#BEBEBE");
		} else if (e.currentTarget.innerText == "Calendar") {
            $('#html-signup-description').hide();
			$('#html-signup-description-short').show();
			$('#html-signup-instructions').hide();
			$('.inst').css("background-color", "#F8F8F8");
			$('.short-desc').css("background-color", "#BEBEBE");
			$('.long-desc').css("background-color", "#F8F8F8");
		} if (e.currentTarget.innerText == "Instructions") {
			$('#html-signup-instructions').show();
            $('#html-signup-description').hide();
			$('#html-signup-description-short').hide();
			$('.inst').css("background-color", "#BEBEBE");
			$('.short-desc').css("background-color", "#F8F8F8");
			$('.long-desc').css("background-color", "#F8F8F8");
		}
	})

	/**
	 * Called when the Start DateTime Picker is changed in the 
	 * Rolling Exceptions Editor.
	 */
	$(".datetime-picker-start").on("change", function(e){
		var key =  $(e.target).attr('key');
		var time = $(".datetime-picker-start[key=" + key + "]").val();
		var len = time.length;
		time = time.substring(0, len -2) + "00"
		$(".datetime-picker-start[key=" + key + "]").val(time);
		$(".datetime-picker-end[key=" + key + "]").val(time);
	})

	/**
	 * Called when the End DateTime Picker is changed in the 
	 * Rolling Exceptions Editor.
	 */
	$(".datetime-picker-end").on("change", function(e){
		var key =  $(e.target).attr('key');
		var time = $(".datetime-picker-end[key=" + key + "]").val();
		var len = time.length;
		time = time.substring(0, len -2) + "00"
		$(".datetime-picker-end[key=" + key + "]").val(time);
	})

	/**
	 * When adding sessions to a signup you can set the Start Time
	 * in two different places. In the upper half of the form or in the
	 * first item in the list of sessions to be added. 
	 * The next two function keep these two in sync.  
	 */	
	$("#start-time").on("change", function(e) {
		$("#start-time-0").val($("#start-time").val());
	});
	
	$("#start-time-0").on("change", function(e) {
		$("#start-time").val($("#start-time-0").val());
	});

	/**
	 * When there are more than three slots in a signup list, 
	 * only the first 3 are visible. This allows the user to expand
	 * and see the full list. 
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
});

jQuery(function ($) {
  // Read preloaded JSON
  var mdMap = {};
  var el = document.getElementById('md-items-json');
  if (el && el.textContent) {
    try { mdMap = JSON.parse(el.textContent); } catch (e) { mdMap = {}; }
  }

  var $body = $('#md-items-body');

  function addRow(prefill) {
    prefill = prefill || {};
    var daysAfter = (typeof prefill.days_after !== 'undefined') ? prefill.days_after : 0;
    var timeOfDay = prefill.time_of_day || '08:00';
    var duration  = prefill.duration  || '01:00';

	if ($('#orig-date').length) {
		var rowHtml =
		'<tr class="md-item-row">' +
			'<td><input type="number" name="md_days_after[]" class="w-125px" min="0" step="1" value="' + daysAfter + '" required /></td>' +
			'<td><input type="time" name="md_time_of_day[]" class="w-150px" step="60" value="' + timeOfDay + '" required /></td>' +
			'<td><input type="time" name="md_duration[]" class="w-150px without_ampm" step="60" value="' + duration + '" required /></td>' +
			'<td><div class="md-start-display font-weight-bold mt-1"></div></td>' +
			'<td class="text-center"><button type="button" class="btn btn-danger md-remove-row" title="Remove">&minus;</button></td>' +
		'</tr>';

		$body.append(rowHtml);
	} else {
		var rowHtml =
		'<tr class="md-item-row">' +
			'<td><input type="number" name="md_days_after[]" class="w-125px" min="0" step="1" value="' + daysAfter + '" required /></td>' +
			'<td><input type="time" name="md_time_of_day[]" class="w-150px" step="60" value="' + timeOfDay + '" required /></td>' +
			'<td><input type="time" name="md_duration[]" class="w-150px without_ampm" step="60" value="' + duration + '" required /></td>' +
			'<td class="text-center"><button type="button" class="btn btn-danger md-remove-row" title="Remove">&minus;</button></td>' +
		'</tr>';

		$body.append(rowHtml);
	}
  }

  // Populate rows when dropdown changes
  $(document).on('change', '#signup-select', function () {
    var id = String($(this).val() || '');
    var items = mdMap[id] || [];

    $body.empty();
    if (items.length === 0) {
      addRow(); // default single row
    } else {
      items.forEach(function (it) { addRow(it); });
    }
  });

  // Add/remove handlers (if not already present)
  $(document).on('click', '.md-add-row', function (e) {
    e.preventDefault();
    addRow();
  });
  $(document).on('click', '.md-remove-row', function (e) {
    e.preventDefault();
    $(this).closest('tr').remove();
  });

  var $baseEl = $('#orig-date');
    var baseStr = ($baseEl.length ? $baseEl.text().trim() : '').replace(/\s+/g,' ');
    // Parse "YYYY-MM-DD h:mm AM/PM"
    function parseBase(str) {
        var m = str.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!m) return null;
        var Y = +m[1], Mo = +m[2]-1, D = +m[3], h = +m[4], mi = +m[5], ap = m[6].toUpperCase();
        if (ap === 'PM' && h < 12) h += 12;
        if (ap === 'AM' && h === 12) h = 0;
        return new Date(Y, Mo, D, h, mi, 0, 0);
    }
    var baseDate = parseBase(baseStr);

    function format12(dt) {
        var Y = dt.getFullYear();
        var M = String(dt.getMonth()+1).padStart(2,'0');
        var D = String(dt.getDate()).padStart(2,'0');
        var h24 = dt.getHours();
        var ap = h24 >= 12 ? 'PM' : 'AM';
        var h12 = h24 % 12; if (h12 === 0) h12 = 12;
        var mi = String(dt.getMinutes()).padStart(2,'0');
        return Y + '-' + M + '-' + D + ' ' + h12 + ':' + mi + ' ' + ap;
    }

    function computeRow($tr) {
        if (!baseDate) return;
        var daysVal = parseInt($tr.find('input[name="md_days_after[]"]').val(), 10);
        if (isNaN(daysVal)) daysVal = 0;
        var timeVal = $tr.find('input[name="md_time_of_day[]"]').val(); // "HH:MM"
        if (!timeVal || !/^\d{2}:\d{2}$/.test(timeVal)) {
            $tr.find('.md-start-display').text('');
            return;
        }
        var parts = timeVal.split(':');
        var h = +parts[0], m = +parts[1];
        var dt = new Date(baseDate.getTime());
        dt.setDate(dt.getDate() + daysVal);
        dt.setHours(h, m, 0, 0);
        $tr.find('.md-start-display').text(format12(dt));
    }

    function computeAll() {
        $('#md-items-body tr.md-item-row').each(function(){ computeRow($(this)); });
    }

    // Initial compute
    computeAll();

    // Recompute on changes
    $(document).on('input change', 'input[name="md_days_after[]"], input[name="md_time_of_day[]"]', function () {
        computeRow($(this).closest('tr.md-item-row'));
    });

    // When adding a new row (hook your existing add-row logic)
    $(document).on('click', '.md-add-row', function () {
        // Defer to allow the row to be appended by existing code
        setTimeout(function () {
            var $last = $('#md-items-body tr.md-item-row').last();
            computeRow($last);
        }, 0);
    });

    // When removing rows, you might optionally recompute others (not required)
    $(document).on('click', '.md-remove-row', function () {
        setTimeout(computeAll, 0);
    });
});