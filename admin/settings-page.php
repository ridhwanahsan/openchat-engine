<?php
function chatbot_admin_menu()
{
    add_options_page('Chatbot Settings', 'Chatbot', 'manage_options', 'chatbot-settings', 'chatbot_settings_page');
    add_menu_page('Chatbot Analytics', 'Chatbot Analytics', 'manage_options', 'chatbot-analytics', 'chatbot_analytics_page', 'dashicons-chart-bar', 27);
}
add_action('admin_menu', 'chatbot_admin_menu');

function chatbot_settings_page()
{
?>
    <div class="wrap">
        <h1>Chatbot Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('chatbot_options_group');
            ?>
            <div class="tabs">
                <ul class="tab-links">
                    <li class="active"><a href="#tab-api">API Settings</a></li>
                    <li><a href="#tab-ui">UI Settings</a></li>
                    <li><a href="#tab-recaptcha">reCAPTCHA</a></li>
                </ul>

                <div class="tab-content">
                    <div id="tab-api" class="tab active">
                        <?php do_settings_sections('chatbot-api-settings'); ?>
                    </div>
                    <div id="tab-ui" class="tab">
                        <?php do_settings_sections('chatbot-ui-settings'); ?>
                    </div>
                    <div id="tab-recaptcha" class="tab">
                        <?php do_settings_sections('chatbot-recaptcha-settings'); ?>
                    </div>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
    <style>
        .tabs {
            margin-top: 20px;
        }

        .tab-links {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .tab-links:after {
            display: block;
            clear: both;
            content: '';
        }

        .tab-links li {
            margin: 0 5px;
            float: left;
            list-style: none;
        }

        .tab-links a {
            padding: 10px 15px;
            display: inline-block;
            border-radius: 3px 3px 0 0;
            background: #e0e0e0;
            font-size: 16px;
            font-weight: 600;
            color: #444;
            transition: all linear 0.15s;
            text-decoration: none;
        }

        .tab-links a:hover {
            background: #d0d0d0;
        }

        li.active a,
        li.active a:hover {
            background: #fff;
            color: #444;
        }

        .tab-content {
            padding: 15px;
            border-radius: 3px;
            box-shadow: -1px 1px 1px rgba(0, 0, 0, 0.15);
            background: #fff;
        }

        .tab {
            display: none;
        }

        .tab.active {
            display: block;
        }
    </style>
    <script>
        jQuery(document).ready(function() {
            jQuery('.tabs .tab-links a').on('click', function(e) {
                var currentAttrValue = jQuery(this).attr('href');

                // Show/Hide Tabs
                jQuery('.tabs ' + currentAttrValue).show().siblings().hide();

                // Change/remove current tab to active
                jQuery(this).parent('li').addClass('active').siblings().removeClass('active');

                e.preventDefault();
            });
        });
    </script>
<?php
}

function chatbot_settings_init()
{
    register_setting('chatbot_options_group', 'chatbot_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_model', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_recaptcha_site_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_recaptcha_secret_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_recaptcha_enabled', ['sanitize_callback' => 'absint']);
    register_setting('chatbot_options_group', 'chatbot_system_prompt', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('chatbot_options_group', 'chatbot_example_questions', ['sanitize_callback' => 'sanitize_textarea_field']);
    
    register_setting('chatbot_options_group', 'chatbot_header_title', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_bot_avatar', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('chatbot_options_group', 'chatbot_user_avatar', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('chatbot_options_group', 'chatbot_prompt_limit_enabled', ['sanitize_callback' => 'absint']);
    register_setting('chatbot_options_group', 'chatbot_prompt_limit', ['sanitize_callback' => 'absint']);
    register_setting('chatbot_options_group', 'chatbot_prompt_limit_message', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('chatbot_options_group', 'chatbot_typing_indicator_text', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('chatbot_options_group', 'chatbot_support_email_recipient', ['sanitize_callback' => 'sanitize_email']);

    // API Settings
    add_settings_section('chatbot_api_section', 'API Settings', null, 'chatbot-api-settings');

    add_settings_field('chatbot_api_key', 'OpenRouter API Key', function () {
        $val = esc_attr(get_option('chatbot_api_key'));
        echo "<input type='text' name='chatbot_api_key' value='$val' size='50'>";
    }, 'chatbot-api-settings', 'chatbot_api_section');

    add_settings_field('chatbot_model', 'Model (e.g. openai/gpt-3.5-turbo)', function () {
        $val = esc_attr(get_option('chatbot_model'));
        echo "<input type='text' name='chatbot_model' value='$val' size='50'>";
    }, 'chatbot-api-settings', 'chatbot_api_section');

    // UI Settings
    add_settings_section('chatbot_ui_section', 'UI Settings', null, 'chatbot-ui-settings');

    

    add_settings_field('chatbot_header_title', 'Chatbot Header Title', function () {
        $val = esc_attr(get_option('chatbot_header_title', 'SUPPORT'));
        echo "<input type='text' name='chatbot_header_title' value='$val' size='50'>";
        echo "<br><small>The main title text in the chatbot header.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_bot_avatar', 'Bot Avatar URL', function () {
        $val = esc_attr(get_option('chatbot_bot_avatar', ''));
        echo "<input type='text' name='chatbot_bot_avatar' value='$val' size='50'>";
        echo "<br><small>Enter the full URL for the bot's avatar image. Leave blank for the default.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_user_avatar', 'User Avatar URL', function () {
        $val = esc_attr(get_option('chatbot_user_avatar', ''));
        echo "<input type='text' name='chatbot_user_avatar' value='$val' size='50'>";
        echo "<br><small>Enter the full URL for the user's avatar image. Leave blank for the default.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_system_prompt', 'System Prompt', function () {
        $val = esc_textarea(get_option('chatbot_system_prompt', 'You are a helpful assistant.'));
        echo "<textarea name='chatbot_system_prompt' rows='4' cols='60' style='width:98%;max-width:600px;'>$val</textarea>";
        echo "<br><small>The chatbot will strictly follow this prompt. If a user asks something outside the topic, it will reply that it cannot help and only answers questions related to the specified topic.<br><b>Example instruction:</b> <code>Always follow this system prompt strictly. Do not provide answers outside the scope defined by it.</code></small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_example_questions', 'Example Questions', function () {
        $val = esc_textarea(get_option('chatbot_example_questions', ''));
        echo "<textarea name='chatbot_example_questions' rows='4' cols='60' style='width:98%;max-width:600px;'>$val</textarea><br><small>One example question per line. These will be shown as suggestions in the chatbot UI.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_prompt_limit_enabled', 'Enable Prompt Limit', function () {
        $val = get_option('chatbot_prompt_limit_enabled');
        echo "<input type='checkbox' name='chatbot_prompt_limit_enabled' value='1'" . checked(1, $val, false) . "> Enable a limit on the number of prompts a user can send";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_prompt_limit', 'Prompt Limit', function () {
        $val = esc_attr(get_option('chatbot_prompt_limit', '10'));
        echo "<input type='number' name='chatbot_prompt_limit' value='$val' size='10'>";
        echo "<br><small>The maximum number of prompts a user can send in a session.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_prompt_limit_message', 'Prompt Limit Message', function () {
        $val = esc_textarea(get_option('chatbot_prompt_limit_message', 'You have reached the daily limit of %s questions. Please come back tomorrow.'));
        echo "<textarea name='chatbot_prompt_limit_message' rows='2' cols='60' style='width:98%;max-width:600px;'>$val</textarea>";
        echo "<br><small>The message displayed when the prompt limit is reached. Use %s as a placeholder for the limit number.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_typing_indicator_text', 'Typing Indicator Text', function () {
        $val = esc_attr(get_option('chatbot_typing_indicator_text', 'Assistant is typing...'));
        echo "<input type='text' name='chatbot_typing_indicator_text' value='$val' size='50'>";
        echo "<br><small>The text displayed when the chatbot is typing a response.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    add_settings_field('chatbot_support_email_recipient', 'Support Email Recipient', function () {
        $val = esc_attr(get_option('chatbot_support_email_recipient', get_option('admin_email')));
        echo "<input type='email' name='chatbot_support_email_recipient' value='$val' size='40'>";
        echo "<br><small>The email address where chatbot support requests will be sent. Defaults to WordPress admin email.</small>";
    }, 'chatbot-ui-settings', 'chatbot_ui_section');

    // reCAPTCHA Settings
    add_settings_section('chatbot_recaptcha_section', 'reCAPTCHA Settings', null, 'chatbot-recaptcha-settings');

    add_settings_field('chatbot_recaptcha_enabled', 'Enable Google reCAPTCHA', function () {
        $val = get_option('chatbot_recaptcha_enabled');
        echo "<input type='checkbox' name='chatbot_recaptcha_enabled' value='1'" . checked(1, $val, false) . "> Enable reCAPTCHA on chatbot";
    }, 'chatbot-recaptcha-settings', 'chatbot_recaptcha_section');

    add_settings_field('chatbot_recaptcha_site_key', 'Google reCAPTCHA Site Key', function () {
        $val = esc_attr(get_option('chatbot_recaptcha_site_key'));
        echo "<input type='text' name='chatbot_recaptcha_site_key' value='$val' size='50'>";
    }, 'chatbot-recaptcha-settings', 'chatbot_recaptcha_section');

    add_settings_field('chatbot_recaptcha_secret_key', 'Google reCAPTCHA Secret Key', function () {
        $val = esc_attr(get_option('chatbot_recaptcha_secret_key'));
        echo "<input type='text' name='chatbot_recaptcha_secret_key' value='$val' size='50'>";
    }, 'chatbot-recaptcha-settings', 'chatbot_recaptcha_section');
}
add_action('admin_init', 'chatbot_settings_init');
