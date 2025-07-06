# OpenChat Engine – AI Chatbot Plugin for WordPress

This guide will help you install, configure, and use the OpenChat Engine plugin to add a customizable AI-powered chatbot to your WordPress website.

## Table of Contents
1.  [Installation](#installation)
2.  [Configuration](#configuration)
    *   [API Settings](#api-settings)
    *   [UI Settings](#ui-settings)
    *   [reCAPTCHA Settings](#recaptcha-settings)
3.  [Usage](#usage)
4.  [Analytics](#analytics)
5.  [Troubleshooting](#troubleshooting)

---

## 1. Installation

### A. Via WordPress Dashboard (Recommended)
1.  Navigate to **Plugins > Add New** in your WordPress admin dashboard.
2.  Click **Upload Plugin** at the top of the page.
3.  Click **Choose File** and select the `openchat-engine.zip` file you downloaded.
4.  Click **Install Now**.
5.  Once installed, click **Activate Plugin**.

### B. Manual Installation
1.  Unzip the `openchat-engine.zip` file.
2.  Upload the entire `openchat-engine` folder to the `/wp-content/plugins/` directory of your WordPress installation via FTP or your hosting provider's file manager.
3.  Navigate to **Plugins** in your WordPress admin dashboard.
4.  Locate "OpenChat Engine – AI Chatbot Plugin for WordPress" and click **Activate**.

---

## 2. Configuration

After activation, you'll find the plugin settings under **OpenChat Engine > Settings** in your WordPress admin menu. The settings are organized into tabs:

### A. API Settings
*   **OpenRouter API Key:** Enter your API key obtained from [OpenRouter.ai](https://openrouter.ai). This is essential for the chatbot to function.
*   **Model (e.g. openai/gpt-3.5-turbo):** Specify the AI model you wish to use. Refer to OpenRouter's documentation for available models.

### B. UI Settings
*   **OpenChat Engine Header Title:** Customize the title displayed in the chatbot's header (e.g., "Support", "Ask Us Anything").
*   **Bot Avatar URL:** Provide a URL for the chatbot's avatar image. Leave blank for the default icon.
*   **User Avatar URL:** Provide a URL for the user's avatar image. Leave blank for the default icon.
*   **System Prompt:** Define the chatbot's persona and instructions. This is crucial for guiding the AI's responses. For example: "You are a helpful assistant for a WordPress theme shop. Only answer questions related to theme installation, demo import, and basic customization."
*   **Example Questions:** Enter a list of suggested questions, one per line. These will appear as clickable suggestions in the chatbot UI.
*   **Enable Prompt Limit:** Toggle to enable or disable a daily prompt limit for users.
*   **Prompt Limit:** Set the maximum number of questions a user can ask per day.
*   **Prompt Limit Message:** Customize the message displayed when a user reaches the prompt limit. Use `%s` as a placeholder for the limit number.
*   **Typing Indicator Text:** Customize the text shown when the chatbot is generating a response.
*   **Support Email Recipient:** Enter the email address where support requests (triggered when a user reaches the prompt limit or explicitly asks for email support) will be sent. Defaults to your WordPress admin email.

### C. reCAPTCHA Settings
*   **Enable Google reCAPTCHA:** Check this box to enable reCAPTCHA for spam protection.
*   **Google reCAPTCHA Site Key:** Enter your reCAPTCHA site key.
*   **Google reCAPTCHA Secret Key:** Enter your reCAPTCHA secret key.
    *   You can obtain reCAPTCHA keys from the [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin).

---

## 3. Usage

Once configured, the chatbot widget will appear on the frontend of your website (typically in the bottom right corner).

*   **Toggle Button:** Click the chatbot toggle button (usually an avatar or icon) to open or close the chatbot interface.
*   **Send Messages:** Type your questions into the input field and press Enter or click the send button.
*   **Suggestions:** Click on the suggested questions to quickly ask common queries.
*   **Email Support:** If enabled and configured, users can request email support when they reach the prompt limit or by typing "email support" or "contact support".

---

## 4. Analytics

The plugin includes an analytics section to monitor chatbot interactions.
*   Navigate to **OpenChat Engine > Analytics** in your WordPress admin menu.
*   Here you can view:
    *   Total interactions, unique sessions, and average interactions per session.
    *   Interactions today and this week.
    *   First and last interaction timestamps.
    *   Total email support requests.
    *   Popular queries (with a chart visualization).
    *   Recent interactions.
*   You can also **Clear All Analytics Data** or **Clear Email Support Data** from this page.

---

## 5. Troubleshooting

If you encounter any issues:
*   **Check Plugin Settings:** Ensure all API keys, models, and reCAPTCHA settings are correctly entered and saved.
*   **Deactivate/Reactivate:** Sometimes, deactivating and reactivating the plugin can resolve minor issues, especially after updates or manual file changes.
*   **WordPress Debug Log:** If you see a "critical error" or "unknown error (500)", enable WordPress debugging (`WP_DEBUG`, `WP_DEBUG_LOG` in `wp-config.php`) and check the `wp-content/debug.log` file for detailed error messages.
*   **Browser Console:** Open your browser's developer console (usually F12) and check the "Console" tab for any JavaScript errors.
*   **Contact Support:** If problems persist, reach out for support with details from your debug logs and browser console.
