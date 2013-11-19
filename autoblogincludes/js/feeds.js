(function($) {
	$(document).ready(function() {
		$('select.blog').change(function() {
			var blog = $(this).val();

			if (blog != '') {
				var postbox = $(this).parents('div.postbox');

				postbox.find('select.author').html('<option value="">Loading...</option>');
				postbox.find('select.altauthor').html('<option value="">Loading...</option>');
				postbox.find('select.cat').html('<option value="">Loading...</option>');

				$.getJSON(ajaxurl, {action: 'autoblog-get-blog-authors', id: blog, nocache: new Date().getTime()}, function(ret) {
					var opts = "";
					for (var author in ret.data) {
						opts += "<option value='" + ret.data[author].user_id.toLowerCase() + "'>" + ret.data[author].user_login + "</option>";
					}
					postbox.find('select.altauthor').html(opts);
					opts = '<option value="0">Use feed author</option>' + opts;
					postbox.find('select.author').html(opts);
				});

				$.getJSON(ajaxurl, {action: 'autoblog-get-blog-categories', id: blog, nocache: new Date().getTime()}, function(ret) {
					var opts = "";
					for (var cat in ret.data) {
						opts += "<option value='" + ret.data[cat].term_id.toLowerCase() + "'>" + ret.data[cat].name + "</option>";
					}
					postbox.find('select.cat').html(opts);
				});
			}
		});
	});
})(jQuery);