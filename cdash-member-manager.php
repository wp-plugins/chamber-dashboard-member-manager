<?php
/*
Plugin Name: Chamber Dashboard Member Manager
Plugin URI: http://chamberdashboard.com
Description: Manage the membership levels and payments for your chamber of commerce or other membership based organization
Version: 1.4
Author: Morgan Kay
Author URI: http://wpalchemists.com
*/

/*  Copyright 2014 Morgan Kay and the Fremont Chamber of Commerce (email : info@chamberdashboard.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// ------------------------------------------------------------------------
// REQUIRE MINIMUM VERSION OF WORDPRESS:                                               
// ------------------------------------------------------------------------

function cdashmm_requires_wordpress_version() {
    global $wp_version;
    $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );

    if ( version_compare($wp_version, "3.8", "<" ) ) {
        if( is_plugin_active($plugin) ) {
            deactivate_plugins( $plugin );
            wp_die( "'".$plugin_data['Name']."' requires WordPress 3.8 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
        }
    }
}
add_action( 'admin_init', 'cdashmm_requires_wordpress_version' );

// ------------------------------------------------------------------------
// REQUIRE CHAMBER DASHBOARD BUSINESS DIRECTORY   
// thanks to http://wordpress.stackexchange.com/questions/127818/how-to-make-a-plugin-require-another-plugin                                           
// ------------------------------------------------------------------------

add_action( 'admin_init', 'cdashmm_require_business_directory' );
function cdashmm_require_business_directory() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'chamber-dashboard-business-directory/cdash-business-directory.php' ) ) {
        add_action( 'admin_notices', 'cdashmm_business_directory_notice' );

        deactivate_plugins( plugin_basename( __FILE__ ) ); 

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function cdashmm_business_directory_notice(){
    ?><div class="error"><p><?php _e('Sorry, but the Chamber Dashboard Member Manager requires the <a href="https://wordpress.org/plugins/chamber-dashboard-business-directory/" target="_blank">Chamber Dashboard Business Directory</a> to be installed and active.', 'cdashmm' ); ?></p></div><?php
}


// ------------------------------------------------------------------------
// REGISTER HOOKS & CALLBACK FUNCTIONS:
// ------------------------------------------------------------------------

// Set-up Action and Filter Hooks
register_activation_hook(__FILE__, 'cdashmm_add_defaults');
register_uninstall_hook(__FILE__, 'cdashmm_delete_plugin_options');
add_action('admin_init', 'cdashmm_init' );
add_action('admin_menu', 'cdashmm_add_options_page');
add_filter( 'plugin_action_links', 'cdashmm_plugin_action_links', 10, 2 );

// Require options stuff
require_once( plugin_dir_path( __FILE__ ) . 'options.php' );
// Require views
require_once( plugin_dir_path( __FILE__ ) . 'views.php' );
// Require PayPal handler
require_once( plugin_dir_path( __FILE__ ) . 'paypal-ipn.php' );
// Require payment report
require_once( plugin_dir_path( __FILE__ ) . 'payment-report.php' );

// Initialize language so it can be translated
function cdashmm_language_init() {
  load_plugin_textdomain( 'cdashmm', false, 'chamber-dashboard-member-manager/languages' );
}
add_action('init', 'cdashmm_language_init');

define( 'CDASHMM_STATUS', 'installed' );

// ------------------------------------------------------------------------
// FILTER SO WE CAN SEARCH BY TITLE
// http://wordpress.stackexchange.com/questions/18703/wp-query-with-post-title-like-something
// ------------------------------------------------------------------------

function cdashmm_search_by_title( $where, &$wp_query ) {
    global $wpdb;
    if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql( like_escape( $post_title_like ) ) . '%\'';
    }
    return $where;
}

add_filter( 'posts_where', 'cdashmm_search_by_title', 10, 2 );

// ------------------------------------------------------------------------
// SET UP CUSTOM POST TYPES AND TAXONOMIES
// ------------------------------------------------------------------------

// Register Custom Taxonomy - Membership Status
function cdashmm_register_tax_membership_status() {

    $labels = array(
        'name'                       => _x( 'Membership Statuses', 'Taxonomy General Name', 'cdashmm' ),
        'singular_name'              => _x( 'Membership Status', 'Taxonomy Singular Name', 'cdashmm' ),
        'menu_name'                  => __( 'Membership Statuses', 'cdashmm' ),
        'all_items'                  => __( 'All Membership Statuses', 'cdashmm' ),
        'parent_item'                => __( 'Parent Membership Status', 'cdashmm' ),
        'parent_item_colon'          => __( 'Parent Membership Status:', 'cdashmm' ),
        'new_item_name'              => __( 'New Membership Status Name', 'cdashmm' ),
        'add_new_item'               => __( 'Add New Membership Status', 'cdashmm' ),
        'edit_item'                  => __( 'Edit Membership Status', 'cdashmm' ),
        'update_item'                => __( 'Update Membership Status', 'cdashmm' ),
        'separate_items_with_commas' => __( 'Separate Membership Statuses with commas', 'cdashmm' ),
        'search_items'               => __( 'Search Membership Statuses', 'cdashmm' ),
        'add_or_remove_items'        => __( 'Add or remove Membership Statuses', 'cdashmm' ),
        'choose_from_most_used'      => __( 'Choose from the most used Membership Statuses', 'cdashmm' ),
        'not_found'                  => __( 'Not Found', 'cdashmm' ),
    );
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
    );
    register_taxonomy( 'membership_status', array( 'business' ), $args );

    // Create default statuses
    wp_insert_term(
        'Current', // the term 
        'membership_status', // the taxonomy
        array(
            'description'=> 'Membership is current',
            'slug' => 'current',
        )
    );

    wp_insert_term(
        'Lapsed', // the term 
        'membership_status', // the taxonomy
        array(
            'description'=> 'Membership has lapsed due to lack of payment',
            'slug' => 'lapsed',
        )
    );

}
add_action( 'init', 'cdashmm_register_tax_membership_status', 0 );




// Register Custom Taxonomy - Invoice Status
function cdashmm_register_tax_invoice_status() {

    $labels = array(
        'name'                       => _x( 'Invoice Statuses', 'Taxonomy General Name', 'cdashmm' ),
        'singular_name'              => _x( 'Invoice Status', 'Taxonomy Singular Name', 'cdashmm' ),
        'menu_name'                  => __( 'Invoice Statuses', 'cdashmm' ),
        'all_items'                  => __( 'All Invoice Statuses', 'cdashmm' ),
        'parent_item'                => __( 'Parent Invoice Status', 'cdashmm' ),
        'parent_item_colon'          => __( 'Parent Invoice Status:', 'cdashmm' ),
        'new_item_name'              => __( 'New Invoice Status Name', 'cdashmm' ),
        'add_new_item'               => __( 'Add New Invoice Status', 'cdashmm' ),
        'edit_item'                  => __( 'Edit Invoice Status', 'cdashmm' ),
        'update_item'                => __( 'Update Invoice Status', 'cdashmm' ),
        'separate_items_with_commas' => __( 'Separate Invoice Statuses with commas', 'cdashmm' ),
        'search_items'               => __( 'Search Invoice Statuses', 'cdashmm' ),
        'add_or_remove_items'        => __( 'Add or remove Invoice Statuses', 'cdashmm' ),
        'choose_from_most_used'      => __( 'Choose from the most used Invoice Statuses', 'cdashmm' ),
        'not_found'                  => __( 'Not Found', 'cdashmm' ),
    );
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
    );
    register_taxonomy( 'invoice_status', array( 'invoice' ), $args );

    // Create default statuses
    wp_insert_term(
        'Paid', // the term 
        'invoice_status', // the taxonomy
        array(
            'description'=> 'Invoice has been paid',
            'slug' => 'paid',
        )
    );

    wp_insert_term(
        'Pending', // the term 
        'invoice_status', // the taxonomy
        array(
            'description'=> 'Payment initiated, but not completed',
            'slug' => 'pending',
        )
    );

    wp_insert_term(
        'Overdue', // the term 
        'invoice_status', // the taxonomy
        array(
            'description'=> 'Invoice is overdue',
            'slug' => 'overdue',
        )
    );

}
add_action( 'init', 'cdashmm_register_tax_invoice_status', 0 );



// Register Custom Post Type
function cdashmm_register_cpt_invoice() {

    $labels = array(
        'name'                => _x( 'Invoices', 'Post Type General Name', 'cdashmm' ),
        'singular_name'       => _x( 'Invoice', 'Post Type Singular Name', 'cdashmm' ),
        'menu_name'           => __( 'Invoices', 'cdashmm' ),
        'parent_item_colon'   => __( 'Parent Invoice:', 'cdashmm' ),
        'all_items'           => __( 'All Invoices', 'cdashmm' ),
        'view_item'           => __( 'View Invoice', 'cdashmm' ),
        'add_new_item'        => __( 'Add New Invoice', 'cdashmm' ),
        'add_new'             => __( 'Add New', 'cdashmm' ),
        'edit_item'           => __( 'Edit Invoice', 'cdashmm' ),
        'update_item'         => __( 'Update Invoice', 'cdashmm' ),
        'search_items'        => __( 'Search Invoice', 'cdashmm' ),
        'not_found'           => __( 'Not found', 'cdashmm' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'cdashmm' ),
    );
    $args = array(
        'label'               => __( 'invoice', 'cdashmm' ),
        'description'         => __( 'Invoices for membership dues', 'cdashmm' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', ),
        'taxonomies'          => array( 'invoice_status' ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-cart',
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
    );
    register_post_type( 'invoice', $args );

}
add_action( 'init', 'cdashmm_register_cpt_invoice', 0 );

// tell robots not to index invoices
function cdashmm_hide_invoices_from_robots() {
    $output= '<meta name="robots" content="noindex,follow" />';
    echo $output;
}
add_action('wp_head','cdashmm_hide_invoices_from_robots');

// add unique string to invoice URLs
// http://stackoverflow.com/questions/4518527/customize-the-auto-generation-of-post-slug-in-wordpress
function cdashmm_check_for_new_invoice( $post ) {
    add_post_meta( $post, 'url_lock', 'locked', true );
}
add_action( 'publish_invoice', 'cdashmm_check_for_new_invoice', 10, 2 );

function cdashmm_obfuscate_invoice_slug( $slug, $post_ID, $post_status, $post_type ) {
    $lock = get_post_meta( $post_ID, 'url_lock', true );
    if ( 'invoice' == $post_type && 'locked' != $lock ) {
        $slug = md5( time() );
    }
    return $slug;
}
add_filter( 'wp_unique_post_slug', 'cdashmm_obfuscate_invoice_slug', 10, 4 );




// ------------------------------------------------------------------------
// Connect Invoices to Businesses
// https://github.com/scribu/wp-posts-to-posts/blob/master/posts-to-posts.php
// ------------------------------------------------------------------------

// Create the connection between businesses and invoices
function cdashmm_businesses_and_invoices() {
    p2p_register_connection_type( array(
        'name' => 'invoices_to_businesses',
        'from' => 'invoice',
        'to' => 'business',
        'cardinality' => 'many-to-one',
        'admin_column' => 'from',
    ) );
}
add_action( 'p2p_init', 'cdashmm_businesses_and_invoices' );

// ------------------------------------------------------------------------
// ADD CUSTOM META BOXES
// ------------------------------------------------------------------------

if( class_exists( 'WPAlchemy_MetaBox' ) ) {
    // Create metabox for businesses to display next payment due date
    $membership_metabox = new WPAlchemy_MetaBox(array
    (
        'id' => 'membership_renewal',
        'title' => 'Membership Renewal',
        'types' => array('business'),
        'template' => plugin_dir_path( __FILE__ ) . '/includes/membership_renewal.php',
        'mode' => WPALCHEMY_MODE_EXTRACT,
        'prefix' => '_cdashmm_',
        'context' => 'side',
        'priority' => 'high'
    ));

    // Create metabox for invoices
    $invoice_metabox = new WPAlchemy_MetaBox(array
    (
        'id' => 'invoice_meta',
        'title' => 'Invoice Details',
        'types' => array('invoice'),
        'template' => plugin_dir_path( __FILE__ ) . '/includes/invoice_meta.php',
        'mode' => WPALCHEMY_MODE_EXTRACT,
        'prefix' => '_cdashmm_',
        'context' => 'normal',
        'priority' => 'high'
    ));

    // Create metabox for invoice emails
    $notification_metabox = new WPAlchemy_MetaBox(array
    (
        'id' => 'notification_meta',
        'title' => 'Email Invoice',
        'types' => array('invoice'),
        'template' => plugin_dir_path( __FILE__ ) . '/includes/invoice_notification.php',
        'mode' => WPALCHEMY_MODE_EXTRACT,
        'prefix' => '_cdashmm_',
        'context' => 'side',
        'priority' => 'default'
    ));
}


// Enqueue JS for invoice metabox
function my_enqueue($hook) {
    global $post;  
    global $reports_page;
  
    if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == $reports_page ) {  
        if ( 'invoice' === $post->post_type || 'business' === $post->post_type || $hook == $reports_page ) {       
            wp_enqueue_script( 'invoice-meta', plugin_dir_url(__FILE__) . 'js/invoices.js', array( 'jquery' ) );
            wp_localize_script( 'invoice-meta', 'invoiceajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); 


            global $wp_locale;

            wp_register_script(
                'cdashmm-datetimepicker',
                plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/jquery-ui-timepicker-addon.min.js',
                array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker',)
            );

            wp_enqueue_script('cdashmm-datetimepicker');
            
            $lang = str_replace('_', '-', get_locale());
            $lang_exp = explode('-', $lang);

            if(file_exists(plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang.'.js'))
                $lang_path = plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang.'.js';
            elseif(file_exists(plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang_exp[0].'.js'))
                $lang_path = plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang_exp[0].'.js';
            
            if(isset($lang_path))
            {
                wp_register_script(
                    'cdashmm-datetimepicker-localization',
                    $lang_path,
                    array('jquery', 'cdashmm-datetimepicker')
                );

                wp_enqueue_script('cdashmm-datetimepicker-localization');
            }

            wp_register_style(
                'cdashmm-datetimepicker',
                plugin_dir_url(__FILE__) . 'js/jquery-timepicker-addon/jquery-ui-timepicker-addon.min.css'
            );

            wp_enqueue_style('cdashmm-datetimepicker');
 
        }  
    } 
    
}
add_action( 'admin_enqueue_scripts', 'my_enqueue' );

function cdashmm_update_membership_price() {
 
    $levelid = $_POST['level_id'];
    $cost = get_tax_meta( $levelid, 'cost' );
    $results = $cost;

    die($results);
}
add_action( 'wp_ajax_cdashmm_update_membership_price', 'cdashmm_update_membership_price' );

// When you create an invoice, automatically generate the invoice number

function cdashmm_insert_invoice_id( $post_id ) {

    $invoicefields = array( 
        '_cdashmm_invoice_number',
        '_cdashmm_amount', 
        '_cdashmm_duedate', 
        '_cdashmm_item_membershiplevel', 
        '_cdashmm_item_membershipamt',
        '_cdashmm_item_donation', 
        '_cdashmm_paidamt', 
        '_cdashmm_paiddate',
        '_cdashmm_paymethod',
        '_cdashmm_transaction' 
        );
    $str = $invoicefields;
    update_post_meta( $post_id, 'invoice_meta_fields', $str );

    $invoice_id = cdashmm_calculate_invoice_number();
     
    // update the individual fields
    update_post_meta( $post_id, '_cdashmm_invoice_number', $invoice_id );

}
add_action( 'save_post_invoice', 'cdashmm_insert_invoice_id' );

// ------------------------------------------------------------------------
// ADD COLUMNS TO INVOICES OVERVIEW PAGE
// ------------------------------------------------------------------------

function cdashmm_invoices_overview_columns_headers($defaults) {
    $defaults['invoice_number'] = 'Invoice #';
    $defaults['invoice_amount'] = 'Amount';
    return $defaults;
}

function cdashmm_invoices_overview_columns($column_name, $post_ID) {
    global $invoice_metabox;
    $invoicemeta = $invoice_metabox->the_meta();
    if ($column_name == 'invoice_number') {
        $invoice_number = '';
        if( isset( $invoicemeta['invoice_number'] ) ) {
            $invoice_number = $invoicemeta['invoice_number'];
        }
        echo $invoice_number;
    }    

    if ($column_name == 'invoice_amount') {
        $invoice_amount = '';
        if( isset( $invoicemeta['amount'] ) ) {
            $invoice_amount = $invoicemeta['amount'];
        }
        echo $invoice_amount;
    }  
}

add_filter('manage_invoice_posts_columns', 'cdashmm_invoices_overview_columns_headers', 10);
add_action('manage_invoice_posts_custom_column', 'cdashmm_invoices_overview_columns', 10, 2);


// ------------------------------------------------------------------------
// ADD CUSTOM META DATA TO TAXONOMIES - http://en.bainternet.info/wordpress-taxonomies-extra-fields-the-easy-way/
// ------------------------------------------------------------------------

if( function_exists( 'cdash_requires_wordpress_version' ) ) {
    // configure custom fields
    $config = array(
       'id' => 'member_level_meta',
       'title' => 'Membership Level Details',
       'pages' => array('membership_level'),
       'context' => 'normal',
       'fields' => array(),
       'local_images' => true,
       'use_with_theme' => false
    );

    $member_level_meta = new Tax_Meta_Class($config);
    $member_level_meta->addWysiwyg('perks',array('name'=> 'Membership Level Perks'));
    $member_level_meta->addText('cost',array('name'=> 'Membership Level Cost (number only, no currency symbol'));
    $member_level_meta->Finish();
} else {
    cdashmm_business_directory_notice();
}

// ------------------------------------------------------------------------
// MAKE MEMBERSHIP LEVEL SORTABLE BY PRIORITY
// largely borrowed from https://wordpress.org/plugins/custom-taxonomy-order-ne/
// ------------------------------------------------------------------------

function cdashmm_membership_level_menu() {
    add_submenu_page( '/chamber-dashboard-business-directory/options.php', __('Rank Membership Levels', 'cdashmm'), __('Rank Membership Levels', 'cdashmm'), 'manage_options', 'cdashmm_levels', 'cdashmm_rank_membership_levels' );
}
add_action('admin_menu', 'cdashmm_membership_level_menu');

function cdashmm_sort_membership_level_css() {
    if ( isset($_GET['page']) ) {
        $pos_page = $_GET['page'];
        $pos_args = 'cdashmm_levels';
        $pos = strpos($pos_page,$pos_args);
        if ( $pos === false ) {} else {
            wp_enqueue_style('cdashmm', plugins_url('css/reorder.css', __FILE__), 'screen');
        }
    }
}
add_action('admin_print_styles', 'cdashmm_sort_membership_level_css');

function cdashmm_sort_membership_level_js() {
    if ( isset($_GET['page']) ) {
        $pos_page = $_GET['page'];
        $pos_args = 'cdashmm_levels';
        $pos = strpos($pos_page,$pos_args);
        if ( $pos === false ) {} else {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
        }
    }
}
add_action('admin_print_scripts', 'cdashmm_sort_membership_level_js');

function cdashmm_cmp( $a, $b ) {
    if ( $a->term_order ==  $b->term_order ) {
        return 0;
    } else if ( $a->term_order < $b->term_order ) {
        return -1;
    } else {
        return 1;
    }
}

function cdashmm_rank_membership_levels() {

    $message = "";
    if (isset($_POST['order-submit'])) {
        cdashmm_update_order();
    }
?>
<div class='wrap'>
    <h2><?php echo __('Rank Membership Levels ', 'cdashmm'); ?></h2>
    <form name="custom-order-form" method="post" action="">
        <?php
        $terms = get_terms( 'membership_level', 'hide_empty=0' );
        if ( $terms ) {
            usort($terms, 'cdashmm_cmp');
            ?>
            <div id="poststuff" class="metabox-holder">
                <div class="widget order-widget">
                    <p><?php _e('Order the membership levels by dragging and dropping them into the desired order.', 'cdashmm') ?></p>
                    <div class="misc-pub-section">
                        <ul id="custom-order-list">
                            <?php foreach ( $terms as $term ) : ?>
                            <li id="id_<?php echo $term->term_id; ?>" class="lineitem"><?php echo $term->name; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="misc-pub-section misc-pub-section-last">
                        <div id="publishing-action">
                            <img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="custom-loading" style="display:none" alt="" />
                            <input type="submit" name="order-submit" id="order-submit" class="button-primary" value="<?php _e('Update Order', 'cdashmm') ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>
                    <input type="hidden" id="hidden-custom-order" name="hidden-custom-order" />
                </div>
            </div>
        <?php } else { ?>
            <p><?php _e('No terms found', 'cdashmm'); ?></p>
        <?php } ?>
    </form>

</div>
<?php if ( $terms ) { ?>
<script type="text/javascript">
// <![CDATA[

    jQuery(document).ready(function(jQuery) {
        jQuery("#custom-loading").hide();
        jQuery("#order-submit").click(function() {
            orderSubmit();
        });
        jQuery("#order-alpha").click(function(e) {
            e.preventDefault();
            jQuery("#custom-loading").show();
            orderAlpha();
            //jQuery("#order-submit").trigger("click");
            setTimeout(function(){
                jQuery("#custom-loading").hide();
            },500);
            jQuery("#order-alpha").blur();
        });
    });

    function customtaxorderAddLoadEvent(){
        jQuery("#custom-order-list").sortable({
            placeholder: "sortable-placeholder",
            revert: false,
            tolerance: "pointer"
        });
    };

    addLoadEvent(customtaxorderAddLoadEvent);

    function orderSubmit() {
        var newOrder = jQuery("#custom-order-list").sortable("toArray");
        jQuery("#custom-loading").show();
        jQuery("#hidden-custom-order").val(newOrder);
        return true;
    }

    // accending sort
    function asc_sort(a, b) {
        //return (jQuery(b).text()) < (jQuery(a).text()) ? 1 : -1;
        //console.log (jQuery(a).text());
        return jQuery(a).text().toUpperCase().localeCompare(jQuery(b).text().toUpperCase());
    }

// ]]>
</script>
<?php }
}

/*
 * Function to update the database with the submitted order
 */

