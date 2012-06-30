<h2>Subscriptions</h2>

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

    // Formatting
    $subscription->status = ucfirst($subscription->status);
    $subscription->date = date(get_option('date_format'), strtotime($subscription->created_at));

    echo <<<HTML
    <tr>
      <td>{$subscription->date}</td>
      <td>{$subscription->id}</td>
      <td>{$subscription->user->email}</td>
      <td>{$subscription->status}</td>
      <td>{$subscription->bill_count}</td>
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
