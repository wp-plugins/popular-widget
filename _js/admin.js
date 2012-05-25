jQuery(document).ready(function($){ 
	var popw_init = function( ){
		$('.popw-inner').hide();
		
		$( ".popw-sortable" ).sortable({ items: 'label' });
		$( ".popw-sortable" ).disableSelection();
		
		$( '#widgets-right .popw-collapse:not("clickable")' ).each(function(){
			$(this).addClass( 'clickable' ).unbind('click').click( function(){
				$(this).next( ).toggle( );
			});
		});
	};
	popw_init( );
	$( "#widgets-right" ).ajaxSuccess( popw_init );
});