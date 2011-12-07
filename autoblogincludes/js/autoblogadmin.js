function ab_changeBlog() {

	var blog = jQuery(this).val();

	if(blog != '') {

		ths = jQuery(this);

		jQuery(this).parents('div.postbox').find('select.author').html('<option value="">Loading...</option>');
		jQuery(this).parents('div.postbox').find('select.altauthor').html('<option value="">Loading...</option>');
		jQuery(this).parents('div.postbox').find('select.cat').html('<option value="">Loading...</option>');

		// Call JSON here
		//ajaxurl
		jQuery.getJSON(ajaxurl, {action: '_getblogauthorlist', id: blog, nocache: new Date().getTime() },
		        function(ret){
					//var opts = "<option value=''>Select a League...</option>";
					var opts = "";
					for (author in ret.data) {
						opts += "<option value='" + ret.data[author].user_id.toLowerCase() + "'>" + ret.data[author].user_login + "</option>";
					}
					ths.parents('div.postbox').find('select.altauthor').html(opts);
					opts = '<option value="0">Use feed author</option>' + opts;
					ths.parents('div.postbox').find('select.author').html(opts);
		        });

		jQuery.getJSON(ajaxurl, {action: '_getblogcategorylist', id: blog, nocache: new Date().getTime()},
		        function(ret){
					//var opts = "<option value=''>Select a League...</option>";
					var opts = "";
					for (cat in ret.data) {
						opts += "<option value='" + ret.data[cat].term_id.toLowerCase() + "'>" + ret.data[cat].name + "</option>";
					}
					ths.parents('div.postbox').find('select.cat').html(opts);
		        });

	} else {
	}

}

function ab_niceHeading() {
	tval = jQuery(this).val();
	jQuery(this).parents('div.postbox').find('h3.hndle span').html('Feed : ' + tval);
}

function ab_headings() {
	jQuery('input.title').unbind('change').change(ab_niceHeading);
}

function ab_delfeedcheck() {
	if(confirm(autoblog.deletefeed)) {
		return true;
	} else {
		return false;
	}
}

function ab_processfeedcheck() {
	if(confirm(autoblog.processfeed)) {
		return true;
	} else {
		return false;
	}
}

function toggleallofflist() {

	if(jQuery('#select-all').is(':checked')) {
		jQuery('.selectfeed').each( function() {
			jQuery(this).attr('checked', true);
		});
	} else {
		jQuery('.selectfeed').each( function() {
			jQuery(this).attr('checked', false);
		});
	}
	return true;

}

function ab_adminReady() {

	ab_headings();

	jQuery('select.blog').change(ab_changeBlog);

	jQuery('a.deletefeed').click(ab_delfeedcheck);
	jQuery('a.processfeed').click(ab_processfeedcheck);
	jQuery('input.del').click(ab_delfeedcheck);
	jQuery('input.process').click(ab_processfeedcheck);

	jQuery('#select-all').click(toggleallofflist);

}

jQuery(document).ready(ab_adminReady);