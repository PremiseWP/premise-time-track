
(function($){

	$(document).ready(function(){
		PremiseTimeTrack.init();
	});

	var PremiseTimeTrack = {


		months: ['01','02','03','04','05','06','07','08','09','10','11','12'],
		//months: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],


		fields: null,




		timestampStart: null,




		start: null,




		date: null,




		timestampStop: null,




		stop: null,




		timer: null,




		publish: null,



		startBtn: null,



		stopBtn: null,




		init: function(){
			this.startBtn = $('.ptt-start-btn') ? $('.ptt-start-btn') : null;
			this.stopBtn  = $('.ptt-stop-btn')  ? $('.ptt-stop-btn')  : null;

			this.resetTimer();

			this.bindEvents();
		},




		bindEvents: function() {

			this.startBtn.click(this.startTimer);
			this.stopBtn.click(this.stopTimer);

			// this.start.change(this.recordTime);
			// this.stop.change(this.recordTime);

			this.timeField.keyup(this.inputTime);
			// this.timeField.change(this.checkTimeString);
			// this.stop.keyup(this.validateTime);

			$('.ptt-new-timer').click(this.newTimer);

			$('.ptt-delete-time-history').click(function(){
				console.log('hi')
				$(this).parent().slideToggle();
			});
		},



		startTimer: function(){
			var self = PremiseTimeTrack.resetTimer();


			// Check if time stamp has already been recorded
			if ( self.timestampStart.val() ||
				'' !== self.timestampStart.val() ) {
				var check = confirm('Are you sure you want to override the current time stamp?');
				if ( ! check ) {
					return false;
				}
			}

			var timeStamp = new Date(),
			M = self.months[timeStamp.getMonth()],
			D = timeStamp.getDate(),
			Y = timeStamp.getFullYear(),
			h = timeStamp.getHours(),
			m = "0" + timeStamp.getMinutes(),
			s = "0" + timeStamp.getSeconds();
			
			var start = h + ":" + m.substr(-2);
			var date = M + "/" + D + "/" + Y;
			
			self.timestampStart.val(timeStamp);
			self.start.val(start);
			self.date.val(date);

			self.stop.val('');
			self.timestampStop.val('');

			self.saveTimer();
			
			return false;
		},




		stopTimer: function(){
			var self = PremiseTimeTrack.resetTimer();

			if ( '' == self.start.val() ) {
				alert('You must start the timer first or fill in the \'From\' field.');
				return false;
			}

			var timeStamp = new Date(),
			h = timeStamp.getHours(),
			m = "0" + timeStamp.getMinutes(),
			s = "0" + timeStamp.getSeconds();
			
			var stop = h + ":" + m.substr(-2);
			var start = self.start.val().split(':');

			var hDiff = +h - +start[0];
			
			var mDiff = +m - +start[1];


			var time = hDiff + "h " + mDiff + "m";

			self.timestampStop.val(timeStamp);
			self.stop.val(stop);
			
			self.recordTime();
			
			self.saveTimer();
			
			return false;
		},



		recordTime: function( start, stop ) {
			var self = PremiseTimeTrack;//.resetTimer();

			stop = stop || '00:00';
			if ( '' !== stop ) {
				stop = stop.split(':');
				stop[0] = '00' == stop[0] ? 24 : stop[0];
			}
			else {
				self.timer.val('');
				return false;
			}

			start = start || '00:00';
			if ( '' !== start ) {
				start = start.split(':');
			}
			else {
				self.timer.val('');
				return false;
			}

			if ( stop[0] >= start[0] ) {
				var hours = ( stop[0] - start[0] ) * 60;
				var minutes = stop[1] - start[1];
				var total = +hours + +minutes

				if ( 0 > total ) {
					// self.stop.val('');
					return '';
				}
				else {
					var timer = (Math.ceil( ( +total / 60 ) * 4) / 4).toFixed(2);
					return timer;
				}
			}
			else {
				// self.stop.val('');
				// self.timer.val('');
				return '';
			}

			return '';
		},



		validateTime: function( time, el ){
			time = time || '';

			var type = $(el).is('.ptt-start') ? 'start' : 'stop';

			var s = time,
			c     = s.length;
			
			// remove any that is not numeric or a colon
			var r = new RegExp(/[^0-9:]/g);
			s     = s.replace( r, '' );

			// prevent hours from being higher than 24
			if ( 2 == c && 2 == s.length ) {
				if ( 24 > +s ) {
					$(el).val(s+':');
					$(el).focusout(PremiseTimeTrack.checkTimeString);
				}
				else {
					$(el).val('00:');
					$(el).focusout(PremiseTimeTrack.checkTimeString);
				}
				return false;
			}
			// prevent minutes being higher than 60
			if ( 5 == c && 5 == s.length ) {
				var m = s.substr(-2);
				if ( 60 > +m ) {
					$(el).val(s);
				}
				else {
					$(el).val( s.substr(0,3) + "00" );
				}

				if ( 'stop' == type ){
					var start = $(el).parents('.ptt-fields-wrapper').find('.ptt-start').val();
					if ( '' !== start ) {
						var t = $(el).parents('.ptt-fields-wrapper').find('.ptt-timer-field');
						t.val( self.recordTime( start, s ) );
					}
				}

				if ( 'start' == type ){
					var stop = $(el).parents('.ptt-fields-wrapper').find('.ptt-stop').val();
					if ( '' !== stop ) {
						var t = $(el).parents('.ptt-fields-wrapper').find('.ptt-timer-field');
						t.val( self.recordTime( s, stop ) );
					}
				}
				return false;
			}
			// prevent more than 5 chars (12:35)
			if ( 5 < c ) {
				$(el).val(s.substr(0,5));
				return  false;
			}

			$(el).val(s);
		},



		inputTime: function() {
			var self = PremiseTimeTrack;//.resetTimer();

			var time = $(this).val();

			self.validateTime(time, this);
			// self.recordTime();
		},




		checkTimeString: function( time ) {
			str = time || '00:00';

			if( 5 >= str.length ) {

				if ( 3 == str.length ) {
					$(this).val(str+"00");
				}
				else if ( 4 == str.length ) {
					var hours = str.substr(0,3);
					var _mins = '0' + str.substr(-2);
					$(this).val(hours+mins);
					console.log($(this).val());
				}
				else {
					var start = $(this).parents('.ptt-fields-wrapper').find('.ptt-start').val();
					if ( '' !== start ) {
						var t = $(this).parents('.ptt-fields-wrapper').find('.ptt-timer-field');
						t.val( PremiseTimeTrack.recordTime( start, str ) );
					}
				}
			}
			return false;
		},




		newTimer: function(){
			var self = PremiseTimeTrack.resetTimer(this);
			var count = self.countUp();
			$('.ptt-time-history').append( '<div class="ptt-fields-wrapper ptt-time-history-'+count+'"></div>' );

			var _fields = self.fields.clone();

			$('.ptt-time-history-'+count).append( _fields );

			var data = {
				action: 'ptt_new_timer',
				count: self.historyCount
			}

			$.post(ajaxurl, data, function(resp){
				$('.ptt-timer-fields').html(resp);
			}).
			done(function(){
				self.resetTimer(this);
				self.timeField.keyup(self.validateTime);
			});
			


			// bind time fields again
			console.log(self.timeField);

			return false;
		},



		saveTimer: function(){
			$('#publish').click();
		},



		resetTimer: function() {
			self = PremiseTimeTrack;

			self.historyCount = $('.ptt-time-history > div').length;

			self.fields         = $('.ptt-timer').find('.ptt-timer-fields > .premise-field');
			self.timestampStart = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-timestamp_start');
			self.start          = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-start');
			self.date           = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-date');
			self.timestampStop  = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-timestamp_stop');
			self.stop           = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-stop');
			self.timer          = $('.ptt-timer').find('#ptt_meta-timers-'+self.historyCount+'-timer');

			self.timeField      = $('.ptt-time-field'); // register in the DOM again for

			return self;
		},



		countUp: function() {
			return self.historyCount+1;
		}

	};
})(jQuery);