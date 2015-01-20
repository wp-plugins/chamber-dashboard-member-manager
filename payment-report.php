<?php

// add submenu page
function cdashmm_add_payment_reports_page() {
	global $reports_page;
	$reports_page = add_submenu_page( 'edit.php?post_type=invoice', 'Payment Report', 'Payment Report', 'delete_posts', 'payment-report', 'cdashmm_generate_payment_report' );
}
add_action('admin_menu', 'cdashmm_add_payment_reports_page');

function cdashmm_generate_payment_report() { ?>

		<h2><?php _e('Payment Report', 'cdashmm'); ?></h2>
	
		<form method='post' action='<?php echo admin_url( 'edit.php?post_type=invoice&page=payment-report'); ?>' id='payroll-report'>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Select Date Range:', 'cdashmm'); ?></th>
					<td>
						From <input type="text" size="10" name="start_date" id="start_date" value="" required /> to <input type="text" size="10" name="end_date" id="end_date" value="" required />
					</td>					
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Generate Report') ?>" />
			</p>
		</form>

		<?php if($_POST) { 
			if( ($_POST['start_date'] == '') || ($_POST['end_date'] == '')) {
				_e('You must enter both a start date and an end date to create a report.', 'cdashmm');
			} elseif($_POST['start_date'] > $_POST['end_date']) {
				_e('The report end date must be after the report begin date.', 'cdashmm');
			} else {
				// find the payments and generate the report
				$args = array( 
				    'post_type' => 'invoice',
				    'posts_per_page' => -1, 
				    'invoice_status' => 'paid',
				    'meta_query' => array(
				    	//'relation' => 'AND',
						array(
							'key'     => '_cdashmm_paiddate',
							'value'   => $_POST['start_date'],
							'compare' => '>=',
						),
						array(
							'key'     => '_cdashmm_paiddate',
							'value'   => $_POST['end_date'],
							'compare' => '<=',
						),
					),
					'meta_key' => '_cdashmm_paiddate',
					'orderby' => 'meta_value',
					'order' => 'ASC',
					);
				
				$invoice_query = new WP_Query( $args );
				
				// The Loop
				if ( $invoice_query->have_posts() ) : ?>
					<table id="payment-report" class="wp-list-table widefat fixed">
						<thead>
							<tr>
								<th class="manage-column">
									<?php _e( 'Date', 'cdashmm' ); ?>
								</th>
								<th class="manage-column">
									<?php _e( 'Amount', 'cdashmm' ); ?>
								</th>
								<th class="manage-column">
									<?php _e( 'Business', 'cdashmm' ); ?>
								</th>
								<th class="manage-column">
									<?php _e( 'Invoice ID', 'cdashmm' ); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php while ( $invoice_query->have_posts() ) : $invoice_query->the_post(); 
								global $invoice_metabox;
	        					$invoice_meta = $invoice_metabox->the_meta();
	        					$date = '';
	        					if( isset( $invoice_meta['paiddate'] ) )
	        						$date = $invoice_meta['paiddate'];
	        					$amount = '';
	        					if( isset( $invoice_meta['amount'] ) ) 
	        						$amount = cdashmm_display_price( $invoice_meta['amount'] );
	        					$thisbusiness = get_posts( array(
								  'connected_type' => 'invoices_to_businesses',
								  'connected_items' => get_the_id(),
								  'nopaging' => true,
								  'suppress_filters' => false
								) );
								foreach( $thisbusiness as $thisone ) {
									$business = '<a href="' . get_edit_post_link( $thisone->ID ) . '">' . $thisone->post_title . '</a>';
								}
	        					$invoice_number = '';
	        					if( isset( $invoice_meta['invoice_number'] ) )
	        						$invoice_number = '<a href="' . get_edit_post_link( get_the_id() ) . '">' . $invoice_meta['invoice_number'] . '</a>';
								?>
								<tr class="alternate">
									<td><?php echo $date; ?></td>
									<td><?php echo $amount; ?></td>
									<td><?php echo $business; ?></td>
									<td><?php echo $invoice_number; ?></td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				<?php endif;
				
				// Reset Post Data
				wp_reset_postdata(); ?>
			

			<?php } 
		}?>

<?php }


?>