(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Populate the users and refresh when you click.
		conf_sch_populate_users();
		$( '.conf-sch-refresh-users' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_users();
			return false;
		});

	});

	// Populate the users field.
	function conf_sch_populate_users() {

		// Set the <select> and disable.
		var $users_select = $( '#conf-sch-users' );

		// Only if the select exists.
		if ( 0 == $users_select.length ) {
			return;
		}

		// Disable the select until it loads.
		$users_select.prop( 'disabled', 'disabled' );

		// Reset the <select>.
		$users_select.empty();

		// Add the default/blank option.
		$users_select.append( '<option value="">' + $users_select.data( 'default' ) + '</option>' );

		// Get the users information.
		$.ajax( {
			url: ajaxurl,
			type: 'GET',
			dataType: 'json',
			async: true,
			cache: false,
			data: {
				action: 'conf_sch_get_users',
				speaker_post_id: $( '#post_ID' ).val(),
			},
			success: function( user_data ) {

				// Make sure we have users info.
				if ( undefined === user_data.users || 'object' != typeof user_data.users || user_data.users.length == 0 ) {
					return false;
				}

				// Get the selected user ID.
				var selected_user_id = 0;
				if ( undefined !== user_data.selected && user_data.selected > 0 ) {
					selected_user_id = user_data.selected;
				}

				// Add the options.
				$.each( user_data.users, function( index, value ) {

					// Build the user option.
					var user_option = $( '<option value="' + value.ID + '">' + value.data.display_name + ' (' + value.data.user_login + ')</option>' );

					// Mark as selected.
					if ( selected_user_id == value.ID ) {
						user_option.attr( 'selected', true );
					}

					// Add to select field.
					$users_select.append( user_option );

				});

				// Enable the select.
				$users_select.prop( 'disabled', false );

			}
		});

	}

})( jQuery );