function cdashmm_update_order() {
    if (isset($_POST['hidden-custom-order']) && $_POST['hidden-custom-order'] != "") {
        global $wpdb;
        $new_order = $_POST['hidden-custom-order'];
        $IDs = explode(",", $new_order);
        $ids = Array();
        $result = count($IDs);
        for($i = 0; $i < $result; $i++) {
            $id = (int) str_replace("id_", "", $IDs[$i]);
            $wpdb->query( $wpdb->prepare(
                "
                    UPDATE $wpdb->terms SET term_order = '%d' WHERE term_id ='%d'
                ",
                $i,
                $id
            ) );
            $wpdb->query( $wpdb->prepare(
                "
                    UPDATE $wpdb->term_relationships SET term_order = '%d' WHERE term_taxonomy_id ='%d'
                ",
                $i,
                $id
            ) );
            $ids[] = $id;
        }
        echo '<div id="message" class="updated fade"><p>'. __('Order updated successfully.', 'cdashmm').'</p></div>';
        do_action('cdashmm_update_order', $ids);
    } else {
        echo '<div id="message" class="error fade"><p>'. __('An error occured, order has not been saved.', 'cdashmm').'</p></div>';
    }
}

/*
 * cdashmm_apply_order_filter
 * Function to sort the standard WordPress Queries.
 */

