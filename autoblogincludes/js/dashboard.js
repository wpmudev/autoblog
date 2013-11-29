(function($) {
	$(document).ready(function() {
		$('.autoblog-log-feed-url').click(function(e) {
			e.stopPropagation();
			return true;
		});

		$('.autoblog-log-feed > .autoblog-log-row').click(function() {
			var parent = $(this).parent();

			parent.find('.autoblog-log-feed-collapse').toggle();
			parent.find('.slimScrollDiv').toggle();

			return false;
		});

		$('.autoblog-log-feed-records').slimScroll().show();
	});
})(jQuery);