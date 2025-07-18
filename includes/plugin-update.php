<?php

    // 1. License Key Settings Page
    function openchat_license_page()
    {
        if (isset($_POST['openchat_license_key'])) {
            update_option('openchat_license_key', sanitize_text_field($_POST['openchat_license_key']));
            echo '<div class="updated"><p>License key saved.</p></div>';
        }
        $key = get_option('openchat_license_key', '');
    ?>
  <div class="wrap">
      <h1>OpenChat Engine License Setting</h1>
      <form method="post">
          <input type="password" name="openchat_license_key" value="<?php echo esc_attr($key); ?>" style="width:400px;">
          <input type="submit" value="Save License" class="button button-primary">
          <p class="description">Note: License key verification requires an active internet connection. Please make sure your website is connected to the internet before saving the license key.</p>
          <br><br>
      </form>
  </div>
  <?php
      }

      // 2. Update Checker Hook
      add_filter('site_transient_update_plugins', function ($transient) {
          if (empty($transient->checked)) {
              return $transient;
          }

          require_once (ABSPATH . 'wp-admin/includes/plugin.php');

          $plugin_file = plugin_basename(OPENCHAT_ENGINE_PLUGIN_FILE);
          $license_key = get_option('openchat_license_key');
          if (! $license_key) {
              return $transient;
          }

          $api_url  = 'https://license-api-ocm3.vercel.app/api/check-key?key=' . urlencode($license_key);
          $response = wp_remote_get($api_url);

          if (is_wp_error($response)) {
              return $transient;
          }

          $data = json_decode(wp_remote_retrieve_body($response));
          if (empty($data->valid) || empty($data->version) || empty($data->download_url)) {
              return $transient;
          }

          $plugin_data     = get_plugin_data(OPENCHAT_ENGINE_PLUGIN_FILE);
          $current_version = $plugin_data['Version'];

          if (version_compare($current_version, $data->version, '<')) {
              $transient->response[$plugin_file] = (object) [
                  'slug'        => dirname(plugin_basename(OPENCHAT_ENGINE_PLUGIN_FILE)),
                  'plugin'      => $plugin_file, // ✅ THIS LINE IS VERY IMPORTANT
                  'new_version' => $data->version,
                  'url'         => 'https://github.com/ridhwanahsan/openchat-engine',
                  'package'     => $data->download_url,
                  'tested'      => '6.5',
                  'requires'    => '6.0',
              ];
          }

      return $transient;
  });