/**
 * Statistics library.
 * @var object
 */
var Statistics = new Object();

/**
 * Google dashboard.
 * @var object
 */
Statistics.dashboard;

/**
 * Google controll wrapper.
 * @var object
 */
Statistics.control;

/**
 * Google chart wrapper.
 * @var object
 */
Statistics.chart;

/**
 * Google data table.
 * @var object
 */
Statistics.dataTable;

/**
 * Start date value.
 * @var string
 */
Statistics.startDateVal = $('#start-date').val();

/**
 * End date value.
 * @var string
 */
Statistics.endDateVal = $('#end-date').val();

/**
 * Start filter range.
 * @var string
 */
Statistics.startRangeDate;

/**
 * End filter range.
 * @var string
 */
Statistics.endRangeDate;

/**
 * Platform with bound ids to it.
 * @var json
 */
Statistics.platformPlugins;

/**
 * Stat filter url.
 * @var string
 */
Statistics.filterUrl;

/**
 * Initialization.
 * @param object params - chart parameters.
 */
Statistics.init = function ( params ) 
{
	this.dataTable = params.dataTable;
	this.startRangeDate = params.startRangeDate;
	this.endRangeDate = params.endRangeDate;
	this.platformPlugins = params.platformPlugins;
	this.filterUrl = params.filterUrl;
	this.calcRowVals = params.calcRowVals;
	google.load( 'visualization', '1', {'packages': ['controls'] } );
	google.setOnLoadCallback( this.drawCharts );
};

/**
 * Calculates total amount of installed and uninstalled values.
 */
Statistics.calcRowVals;

/**
 * Redraws google dashboard.
 * @param object data - chart data.
 */
Statistics.redrawChart = function ( data )
{
	eval( 'var dataTable = new google.visualization.DataTable( '+ data.chartData +' );' );
	Statistics.dataTable = dataTable;
	Statistics.control.setState( {
		range: {
			start: new Date( data.startRangeDate ),
			end: new Date( data.endRangeDate )
		}
	} );
	google.visualization.events.addListener( Statistics.control, 'statechange', function () {
		Statistics.calcRowVals();
	} );
	Statistics.dashboard.draw( dataTable );
	Statistics.calcRowVals();
};

/**
 * Draws google dashboard.
 */
Statistics.drawCharts = function ()
{
	// Chart.
	Statistics.dataTable = new google.visualization.DataTable( Statistics.dataTable );
	Statistics.chart = new google.visualization.ChartWrapper( {
		chartType: 'LineChart',
		dataTable: Statistics.dataTable,
		options: {
			'chartArea': {
				'width': '82%',
				'height': 300,
				'top': 30,
				'left': 80
			}
		},
		containerId: 'installs-chart'
	} );
	// Control.
	Statistics.control = new google.visualization.ControlWrapper( {
		'controlType': 'ChartRangeFilter',
		'containerId': 'installs-chart-control',
		'options': {
			'filterColumnIndex': 0,
			'ui': {
				'chartOptions': {
					'chartArea': {
						'width': '88%'
					}
	         },
				// 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
				'minRangeSize': 86400000
			}
		},
		'state': {
			'range': {
				'start': Statistics.startRangeDate,
				'end': Statistics.endRangeDate
			}
		}
	} );
	// Event for control change.
	google.visualization.events.addListener( Statistics.control, 'statechange', function () {
		Statistics.calcRowVals();
	} );
	// Draw.
	Statistics.dashboard = new google.visualization.Dashboard(
		document.getElementById( 'dashboard' )
	);
	Statistics.chart.draw();
	Statistics.dashboard.bind( Statistics.control, Statistics.chart );
	Statistics.dashboard.draw( Statistics.dataTable );
	Statistics.calcRowVals();
	// Start date event.
	$('#start-date').change( function () {
		if ( Statistics.startDateVal == $( this ).val() ) {
			return;
		}
		Statistics.startDateVal = $( this ).val();
		var start = new Date( Statistics.startDateVal + ' 00:00:00' );
		var end = Statistics.endRangeDate;
		if ( Statistics.endDateVal = $('#end-date').val() ) {
			end = new Date( Statistics.endDateVal + ' 23:59:59' );
		}
		Statistics.control.setState( {
			'range':{
				'start': start,
				'end': end
			}
		} );
		Statistics.control.draw();
		Statistics.calcRowVals();
	} );
	// End date event.
	$('#end-date').change( function () {
		if ( Statistics.endDateVal == $( this ).val() ) {
			return;
		}
		Statistics.endDateVal = $( this ).val();
		var end = new Date( Statistics.endDateVal + ' 23:59:59' );
		var start = Statistics.startRangeDate;
		if ( Statistics.startDateVal = $('#start-date').val() ) {
			start = new Date( Statistics.startDateVal + ' 00:00:00' );
		}
		Statistics.control.setState( {
			'range':{
				'start': start,
				'end': end
			}
		} );
		Statistics.control.draw();
		Statistics.calcRowVals();
	} );
	// Platform event.
	$('#platform').change(
		{
			platformPlugins: Statistics.platformPlugins,
			callback: function () {
				$.get(
					Statistics.filterUrl +'/platform/'+ $('#platform').val(),
					function ( data ) {
						Statistics.redrawChart( data );
				} );
			}
		},
		Admin.platformSelectorEvent
	);
	// Plugin event.
	$('#plugin').change( function () {
		$.get(
			Statistics.filterUrl +'/platform/'+ $('#platform').val() +'/plugin/'+ $( this ).val(),
			function ( data ) {
				Statistics.redrawChart( data );
		} );
	} );
};