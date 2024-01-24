jQuery(document).ready(function($) {         
    $("#import-id").click(function(){       
        $.ajax({
          url: ajax_actions.ajaxurl,
          type : "post",
          dataType : "json",
          data: {
            action: 'get_categories',
          },        
          beforeSend: function() {
                $('#import-categories-result').html('Importing categories...');
            },
            success: function(response) {
                $('#import-categories-result').html(' category imported successfully.');
                $('#import-id').replaceWith($('<span>✔</span>'));
            },
            error: function(xhr, status, error) {
                $('#import-categories-result').html();
            }
        });                                                                                                                                                                                         
    });
    
    $("#import-cust-id").click(function(){       
        $.ajax({
          url: ajax_actions.ajaxurl,
          type : "post",
          dataType : "json",
          data: {
            action: 'get_customers',
          },        
          beforeSend: function() {
                $('#import-customer-result').html('Importing customers...');
            },
            success: function(response) {
                $('#import-customer-result').html('Customers imported successfully.');
                $('#import-cust-id').replaceWith($('<span>✔</span>'));

            },
            error: function(xhr, status, error) {
                $('#import-customer-result').html('Error importing customers: ' + xhr.responseText);
            }
        });                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
    });

    $("#import-item-id").click(function(){       
        $.ajax({
          url: ajax_actions.ajaxurl,
          type : "post",
          dataType : "json",
          data: {
            action: 'get_items',
          },        
          beforeSend: function() {
                $('#import-item-result').html('Importing Items...');
            },
            success: function(response) {
                $('#import-item-result').html('Items imported successfully.');
                $('#import-item-id').replaceWith($('<span>✔</span>'));

            },
            error: function(xhr, status, error) {
                $('#import-item-result').html('Error importing items: ' + xhr.responseText);
            }
        });
    });

    $("#import-order-id").click(function(){       
        $.ajax({
          url: ajax_actions.ajaxurl,
          type : "get",
          dataType : "json",
          data: {
            action: 'get_orders',
          },        
            beforeSend: function() {
                $('#import-order-result').html('Importing Orders...');
            },
            success: function(responce) {
                if ( responce.data ){
                    $('#import-order-result').html('Orders imported successfully.');
                    $('#import-order-id').replaceWith($('<span>✔</span>'));

                }
            },
            error: function(xhr, status, error) {
                $('#import-order-result').html('Error importing Or: ' + xhr.responseText);
            }
        });                                                                                                                                                                                                                                                                                         
    });

 });

