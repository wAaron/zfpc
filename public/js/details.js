$(document).ready( function () 
{
	// Tabs.
	$( function () {
		if ( $('#tabs').get( 0 ) != undefined ) {
			$('#tabs').tabs();
		}
	} );
	// Tooltip.
	$( function () {
		$( '.your-plan strong' ).tipsy( {
			gravity: 'n',
			opacity: 0.9,
			html: true
		} );
	} );
	// Set menu link as selected.
	$('#payment-info-link').addClass( 'selected' );
	// Change event for product selector.
	$('#products').change( function () {
		Payment.setProduct( this );
	} );
	// Click event for overdrafts checkbox.
	if ( $('#with-overdrafts') ) {
		$('#with-overdrafts').click( function () {
			Payment.setOverdrafts();
		} );
	}
	// Click event for recurrent checkbox.
	$('#recurrent').click( function () {
		Payment.setRecurrent();
	} );
	// Submit event for a form.
	$('#payment-form').submit( function () {
		return Payment.checkForm();
	} );
	// History link event.
	if ( $('#payment-history-link') ) {
		$('#payment-history-link').click( function () {
			$('#payment-history').slideToggle();
			return false;
		} );
	}
} );