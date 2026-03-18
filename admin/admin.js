jQuery(document).ready(function($) {
	// --- State Tracking ---
	const initialValues = {};
	const $form = $( '.lchp-settings-wrap form' );
	const $btnUndo = $( '.lchp-btn-undo' );

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
			$el.closest( '.lchp-color-input-wrap' ).find( 'input[type="text"]' ).val( val.toUpperCase() );
		}

		if ( $el.is( '[type="text"]' ) && $el.closest( '.lchp-color-input-wrap' ).length ) {
			const val = $el.val();
			if ( /^#[0-9A-F]{6}$/i.test( val ) ) {
				$el.closest( '.lchp-color-input-wrap' ).find( '.lchp-color-preview' ).css( 'background-color', val );
				$el.closest( '.lchp-color-input-wrap' ).find( 'input[type="color"]' ).val( val );
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
			if ( $el.closest( '.lchp-color-input-wrap' ).length ) {
				const colorWrap = $el.closest( '.lchp-color-input-wrap' );
				colorWrap.find( '.lchp-color-preview' ).css( 'background-color', val );
				colorWrap.find( 'input[type="color"]' ).val( val );
			}
		});
		checkDirtiness();
	});

	// --- Reset to Defaults Action ---
	const $modal = $( '#lchp-reset-modal' );

	$( '.lchp-btn-reset' ).on( 'click', function( e ) {
		e.preventDefault();
		$modal.addClass( 'is-active' );
	});

	$( '.lchp-modal-close, .lchp-btn-cancel' ).on( 'click', function() {
		$modal.removeClass( 'is-active' );
	});

	$modal.on( 'click', function( e ) {
		if ( $( e.target ).is( $modal ) ) {
			$modal.removeClass( 'is-active' );
		}
	});

	$( '.lchp-btn-confirm-reset' ).on( 'click', function() {
		const $btn = $( this );
		$btn.prop( 'disabled', true ).text( 'Resetting...' );

		$.post( LCHP_ADMIN.ajax_url, {
			action: 'lchp_reset_settings',
			nonce: LCHP_ADMIN.nonce,
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
	$( '#lchp-info-toggle' ).on( 'click', function() {
		$( this ).closest( '.lchp-info' ).toggleClass( 'is-expanded' );
	});
});
