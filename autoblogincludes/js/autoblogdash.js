function autoblogSetWidth() {

	jQuer('.dashchart').each( function(index) {
		var width = jQuery(this).parents('div.inner').width();
		jQuery(this).width((width - 10) + 'px');
	});

}

function autoblogReBuildCharts() {
	memReBuildCharts();
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

	memSetWidth();
	memReBuildCharts();

	jQuery(window).resize( function() {
		memSetWidth();
		memReBuildCharts();
	});

}


jQuery(document).ready(autoblogReportReady);