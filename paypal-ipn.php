<?php 

// Create a query var so PayPal has somewhere to go
// https://willnorris.com/2009/06/wordpress-plugin-pet-peeve-2-direct-calls-to-plugin-files
function cdashmm_register_query_var($vars) {
    $vars[] = 'cdash-member-manager';
    return $vars;
}
add_filter('query_vars', 'cdashmm_register_query_var');


// If PayPal has gone to our query var, check that it is correct and process the payment
function cdashmm_parse_paypal_ipn_request($wp) {
    // only process requests with "cdash-member-manager=paypal-ipn"
   if (array_key_exists('cdash-member-manager', $wp->query_vars) && $wp->query_vars['cdash-member-manager'] == 'paypal-ipn') {

        // send a 200 message to PayPal IPN so it knows this happened
        header('HTTP/1.1 200 OK'); 

        // process the request.
        $req = 'cmd=_notify-validate';
        foreach($_POST as $key => $value) :
          $value = urlencode(stripslashes($value));
          $req .= "&$key=$value";
        endforeach;
         
        $header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Host: www.paypal.com\r\n";
        $header .= "Connection: close\r\n\r\n";
        $fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
       
        if(!$fp) {
          // HTTP ERROR
        } else {
            fputs ($fp, $header . $req);
            while(!feof($fp)) {
             
                $res = fgets ($fp, 1024);
                 
                $fh = fopen('result.txt', 'w');
                    fwrite($fh, $res);
                    fclose($fh);
                 
                if (strcmp (trim($res), "VERIFIED") == 0) {

                    // gather variables
                    $invoice_id = $_POST['custom'];
                    $transaction_id = $_POST['txn_id'];
                    $paid = get_term_by( 'slug', 'paid', 'invoice_status' );
                    $current = get_term_by( 'slug', 'current', 'membership_status' );
                    $options = get_option( 'cdashmm_options' );
                    $today = date('Y-m-d');

                    // get the invoice
                    $args = array( 
                        'post_type' => 'invoice',
                        'meta_key' => '_cdashmm_invoice_number',
                        'meta_value' => $invoice_id, 
                    );

                    $thisinvoice = new WP_Query( $args );

                    if ( $thisinvoice->have_posts() ) :
                        while ( $thisinvoice->have_posts() ) : $thisinvoice->the_post();
                            $invoice = get_the_id();
                        endwhile;
                    endif;
                    wp_reset_postdata();

                    // update the invoice status
                    if( isset( $invoice ) ) {
                        wp_set_post_terms( $invoice, $paid->term_id, 'invoice_status', false );
                    } else {
                        $invoice_error = __( 'Chamber Dashboard couldn\'t find the member or the invoice associated with this transaction in the database.', 'cdashmm' );
                    }

                    // update the invoice - add transaction ID
                    update_post_meta( $invoice, '_cdashmm_transaction', $transaction_id );
                    update_post_meta( $invoice, '_cdashmm_paiddate', $today );

                    // get the business
                    $args = array( 
                        'post_type' => 'business',
                        'connected_type' => 'invoices_to_businesses',
                        'connected_items' => $invoice,
                    );
                    
                    $thisbusiness = new WP_Query( $args );

                    if ( $thisbusiness->have_posts() ) :
                        while ( $thisbusiness->have_posts() ) : $thisbusiness->the_post();
                            $business = get_the_id();
                        endwhile;
                    endif;
                    wp_reset_postdata();

                    // change business membership status
                    if( isset( $business ) ) {
                        wp_set_post_terms( $business, $current->term_id, 'membership_status', false );
                    } 

                    // update the business's membership renewal date
                    $renewaldate = date('Y-m-d', strtotime('+1 year'));
                    $fields = array( '_cdashmm_renewal_date' );
                    $str = $fields;
                    add_post_meta( $business, 'membership_renewal_fields', $str );
                    update_post_meta( $business, '_cdashmm_renewal_date', $renewaldate );

                    // send email receipt to business
                    $receipt_to = $_POST['payer_email'];
                    $receipt_subject = $options['receipt_subject'];
                    $receipt_message = $options['receipt_message'] . "\r\n\r\n";
                    $receipt_message .= __( 'Transaction details: ', 'cdashmm' ) . "\r\n\r\n";
                    $receipt_message .= __( 'Payment amount: ', 'cdashmm' ) . cdashmm_display_price( $_POST['mc_gross'] ) . "\r\n";
                    $receipt_message .= __( 'Payment date: ', 'cdashmm' ) . $_POST['payment_date'] . "\r\n";
                    $receipt_message .= __( 'View the invoice: ', 'cdashmm' ) . get_the_permalink( $invoice ) . "\r\n";
                    $receipt_headers = "From" . $options['receipt_from_name'] . "<" . $options['receipt_from_email'] . ">\r\n";

                    wp_mail( $receipt_to, $receipt_subject, $receipt_message, $receipt_headers );

                    // send email to site admin (create options fields for whether to send email and who to send it to)
                    $admin_to = $options['admin_email'];
                    $admin_subject = __( 'New Payment Received', 'cdashmm' );
                    $admin_message = __( 'You have just received a new payment from ', 'cdashmm' ) . get_the_title( $business ) . "\r\n";
                    $admin_message .= __( 'Payment amount: ', 'cdashmm' ) . cdashmm_display_price( $_POST['mc_gross'] ) . "\r\n\r\n";
                    $admin_message .= __( 'View the invoice: ', 'cdashmm' ) . get_the_permalink( $invoice ) . "\r\n";
                    if( isset( $invoice_error ) ) {
                        $admin_message .= $invoice_error . "\r\n";
                    }
                    $admin_message .= __( 'View the business: ', 'cdashmm' ) . get_the_permalink( $business ) . "\r\n";
                    if( "draft" == get_post_status( $business) ) {
                        $admin_message .= get_the_title( $business ) . __( 'is a new business, so you need to publish the new business before it will appear in your member directory.', 'cdashmm' );
                    }
                    $admin_headers = "From: Chamber Dashboard <" . $options['receipt_from_email'] . ">\r\n";

                    wp_mail( $admin_to, $admin_subject, $admin_message, $admin_headers );

                  }
                 
                elseif(strcmp (trim($res), "INVALID") == 0) {
                    // probably ought to do something here
                     
                }
                 
            }
        fclose ($fp);
        }
    }
}
add_action('parse_request', 'cdashmm_parse_paypal_ipn_request');



?>