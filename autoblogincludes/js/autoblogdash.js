function autoblogSetWidth() {

	jQuery('.dashchart').each( function(index) {
		var width = jQuery(this).parents('div.inside').width();
		jQuery(this).width((width - 10) + 'px');
	});

}

function autoblogBuildChart( thedata, theid ) {

	alert(thedata);

	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		points: { show: true, barWidth: 1.0 },
		lines: { show: true, barWidth: 1.0 },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: autoblogdata.ticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "nw" }
	  };

		autoplot = jQuery.plot(jQuery('#feedchart-' + theid), [ {
			data: eval('autoblogdata.feed-' + theid),
			label: autoblog.imports
		} ], options
		);


}

function autoblogReBuildCharts() {

	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: autoblogdata.ticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: false,
		    position: "ne" }
	  };

	for(n=0; n < autoblogdata.feeds.length; n++) {

		var autoplot = jQuery.plot(jQuery('#feedchart-' + autoblogdata.feeds[n][0]), [ {
			color: 3,
			data: autoblogdata.feeds[n][1],
			label: autoblog.imports
		} ], options
		);

	}

}

function autoblogReportReady() {

	autoblogSetWidth();
	autoblogReBuildCharts();

	jQuery(window).resize( function() {
		autoblogSetWidth();
		autoblogReBuildCharts();
	});

}


jQuery(document).ready(autoblogReportReady);