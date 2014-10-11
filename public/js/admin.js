/**
 * Main admin js library.
 * @author Polyakov Ivan
 * @version 1.3.1
 * @var object
 */
var Admin = new Object();

/**
 * Create new item url.
 * @var string
 */
Admin.createUrl;

/**
 * Edit item url.
 * @var string
 */
Admin.editUrl;

/**
 * Delete item url.
 * @var string
 */
Admin.deleteUrl;

/**
 * Pagination container id.
 * @var string
 */
Admin.paginationContainer;

/**
 * Pagination callback function.
 * @var function
 */
Admin.paginationCallback;

/**
 * Enables main or dialog loader.
 * Dialog loader id should contains 'dialog' key.
 * @param string id
 */
Admin.enableLoader = function ( id ) 
{
	if ( id.indexOf( 'dialog' ) != -1 ) {
		$( id ).html( '<img src="public/images/dialog-box-loader.gif" alt="loader">' );
	} else {
		$( id ).html( '<img src="public/images/loader.gif" alt="loader">' );
	}
};

/**
 * Loads ajax paginator page.
 * @param string url - page url.
 */
Admin.loadPage = function ( url ) {
	Admin.enableLoader( this.paginationContainer );
	$.get( url, this.paginationCallback );
};

/**
 * Reloads current page.
 */
Admin.reloadPage = function () {
	var timeout = window.setTimeout( 'location.reload()', 3000 );
};

/**
 * Event for platform selection.
 * @param object event - an event.
 * @internal [ tableId ] [ cellId ] [ platformPlugins ]
 */
Admin.platformSelectorEvent = function ( event )
{
	// Bind plugins if ids were given.
	if ( event.data.platformPlugins ) {
		var ids = event.data.platformPlugins[ $( this ).val() ];
		var pluginId = event.data.pluginId ? event.data.pluginId : '#plugin';
		if ( ids ) {
			// Show plugins for selected platform only.
			$( pluginId + ' option').each( function ( index, element ) {
				var val = $( element ).attr( 'value' );
				if ( val.length && ids.indexOf( val ) == -1 ) {
					$( element ).css( 'display', 'none' );
				} else {
					$( element ).css( 'display', 'block' );
				}
			} );
			// Set first plugin in case platform changed.
			if ( ids.indexOf( $( pluginId ).val() ) == -1 ) {
				var firstOption = $( pluginId ).find( 'option[style*="block"]' ).get( 0 );
				$( pluginId ).val( $( firstOption ).val() );
			}
		}
		// Show all plugins.
		else {
			$( pluginId + ' option').each( function ( index, element ) {
				$( element ).css( 'display', 'block' );
			} );
		}
	}
	// Filter a table.
	if ( event.data.tableId ) {
		var _this = this;
		var optionText = $( this.options[ this.selectedIndex ] ).text();
		$('#'+ event.data.tableId +' tr').each( function ( index, element ) {
			$( element ).css( 'display', 'table-row' );
			if ( $( _this ).val() ) {
				var result = $( element ).find( '.' + event.data.cellId );
				if ( result.length ) {
					var tdText = $( result.get( 0 ) ).text();
					if ( tdText.toLowerCase().indexOf( optionText.toLowerCase() ) == -1 ) {
						$( element ).css( 'display', 'none' );
					}
				}
			}
		} );
	}
	// Callback function.
	if ( event.data.callback ) {
		event.data.callback.call();
	}
};

/**
 * Event for plugin selection.
 * @param object event - an event.
 * @internal [ tableId ] [ cellId ]
 */
Admin.pluginSelectorEvent = function ( event )
{
	// Filter a table.
	var _this = this;
	var optionText = $( this.options[ this.selectedIndex ] ).text();
	$('#'+ event.data.tableId +' tr').each( function ( index, element ) {
		$( element ).css( 'display', 'table-row' );
		if ( $( _this ).val() ) {
			var result = $( element ).find( '.' + event.data.cellId );
			if ( result.length ) {
				var tdText = $( result.get( 0 ) ).text().toLowerCase();
				if ( tdText.indexOf( optionText.toLowerCase() ) == -1 ) {
					$( element ).css( 'display', 'none' );
				}
			}
		}
		else if ( event.data.linkedSelectorid ) {
			$('#' + event.data.linkedSelectorid ).change();
		}
	} );
	// Callback function.
	if ( event.data.callback ) {
		event.data.callback.call();
	}
};

