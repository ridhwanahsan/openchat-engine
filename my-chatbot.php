<?php
/**
 * Plugin Name: OpenRouter AI Chatbot
 * Description: Add a customizable AI-powered chatbot to your site using OpenRouter. Users can enter their own API key and model, and optionally enable Google reCAPTCHA for spam protection.
 * Version: 1.0.0
 * Author: Rxdbot
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openrouter-ai-chatbot
 * Requires PHP: 7.4
 * Requires at least: 5.5
 * Tested up to: 6.5
 *
 * Note: This plugin requires a free API key from https://openrouter.ai to function. Instructions are available on the settings page.
 */

defined('ABSPATH') or die();

// Activation hook to create the chat analytics table
function chatbot_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_analytics';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL,
        user_message text NOT NULL,
        bot_response text NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $email_support_table_name = $wpdb->prefix . 'chatbot_email_support';
    $email_support_sql = "CREATE TABLE $email_support_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_email varchar(255) NOT NULL,
        problem_description text NOT NULL,
        submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($email_support_sql);
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'chatbot_activate');

require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/analytics-page.php';

add_action('wp_ajax_clear_chatbot_analytics', 'clear_chatbot_analytics_data');

// Enqueue JS
function my_chatbot_enqueue_scripts()
{
    $recaptcha_enabled  = get_option('chatbot_recaptcha_enabled');
    $recaptcha_site_key = get_option('chatbot_recaptcha_site_key', '');
    wp_enqueue_script('my-chatbot-ui', plugin_dir_url(__FILE__) . 'public/chatbot-ui.js', ['jquery'], null, true);
    wp_enqueue_style('my-chatbot-ui-css', plugin_dir_url(__FILE__) . 'public/chatbot-ui.css');
    if ($recaptcha_enabled && $recaptcha_site_key) {
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true); // Add reCAPTCHA
    }
    wp_localize_script('my-chatbot-ui', 'chatbot_ajax', [
        'ajax_url'           => admin_url('admin-ajax.php'),
        'nonce'              => wp_create_nonce('chatbot_nonce'),
        'recaptcha_site_key' => ($recaptcha_enabled && $recaptcha_site_key) ? $recaptcha_site_key : '',
        'example_questions'  => array_filter(array_map('trim', explode("\n", get_option('chatbot_example_questions', '')))),
        
        'bot_avatar_url' => esc_url(get_option('chatbot_bot_avatar', '')),
        'user_avatar_url' => esc_url(get_option('chatbot_user_avatar', plugin_dir_url(__FILE__) . 'public/rxdbot-theme-preview.png')),
        'prompt_limit_enabled' => get_option('chatbot_prompt_limit_enabled'),
        'prompt_limit' => get_option('chatbot_prompt_limit', 10),
        'prompt_limit_message' => get_option('chatbot_prompt_limit_message', 'You have reached the daily limit of %s questions. Please come back tomorrow.'),
        'typing_indicator_text' => esc_html(get_option('chatbot_typing_indicator_text', 'Assistant is typing...')),
        ]);
    wp_localize_script('my-chatbot-ui', 'chatbot_analytics_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('chatbot_analytics_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'my_chatbot_enqueue_scripts');

function my_chatbot_admin_enqueue_scripts($hook_suffix) {
    // Only load on the specific analytics page
    if ('toplevel_page_chatbot-analytics' !== $hook_suffix) {
        return;
    }

    wp_enqueue_script('my-chatbot-admin-analytics-script', plugin_dir_url(__FILE__) . 'admin/analytics-script.js', ['jquery'], null, true);
    wp_localize_script('my-chatbot-admin-analytics-script', 'chatbot_analytics_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('chatbot_analytics_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'my_chatbot_admin_enqueue_scripts');

function my_chatbot_display()
{
    $recaptcha_enabled  = get_option('chatbot_recaptcha_enabled');
    $recaptcha_site_key = esc_attr(get_option('chatbot_recaptcha_site_key', ''));
    
    $chatbot_header_title = esc_html(get_option('chatbot_header_title', 'SUPPORT'));
    $bot_avatar_url = esc_url(get_option('chatbot_bot_avatar', ''));
    $user_avatar_url = esc_url(get_option('chatbot_user_avatar', ''));

    $bot_avatar_html = $bot_avatar_url ? '<img src="' . $bot_avatar_url . '" alt="Bot Avatar" class="rxd-bot-avatar-img" width="40" height="40" style="border-radius:50%;background:#e0e0e0;" />' : '👤';

    echo '<div id="rxd-chatbot-widget">

                ' . (($recaptcha_enabled && $recaptcha_site_key) ? '<div class="g-recaptcha" data-sitekey="' . $recaptcha_site_key . '" style="margin-left:10px;"></div>' : '') . '
        <button id="rxd-chatbot-toggle">
            <span class="rxd-chatbot-toggle-avatar">' . $bot_avatar_html . '</span>
        </button>
        <div id="rxd-chatbot-ui" style="display:none;">
            <div id="rxd-chatbot-header" >
                <span class="rxd-bot-avatar">' . $bot_avatar_html . '</span>
                <div class="rxd-header-info">
                    <div class="rxd-header-title">' . $chatbot_header_title . '</div>
                    <div class="rxd-header-sub"></div>
                </div>
                <span class="rxd-header-menu">&#9776;</span>
                <button id="rxd-chatbot-close" title="Close">&times;</button>
            </div>
            <div id="rxd-chat-output"></div>
            <div id="rxd-chatbot-input-row">
                <input id="rxd-chat-input" type="text" placeholder="Type your message..." autocomplete="off" />
                <button id="rxd-send-chat" type="button" title="Send">&#9658;</button>
            </div>
            <div class="rxd-chat-suggestions-wrap" style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:0;">
                <button class="rxd-chat-suggestion-arrow" id="rxd-chat-suggestion-left" title="Scroll left">&#8592;</button>
                <div id="rxd-chat-suggestions"></div>
                <button class="rxd-chat-suggestion-arrow" id="rxd-chat-suggestion-right" title="Scroll right">&#8594;</button>
            </div>
        </div>
    </div>';
}
add_action('wp_footer', 'my_chatbot_display');