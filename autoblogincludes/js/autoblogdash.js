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
	jQuery('.dashchart').each( function(index) {
		var theid = jQuery(this).attr('id');
		theid = theid.replace('feedchart-','');
		autoblogBuildChart( 'feed-' + theid, theid);
	});
}

function autoblogReBuildChartOne() {
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		points: { show: true, barWidth: 1.0 },
		lines: { show: true, barWidth: 1.0 },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartoneticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "nw" }
	  };

	memplot = jQuery.plot(jQuery('#memchartone'), [ {
		data: membershipdata.chartonestats,
		label: membership.signups
	} ], options
	);


}

function autoblogReBuildChartTwo() {
	// Chart two
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.charttwoticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memcharttwo'), [ {
		color: 1,
		data: membershipdata.charttwostats,
		label: membership.members
	} ], options
	);
}

function autoblogReBuildChartThree() {
	// Chart three
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartthreeticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memchartthree'), [ {
		color: 3,
		data: membershipdata.chartthreestats,
		label: membership.members
	} ], options
	);
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