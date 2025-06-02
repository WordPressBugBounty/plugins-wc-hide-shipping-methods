jQuery(document).ready( function ($) {

	var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('post_type') && urlParams.get('post_type') === 'wwp_requests') {
        // Remove specific parameters
        urlParams.delete('post_id');
        urlParams.delete('user_status');
        urlParams.delete('_wpnonce');

        var newUrl = window.location.pathname + '?' + urlParams.toString();

        // Update the URL in the address bar without reloading the page
        window.history.replaceState({}, '', newUrl);
    }
	
	tier_validation();
	jQuery('#rejected_note').hide();
	function rejected_note() {
		if ( jQuery('#rejected').is(':checked') ) {
	 
			jQuery('#rejected_note').show();
		} else {
		
			jQuery('#rejected_note').hide();
		}
		
	}
	
	jQuery("#rejected,#active").click(function () {
	rejected_note();
	});		

	rejected_note();
	
	jQuery( "#section1" ).show();
	jQuery( ".section1" ).click(function () {
		
		jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
		localStorage.setItem('activeTabGeneral', 'section1' );
		jQuery( "#section1" ).show();
		select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
	});
	
	jQuery( ".section2" ).click(function () {
		
		jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
		localStorage.setItem('activeTabGeneral', 'section2' );
		jQuery( "#section2" ).show();
		select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
	});
	
	
	jQuery( ".section3" ).click(function () {
	   
		jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
		localStorage.setItem('activeTabGeneral', 'section3' );
		jQuery( "#section3" ).show();
		select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
	});
	
	jQuery( ".section4" ).click(function () {
		
		jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
		localStorage.setItem('activeTabGeneral', 'section4' );
		jQuery( "#section4" ).show();
		select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
	});
	select_activeTabGeneral (localStorage.getItem('activeTabGeneral'));
	
	jQuery("#emailuserrole,#order_email_custom").click(function () {
		order_notification_email();
	});
	order_notification_email();

	jQuery( '#register_redirect_autocomplete' ).autocomplete({
		source: function (request, response) {
			jQuery.ajax({
				dataType: 'jsonp',
				url: ajaxurl,
				data: {
					action: 'register_redirect',
					name: request.term
				},
				success: function (data) {
					response(data);
				}
			});
		},
		minLength: 2,
		select: function (event, ui) {
			// Set selectionhttp://staging.local/wp-admin/admin.php?page=wwp_wholesale
			jQuery('#register_redirect_autocomplete').val(ui.item.label); // display the selected text
			jQuery('#register_redirect').val(ui.item.value); // save selected id to input
			return false;
		}
	});

	jQuery("#register_redirect_autocomplete").keyup(function () {
		if (jQuery("#register_redirect_autocomplete").val() == '') {
			jQuery("#register_redirect").val('');
		}
	});



});




function select_activeTabGeneral(activeTab) { 

	if ( activeTab ) {
		jQuery('#wwp-global-settings  ul.nav-tabs li a').removeClass('active');
		jQuery('body.toplevel_page_wwp_wholesale  ul.nav-tabs li.' + activeTab +' a').addClass('active');
		jQuery( "#wwp-global-settings .tab-content .tab-pane" ).hide();
		jQuery( '#'+ activeTab +'' ).show();
	} else {
		jQuery('body.toplevel_page_wwp_wholesale  ul.nav-tabs li.section1 a').addClass('active');
		localStorage.setItem('activeTabGeneral', 'section1' );
	}
}

function order_notification_email(){ 
	if (jQuery('#emailuserrole').is(':checked')) { 
		jQuery('#select_role_wrap').show();
		jQuery('#select_email_custom_wrap').hide();
	} else { 
		jQuery('#select_role_wrap').hide();
		jQuery('#select_email_custom_wrap').show();
	}
}

function tier_validation() {
	jQuery('input[name="options[tier_pricing][min][]"]').on('keyup', function () {
		if ( !!jQuery(this).val() ) {
			jQuery(this).parent().next().next().children('input').attr('required', true);
		} else {
			jQuery(this).parent().next().next().children('input').attr('required', false);
		}
	});

	jQuery('input[name="options[tier_pricing][min][]"]').each(function (i, e) {
		if ( !!e.value ) {
			e.parentElement.nextElementSibling.nextElementSibling.children[0].required = true;
		} else {
			e.parentElement.nextElementSibling.nextElementSibling.children[0].required = false;
		}
	});
} 



