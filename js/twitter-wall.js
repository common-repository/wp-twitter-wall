(function($) {
	if ( document.getElementById( 'twitter-wall-2' ) ) {
		var $wall = $('#twitter-wall-2');
		if ( $('body').hasClass( 'twitterwall-full' ) ) {
			var lastTweetId;

			$wall.imagesLoaded( function() {
				$wall.isotope({
					// options
					itemSelector: 'li',
					layoutMode: 'masonry'
				});
			});

			var times;
			var tweetReload = setInterval( function() {
				lastTweetId = $wall.find('li:first-child').attr('data-id');
				times = {};
				$wall.find('li').each( function(){
					times[ $(this).attr('data-id') ] = $(this).attr('data-time');
				});
				$.ajax({
					url:ajaxUrl,
					method:'POST',
					data:{
						action:'twitterwall.get-tweets',
						since_id:lastTweetId,
						dates:times
					},
					success:function( data ) {
						if ( data.success && data.data.tweets ) {
							$newtweets = $( data.data.tweets );
							$wall.prepend( $newtweets ).imagesLoaded().always( function() {
								$wall.isotope( 'prepended', $newtweets ).isotope('layout');
							});
						}
						if ( data.success && data.data.times ) {
							for ( var i in data.data.times ) {
								$wall.find('li[data-id="' + i + '"] .date').text( ' – ' + data.data.times[i] );
							}
						}
					}
				});
			}, 30000 );
		} else {
			var lastTweetId;
			var times;
			var tweetReload = setInterval( function() {
				lastTweetId = $wall.find('li:first-child').attr('data-id');
				times = {};
				$wall.find('li').each( function(){
					times[ $(this).attr('data-id') ] = $(this).attr('data-time');
				});
				$.ajax({
					url:ajaxUrl,
					method:'POST',
					data:{
						action:'twitterwall.get-tweets',
						since_id:lastTweetId,
						dates:times
					},
					success:function( data ) {
						if ( data.success && data.data.tweets ) {
							$( data.data.tweets ).prependTo( $wall );
						}
						if ( data.success && data.data.times ) {
							for ( var i in data.data.times ) {
								$wall.find('li[data-id="' + i + '"] .date').text( ' – ' + data.data.times[i] );
							}
						}
					}
				});
			}, 60000 );
		}

		if ( typeof 'TWActions' != 'undefined' ) {
			$(document).on('click', 'span[data-user]', function() {
				var user = $(this).attr('data-user');
				if ( confirm( TWActions.confirm.replace( '%s', user ) ) ) {
					$.ajax({
						url:ajaxUrl,
						method:'POST',
						data:{
							action:'twitterwall.report_as_spam',
							toSpam:user,
							nonce:TWActions.nonce,
						},
						success:function(data) {
							if ( data.success ) {
								$('span[data-user="' + user + '"]').parents('li').remove();
							}
							$wall.isotope('layout');
						}
					});
				}
			});
		}

		$(document).on('click', '.rp-button', function() {
			var hashtags = '';
			var twitterUrl = 'https://twitter.com/intent/tweet?hashtags=' + hashtags + '&in_reply_to=';
			twitterUrl += $(this).parent().parent('li').attr('data-id');
			window.open(twitterUrl);
		});

		$(document).on('click', '.rt-button', function() {
			var twitterUrl = 'https://twitter.com/intent/retweet?tweet_id=';
			twitterUrl += $(this).parent().parent('li').attr('data-id');
			window.open(twitterUrl);
		});
	}
})(jQuery);