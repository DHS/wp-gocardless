<?php

/*
Plugin Name: GoCardless Wordpress plugin
Plugin URI: http://github.com/DHS/wp-gocardless
Description: Create GoCardless subscriptions within Wordpress
Version: 0.1.0
Author: David Haywood Smith
Author URI: https://github.com/DHS
License: MIT
*/

// If user is an admin include the file that contains admin functionality
if ( is_admin() ) {
  require_once dirname( __FILE__ ) . '/admin.php';
}

// Initialize the GoCardless PHP library
function gocardless_init() {

  define('GCWP_VERSION', '0.1.0');

  global $gocardless_config;
  global $gocardless_limit;

  $gocardless_config = get_option('gocardless_config');
  $gocardless_limit = get_option('gocardless_limit');

  $gocardless_limit = array_map('stripslashes', $gocardless_limit);

  // Check to see if already instantiated
  if ( ! class_exists('GoCardless')) {

    // Include GoCardless PHP library
    require_once dirname( __FILE__ ) . '/lib/GoCardless.php';

    // Sandbox mode? Defaults to production
    if ($gocardless_config['sandbox']) {
      GoCardless::$environment = 'sandbox';
    }

    // Initialize library
    GoCardless::set_account_details(array(
      'app_id'        => $gocardless_config['app_id'],
      'app_secret'    => $gocardless_config['app_secret'],
      'merchant_id'   => $gocardless_config['merchant_id'],
      'access_token'  => $gocardless_config['access_token'],
      'ua_tag'        => 'gocardless-wp/v' . GCWP_VERSION
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

  global $gocardless_config;
  global $gocardless_limit;

  // Load GoCardless
  gocardless_init();

  // Create $payment_details array
  $payment_details = array();

  // Array of expected vars
  $expected_vars = array('name', 'description', 'amount', 'interval_length', 'interval_unit', 'calendar_intervals');

  // Loop through expected vars, setting $payment_details
  foreach ($expected_vars as $key) {

    // Only set $payment_details if value is not null
    if ($gocardless_limit['limit_' . $key] != null) {
      $payment_details[$key] = $gocardless_limit['limit_' . $key];
    }

  }

  // Uncomment the following to inspect the payment vars
  //echo '<pre>';
  //print_r($payment_details);
  //echo '</pre>';

  // Generate paylink
  $paylink = GoCardless::new_subscription_url($payment_details);

  if ($attrs['url']) {

    // Return raw url
    return $paylink;

  } else {

    // Return link w/text

    if (isset($payment_details['name'])) {

      // Use the link name if available
      $link_text = $payment_details['name'];

    } else {

      // Otherwise show default text
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
    // Get vars found so confirm payment

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
