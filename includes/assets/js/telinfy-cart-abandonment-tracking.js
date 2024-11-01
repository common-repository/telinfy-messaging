( function ( $ ) {
	let timer;
	const TELINFY_cart_abandonment = {
		init() {

			$( document ).on(
				'focusout',
				'#billing_phone',
				this._getCheckoutData
			);

			$( document.body ).on( 'updated_checkout', function () {
				TELINFY_cart_abandonment._getCheckoutData();
			} );

			$( function () {
				setTimeout( function () {
					TELINFY_cart_abandonment._getCheckoutData();
				}, 800 );
			} );
		},

		_validate_phone_number( value ) {
			// var re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
			var re = /^[\+]?\d+$/;
			return re.test(value);
		},

		_getCheckoutData() {
			
			const telinfy_email = jQuery( '#billing_email' ).val();

			if ( typeof telinfy_email === 'undefined' ) {
				return;
			}

			let telinfy_phone = jQuery( '#billing_phone' ).val();
			
			if ( typeof telinfy_phone === 'undefined' || telinfy_phone === null ) {
				//If phone number field does not exist on the Checkout form
				telinfy_phone = '';
			}

			clearTimeout( timer );

			if (
				telinfy_phone.length >= 1
			) {
				//Checking if the email field is valid or phone number is longer than 1 digit
				//If Email or Phone valid
				telinfy_phone = jQuery( '#billing_phone' ).val();

				const data = {
					action: 'telinfy_tm_update_cart_abandonment_data',
					telinfy_email,
					telinfy_phone,
					security: telinfy_ca_vars._nonce,
					telinfy_post_id: telinfy_ca_vars._post_id,
				};

				timer = setTimeout( function () {
					if (
						TELINFY_cart_abandonment._validate_phone_number( data.telinfy_phone )
					) {
						jQuery.post(
							telinfy_ca_vars.ajaxurl,
							data, //Ajaxurl coming from localized script and contains the link to wp-admin/admin-ajax.php file that handles AJAX requests on Wordpress
							function () {
								// success response
							}
						);
					}
				}, 500 );
			} else {
				//console.log("Not a valid e-mail or phone address");
			}
		},
	};

	TELINFY_cart_abandonment.init();
} )( jQuery );
