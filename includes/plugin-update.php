<?php 


// 1. License Key Settings Page
add_action('admin_menu', function() {
  add_options_page('OpenChat License', 'OpenChat License', 'manage_options', 'openchat-license', 'openchat_license_page');
});

function openchat_license_page() {
  if (isset($_POST['openchat_license_key'])) {
      update_option('openchat_license_key', sanitize_text_field($_POST['openchat_license_key']));
      echo '<div class="updated"><p>License key saved.</p></div>';
  }
  $key = get_option('openchat_license_key', '');
  ?>
  <div class="wrap">
      <h1>Enter Your License Key</h1>
      <form method="post">
          <input type="text" name="openchat_license_key" value="<?php echo esc_attr($key); ?>" style="width:400px;">
          <br><br>
          <input type="submit" value="Save License" class="button button-primary">
      </form>
  </div>
  <?php
}

// 2. Update Checker Hook
add_filter('site_transient_update_plugins', function ($transient) {
  if (empty($transient->checked)) return $transient;

  $plugin_file = plugin_basename(__FILE__);
  $license_key = get_option('openchat_license_key');
  if (!$license_key) return $transient;

$api_url = 'https://license-api-ocm3.vercel.app/api/check-key?key=' . urlencode($license_key);


  $response = wp_remote_get($api_url);

  if (is_wp_error($response)) return $transient;

  $data = json_decode(wp_remote_retrieve_body($response));
  if (empty($data->valid) || empty($data->version)) return $transient;

  $plugin_data = get_plugin_data(__FILE__);
  $current_version = $plugin_data['Version'];

  if (version_compare($current_version, $data->version, '<')) {
      $transient->response[$plugin_file] = (object) [
          'slug' => 'openchat-engine',
          'new_version' => $data->version,
          'url' => 'https://github.com/ridhwanahsan/openchat-engine',
          'package' => $data->download_url
      ];
  }

  return $transient;
});