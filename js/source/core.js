(function($){

	$(document).ready( function() {
		pwpTimeTracker();
	} );


	/**
	 * Premise Time Tracker main object
	 *
	 * @return {object} class for our main object
	 */
	function pwpTimeTracker() {
		// reference elements for efficiency
		var tcIntro = $( '.pwptt-time-card-intro' ),
		tcSearch    = $( '.pwptt-search' ),
		tcWrapper   = $( '#pwptt-body' ),
		tcTime      = $( '.pwptt-time-card-time' ),
		totalHours  = $( '.pwptt-total-hours' ),
		quickChange = $( '#pwptt-quick-change' ),
		// html for loading icon
		loadingIcon = '<p class="pwptt-loading"><i class="fa fa-spin fa-spinner"></i></p>',
		wpajaxurl   = '/wp-admin/admin-ajax.php';

		// run our code
		var init = function() {
			tcSearch.change( doSearch );

			tcSearch.keyup( function( e ) {
				( 13 === e.keyCode ) ? doSearch : false;
			} );

			quickChange.change( function( e ) {
				e.preventDefault();
				// display loading icon
				loading();

				tcSearch.val('');

				$.post( wpajaxurl, {
					action: 'ptt_search_timers',
					quick_change: $(this).val(),
					taxonomy: tcSearch.attr( 'data-tax' ),
					slug: tcSearch.attr( 'data-slug' )
				}, ajaxSearch );

				return false;
			} );
		};

		// do the search
		var doSearch = function( e ) {
			var $this = $(this),
			s         = $this.val(),
			_tax      = $this.attr( 'data-tax' ),
			_slug     = $this.attr( 'data-slug' ),
			_drr      = new RegExp( "^(([0-9]{1,2})/([0-9]{1,2})/([0-9]{2,4})) ?(-) ?(([0-9]{1,2})/([0-9]{1,2})/([0-9]{2,4}))$", "g" ),
			_m        = s.match( _drr );

			if ( '' !== s && null !== _m ) {
				// display loading icon
				loading();
				// prepare our data
				var dr = _m[0].split( '-' ),
				data   = {
					action: 'ptt_search_timers',
					date_range: {
						from: dr[0],
						to: dr[1],
					},
					taxonomy: _tax,
					slug: _slug
				}
				// call the ajax search
				$.post( wpajaxurl, data, ajaxSearch );
			}

			return false;
		};

		// perform the ajax request for the search function
		var ajaxSearch = function(r) {
			tcWrapper.html( r );
			updateTotal();
			return false;
		};

		// updates the total based on the time cards being viewed
		var updateTotal = function() {
			var th = 0.00;
			$( '.pwptt-time-card-time' ).each( function() {
				th = ( parseFloat( $(this).text() ) + parseFloat( th ) );
				totalHours.html( th );
			} );
		};

		init();

		/*
			Helpers
		 */

		function loading() {
			tcWrapper.html( loadingIcon );
		}
	};

})(jQuery);