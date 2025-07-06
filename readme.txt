=== OpenRouter AI Chatbot ===
Contributors: rxdbot
Tags: chatbot, ai, openrouter, support, assistant, gpt, recaptcha
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A customizable AI-powered chatbot for your site using OpenRouter. The chatbot automatically appears on every page.

== Description ==

OpenRouter AI Chatbot lets you add a modern, AI-powered chatbot to your WordPress site. The plugin uses the [OpenRouter API](https://openrouter.ai/) for chat responses. You can use your own free or paid OpenRouter API key and choose your preferred model.

**Features:**
* Easy setup – just add your OpenRouter API key
* Choose your AI model (e.g., openai/gpt-3.5-turbo)
* Optional Google reCAPTCHA for spam protection
* Customizable UI and suggested questions
* No hardcoded API keys – you control your usage
* Automatically displays on every page - no shortcode needed!

**Note:** You must create a free account at [OpenRouter](https://openrouter.ai/) and generate your own API key. The plugin does not provide an API key.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/openrouter-ai-chatbot/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Settings > Chatbot** and enter your OpenRouter API key and model.
4. (Optional) Enable Google reCAPTCHA for spam protection.
5. The chatbot will now automatically appear on the bottom right of every page.

== Frequently Asked Questions ==

= Where do I get an OpenRouter API key? =
Sign up for a free account at [OpenRouter](https://openrouter.ai/) and generate your API key at [https://openrouter.ai/keys](https://openrouter.ai/keys).

= Is this plugin free? =
Yes! You only pay for your own OpenRouter API usage (free tier available).

= Does this plugin store my API key? =
Your API key is stored securely in your WordPress database and only used server-side.

= Can I use reCAPTCHA? =
Yes, you can enable Google reCAPTCHA in the plugin settings to help prevent spam.

== Screenshots ==

1. Chatbot UI on the frontend
2. Plugin settings page

== Changelog ==

= 1.1.0 =
* The chatbot now displays automatically on all pages without needing a shortcode.
* All CSS classes and IDs are now prefixed with `rxd-` to prevent conflicts with themes.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
This version removes the need for the `[my_chatbot]` shortcode. The chatbot will now appear automatically. Please remove the shortcode from your pages.

= 1.0.0 =
First release.

== License ==

This plugin is licensed under the GPLv2 or later.