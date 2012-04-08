<?php

/*
Plugin Name: GoCardless Wordpress plugin
Plugin URI: http://github.com/gocardless/wp-gocardless
Description: Create GoCardless payments within WordPress
Version: 0.1.0
Author: David Haywood Smith
Author URI: http://gocardless.com
License: MIT
*/

// If user is an admin include the file that contains admin functionality
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/admin.php';
}

// Helper function to include and initialize the GoCardless PHP library
function gocardless_init() {

  // Check to see if already instantiated
  if ( ! class_exists('GoCardless')) {

    // Include GoCardless PHP library
    require_once dirname( __FILE__ ) . '/lib/GoCardless.php';

    // Sandbox mode? Defaults to production
    if (get_option('gocardless_sandbox') == true) {
      GoCardless::$environment = 'sandbox';
    }

    // Initialize library
    GoCardless::set_account_details(array(
      'app_id'        => get_option('gocardless_app_id'),
      'app_secret'    => get_option('gocardless_app_secret'),
      'merchant_id'   => get_option('gocardless_merchant_id'),
      'access_token'  => get_option('gocardless_access_token')
    ));

  }

}

// Admin menu option
function gocardless_admin_menu_option() {

  if (function_exists('add_submenu_page')) {
	  add_submenu_page('plugins.php', 'GoCardless', 'GoCardless', 'manage_options', 'gocardless_admin', 'gocardless_admin');
	}

}

// Bind admin menu option
add_action('admin_menu', 'gocardless_admin_menu_option');

// [GoCardless] shortcode
function gocardless_shortcode($attrs) {

  // Load GoCardless
  gocardless_init();

  // Create $payment_details array
  $payment_details = array();

  // Array of expected vars
  $expected_vars = array('name', 'description', 'amount', 'interval_length', 'interval_unit', 'calendar_intervals');

  // Loop through expected vars, setting $payment_details
  foreach ($expected_vars as $key) {

    // Fetch value from WP Options
    $value = get_option('gocardless_limit_' . $key);

    // Only set $payment_details if value is not null
    if ($value != null) {
      $payment_details[$key] = get_option('gocardless_limit_' . $key);
    }

  }

  //echo '<pre>';
  //print_r($payment_details);
  //echo '</pre>';

  // Generate paylink
  $paylink = GoCardless::new_subscription_url($payment_details);

  if ($attrs['url'] == true) {

    // Return raw url
    return $paylink;

  } else {

    // Return link w/text

    if (isset($payment_details['name'])) {
      $link_text = $payment_details['name'];
    } else {
      $link_text = 'New subscription';
    }

    return '<a href="' . $paylink . '">' . $link_text . '</a>';

  }

}

// Bind shortcode function
add_shortcode('GoCardless', 'gocardless_shortcode');

// Confirm the payment
function gocardless_confirm() {

  if (isset($_GET['resource_id']) && isset($_GET['resource_type'])) {
    // Get vars found so let's try confirming payment

    // Load GoCardless
    gocardless_init();

    // Params for confirming the resource
    $confirm_params = array(
      'resource_id'   => $_GET['resource_id'],
      'resource_type' => $_GET['resource_type'],
      'resource_uri'  => $_GET['resource_uri'],
      'signature'     => $_GET['signature']
    );

    // State is optional
    if (isset($_GET['state'])) {
      $confirm_params['state'] = $_GET['state'];
    }

    // Confirm the resource
    $confirmed_resource = GoCardless::confirm_resource($confirm_params);

  }

}

// Bind confirmation function to the footer of every page (not ideal)
add_action('wp_footer', 'gocardless_confirm');
