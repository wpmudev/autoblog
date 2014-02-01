(function($) {
	$(document).ready(function() {
		var file_frame;

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

		$('#abtble_posttype').change(function() {
			var post_type = $(this).val();

			$('#abtble_feedcatsare option').each(function() {
				var $this = $(this),
					objects = $this.attr('data-objects');

				if (objects) {
					if ( objects.split(',').indexOf(post_type) < 0 ) {
						$this.attr('disabled', 'disabled');
					} else {
						$this.removeAttr('disabled');
					}
				}
			});

			$("#abtble_feedcatsare").val($('#abtble_feedcatsare option[value]:not(:disabled):first').val());
		});

		$('#featureddefault_select').click(function(e) {
			var $this = $(this);

			e.preventDefault();

			if (file_frame) {
				file_frame.open();
				return;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				title: autoblog.fileframe.title,
				button: {text: autoblog.fileframe.button},
				multiple: false
			});

			file_frame.on('select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON(),
					td = $this.parents('td');

				td.find('input').val(attachment.id);
				td.find('img').attr('src', attachment.url);
			});

			file_frame.open();
		});

		$('#featureddefault_delete').click(function() {
			var td = $(this).parents('td');

			td.find('input').val('');
			td.find('img').attr('src', '');
		});
	});
})(jQuery);