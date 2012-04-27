<?php

// Show the GoCardless admin page
function gocardless_admin() {

  global $gocardless_config;
  global $gocardless_limit;

  // Load GoCardless
  gocardless_init();

  // Title
  echo '<h1>GoCardless Wordpress plugin</h1>';

  // POST vars passed so call form processing method
  if (isset($_POST)) {
    gocardless_admin_update($_POST);
  }

  // Intro sentence
  echo '<p>This plugin allows you to create a link within Wordpress that lets
  users pay a subscription.</p>';

  // Load dashboard
  if (  isset($gocardless_config['app_id'])
        && isset($gocardless_config['app_secret'])
        && isset($gocardless_config['merchant_id'])
        && isset($gocardless_config['access_token'])) {

    gocardless_admin_dashboard();

  }


  // Load setup form
  gocardless_admin_setup();

}

// Show the admin dashboard
function gocardless_admin_dashboard() {

  global $gocardless_config;
  global $gocardless_limit;

  // Grab timestamp for measuring API data load time
  $start_time = microtime(true);

  // Fetch subscriptions
  $raw_subscriptions = GoCardless_Merchant::find($gocardless_config['merchant_id'])->subscriptions();

  if (count($raw_subscriptions) > 0) {

    // Sort subscriptions, most recent first

    $index = array();
    foreach ($raw_subscriptions as $key => $value) {
        $index[$key] = $value->created_at;
    }

    arsort($index);

    $subscriptions = array();
    foreach ($index as $key => $value) {
      $subscriptions[$raw_subscriptions[$key]->id] = $raw_subscriptions[$key];
    }

    // End sorting

    // Fetch bills
    $raw_bills = GoCardless_Merchant::find($gocardless_config['merchant_id'])
      ->bills();

    // Add bill count to each subscription
    foreach ($raw_bills as $key => $value) {
      if (is_object($subscriptions[$value->source_id])) {
        $subscriptions[$value->source_id]->bill_count++;
      }
    }

    // Fetch user list
    $user_list = GoCardless_Merchant::find($gocardless_config['merchant_id'])
      ->users();

    // Fetch individual user as index function doesn't contain full info
    $users = array();
    foreach ($user_list as $key => $value) {
      $users[$value->id] = GoCardless_User::find($value->id);
    }

    // Add user object to subscriptions
    foreach ($subscriptions as $key => $value) {
      $subscriptions[$key]->user = $users[$value->user_id];
    }

    // Load dashboard view, requires $subscriptions
    include 'view_dashboard.php';

    // Show API data load time
    $finish_time = microtime(true);
    $total_time = round(($finish_time - $start_time), 2);
    echo '<p class="description">Data fetched in ' . $total_time .
      ' seconds.</p>';


  }

}

// Show admin setup
function gocardless_admin_setup() {

  global $gocardless_config;
  global $gocardless_limit;

  // Load setup view, requires $gocardless_config and $gocardless_limit
  include 'view_setup.php';

}

// Process admin form udates
function gocardless_admin_update($params = array()) {

  global $gocardless_config;
  global $gocardless_limit;

  // Check form selector is passed
  if (isset($params['form'])) {

    if ($params['form'] == 'config' || $params['form'] == 'limit') {

      // Updating API config
      if ($params['form'] == 'config') {
        $expected_vars = array(
          'app_id', 'app_secret', 'merchant_id', 'access_token', 'sandbox'
        );
        $response = 'API keys updated!';
      }

      // Updating payment info
      if ($params['form'] == 'limit') {
        $expected_vars = array(
          'limit_name', 'limit_description', 'limit_amount',
          'limit_interval_length', 'limit_interval_unit',
          'limit_calendar_intervals'
        );
        $response = 'Payment updated!';
        $gocardless_limit = $to_save;
      }

      // Loop through expected vars creating array
      foreach ($expected_vars as $key) {

        // Special treatment for checkboxes
        if ($key == 'sandbox' || $key == 'limit_calendar_intervals') {
          if ($_POST[$key] == 'on') {
            $to_save[$key] = 'true';
          } else {
            $to_save[$key] = false;
          }
        } else {
          $to_save[$key] = $_POST[$key];
        }

      }

      // Save with Wordress Options value
      update_option('gocardless_' . $params['form'], $to_save);

      // Run the initialize function again to load vars
      gocardless_init();

    } elseif ($params['form'] == 'cancel') {

      if ($_POST['subscription_id']) {
        // ID found

        // Cancel subscription
        GoCardless_Subscription::find($_POST['subscription_id'])->cancel();

        $response = 'Subscription cancelled!';

      } else {
        // ID not found, fail

        $response = 'Subscription not found!';

      }

    }

    // Return a message
    echo '<div class="updated fade"><p>' . $response . '</p></div>';

  }

}