function cdashmm_apply_order_filter($orderby, $args) {
    global $customtaxorder_settings;
    $options = $customtaxorder_settings;
    if ( isset( $args['taxonomy'] ) ) {
        $taxonomy = $args['taxonomy'];
    } else {
        $taxonomy = 'category';
    }
    if ( !isset ( $options[$taxonomy] ) ) {
        $options[$taxonomy] = 0; // default if not set in options yet
    }
    if ( $args['orderby'] == 'term_order' ) {
        return 't.term_order';
    } elseif ( $args['orderby'] == 'name' ) {
        return 't.name';
    } elseif ( $options[$taxonomy] == 1 && !isset($_GET['orderby']) ) {
        return 't.term_order';
    } elseif ( $options[$taxonomy] == 2 && !isset($_GET['orderby']) ) {
        return 't.name';
    } else {
        return $orderby;
    }
}
add_filter('get_terms_orderby', 'cdashmm_apply_order_filter', 10, 2);


function cdashmm_object_terms_order_filter( $terms ) {
    global $customtaxorder_settings;
    $options = $customtaxorder_settings;

    if ( empty($terms) || !is_array($terms) ) {
        return $terms; // only work with an array of terms
    }
    foreach ($terms as $term) {
        if ( is_object($term) && isset( $term->taxonomy ) ) {
            $taxonomy = $term->taxonomy;
        } else {
            return $terms; // not an array with objects
        }
        break; // just the first one :)
    }

    if ( !isset ( $options[$taxonomy] ) ) {
        $options[$taxonomy] = 0; // default if not set in options yet
    }
    if ( $options[$taxonomy] == 1 && !isset($_GET['orderby']) ) {
        if (current_filter() == 'get_terms' ) {
            if ( $taxonomy == 'post_tag' || $taxonomy == 'product_tag' ) {
                return $terms;
            }
        }
        usort($terms, 'cdashmm_cmp');
        return $terms;
    }
    return $terms;
}
add_filter( 'wp_get_object_terms', 'cdashmm_object_terms_order_filter', 10, 3 );
add_filter( 'get_terms', 'cdashmm_object_terms_order_filter', 10, 3 );
add_filter( 'get_the_terms', 'cdashmm_object_terms_order_filter', 10, 3 );
add_filter( 'tag_cloud_sort', 'cdashmm_object_terms_order_filter', 10, 3 );



