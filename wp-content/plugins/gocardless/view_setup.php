
<h2>Setup</h2>

<h3>1. Set up your API keys</h3>

<p>
<a href="https://gocardless.com/merchants/new">Sign
up</a> for a GoCardless merchant account.
</p>

<p>Update the Redirect URI in the GoCardless Developer tab to point to any page or post on your website such as:</p>
<input type="text" readonly="true" value="<?php echo site_url(); ?>" />

<p>Copy your API keys from the GoCardless Developer tab and paste them in below.</p>

<form action="" method="post">
<input type="hidden" name="form" value="config" />
<table>
<tr><td>App ID</td><td><input type="text" name="app_id" value="<?php echo $gocardless_config['app_id']; ?>" /></td></tr>
<tr><td>App secret</td><td><input type="text" name="app_secret" value="<?php echo $gocardless_config['app_secret']; ?>" /></td></tr>
<tr><td>Merchant ID</td><td><input type="text" name="merchant_id" value="<?php echo $gocardless_config['merchant_id']; ?>" /></td></tr>
<tr><td>Access token</td><td><input type="text" name="access_token" value="<?php echo $gocardless_config['access_token']; ?>" /></td></tr>
<tr><td><input type="checkbox" name="sandbox" id="sandbox" <?php if ($gocardless_config['sandbox']) { echo 'checked'; } ?>/> <label for="">Sandbox account</label></td></tr>
<tr><td colspan="2" class="submit"><input type="submit" name="submit" value="Update API keys &raquo;" /></td></tr>
</table>
</form>

<h3>2. Configure your subscription</h3>

<form action="" method="post">
<input type="hidden" name="form" value="limit" />
<table>
<tr><td>Name</td><td><input type="text" name="limit_name" value="<?php echo $gocardless_limit['limit_name']; ?>" /></td></tr>
<tr><td>Description</td><td><input type="text" name="limit_description" value="<?php echo $gocardless_limit['limit_description']; ?>" /></td></tr>
<tr><td>Amount</td><td>&pound; <input type="text" name="limit_amount" value="<?php echo $gocardless_limit['limit_amount']; ?>" size="5" /></td></tr>
<tr><td>Interval length</td><td><input type="text" name="limit_interval_length" value="<?php echo $gocardless_limit['limit_interval_length']; ?>" size="5" /></td></tr>
<tr><td>Interval unit</td><td><select name="limit_interval_unit"><option value="month"<?php if ($gocardless_limit['limit_interval_unit'] == 'month') { echo 'selected '; } ?>>month</option><option value="day"<?php if ($gocardless_limit['limit_interval_unit'] == 'day') { echo ' selected '; } ?>>day</option></select></td></tr>
<!--<tr><td><input type="checkbox" name="limit_calendar_intervals" id="limit_calendar_intervals" <?php if ($gocardless_limit['limit_calendar_intervals']) { echo 'checked'; } ?>/> <label for="limit_calendar_intervals">Align with calendar intervals</label></td></tr>-->
<tr><td colspan="2" class="submit"><input type="submit" name="submit" value="Update subscription &raquo;" /></td></tr>
</table>
</form>

<h3>3. Publish your subscription</h3>

<p>On the page or post where you want your link to appear simply place the following code (the link text will be the subscription name you defined):</p>
<input type="text" readonly="true" value="[GoCardless]" />

<p>You can also return the raw URL. While editing a post click 'Insert link' and paste the following into the URL field:</p>
<input type="text" readonly="true" value="[GoCardless url='true']" />
<p>NB. Subscriptions will appear in a table above.</p>

<p>&nbsp;</p>
