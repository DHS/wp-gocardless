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

  // Tabs, might be useful later
  //echo '<h2 class="nav-tab-wrapper"><a href="?page=gocardless_dashboard" class="nav-tab">Dashboard</a> <a href="?page=gocardless_settings" class="nav-tab nav-tab-active">Settings</a></h2>';

  gocardless_admin_dashboard();
  gocardless_admin_settings();

}

function gocardless_admin_dashboard() {

  // Load GoCardless
  gocardless_init();

  $subscriptions = GoCardless_Merchant::find(get_option('gocardless_merchant_id'))->subscriptions();

  if (count($subscriptions) > 0) {

    echo '<h2>Subscriptions</h2>';

?>

<table class="widefat">
  <thead>
    <tr>
      <th>Date created</th>
      <th>Subscription ID</th>
      <th>Email</th>
      <th>Status</th>
      <th>Bills created</th>
      <th>Cancel</th>
    </tr>
  </thead>
  <tfoot>
    <tr>
      <th>Date created</th>
      <th>Subscription ID</th>
      <th>Email</th>
      <th>Status</th>
      <th>Bills created</th>
      <th>Cancel</th>
    </tr>
  </tfoot>
  <tbody>

<?php

  // Loop through subscriptions
  foreach ($subscriptions as $subscription) {

    $subscription->status = ucfirst($subscription->status);

    //$subscription['user'] = array(
    //  'name' => 'David Haywood Smith',
    //  'email' => 'davehs@gmail.com'
    //);
    $subscription->user = GoCardless_User::find($subscription->user_id);

    //$bills = array(1, 2, 3);
    $bills = GoCardless_Merchant::find(get_option('gocardless_merchant_id'))->bills(array('source_id' => $subscription->id));
    $subscription->bills = count($bills);

    //$subscription->date = date('j F Y', strtotime('2011-10-12T13:51:30Z'));
    $subscription->date = date('j F Y', strtotime($subscription->created_at));

    echo <<<HTML
    <tr>
      <td>{$subscription->date}</td>
      <td>{$subscription->id}</td>
      <td>{$subscription->user->email}</td>
      <td>{$subscription->status}</td>
      <td>{$subscription->bills}</td>
      <td class="submit">
        <form action="" method="post">
          <input type="hidden" name="form" value="cancel" />
          <input type="hidden" name="subscription_id" value="{$subscription->id}" />
          <input type="submit" value="Cancel" />
        </form>
      </td>
    </tr>
HTML;

    unset($bills);

  }

?>

  </tbody>
</table>

<?php

  }

}

function gocardless_admin_settings() {

?>

<h2>Setup</h2>

<h3>1. Set up your API keys</h3>

<p>
<a href="https://sandbox.gocardless.com/merchants/sandbox_accounts/new">Sign
up</a> for a GoCardless account. There's an overview of that process
<a href="http://blog.gocardless.com/post/19695292096/goingcardless-an-introduction-to-gocardless-for">here</a>.
</p>

<p>Update the Redirect URI in the GoCardless Developer tab to point to any page or post on your website such as:</p>
<input type="text" readonly="true" value="<?php echo site_url(); ?>" />

<p>Copy your API keys from the GoCardless Developer tab and paste them in below (make sure the 'sandbox account' option is checked).</p>

<form action="" method="post">
<input type="hidden" name="form" value="keys" />
<table>
<tr><td>App ID</td><td><input type="text" name="app_id" value="<?php echo get_option('gocardless_app_id'); ?>" /></td></tr>
<tr><td>App secret</td><td><input type="text" name="app_secret" value="<?php echo get_option('gocardless_app_secret'); ?>" /></td></tr>
<tr><td>Merchant ID</td><td><input type="text" name="merchant_id" value="<?php echo get_option('gocardless_merchant_id'); ?>" /></td></tr>
<tr><td>Access token</td><td><input type="text" name="access_token" value="<?php echo get_option('gocardless_access_token'); ?>" /></td></tr>
<tr><td><input type="checkbox" name="sandbox" id="sandbox" <?php if (get_option('gocardless_sandbox')) { echo 'checked'; } ?>/> <label for="">Sandbox account</label></td></tr>
<tr><td colspan="2" class="submit"><input type="submit" name="submit" value="Update API keys &raquo;" /></td></tr>
</table>
</form>

<h3>2. Configure your subscription</h3>

<form action="" method="post">
<input type="hidden" name="form" value="limit" />
<table>
<tr><td>Name</td><td><input type="text" name="limit_name" value="<?php echo get_option('gocardless_limit_name'); ?>" /></td></tr>
<tr><td>Description</td><td><input type="text" name="limit_description" value="<?php echo get_option('gocardless_limit_description'); ?>" /></td></tr>
<tr><td>Amount</td><td>&pound; <input type="text" name="limit_amount" value="<?php echo get_option('gocardless_limit_amount'); ?>" size="5" /></td></tr>
<tr><td>Interval length</td><td><input type="text" name="limit_interval_length" value="<?php echo get_option('gocardless_limit_interval_length'); ?>" size="5" /></td></tr>
<tr><td>Interval unit</td><td><select name="limit_interval_unit"><option value="month"<?php if (get_option('gocardless_limit_interval_unit') == 'month') { echo 'selected '; } ?>>month</option><option value="day"<?php if (get_option('gocardless_limit_interval_unit') == 'day') { echo ' selected '; } ?>>day</option></select></td></tr>
<!--<tr><td><input type="checkbox" name="limit_calendar_intervals" id="limit_calendar_intervals" <?php if (get_option('gocardless_limit_calendar_intervals')) { echo 'checked'; } ?>/> <label for="limit_calendar_intervals">Align with calendar intervals</label></td></tr>-->
<tr><td colspan="2" class="submit"><input type="submit" name="submit" value="Update subscription &raquo;" /></td></tr>
</table>
</form>

<h3>3. Publish your subscription</h3>

<p>On the page or post where you want your link to appear simply place the following code (the link text will be the subscription name you defined):</p>
<input type="text" readonly="true" value="[GoCardless]" />

<p>You can also return the raw URL. While editing a post click 'Insert link' and paste the following into the URL field:</p>
<input type="text" readonly="true" value="[GoCardless url='true']" />

<p>&nbsp;</p>

<h3>4. Test your subscription</h3>

<p>You can now test your subscription link in the sandbox using the following dummy bank account details:</p>

<blockquote>
<table>
<tr><td>Account number</td><td><input type="text" readonly="true" value="55779911" /></td></tr>
<tr><td>Sort code</td><td><input type="text" readonly="true" value="20-00-00" /></td></tr>
</table>
</blockquote>
<p>&nbsp;</p>

<h3>5. Go live!</h3>

<p>Upgrade your GoCardless sandbox account to a 'production' account using the link at the top of <a href="http://sandbox.gocardless.com">your dashboard</a>.
<br />Paste your 'production' API keys in above and make sure the 'sandbox account' option is <strong>unticked</strong>.</p>
<p>Congratulations - your subscription is now live!</p>
<p>NB. Subscriptions will appear in a table above.</p>
<p>&nbsp;</p>

<?php } ?>
