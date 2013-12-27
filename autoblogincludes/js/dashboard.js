google.load('visualization', '1', {packages: ['corechart']});
google.setOnLoadCallback(drawDashboardCharts);

function drawDashboardCharts() {
	var date, chart, table, i, today, imports, errors, processed;

	today = new Date();
	table = new google.visualization.DataTable();

	table.addColumn('date', autoblog.date_column);
	table.addColumn('number', autoblog.processes_column);
	table.addColumn('number', autoblog.imports_column);
	table.addColumn('number', autoblog.errors_column);

	for (i = 1; i <= 7; i++) {
		imports = errors = processed = 0;

		date = jQuery('#autoblog-log-date-' + today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + (today.getDate() - 7 + i));

		date.find('.autoblog-log-feed-imports').each(function() { imports += parseInt(jQuery(this).text()) });
		date.find('.autoblog-log-feed-iterations').each(function() { processed += parseInt(jQuery(this).text()) });
		date.find('.autoblog-log-feed-errors').each(function() { errors = parseInt(jQuery(this).text()) });

		date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 7 + i);
		table.addRow([date, processed, imports, errors]);
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
			}
		},
		hAxis: { baselineColor: 'white' },
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

		$('.autoblog-log-feed-records').slimScroll({height: '400px'}).show();

		$(window).resize(drawDashboardCharts);
	});
})(jQuery);