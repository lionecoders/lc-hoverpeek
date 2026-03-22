jQuery(document).ready(function($) {
	// --- State Tracking ---
	const initialValues = {};
	const $form = $( '.lcho-settings-wrap form' );
	const $btnUndo = $( '.lcho-btn-undo' );

	function captureState() {
		$form.find( 'input[name]' ).each( function() {
			const $el = $( this );
			const name = $el.attr( 'name' );
			if ( $el.is( ':checkbox' ) ) {
				initialValues[name] = $el.is( ':checked' );
			} else {
				initialValues[name] = $el.val();
			}
		});
	}

	function checkDirtiness() {
		let isSaved = false;
		$form.find( 'input[name]' ).each( function() {
			const $el = $( this );
			const name = $el.attr( 'name' );
			let currentVal;
			if ( $el.is( ':checkbox' ) ) {
				currentVal = $el.is( ':checked' );
			} else {
				currentVal = $el.val();
			}

			if ( currentVal !== initialValues[name] ) {
				isSaved = true;
				return false; // Break loop
			}
		});

		$btnUndo.css( 'display', !isSaved ? 'none' : 'flex' );
	}

	captureState();

	// --- Visual Sync & Tracking ---
	$form.on( 'input change', 'input', function() {
		const $el = $( this );

		// Specific handling for color picker visual sync
		if ( $el.is( '[type="color"]' ) ) {
			const val = $el.val();
			$el.parent().css( 'background-color', val );
			$el.closest( '.lcho-color-input-wrap' ).find( 'input[type="text"]' ).val( val.toUpperCase() );
		}

		if ( $el.is( '[type="text"]' ) && $el.closest( '.lcho-color-input-wrap' ).length ) {
			const val = $el.val();
			if ( /^#[0-9A-F]{6}$/i.test( val ) ) {
				$el.closest( '.lcho-color-input-wrap' ).find( '.lcho-color-preview' ).css( 'background-color', val );
				$el.closest( '.lcho-color-input-wrap' ).find( 'input[type="color"]' ).val( val );
			}
		}

		checkDirtiness();
	});

	// --- Undo Action ---
	$btnUndo.on( 'click', function() {
		$.each( initialValues, function( name, val ) {
			const $el = $form.find( '[name="' + name + '"]' );
			if ( $el.is( ':checkbox' ) ) {
				$el.prop( 'checked', val ).trigger( 'change' );
			} else {
				$el.val( val ).trigger( 'input' );
			}

			// Force color swatch update
			if ( $el.closest( '.lcho-color-input-wrap' ).length ) {
				const colorWrap = $el.closest( '.lcho-color-input-wrap' );
				colorWrap.find( '.lcho-color-preview' ).css( 'background-color', val );
				colorWrap.find( 'input[type="color"]' ).val( val );
			}
		});
		checkDirtiness();
	});

	// --- Reset to Defaults Action ---
	const $modal = $( '#lcho-reset-modal' );

	$( '.lcho-btn-reset' ).on( 'click', function( e ) {
		e.preventDefault();
		$modal.addClass( 'is-active' );
	});

	$( '.lcho-modal-close, .lcho-btn-cancel' ).on( 'click', function() {
		$modal.removeClass( 'is-active' );
	});

	$modal.on( 'click', function( e ) {
		if ( $( e.target ).is( $modal ) ) {
			$modal.removeClass( 'is-active' );
		}
	});

	$( '.lcho-btn-confirm-reset' ).on( 'click', function() {
		const $btn = $( this );
		$btn.prop( 'disabled', true ).text( 'Resetting...' );

		$.post( lcho_admin.ajax_url, {
			action: 'lcho_reset_settings',
			nonce: lcho_admin.nonce,
		}, function( res ) {
			if ( res.success ) {
				window.location.reload();
			} else {
				alert( 'Reset failed. Please try again.' );
				$btn.prop( 'disabled', false ).text( 'Yes, Reset Defaults' );
			}
		});
	});

	// --- Info Box Toggle ---
	$( '#lcho-info-toggle' ).on( 'click', function() {
		$( this ).closest( '.lcho-info' ).toggleClass( 'is-expanded' );
	});
});
