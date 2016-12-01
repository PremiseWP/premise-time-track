(function($){

	var pwpTimeTracker = function() {

		var tcIntro = $( '.pwptt-time-card-intro' ),
		tcSearch    = $( '.pwptt-search' ),
		tcWrapper   = $( '#pwptt-body' );

		// bind the enter key on search
		tcSearch.keyup( function( e ) {
			var enterKey = ( 13 === e.keyCode ) ? true : false,
			s = $(this).val();

			if ( enterKey && '' !== s ) {
				// date range regexp
				var drr = new RegExp( "^(([0-9]{2})/([0-9]{2})/([0-9]{2,4})) ?(-) ?(([0-9]{2})/([0-9]{2})/([0-9]{2,4}))$", "g" );
				// check if s is a date range string
				if ( null !== s.match( drr ) ) {
					var dr = s.match( drr )[0].split( '-' ),
					data   = {
						action: 'ptt_search_timers',
						date_range: {
							from: dr[0],
							to: dr[1],
						}
					}

					$.post( '/wp-admin/admin-ajax.php', data, function(r) {
						tcWrapper.html( r );
					} );
				}
			}
			else {

			}
		} );
	};


	$(document).ready( function() {
		pwpTimeTracker();
	} );

})(jQuery);