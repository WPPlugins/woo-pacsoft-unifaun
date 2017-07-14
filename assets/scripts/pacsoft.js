jQuery( document ).ready(function() {
    jQuery('#pacsoft_shipping_type').change(function() {
        if(jQuery(this).val() == 'custom'){
            jQuery('#custom-freight-title').show();
            jQuery('#custom-freight-table').show();
        }else{
            jQuery('#custom-freight-title').hide();
            jQuery('#custom-freight-table').hide();
        }
    });
});


function pacsoft_sync_order(orderId, nonce) {
    var data = {
        action: 'pacsoft_sync_order',
        security: nonce,
        order_id: orderId
    };
    
    jQuery.post(ajaxurl, data, function(response) {
        jQuery('#ajax-pacsoft-notification').show();
        jQuery('#ajax-pacsoft-message').html('WooCommerce Pacsoft: ' + response['message']);
        
        jQuery('html,body').animate({scrollTop: jQuery('#ajax-pacsoft-notification').offset().top - 100 });
        
        if(response['success'] == false){
            jQuery('#ajax-pacsoft-notification')
                .removeClass('updated')
                .addClass('error');
        }
    }, 'json');
}

function pacsoft_sync_order_with_options(orderId) {
    var data = {
        action: 'pacsoft_sync_order',
        order_id: orderId,
        service_id: jQuery('#pacsoft_service').val()
    };

    jQuery.post(ajaxurl, data, function(response) {
        jQuery('#ajax-pacsoft-notification').show();
        jQuery('#ajax-pacsoft-message').html('WooCommerce Pacsoft: ' + response['message']);

        jQuery('html,body').animate({scrollTop: jQuery('#ajax-pacsoft-notification').offset().top - 100 });

        if(response['success'] == false){
            jQuery('#ajax-pacsoft-notification')
                .removeClass('updated')
                .addClass('error');
        }

    }, 'json');
}

function pacsoft_show_options(orderId, nonce) {
    jQuery('html,body').animate({scrollTop: jQuery('#ajax-pacsoft-notification').offset().top - 100 });
    var form = '<h3>Skicka order ' + orderId + '</h3>' +
        '<table class="form-table">' +
            '<tr valign="top">' +
                '<th scope="row">Välj typ av tjänst</th>' +
                '<td>' +
                    '<select id="pacsoft_service" name="pacsoft_service"/>' +
                '</td>' +
            '</tr>' +
        '</table>' +
        '<button type="button" class="button" title="Skicka" style="margin:5px" onclick="pacsoft_sync_order_with_options(' + orderId + ')">Skicka</button>';

    jQuery('#ajax-pacsoft-options').show();
    jQuery('#ajax-pacsoft-options').empty();
    jQuery('#ajax-pacsoft-options').append(form);
    var data = {
        action: 'pacsoft_get_services'
    };
    jQuery.get(ajaxurl, data, function(services) {
        for(var val in services){
            jQuery('#pacsoft_service').append('<option value="' + services[val] + '">' + val + '</option>');
        }

    }, 'json');
}

function pacsoft_print_order(orderId, nonce) {
    var data = {
        action: 'pacsoft_print_order',
        security: nonce,
        order_id: orderId
    };
    jQuery.post(ajaxurl, data, function(response) {
        if(response['success'] == false){
            jQuery('#ajax-pacsoft-notification').show();
            jQuery('#ajax-pacsoft-message').html('WooCommerce Pacsoft: ' + response['message']);

            jQuery('html,body').animate({scrollTop: jQuery('#ajax-pacsoft-notification').offset().top - 100 });

            jQuery('#ajax-pacsoft-notification')
                .removeClass('updated')
                .addClass('error');
        }
        else{
            jQuery('#ajax-pacsoft-print').show();
            jQuery('#ajax-pacsoft-print').empty();
            jQuery('#ajax-pacsoft-print').append('<iframe src="' + response['url'] + '" style="width:100%; height:300px;"></iframe>');
        }
    }, 'json');
}