function _cdashmm_taxonomy_order_activate() {
    global $wpdb;
    $init_query = $wpdb->query("SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'");
    if ($init_query == 0) { $wpdb->query("ALTER TABLE $wpdb->terms ADD term_order INT( 4 ) NULL DEFAULT '0'"); }
}
function cdashmm_taxonomy_order_activate($networkwide) {
    global $wpdb;
    if (function_exists('is_multisite') && is_multisite()) {
        $curr_blog = $wpdb->blogid;
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            _cdashmm_taxonomy_order_activate();
        }
        switch_to_blog($curr_blog);
    } else {
        _cdashmm_taxonomy_order_activate();
    }
}
register_activation_hook(__FILE__, 'cdashmm_taxonomy_order_activate');



// ------------------------------------------------------------------------
// Calculate invoice number
// ------------------------------------------------------------------------
function cdashmm_calculate_invoice_number() {
    // find all the invoices
    $args = array( 
        'post_type' => 'invoice',
        'post_status' => 'any'
    );
    
    $invoices = new WP_Query( $args );

    if ( $invoices->have_posts() ) :
        return $invoices->found_posts;
    endif;

    wp_reset_postdata();
}


// ------------------------------------------------------------------------
// figure out how to display price
// ------------------------------------------------------------------------

function cdashmm_display_price( $price ) {
    $options = get_option( 'cdash_directory_options' );
    $cur_symb = $options['currency_symbol'];
    $cur_pos = $options['currency_position'];

    if( "before" == $cur_pos ) {
        $price = $cur_symb . $price;
    }
    if( "after" == $cur_pos ) {
        $price = $price . $cur_symb;
    }

    return $price;
}


