<?php
add_action('wp_ajax_send_chatbot_message', 'handle_chatbot_message');
add_action('wp_ajax_nopriv_send_chatbot_message', 'handle_chatbot_message');

function handle_chatbot_message()
{
    // Google reCAPTCHA validation
    $recaptcha_enabled = get_option('chatbot_recaptcha_enabled');
    if ($recaptcha_enabled) {
        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response'] ?? '');
        if (empty($recaptcha_response)) {
            wp_send_json_error(['message' => 'reCAPTCHA response missing.'], 400);
        }
        $secret_key = trim(get_option('chatbot_recaptcha_secret_key'));
        if (empty($secret_key)) {
            wp_send_json_error(['message' => 'reCAPTCHA secret key missing. Please set it in the plugin settings.'], 400);
        }
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ],
        ]);
        $recaptcha_result = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($recaptcha_result['success']) || $recaptcha_result['success'] !== true) {
            wp_send_json_error(['message' => 'reCAPTCHA verification failed.'], 403);
        }
    }

    // Block obvious bots by user-agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (preg_match('/bot|crawl|spider|wget|curl|python/i', $user_agent)) {
        wp_send_json_error(['message' => '❌ Automated bots are not allowed.'], 403);
    }

    // Per-user daily rate limit: max 10 questions per day
    function get_user_chat_id()
    {
        // Track user with a combination of IP + browser cookie
        $ip     = $_SERVER['REMOTE_ADDR'];
        $cookie = isset($_COOKIE['chat_user_id']) ? sanitize_text_field($_COOKIE['chat_user_id']) : wp_generate_uuid4();

        // Set cookie if not set
        if (! isset($_COOKIE['chat_user_id'])) {
            setcookie('chat_user_id', $cookie, time() + (86400 * 30), "/");
        }

        return md5($ip . '_' . $cookie);
    }

    $user_id       = get_user_chat_id();
    $transient_key = 'chat_count_' . $user_id;
    $count         = (int) get_transient($transient_key);

    if ($count >= 10) {
        wp_send_json_error(['message' => '❌ You have reached the daily limit of 10 questions. Please come back tomorrow.'], 429);
    }

    // Increment count
    set_transient($transient_key, $count + 1, DAY_IN_SECONDS);

    // Check nonce for security
    if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    // Sanitize and get data
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $api_key = trim(get_option('chatbot_api_key'));
    $model   = trim(get_option('chatbot_model'));
    if (empty($model)) {
        $model = 'openai/gpt-3.5-turbo';
    }

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is missing. Please set your OpenRouter API key in the plugin settings.'], 400);
    }
    if (empty($message)) {
        wp_send_json_error(['message' => 'Message is empty.'], 400);
    }
    if (empty($model)) {
        wp_send_json_error(['message' => 'Model is not set. Please select a model in the plugin settings.'], 400);
    }

    // Use only the admin's system prompt, as set in settings
    $system_prompt = get_option('chatbot_system_prompt');
    if (empty($system_prompt)) {
        $system_prompt = "You are a helpful assistant.";
    }
    // Always append strict instruction for topic adherence
    $system_prompt .= "\nYou must only answer questions that are directly related to the topic defined in this system prompt. If a user asks about any other topic, politely reply: 'Sorry, I can only answer questions related to the topic specified above.' Do not provide information or help on any other topics.";

    // Prepare API request
    $api_url = 'https://openrouter.ai/api/v1/chat/completions';
    $headers = [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    ];
    $body = json_encode([
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $message],
        ],
    ]);

    $response = wp_remote_post($api_url, [
        'headers' => $headers,
        'body'    => $body,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot] WP_Error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'Request error: ' . $response->get_error_message()], 500);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body      = wp_remote_retrieve_body($response);
    $data      = json_decode($body, true);

    // Log the raw response for debugging
    if ($http_code !== 200) {
        error_log('[Chatbot] HTTP ' . $http_code . ': ' . $body);
    }

    // If OpenRouter returns an error, show it
    if (isset($data['error'])) {
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
        // Handle rate limit exceeded (HTTP 429)
        if ($http_code == 429 || stripos($error_message, 'rate limit') !== false) {
            $user_message = 'You have reached the free usage limit for today. Please try again later or add credits to your OpenRouter account to continue using the chatbot.';
        } elseif (stripos($error_message, 'Invalid authentication') !== false || stripos($error_message, 'Invalid API key') !== false) {
            $user_message = 'Your OpenRouter API key is invalid. Please check your plugin settings.';
        } elseif (stripos($error_message, 'model') !== false) {
            $user_message = 'The selected model is invalid or not available. Please check your plugin settings.';
        } else {
            $user_message = 'API error: ' . $error_message;
        }
        wp_send_json_error([
            'message' => $user_message,
            'status'  => $http_code,
            'raw'     => $body,
        ], 500);
    }

    // If OpenRouter returns choices, return the reply
    if (isset($data['choices'][0]['message']['content'])) {
        $bot_reply = $data['choices'][0]['message']['content'];

        // Log interaction for analytics
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_analytics';
        $session_id = sanitize_text_field($_POST['session_id'] ?? 'unknown');
        
        error_log('[Chatbot Analytics Debug] Session ID: ' . $session_id);
        error_log('[Chatbot Analytics Debug] User Message: ' . $message);
        error_log('[Chatbot Analytics Debug] Bot Response: ' . $bot_reply);

        $insert_result = $wpdb->insert($table_name, [
            'session_id' => $session_id,
            'user_message' => $message,
            'bot_response' => $bot_reply,
            'timestamp' => current_time('mysql'),
        ]);

        if ($insert_result === false) {
            error_log('[Chatbot Analytics Debug] Database Insert Error: ' . $wpdb->last_error);
        } else {
            error_log('[Chatbot Analytics Debug] Database Insert Success. Rows affected: ' . $insert_result);
        }

        wp_send_json_success(['reply' => $bot_reply]);
    }

    // If OpenRouter returns nothing useful, show debug info
    wp_send_json_error(['message' => 'No valid response from API. Please check your API key and model in the plugin settings.',
        'status'  => $http_code,
        'raw'     => $body,
    ], 500);
}

