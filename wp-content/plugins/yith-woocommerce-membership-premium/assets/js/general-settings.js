jQuery( function ( $ ) {
	var initAlternativeContentSettings = function () {
		var hideContentOption = $( '#yith-wcmbs-hide-contents input:checked' ),
			isVisible         = 'alternative_content' === hideContentOption.val();
		if ( isVisible ) {
			$( '#yith-wcmbs-default-alternative-content-mode input:checked' ).trigger( 'change' );
		} else {
			$( '.yith-wcmbs-default-alternative-content-wrapper, .yith-wcmbs-default-alternative-content-id-wrapper' ).hide();
		}
	};

	$( document ).on( 'change', '#yith-wcmbs-hide-contents input', initAlternativeContentSettings );
	initAlternativeContentSettings();
} );
