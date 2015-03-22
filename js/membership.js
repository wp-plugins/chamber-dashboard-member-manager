jQuery(document).ready(function ($) {
// When someone picks a membership level, add the price to the total
    $('#level').change(function (evt) {
        var url = membershipformajax.ajaxurl;
        var level = $('#level').val();
        var nonce = $('#cdashmm_membership_nonce').val();
        var data = {
            'action': 'cdashmm_update_total_amount',
            'level_id': level,
            'nonce': nonce
        };
        // update the subtotal, then add it to the donation to get the total
        $.post(url, data, function (response) {
            $("#subtotal").val(response);
            var val1 = +$("#donation").val();
            var val2 = +$("#subtotal").val();
            $("#total").val(val1+val2);
            $("#amount_1").val(response);
        });
    });

// when the donation amount changes, add it to the subtotal to get the total
    $("#donation").on("change", function(){
          var val1 = +$("#donation").val();
          var val2 = +$("#subtotal").val();
          $("#total").val(val1+val2);
          $("#amount_2").val(val1);
    });

 // when business name changes, check to see whether the business is already in the database
    $('#name').change(function (evt) {
        var url = membershipformajax.ajaxurl;
        var name = $('#name').val();
        var nonce = $('#cdashmm_membership_nonce').val();
        var data = {
            'action': 'cdashmm_find_existing_business',
            'nonce': nonce,
            'name': name
        };
        // insert the business selection form into the page
        $.post(url, data, function (response) {
            jQuery("#business-picker").html(response);
        });

    });

// when a business is selected, fill in the form
    $('#business-picker').on('change', 'input[name=business_id]:radio', function (evt) {
        var url = membershipformajax.ajaxurl;
        var business_id = $('input[name=business_id]:checked', '#membership_form').val()
        var nonce = $('#cdashmm_membership_nonce').val();
        var data = {
            'action': 'cdashmm_prefill_membership_form',
            'nonce': nonce,
            'business_id': business_id,
        };
        // fill in the form
        $.post(url, data, function (response) {
            $("#address").val(response.address);
            $("#city").val(response.city);
            $("#state").val(response.state);
            $("#zip").val(response.zip);
            $("#phone").val(response.phone);
            $("#email").val(response.email);
            $("#business_id").val(response.business_id);
            $("#name").val(response.business_name);
        });

    });

// when user hits the submit button on the become a member page, create/update the business and create the invoice
    $('#membership_form').on('submit', function (e) {
        if($("#membership_form")[0].checkValidity()) {
            $("#membership_form").addClass("loading");

            console.log("valid");

            var url = membershipformajax.ajaxurl;
            var method = $("input[name=method]:checked").val()
            var business_id = $('#business_id').val();
            var name = $('#name').val();
            var address = $('#address').val();
            var city = $('#city').val();
            var state = $('#state').val();
            var zip = $('#zip').val();
            var phone = $('#phone').val();
            var email = $('#email').val();
            var membership_level = $('select[name="level"]').val()
            var member_amt = $('#subtotal').val();
            var donation = $('#donation').val();
            var total = $('#total').val();
            var invoice_id = $('#invoice_id').val();
            var nonce = $('#cdashmm_membership_nonce').val();
            var data = {
                'action': 'cdashmm_process_membership_form',
                'method': method,
                'business_id': business_id,
                'name': name,
                'address': address,
                'city': city,
                'state': state,
                'zip': zip,
                'phone': phone,
                'email': email,
                'membership_level': membership_level,
                'member_amt': member_amt,
                'donation': donation,
                'total': total,
                'invoice_id': invoice_id,
                'nonce': nonce
            };

            if(method == "check"){
                e.preventDefault();
                $.post(url, data, function (response) {
                    window.location = response;
                });
            }else{
                $.post(url, data, function (response) {
                    $("#invoice_id").val(response);
                });
            }

            
            
        }else console.log("invalid form");
        


    });

// make membership form validate
$('form').h5Validate();

});