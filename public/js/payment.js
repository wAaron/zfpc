var Payment = new Object();

Payment.setProduct = function ( element )
{
	var params = $( element ).val().split( '_' );
	$('#product-id').val( params[0] );
	$('#product-name').val( '{pluginName}. ' + params[1] + ' month use payment.' );
	$('#product-description').val( 'Application usage fee.' );
	$('#product-price').val( params[2] );
	Payment.setRecurrent();	
};

Payment.setOverdrafts = function () 
{
	if ( $('#with-overdrafts').prop('checked') ) {
		$('#overdrafts input[type="hidden"]').each( function () {
			$( this ).prop( 'disabled', false );
		} );
	}
	else {
		$('#overdrafts input[type="hidden"]').each( function () {
			$( this ).attr( 'disabled', 'disabled' );
		} );
	}
};

Payment.setRecurrent = function () 
{
	if ( $('#recurrent').prop('checked') ) {
		if ( $('#products').val() ) {
			var params = $('#products').val().split( '_' );
			$('#product-recurrence').val( params[1] + ' Month' );
			$('#product-duration').val('Forever');
		}
	}
	else {
		$('#product-recurrence').val('');
		$('#product-duration').val('');
	}
};

Payment.checkForm = function ()
{
	if ( ( $('#product-id').length == 0 ) || ( $('#product-id').val() && $('#product-price').val() ) ) {
		return true;
	}
	alert( 'Choose period' );
	return false;	
};