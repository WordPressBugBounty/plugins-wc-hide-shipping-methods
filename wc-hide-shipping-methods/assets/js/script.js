jQuery(document).ready(
	function () {
		jQuery('.variations_form').on(
			'found_variation', function ( event, variation ) {
				console.log(variation['price_html']);
			}
		);
	},

	jQuery( 'input.qty' ).on(
		"change",
		function () {
			if (wwpscript.product_type == 'variable') {
				tire_ajax_call( jQuery( '.variation_id' ).val() );
			} else {
				tire_ajax_call( wwpscript.product_id );
			}

		}
	),

	jQuery( ".single_variation_wrap" ).on(
		"show_variation",
		function ( event, variation ) {
			tire_ajax_call( jQuery( '.variation_id' ).val() );
		}
	),

	jQuery( ".variations_form" ).on( 
		"woocommerce_variation_select_change", 
		function () {
			wwp_variation_update();
		} 
	),

	jQuery( ".single_variation_wrap" ).on( 
		"show_variation", 
		function ( event, variation ) {
			wwp_variation_update();
		} 
	),

	jQuery('form.cart').on( 'click', 'button.plus, button.minus', function () {
		wwp_variation_update();     
	}),
);

function tire_ajax_call( variation_id ) {
	quantity = jQuery( 'input.qty' ).val();

	jQuery( '#wholesale_tire_price .row_tire' ).hide();
	jQuery( '#wholesale_tire_price .wrap_' + variation_id ).show();

	jQuery( '#wholesale_tire_price > tbody  > tr:not(:eq(0))' ).each(
		function (index, tr) {
			this_tr = jQuery( this );
			id      = this_tr.data( 'id' );
			min     = this_tr.data( 'min' );
			max     = this_tr.data( 'max' );
			if (quantity >= min && quantity <= max) {
				jQuery( this_tr ).addClass( "active" );
			} else if ( quantity >= min && !max ) {
				jQuery( this_tr ).addClass( "active" );
			} else {
				jQuery( this_tr ).removeClass( "active" );
			}
		}
	);
}

function wwp_variation_update() {
	
	if (jQuery('.variation_id').val() == '0' || jQuery('.variation_id').val() == '') {
		jQuery('.wwp_variation_wrap').show();
		return;
	}
	variation_data = {};
	jQuery(jQuery("form.variations_form").find('select')).each(function () {
		variation_data[jQuery(this).data( 'attribute_name' )] =this.value;
	});
	jQuery.each( jQuery("form.variations_form").find('.wwp_variation_wrap'), function ( key, value ) {
		vari_obj      = jQuery(this);
		all_variation = vari_obj.attr('data-attr-slug');
		variation_id  = vari_obj.attr('date-variation-id');
		vari_obj.show();
		jQuery.each( JSON.parse( all_variation ) , function ( key, value ) {
			if (variation_data[key] != value) {
				vari_obj.hide();
			}
		});
	});
	wwp_add_to_cart_variation_set();
}

function wwp_add_to_cart_variation_set() {
	variation_seleted ='';
	jQuery.each( jQuery("form.variations_form").find('.wwp_variation_wrap:visible'), function ( key, value ) {
		vari_obj      = jQuery(this);
		all_variation = vari_obj.attr('data-attr-slug');
		variation_id  = vari_obj.attr('date-variation-id');
		variation_qty = jQuery(".get_variation_qty_"+variation_id).val();
		check         =  variation_id + ':' + variation_qty + ',';
		if ( ! variation_seleted.match(check) && variation_qty != '0' ) {
			variation_seleted +=  variation_id + ':' + variation_qty + ',';
		}
	});
	if (variation_seleted != "") {
		if (jQuery("#wwp_variation_add_to_cart").length == 0) {
			jQuery("form.variations_form").append('<input type="hidden" id="wwp_variation_add_to_cart" name="wwp_variation_add_to_cart" value="true" />');
		}
		setTimeout(function () {
			jQuery(".single_add_to_cart_button").removeClass("disabled");
		}, 100);
		
		jQuery("form.variations_form input[name='add-to-cart']").val(variation_seleted);
	}
}