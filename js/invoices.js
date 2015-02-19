jQuery(document).ready(function ($) {
    var numberify = function(numberString) {
      return Number(numberString.replace(/[^0-9.]/g, ''))
    }
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
        });
    });

    // add up the total invoice amount when someone clicks the calculate button
    $('#calculate').on('click', function (e) {
        var val1 = numberify($("#item_donation").val());
        var val2 = numberify($("#item_membershipamt").val());

        var $form = $('#post'),
        $summands = $form.find('.item_amount');

        var sum = 0;
        $summands.each(function ()
        {
            var value = numberify($(this).val());
            if (!isNaN(value)) sum += value;
        });

        $('#amount').val(sum+val1+val2);

    });

    // add up the total invoice amount right before it is saved
    $('#publish').on('click', function (e) {
        var val1 = +$("#item_donation").val();
        var val2 = +$("#item_membershipamt").val();

        var $form = $('#post'),
            $summands = $form.find('.item_amount'),
            $sumDisplay = $('#amount');

            var sum = 0;
            $summands.each(function ()
            {
                var value = Number($(this).val());
                if (!isNaN(value)) sum += value;
            });

            $sumDisplay.val(sum+val1+val2);

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
            $(".notification_to").last().val(response.to);
            $(".notification_date").last().val(response.today);

        });

    });
});