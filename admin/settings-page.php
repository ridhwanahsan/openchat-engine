<?php
function openchat_engine_admin_menu()
{
    add_menu_page('OpenChat', 'OpenChat', 'manage_options', 'openchat-engine', 'openchat_engine_settings_page', 'dashicons-format-chat', 26);
    add_submenu_page('openchat-engine', 'Settings', 'Settings', 'manage_options', 'openchat-engine-settings', 'openchat_engine_settings_page');
    add_submenu_page('openchat-engine', 'Analytics', 'Analytics', 'manage_options', 'openchat-engine-analytics', 'openchat_engine_analytics_page');
    add_submenu_page('openchat-engine', 'License', 'License', 'manage_options', 'openchat-engine-license', 'openchat_license_page');
}
add_action('admin_menu', 'openchat_engine_admin_menu');

function openchat_engine_settings_page()
{
?>
    <div class="wrap">
        <h1>OpenChat Engine Setting</h1>

        <h2 class="nav-tab-wrapper">
            <a href="#api-settings" class="nav-tab nav-tab-active" id="api-settings-tab">API Settings</a>
            <a href="#ui-settings" class="nav-tab" id="ui-settings-tab">UI Settings</a>
            <a href="#recaptcha-settings" class="nav-tab" id="recaptcha-settings-tab">reCAPTCHA Settings</a>
        </h2>

        <form method="post" action="options.php">
            <?php settings_fields('openchat_engine_options_group'); ?>

            <div class="tab-content" id="api-settings">
                <?php do_settings_sections('openchat-engine-api-settings'); ?>
            </div>

            <div class="tab-content" id="ui-settings" style="display:none;">
                <?php do_settings_sections('openchat-engine-ui-settings'); ?>
            </div>

            <div class="tab-content" id="recaptcha-settings" style="display:none;">
                <?php do_settings_sections('openchat-engine-recaptcha-settings'); ?>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.nav-tab-wrapper a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.tab-content').hide();
                $(target).show();
            });

            // Initial tab display
            $('.tab-content').hide();
            $('#api-settings').show();
        });
    </script>
<?php
}

