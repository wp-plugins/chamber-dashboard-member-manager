jQuery(document).ready(function ($) {
    // When someone picks a membership level, add the price to the total
    $('#level').change(function (evt) {
        var url = invoiceajax.ajaxurl;
        var level = $('#level').val();
        var data = {
            'action': 'cdashmm_update_membership_price',
            'level_id': level,
        };
        // update the membership cost
        $.post(url, data, function (response) {
            $("#item_membershipamt").val(response);
            var val1 = +$("#item_donation").val();
            var val2 = +$("#item_membershipamt").val();
            $("#amount").val(val1+val2);
        });
    });

    // when a donation amount is entered, update the total price
    $("#item_donation").on("change", function(){
          var val1 = +$("#item_donation").val();
          var val2 = +$("#item_membershipamt").val();
          $("#amount").val(val1+val2);
    });

    // make the date fields use the datepicker
    dateOptions = {
        dateFormat: 'yy-mm-dd',
        showButtonPanel: false,
    },

    $('#duedate, #paiddate').datepicker(dateOptions);
    $('#start_date, #end_date').datepicker(dateOptions);
    $('#renewal_date').datepicker(dateOptions);

    // when the "send email" button is clicked, send information to the email function, then update meta data
    $('#notification_submit').on('click', function (e) {
        
        var url = invoiceajax.ajaxurl;
        var invoice_id = $('#invoice_id').val();
        var nonce = $('#cdashmm_notification_nonce').val();
        var send_to = $('.send_to:checked').serialize();
        var copy_to = $('.copy_to:checked').serialize();
        var message = $('#message').val();

        var data = {
            'action': 'cdashmm_send_invoice_notification_email',
            'invoice_id': invoice_id,
            'nonce': nonce,
            'send_to': send_to,
            'copy_to': copy_to,
            'message': message,
        };

        $.post(url, data, function (response) {
            $("#result").html(response.message);
            $(".notification_to").slice(-2, -1).val(response.to);
            $(".notification_date").slice(-2, -1).val(response.today);

        });

    });
});