/**
 * Event for standard selection.
 * @param object event - an event.
 * @internal [ tableId ] [ cellId ]
 */
Admin.standardSelectorEvent = function ( event ) 
{
	// Filter a table.
	var _this = this;
	var optionText = $( this.options[ this.selectedIndex ] ).text();
	$('#'+ event.data.tableId +' tr').each( function ( index, element ) {
		$( element ).css( 'display', 'table-row' );
		if ( $( _this ).val() ) {
			var result = $( element ).find( '.' + event.data.cellId );
			if ( result.length ) {
				var tdText = $( result.get( 0 ) ).text().toLowerCase();
				if ( tdText.indexOf( optionText.toLowerCase() ) !== 0 ) {
					$( element ).css( 'display', 'none' );
				}
			}
		}
	} );
	// Callback function.
	if ( event.data.callback ) {
		event.data.callback.call();
	}
};

Admin.setFormExtendedEvents;

/**
 * Sets standard form events.
 * @param string action - current action.
 */
Admin.setFormStandardEvents = function ( action )
{
	if ( typeof Admin.setFormExtendedEvents == 'function' ) {
		Admin.setFormExtendedEvents();
	}
	// Event for create submit button.
	if ( action == 'create' ) {
		$('#modal-save').off( 'click' );
		$('#modal-save').click( function () {
			$.post(
				$('#create-form').attr( 'action' ),
				$('#create-form').serialize(),
				function ( data ) {
					$('#modal-content').html( data );
					if ( data.indexOf( 'alert-success' ) != -1 ) {
						Admin.reloadPage();
					} else {
						Admin.setFormStandardEvents( 'create' );
					}
				}
			);
		} );
	}
	// Event for save submit button.
	else if ( action == 'edit' ) {
		$('#modal-save').off( 'click' );
		$('#modal-save').click( function () {
			$.post(
				$('#edit-form').attr( 'action' ),
				$('#edit-form').serialize(),
				function ( data ) {
					$('#modal-content').html( data );
					if ( data.indexOf( 'alert-success' ) != -1 ) {
						Admin.reloadPage();
					} else {
						Admin.setFormStandardEvents( 'edit' );
					}
				}
			);
		} );
	}
};

/**
 * Standard create item action.
 * @param object event - DOM event.
 */
Admin.standardCreateAction = function ( event )
{
	$.get( Admin.createUrl, function ( data ) {
		$('#modal-title').html( 'Create New Item' );
		$('#modal-content').html( data );
		Admin.setFormStandardEvents( 'create' );
		$('#dialog-box').modal();
	} );
	event.preventDefault();
};

/**
 * Standard edit item action.
 * @param object event - DOM event.
 */
Admin.standardEditAction = function ( event )
{
	var url = Admin.editUrl +'/id/'+ $( this ).attr( 'href' );
	$.get( url, function ( data ) {
		$('#modal-title').html( 'Edit Item' );
		$('#modal-content').html( data );
		Admin.setFormStandardEvents( 'edit' );
		$('#dialog-box').modal();
	} );
	event.preventDefault();
};

/**
 * Standard delete item action.
 * @param object event - DOM event.
 */
Admin.standardDeleteAction = function ( event )
{
	if ( confirm( 'Are you sure?' ) ) {
		var url = Admin.deleteUrl +'/id/'+ $( this ).attr( 'href' );
		$.get( url, function ( data ) {
			Admin.reloadPage();
		} );
	}
	event.preventDefault();
};

/**
 * Pagination through filter if filtering occured.
 * Changes events of pagination links.
 */
Admin.filteredPaginationEvents = function ()
{
	$('.pagination a').click( function ( event ) {
		var page = $( this ).attr( 'href' )
			.match( /page\/(\d+)/ );
		$('#page').val( page[1] );
		$('#filter-form').submit();
		event.preventDefault();
	} );
};

/**
 * Draws standard Google PieChart for stat.
 * @param object chartData - DataTable in json format.
 * @param string containerId - chart container id.
 * @param string pieSliceText - chart option.
 */
Admin.drawPieChart = function ( chartData, containerId, pieSliceText )
{
	// Chart.
	var dataTable = new google.visualization.DataTable( chartData );
	var chart = new google.visualization.ChartWrapper( {
		chartType: 'PieChart',
		dataTable: dataTable,
		options: {
			'chartArea': {
				'width': '100%',
				'height': '90%'
			},
			'pieSliceText': pieSliceText
		},
		containerId: containerId
	} );
	// Draw.
	chart.draw();
};