// ------------------------------------------------------------------------
// Send invoice notification email
// ------------------------------------------------------------------------

function cdashmm_send_invoice_notification_email() {

    if ( !wp_verify_nonce( $_POST['nonce'], "cdashmm_notification_nonce")) {
        $results = array(
            'message' => '<p class="error">' . __( 'Verification error', 'cdashmm' ) . '</p>'
            );
        wp_send_json($results);
        die();
    }

    // send an error if there isn't a "to" email address
    if( !isset( $_POST['send_to'] ) || $_POST['send_to'] == '') {
        $results = array(
            'message' => '<p class="error">' . __( 'You must select at least one email address!', 'cdashmm' ) . '</p>',
            );
        wp_send_json($results);
        die();
    }

    $options = get_option( 'cdashmm_options' );

    // get the invoice information to put in the email 
    $invoiceid = $_POST['invoice_id'];
    $thisinvoice = get_post( $invoiceid );
    global $invoice_metabox;
    $invoiceinfo = $invoice_metabox->the_meta( $invoiceid );

    $send_tos = array();
    parse_str( $_POST['send_to'], $send_tos );
    $emails = $send_tos['send_to'];

    $to = '';
    foreach( $emails as $email ) {
        $to .= $email . ', ';
    }

    $subject = __( 'Invoice from ', 'cdashmm' ) . $options['orgname'];

    if( isset( $_POST['copy_to'] ) && $_POST['copy_to'] !== '') {
        $copy_tos = array();
        parse_str( $_POST['copy_to'], $copy_tos );
        $emails = $copy_tos['copy_to'];
        foreach( $emails as $email ) {
            $headers[] = "Cc: " . $email . "\r\n";
        }
    }

    $headers[] = "From" . $options['receipt_from_name'] . "<" . $options['receipt_from_email'] . ">\r\n";

    $message = '';
    if( isset( $_POST['message'] ) && $_POST['message'] !== '' ) {
        $message .= sanitize_text_field( $_POST['message'] ) . "\r\n\r\n";
    }
    $message .= __( 'Invoice from: ', 'cdashmm' ) . $options['receipt_from_name'] . "\r\n";
    $message .= __( 'Invoice #: ', 'cdashmm' ) . $invoiceinfo['invoice_number'] . "\r\n";
    $message .= __( 'Amount: ', 'cdashmm' ) . cdashmm_display_price( $invoiceinfo['amount'] ) . "\r\n";
    $message .= __( 'Due date: ', 'cdashmm' ) . $invoiceinfo['duedate'] . "\r\n\r\n";
    $message .= __( 'View this invoice online: ', 'cdashmm' ) . get_the_permalink( $invoiceid ) . "\r\n";

    wp_mail( $to, $subject, $message, $headers );

    $results = array();

    $results['message'] = '<p class="success">' . __( 'Email sent successfully!', 'cdashmm' ) . '</p>';

    // update post meta to record that the message was sent
    $today = date('Y-m-d');

    $oldnotifications = get_post_meta( $invoiceid, '_cdashmm_notification' );

    if( isset( $oldnotifications ) ) {
        $notification_array = $oldnotifications[0];
        $new_notification = array(
                'notification_date' => $today,
                'notification_to' => $to,
            );
        $notification_array[] = $new_notification;

        update_post_meta( $invoiceid, '_cdashmm_notification', $notification_array ); 

    } else {
        $fields = array( '_cdashmm_notification' );
        $str = $fields;
        add_post_meta( $invoiceid, 'notification_meta_fields', $str );

        $notificationinfo = array(
            array(
                'notification_date' => $today,
                'notification_to' => $to,
            )
        );
        add_post_meta( $invoiceid, '_cdashmm_notification', $notificationinfo );    
    }

    $results['today'] = $today;
    $results['to'] = $to;

    wp_send_json($results);

    die();

}
add_action( 'wp_ajax_cdashmm_send_invoice_notification_email', 'cdashmm_send_invoice_notification_email' );