function openchat_engine_settings_init()
{
    register_setting('openchat_engine_options_group', 'openchat_engine_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_model', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_recaptcha_site_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_recaptcha_secret_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_recaptcha_enabled', ['sanitize_callback' => 'absint']);
    register_setting('openchat_engine_options_group', 'openchat_engine_system_prompt', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_example_questions', ['sanitize_callback' => 'sanitize_textarea_field']);
    
    register_setting('openchat_engine_options_group', 'openchat_engine_header_title', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_bot_avatar', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('openchat_engine_options_group', 'openchat_engine_user_avatar', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('openchat_engine_options_group', 'openchat_engine_prompt_limit_enabled', ['sanitize_callback' => 'absint']);
    register_setting('openchat_engine_options_group', 'openchat_engine_prompt_limit', ['sanitize_callback' => 'absint']);
    register_setting('openchat_engine_options_group', 'openchat_engine_prompt_limit_message', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_typing_indicator_text', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('openchat_engine_options_group', 'openchat_engine_support_email_recipient', ['sanitize_callback' => 'sanitize_email']);

    // API Settings
    add_settings_section('openchat_engine_api_section', 'API Settings', null, 'openchat-engine-api-settings');

    add_settings_field('openchat_engine_api_key', 'OpenRouter API Key', function () {
        $val = esc_attr(get_option('openchat_engine_api_key'));
        echo "<input type='text' name='openchat_engine_api_key' value='$val' size='50'>";
    }, 'openchat-engine-api-settings', 'openchat_engine_api_section');

    add_settings_field('openchat_engine_model', 'Model (e.g. openai/gpt-3.5-turbo)', function () {
        $val = esc_attr(get_option('openchat_engine_model'));
        echo "<input type='text' name='openchat_engine_model' value='$val' size='50'>";
    }, 'openchat-engine-api-settings', 'openchat_engine_api_section');

    // UI Settings
    add_settings_section('openchat_engine_ui_section', 'UI Settings', null, 'openchat-engine-ui-settings');

    

    add_settings_field('openchat_engine_header_title', 'OpenChat Engine Header Title', function () {
        $val = esc_attr(get_option('openchat_engine_header_title', 'SUPPORT'));
        echo "<input type='text' name='openchat_engine_header_title' value='$val' size='50'>";
        echo "<br><small>The main title text in the chatbot header.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_bot_avatar', 'Bot Avatar URL', function () {
        $val = esc_attr(get_option('openchat_engine_bot_avatar', ''));
        echo "<input type='text' name='openchat_engine_bot_avatar' value='$val' size='50'>";
        echo "<br><small>Enter the full URL for the bot's avatar image. Leave blank for the default.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_user_avatar', 'User Avatar URL', function () {
        $val = esc_attr(get_option('openchat_engine_user_avatar', ''));
        echo "<input type='text' name='openchat_engine_user_avatar' value='$val' size='50'>";
        echo "<br><small>Enter the full URL for the user's avatar image. Leave blank for the default.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_system_prompt', 'System Prompt', function () {
        $val = esc_textarea(get_option('openchat_engine_system_prompt', 'You are a helpful assistant.'));
        echo "<textarea name='openchat_engine_system_prompt' rows='4' cols='60' style='width:98%;max-width:600px;'>$val</textarea>";
        echo "<br><small>The chatbot will strictly follow this prompt. If a user asks something outside the topic, it will reply that it cannot help and only answers questions related to the specified topic.<br><b>Example instruction:</b> <code>Always follow this system prompt strictly. Do not provide answers outside the scope defined by it.</code></small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_example_questions', 'Example Questions', function () {
        $val = esc_textarea(get_option('openchat_engine_example_questions', ''));
        echo "<textarea name='openchat_engine_example_questions' rows='4' cols='60' style='width:98%;max-width:600px;'>$val</textarea><br><small>One example question per line. These will be shown as suggestions in the chatbot UI.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_prompt_limit_enabled', 'Enable Prompt Limit', function () {
        $val = get_option('openchat_engine_prompt_limit_enabled');
        echo "<input type='checkbox' name='openchat_engine_prompt_limit_enabled' value='1'" . checked(1, $val, false) . "> Enable a limit on the number of prompts a user can send";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_prompt_limit', 'Prompt Limit', function () {
        $val = esc_attr(get_option('openchat_engine_prompt_limit', '10'));
        echo "<input type='number' name='openchat_engine_prompt_limit' value='$val' size='10'>";
        echo "<br><small>The maximum number of prompts a user can send in a session.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_prompt_limit_message', 'Prompt Limit Message', function () {
        $val = esc_textarea(get_option('openchat_engine_prompt_limit_message', 'You have reached the daily limit of %s questions. Please come back tomorrow.'));
        echo "<textarea name='openchat_engine_prompt_limit_message' rows='2' cols='60' style='width:98%;max-width:600px;'>$val</textarea>";
        echo "<br><small>The message displayed when the prompt limit is reached. Use %s as a placeholder for the limit number.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_typing_indicator_text', 'Typing Indicator Text', function () {
        $val = esc_attr(get_option('openchat_engine_typing_indicator_text', 'Assistant is typing...'));
        echo "<input type='text' name='openchat_engine_typing_indicator_text' value='$val' size='50'>";
        echo "<br><small>The text displayed when the chatbot is typing a response.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    add_settings_field('openchat_engine_support_email_recipient', 'Support Email Recipient', function () {
        $val = esc_attr(get_option('openchat_engine_support_email_recipient', get_option('admin_email')));
        echo "<input type='email' name='openchat_engine_support_email_recipient' value='$val' size='40'>";
        echo "<br><small>The email address where chatbot support requests will be sent. Defaults to WordPress admin email.</small>";
    }, 'openchat-engine-ui-settings', 'openchat_engine_ui_section');

    // reCAPTCHA Settings
    add_settings_section('openchat_engine_recaptcha_section', 'reCAPTCHA Settings', null, 'openchat-engine-recaptcha-settings');

    add_settings_field('openchat_engine_recaptcha_enabled', 'Enable Google reCAPTCHA', function () {
        $val = get_option('openchat_engine_recaptcha_enabled');
        echo "<input type='checkbox' name='openchat_engine_recaptcha_enabled' value='1'" . checked(1, $val, false) . "> Enable reCAPTCHA on chatbot";
    }, 'openchat-engine-recaptcha-settings', 'openchat_engine_recaptcha_section');

    add_settings_field('openchat_engine_recaptcha_site_key', 'Google reCAPTCHA Site Key', function () {
        $val = esc_attr(get_option('openchat_engine_recaptcha_site_key'));
        echo "<input type='text' name='openchat_engine_recaptcha_site_key' value='$val' size='50'>";
    }, 'openchat-engine-recaptcha-settings', 'openchat_engine_recaptcha_section');

    add_settings_field('openchat_engine_recaptcha_secret_key', 'Google reCAPTCHA Secret Key', function () {
        $val = esc_attr(get_option('openchat_engine_recaptcha_secret_key'));
        echo "<input type='text' name='openchat_engine_recaptcha_secret_key' value='$val' size='50'>";
    }, 'openchat-engine-recaptcha-settings', 'openchat_engine_recaptcha_section');
}
add_action('admin_init', 'openchat_engine_settings_init');