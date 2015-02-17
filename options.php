<?php
/* Options Page for Chamber Dashboard Member Manager */

// --------------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_uninstall_hook(__FILE__, 'cdashmm_delete_plugin_options')
// --------------------------------------------------------------------------------------

// Delete options table entries ONLY when plugin deactivated AND deleted
function cdashmm_delete_plugin_options() {
	delete_option('cdashmm_options');
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_activation_hook(__FILE__, 'cdashmm_add_defaults')
// ------------------------------------------------------------------------------

// Define default option settings
function cdashmm_add_defaults() {
	$tmp = get_option('cdashmm_options');
    if(!is_array($tmp)) {
		delete_option('cdashmm_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
		$arr = array(	
					"orgname" => get_bloginfo( 'name' ),
					"receipt_subject" => "Thank you for your payment!",
					"receipt_message" => "We appreciate your support!  Payment details are below.",
					"receipt_from_name" => get_bloginfo( 'name' ),
					"receipt_from_email" => get_bloginfo( 'admin_email' ),
					"admin_email" => get_bloginfo( 'admin_email' ),
					"suggested_donation" => '0',

		);
		update_option('cdashmm_options', $arr);
	}
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_init', 'cdashmm_init' )
// ------------------------------------------------------------------------------

// Init plugin options to white list our options
function cdashmm_init(){
	register_setting( 'cdashmm_plugin_options', 'cdashmm_options', 'cdashmm_validate_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_menu', 'cdashmm_add_options_page');
// ------------------------------------------------------------------------------

// Add menu page
function cdashmm_add_options_page() {
	add_submenu_page( '/chamber-dashboard-business-directory/options.php', __('Member Manager Options', 'cdashmm'), __('Member Manager Options', 'cdashmm'), 'manage_options', 'cdashmm', 'cdashmm_render_form' );
}


// ------------------------------------------------------------------------------
// CALLBACK FUNCTION SPECIFIED IN: add_options_page()
// ------------------------------------------------------------------------------
// THIS FUNCTION IS SPECIFIED IN add_options_page() AS THE CALLBACK FUNCTION THAT
// ACTUALLY RENDER THE PLUGIN OPTIONS FORM AS A SUB-MENU UNDER THE EXISTING
// SETTINGS ADMIN MENU.
// ------------------------------------------------------------------------------

// Render the Plugin options form
function cdashmm_render_form() {
	?>
	<div class="wrap">
		
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Chamber Dashboard Member Manager Settings', 'cdashmm'); ?></h2>


		<div id="main" style="width: 70%; min-width: 350px; float: left;">
			<!-- Beginning of the Plugin Options Form -->
			<form method="post" action="options.php">
				<?php settings_fields('cdashmm_plugin_options'); ?>
				<?php $options = get_option('cdashmm_options'); ?>

				<table class="form-table">


					<!-- PayPal -->
					<tr>
						<th scope="row"><?php _e('PayPal Email Address', 'cdashmm'); ?></th>
						<td>
							<input type="text" size="35" name="cdashmm_options[paypal_email]" value="<?php if(isset($options['paypal_email'])) { echo $options['paypal_email']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('Enter the email address associated with your PayPal account.  Payments will be sent to this email address.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Organization Name', 'cdashmm'); ?></th>
						<td>
							<input type="text" size="35" name="cdashmm_options[orgname]" value="<?php if(isset($options['orgname'])) { echo $options['orgname']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('This will appear on your invoice and on the PayPal payment page.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Receipt Email Subject', 'cdashmm'); ?></th>
						<td>
							<input type="text" size="35" name="cdashmm_options[receipt_subject]" value="<?php if(isset($options['receipt_subject'])) { echo $options['receipt_subject']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('Subject of email to be sent when an invoice is paid.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Receipt Email From Name', 'cdashmm'); ?></th>
						<td>
							<input type="text" size="35" name="cdashmm_options[receipt_from_name]" value="<?php if(isset($options['receipt_from_name'])) { echo $options['receipt_from_name']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('Name to appear in the "From" field on the receipt email and at the top of your invoice.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Receipt Reply-To Email', 'cdashmm'); ?></th>
						<td>
							<input type="email" size="35" name="cdashmm_options[receipt_from_email]" value="<?php if(isset($options['receipt_from_email'])) { echo $options['receipt_from_email']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('Name to appear in the "From" field on the receipt email.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Receipt Email Message', 'cdashmm'); ?></th>
						<td>
							<textarea name="cdashmm_options[receipt_message]" rows="7" cols="50" type='textarea'><?php echo $options['receipt_message']; ?></textarea><br />
							<span style="color:#666666;margin-left:2px;"><?php _e('Body of email to be sent when an invoice is paid.  This message will be followed by the transaction details.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Admin Notification Email', 'cdashmm'); ?></th>
						<td>
							<input type="email" size="35" name="cdashmm_options[admin_email]" value="<?php if(isset($options['admin_email'])) { echo $options['admin_email']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('Email address that will receive notification of membership payment.', 'cdashmm'); ?></span>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Invoice Contact Information', 'cdashmm'); ?></th>
						<td>
							<span style="color:#666666;margin-left:2px;"><?php _e('Enter your organization\'s name and contact information as you would like to to appear at the top of your invoices.', 'cdashmm'); ?></span><br />
							<?php
								$args = array("textarea_name" => "cdashmm_options[invoice_from]", "textarea_rows" => "5");
								wp_editor( $options['invoice_from'], "from", $args );
							?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Invoice Footer', 'cdashmm'); ?></th>
						<td>
							<span style="color:#666666;margin-left:2px;"><?php _e('Payment information, or anything else you want to appear at the bottom of your invoice.', 'cdashmm'); ?></span><br />
							<?php
								$args = array("textarea_name" => "cdashmm_options[invoice_footer]", "textarea_rows" => "5");
								wp_editor( $options['invoice_footer'], "footer", $args );
							?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Suggested Donation Amount', 'cdashmm'); ?></th>
						<td>
							<input type="text" size="35" name="cdashmm_options[suggested_donation]" value="<?php if(isset($options['suggested_donation'])) { echo $options['suggested_donation']; } ?>" />
							<br /><span style="color:#666666;margin-left:2px;"><?php _e('The membership form includes a field for optional donation.  If you want this field to be pre-filled with a donation amount, enter that amount here (number only, no currency symbols).', 'cdashmm'); ?></span><br />
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e('Suggested Donation Text', 'cdashmm'); ?></th>
						<td>
							<textarea name="cdashmm_options[donation_explanation]" rows="7" cols="50" type='textarea'><?php echo $options['donation_explanation']; ?></textarea><br />
							<span style="color:#666666;margin-left:2px;"><?php _e('This text will appear on the membership form next to the suggested donation field.  It can include information about how your donation will be used, such as "Your donation supports local schools."', 'cdashmm'); ?></span>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'cdashmm') ?>" />
				</p> 
			</form>

		</div><!-- #main -->
			<?php include( plugin_dir_path( __FILE__ ) . '/includes/aside.php' ); ?>
	</div>

	<?php	
}



// Sanitize and validate input. Accepts an array, return a sanitized array.
function cdashmm_validate_options($input) {
	 // strip html from textboxes
	// $input['textarea_one'] =  wp_filter_nohtml_kses($input['textarea_one']); // Sanitize textarea input (strip html tags, and escape characters)
	$input['paypal_email'] =  wp_filter_nohtml_kses($input['paypal_email']); 
	$input['orgname'] =  wp_filter_nohtml_kses($input['orgname']); 
	$input['receipt_subject'] =  wp_filter_nohtml_kses($input['receipt_subject']); 
	$input['receipt_message'] =  wp_filter_nohtml_kses($input['receipt_message']);
	$input['receipt_from_name'] =  wp_filter_nohtml_kses($input['receipt_from_name']);
	$input['receipt_from_email'] =  wp_filter_nohtml_kses($input['receipt_from_email']);
	$input['admin_email'] =  wp_filter_nohtml_kses($input['admin_email']);
	$input['suggested_donation'] =  wp_filter_nohtml_kses($input['suggested_donation']);
	$input['donation_explanation'] =  wp_filter_nohtml_kses($input['donation_explanation']);

	// $input['txt_one'] =  wp_filter_nohtml_kses($input['txt_one']); // Sanitize textbox input (strip html tags, and escape characters)
	return $input;
}

// Display a Settings link on the main Plugins page
function cdashmm_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$cdashmm_links = '<a href="'.get_admin_url().'options-general.php?page=cdash-member-manager/options.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $cdashmm_links );
	}

	return $links;
}

?>