// ------------------------------------------------------------------------
// Cron job - once a day, check for overdue invoices and mark them overdue
// ------------------------------------------------------------------------

if ( ! wp_next_scheduled( 'cdashmm_check_for_overdue_invoices' ) ) {
    wp_schedule_event( time(), 'daily', 'cdashmm_check_for_overdue_invoices' );
}

add_action( 'cdashmm_check_for_overdue_invoices', 'cdashmm_update_overdue_invoices' );

function cdashmm_update_overdue_invoices() {
    // get today's date
    $today = date('Y-m-d');

    // get overdue status
    $overdue_status = get_term_by( 'slug', 'overdue', 'invoice_status' );

    // find invoices with a due date earlier than today
    $args = array( 
        'post_type' => 'invoice',
        'post_status' => 'any',
        'posts_per_page' => -1,                  
        'meta_key' => '_cdashmm_duedate',
        'meta_value' => $today,
        'meta_compare' => '<', 
    );
    
    $overdue = new WP_Query( $args );

    if ( $overdue->have_posts() ) :
        while ( $overdue->have_posts() ) : $overdue->the_post();
            // update invoice status
            wp_set_object_terms( get_the_id(), $overdue_status->term_id, 'invoice_status', false );
        endwhile;
    endif;
    wp_reset_postdata();
    
}

// remove the cron job on deactivation
register_deactivation_hook( __FILE__, 'cdashmm_remove_overdue_invoice_cron_job' );
function cdashmm_remove_overdue_invoice_cron_job() {
    wp_clear_scheduled_hook( 'cdashmm_check_for_overdue_invoices' );
}

?>