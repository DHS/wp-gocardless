<?php

// Process admin form udates
function gocardless_admin_update($params = array()) {

  // Check form selector is passed
  if (isset($params['form'])) {

    if ($params['form'] == 'keys' || $params['form'] == 'limit') {

      // Updating API keys
      if ($params['form'] == 'keys') {
        $expected_vars = array('app_id', 'app_secret', 'merchant_id', 'access_token', 'sandbox');
        $response = 'API keys updated!';
      }

      // Updating payment info
      if ($params['form'] == 'limit') {
        $expected_vars = array('limit_name', 'limit_description', 'limit_amount', 'limit_interval_length', 'limit_interval_unit', 'limit_calendar_intervals');
        $response = 'Payment updated!';
      }

      // Loop through expected vars saving with the Wordpress Options mechanism
      foreach ($expected_vars as $key) {

        // Special treatment for heckboxes
        if ($key == 'sandbox' || $key == 'limit_calendar_intervals') {
          if ($_POST[$key] == 'on') {
            $_POST[$key] = 'true';
          } else {
            $_POST[$key] = false;
          }
        }

        // Update Wordress Options value
        update_option('gocardless_' . $key, $_POST[$key]);

      }

    } elseif ($params['form'] == 'cancel') {

      // Load GoCardless
      gocardless_init();

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

// Show the GoCardless admin page
function gocardless_admin() {

  // Title
  echo '<h1>GoCardless Wordpress plugin</h1>';

  // POST vars passed so call form processing method
  if (isset($_POST)) {
    gocardless_admin_update($_POST);
  }

  // Intro sentence
  echo '<p>This plugin allows you to create a link within Wordpress that lets users pay a subscription.</p>';

  // Load dashboard
  gocardless_admin_dashboard();

  // Load setup
  gocardless_admin_setup();

}

function gocardless_admin_dashboard() {

  // Grab timestamp for measuring API data load time
  $start_time = microtime(true);

  // Load GoCardless
  gocardless_init();

  // Fetch subscriptions
  $raw_subscriptions = GoCardless_Merchant::find(get_option('gocardless_merchant_id'))->subscriptions();

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
    $raw_bills = GoCardless_Merchant::find(get_option('gocardless_merchant_id'))->bills();

    // Add bill count to each subscription
    foreach ($raw_bills as $key => $value) {
      if (is_object($subscriptions[$value->source_id])) {
        $subscriptions[$value->source_id]->bill_count++;
      }
    }

    // Fetch user list
    $user_list = GoCardless_Merchant::find(get_option('gocardless_merchant_id'))->users();

    // Fetch individual user as index function doesn't contain full info
    $users = array();
    foreach ($user_list as $key => $value) {
      $users[$value->id] = GoCardless_User::find($value->id);
    }

    // Add user object to subscriptions
    foreach ($subscriptions as $key => $value) {
      $subscriptions[$key]->user = $users[$value->user_id];
    }

    // Load dashboard view
    include 'view_dashboard.php';

    // Show API data load time
    $finish_time = microtime(true);
    $total_time = round(($finish_time - $start_time), 2);
    echo '<p class="description">Data fetched in ' . $total_time . ' seconds.</p>';


  }

}

function gocardless_admin_setup() {

  // Load setup view
  include 'view_setup.php';

}
