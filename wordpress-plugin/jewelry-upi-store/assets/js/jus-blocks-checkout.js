( function () {
	if ( ! window.wc || ! window.wc.wcBlocksRegistry || ! window.wc.wcSettings ) {
		return;
	}

	var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
	var getSetting = window.wc.wcSettings.getSetting;
	var createElement = window.wp.element.createElement;
	var decodeEntities = window.wp.htmlEntities.decodeEntities;
	var sanitizeHTML = window.wc.sanitize.sanitizeHTML;

	var settings = getSetting( 'jus_manual_upi_data', {} );
	var title = decodeEntities( settings.title || 'Pay through UPI' );
	var description = settings.description ? decodeEntities( settings.description ) : '';
	var upiId = settings.upi_id || '';

	function Content() {
		var children = [];

		if ( description ) {
			children.push(
				createElement( 'div', {
					className: 'jus-blocks-payment-desc',
					dangerouslySetInnerHTML: { __html: sanitizeHTML( description ) },
				} )
			);
		}

		if ( upiId ) {
			children.push(
				createElement(
					'p',
					{ className: 'jus-blocks-payment-upi' },
					createElement( 'strong', null, 'UPI ID: ' ),
					createElement( 'code', null, upiId )
				)
			);
		}

		children.push(
			createElement(
				'p',
				{ className: 'jus-blocks-payment-note' },
				'After placing the order you will see the QR code. Enter your UTR in the UPI Transaction ID field before submitting.'
			)
		);

		return createElement( 'div', { className: 'jus-blocks-payment-content' }, children );
	}

	function Label() {
		return createElement( 'span', { className: 'jus-blocks-payment-label' }, title );
	}

	registerPaymentMethod( {
		name: 'jus_manual_upi',
		label: createElement( Label ),
		content: createElement( Content ),
		edit: createElement( Content ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: title,
		supports: {
			features: settings.supports || [ 'products' ],
		},
	} );
} )();