add_action('wp_ajax_save_email_support_request', 'save_email_support_request');
add_action('wp_ajax_nopriv_save_email_support_request', 'save_email_support_request');

function save_email_support_request() {
    // Check nonce for security
    if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $problem = isset($_POST['problem']) ? sanitize_textarea_field($_POST['problem']) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email address.'], 400);
    }
    if (empty($problem)) {
        wp_send_json_error(['message' => 'Problem description cannot be empty.'], 400);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_email_support';

    $insert_result = $wpdb->insert(
        $table_name,
        [
            'client_email' => $email,
            'problem_description' => $problem,
            'submission_time' => current_time('mysql'),
        ],
        ['%s', '%s', '%s']
    );

    if ($insert_result === false) {
        error_log('[Chatbot Email Support Debug] Database Insert Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to save email support request.'], 500);
    } else {
        // Send email notification to admin
        $recipient_email = get_option('chatbot_support_email_recipient', get_option('admin_email'));
        $subject = 'New Chatbot Email Support Request';
        $body = "A new email support request has been submitted:\n\n" .
                "Client Email: " . $email . "\n" .
                "Problem Description: " . $problem;

        // --- START: Added Debugging Logs for wp_mail --- 
        error_log('[Chatbot Email Support Debug] Attempting to send admin email.');
        error_log('[Chatbot Email Support Debug] Admin Email: ' . $recipient_email);
        error_log('[Chatbot Email Support Debug] Subject: ' . $subject);
        error_log('[Chatbot Email Support Debug] Body: ' . $body);
        // --- END: Added Debugging Logs for wp_mail --- 

        $mail_sent = wp_mail($recipient_email, $subject, $body);

        if ($mail_sent) {
            error_log('[Chatbot Email Support Debug] Admin email notification sent successfully to: ' . $admin_email);
            wp_send_json_success(['message' => 'Email support request saved successfully and admin notified.']);
        } else {
            error_log('[Chatbot Email Support Debug] Failed to send admin email notification to: ' . $admin_email);
            // You might want to send_json_error here if email notification is critical
            wp_send_json_success(['message' => 'Email support request saved successfully, but admin notification failed.']);
        }
    }
}

function clear_chatbot_analytics_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_analytics';

    error_log('[Chatbot Clear Analytics Debug] clear_chatbot_analytics_data function called.');

    // Check for nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_analytics_nonce')) {
        error_log('[Chatbot Clear Analytics Debug] Nonce verification failed.');
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        error_log('[Chatbot Clear Analytics Debug] User does not have sufficient permissions.');
        wp_send_json_error(['message' => 'You do not have sufficient permissions to clear analytics data.'], 403);
    }

    try {
        $result = $wpdb->query("TRUNCATE TABLE $table_name");

        if ($result === false) {
            error_log('[Chatbot Clear Analytics Debug] Database TRUNCATE failed: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Failed to clear analytics data: ' . $wpdb->last_error]);
        } else {
            error_log('[Chatbot Clear Analytics Debug] Database TRUNCATE successful. Rows affected: ' . $result);
            wp_send_json_success(['message' => 'Chatbot analytics data cleared successfully.']);
        }
    } catch (Exception $e) {
        error_log('[Chatbot Clear Analytics Debug] Exception caught: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_clear_chatbot_analytics', 'clear_chatbot_analytics_data');

function clear_email_support_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_email_support';

    error_log('[Chatbot Clear Email Support Debug] clear_email_support_data function called.');

    // Check for nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_analytics_nonce')) {
        error_log('[Chatbot Clear Email Support Debug] Nonce verification failed.');
        wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        error_log('[Chatbot Clear Email Support Debug] User does not have sufficient permissions.');
        wp_send_json_error(['message' => 'You do not have sufficient permissions to clear email support data.'], 403);
    }

    try {
        $result = $wpdb->query("TRUNCATE TABLE $table_name");

        if ($result === false) {
            error_log('[Chatbot Clear Email Support Debug] Database TRUNCATE failed: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Failed to clear email support data: ' . $wpdb->last_error]);
        } else {
            error_log('[Chatbot Clear Email Support Debug] Database TRUNCATE successful. Rows affected: ' . $result);
            wp_send_json_success(['message' => 'Chatbot email support data cleared successfully.']);
        }
    } catch (Exception $e) {
        error_log('[Chatbot Clear Email Support Debug] Exception caught: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_clear_email_support_data', 'clear_email_support_data');
