google.load('visualization', '1', {packages: ['corechart']});
google.setOnLoadCallback(autoblogDrawChart);

function autoblogDrawChart() {
	var date, chart, table, i, today, stamp, imports, errors, processed;

	today = new Date();

	stamp = Date.parse(autoblog.date);
	if (!isNaN(stamp)) {
		today.setTime(stamp);
	}

	today.setDate(today.getDate() - 6);

	table = new google.visualization.DataTable();

	table.addColumn('date', autoblog.date_column);
	table.addColumn('number', autoblog.processes_column);
	table.addColumn('number', autoblog.imports_column);
	table.addColumn('number', autoblog.errors_column);

	for (i = 1; i <= 7; i++) {
		imports = errors = processed = 0;

		date = jQuery('#autoblog-log-date-' + today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate());

		date.find('.autoblog-log-feed-imports').each(function() { imports += parseInt(jQuery(this).text()) });
		date.find('.autoblog-log-feed-iterations').each(function() { processed += parseInt(jQuery(this).text()) });
		date.find('.autoblog-log-feed-errors').each(function() { errors = parseInt(jQuery(this).text()) });

		table.addRow([new Date(today.getFullYear(), today.getMonth(), today.getDate()), processed, imports, errors]);
		today.setDate(today.getDate() + 1);
	}

	chart = new google.visualization.ColumnChart(document.getElementById('autoblog-dashboard-chart'));
	chart.draw(table, {
		chartArea: {
			left: '5%',
			top: '10%',
			width: '90%',
			height: '70%'
		},
		vAxis: {
			format: '#,###',
			textPosition: 'in',
			minorGridlines: {
				count: 4
			},
			viewWindow: {
				min: 0
			}
		},
		hAxis: {
			baselineColor: 'white'
		},
		dataOpacity: 0.9,
		focusTarget: 'category',
		colors: ['green', '#01b1f3', 'red'],
		legend: {
			position: 'bottom',
			alignment: 'center'
		}
	});
}

(function($) {
	$(document).ready(function() {
		$('.autoblog-log-feed > .autoblog-log-row').click(function() {
			var parent = $(this).parent(), rows, height;

			parent.find('.autoblog-log-feed-collapse').toggle();
			parent.find('.slimScrollDiv').toggle();

			rows = parent.find('.slimScrollDiv .autoblog-log-feed-records .autoblog-log-record');
			height = rows.length * $(rows[0]).outerHeight();
			if (height < 400) {
				parent.find('.slimScrollDiv, .autoblog-log-feed-records').height(height + 'px');
			} else {
				parent.find('.slimScrollDiv, .autoblog-log-feed-records').height('400px');
			}

			autoblogDrawChart();

			return false;
		});

		$('.autoblog-log-feed-records').slimScroll({height: '400px'}).show();

		$(window).resize(autoblogDrawChart);
	});
})(jQuery);