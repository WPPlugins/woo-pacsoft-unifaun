;( function( $ ) {
	$( function() {
		/**
		 * Add services row
		 */
		$( '.addPacsoftServiceRow' ).on( 'click', function( event ) {
			event.preventDefault();
			
			$( '#pacsoft-services' ).append( Mustache.render( pacsoft.row, { x: $( '#pacsoft-services tr' ).length } ) );
		} );
		
		/**
		 * Remove services row
		 */
		$( 'body' ).delegate( '#pacsoft-services .removeRow', 'click', function( event ) {
			event.preventDefault();
			
			$( this ).closest( 'tr' ).remove();
		} );
		
		/**
		 * Remove message
		 */
		$( 'body' ).delegate( '#pacsoft-message .notice-dismiss', 'click', function( event ) {
			event.preventDefault();
			
			$( '#pacsoft-message' ).remove();
		} );
		
		/**
		 * Sync order to Pacsoft/Unifaun
		 *
		 * @param orderId
		 * @param serviceId
		 */
		function syncOrder( selector, orderId, serviceId, force )
		{
			var loader = $( selector ).siblings( '.pacsoft-spinner' );
			var status = $( selector ).siblings( '.pacsoft-status' );

			$.ajax( {
				url: window.ajaxurl,
				data: {
			        action: 'pacsoft_sync_order',
			        order_id: orderId,
			        service_id: serviceId,
			        force: force
				},
				type: "post",
				success: function( response ) {
					loader.css( { visibility: "hidden" } );
					
					if( ! response.error ) {
						status.removeClass( 'pacsoft-icon-cross' ).addClass( 'pacsoft-icon-tick' );
						
						$( '#wpbody .wrap h1' ).after( Mustache.render( pacsoft.notice, {
							type: "success",
							message: response.message
						} ) );
					}
					else {
						$( '#wpbody .wrap h1' ).after( Mustache.render( pacsoft.notice, {
							type: "error",
							message: response.message
						} ) );
						$( 'html, body' ).animate( { scrollTop: $( '#pacsoft-message' ).offset().top - 100 } );
					}
					status.show();
				},
				dataType: "json"
			} );
		}
		
		/**
		 * Sync order to Pacsoft/Unifaun
		 */
		$( '.syncOrderToPacsoft' ).on( 'click', function( event ) {
			event.preventDefault();
			
			var selector = this;
			var orderId = $( this ).data( 'order' );
			var serviceId = $( this ).data( 'service' );
			var loader = $( this ).siblings( '.pacsoft-spinner' );
			var status = $( this ).siblings( '.pacsoft-status' );
			var shiftHeld = false;

			$( '#pacsoft-message' ).remove();
			
			loader.css( { visibility: "visible" } );
			status.hide();

			// Determine if shift is being held down and force sync
			$(document).click(function(e) {
			    if (e.shiftKey) {
			        shiftHeld = true;
			    } 
			});
			
			if( ! $( this ).data( 'service' ) ) {
				$( '#pacsoft-sync-options-dialog' ).remove();
				$( 'body' ).append( window.pacsoftSyncOptionsDialog );
				
				var width = $( window ).width() * 0.7;
				var height = $( window ).height() * 0.7;
				
				tb_show( pacsoftI18n[ 'Sync order %d to Pacsoft/Unifaun' ].replace( '%d', orderId ), '#TB_inline?width=' + width + '&height=' + height + '&inlineId=pacsoft-sync-options-dialog' );
				
				$( '.syncPacsoftOrderWithOptions' ).on( 'click', function( event ) {
					event.preventDefault();

					tb_remove();
					loader.css( { visibility: "visible" } );

					syncOrder( selector, orderId, $( '.pacsoft-services' ).val(), shiftHeld );
				} );

				// When dialog is closed, remove it and stop the loader / spinner
				$( '#TB_closeWindowButton' ).on( 'click', function( event ) {
					event.preventDefault();
					tb_remove();
					loader.css( { visibility: "hidden" } );
					status.show();
				} );
			}
			else {
				syncOrder( selector, orderId, serviceId, shiftHeld );
			}
		} );
		
		/**
		 * Print Pacsoft/Unifaun order
		 */
		$( '.printPacsoftOrder' ).on( 'click', function( event ) {
			event.preventDefault();
			
			var orderId = $( this ).data( 'order-id' );
			var loader = $( this ).siblings( '.pacsoft-spinner' );
			var status = $( this ).siblings( '.pacsoft-status' );
			
			loader.css( { visibility: "visible" } );
			status.hide();
			
			$.ajax( {
				url: window.ajaxurl,
				data: {
					action: 'pacsoft_print_order',
					order_id: orderId
				},
				type: "post",
				success: function( response ) {
					loader.css( { visibility: "hidden" } );
					status.show();
					
					if( response.error ) {
						$( '#wpbody .wrap h1' ).after( Mustache.render( pacsoft.notice, {
							type: "error",
							message: response.message
						} ) );
						$( 'html, body' ).animate( { scrollTop: $( '#pacsoft-message' ).offset().top - 100 } );
					}
					else {
						var width = $( window ).width() * 0.8;
						var height = $( window ).height() * 0.8;
						
						tb_show( pacsoftI18n[ 'Print Pacsoft/Unifaun order' ], response.url + '&TB_iframe=1&width=' + width + '&height=' + height );
					}
				},
				dataType: "json"
			} );
		} );
	} );
} )( jQuery );