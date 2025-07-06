jQuery(document).ready(function ($) {
  // Ensure toggle and close handlers are attached only once
  $(document)
    .off("click", "#rxd-chatbot-toggle")
    .on("click", "#rxd-chatbot-toggle", function (e) {
      e.preventDefault();
      var $ui = $("#rxd-chatbot-ui");
      if ($ui.length) {
        $ui.toggle();
        if ($ui.is(":visible")) {
          $("#rxd-chat-input").focus();
          if ($("#rxd-chat-output").children().length === 0) {
            appendBotMessage(
              "Hello! I am your online consultant. How can I help you?"
            );
          }
        }
      }
    });

  $(document)
    .off("click", "#rxd-chatbot-close")
    .on("click", "#rxd-chatbot-close", function (e) {
      e.preventDefault();
      $("#rxd-chatbot-ui").hide();
    });

  // Send chat
  function appendUserMessage(msg) {
    const userAvatar = openchat_engine_ajax.user_avatar_url ? '<img src="' + openchat_engine_ajax.user_avatar_url + '" alt="User Avatar" class="rxd-user-avatar-img" width="40" height="40" style="border-radius:50%;background:#e0e0e0;" />' : '<img src="/wp-content/plugins/openchat-engine/public/rxdbot-theme-preview.png" alt="User Avatar" class="rxd-user-avatar-img" width="40" height="40" style="border-radius:50%;background:#e0e0e0;" />';
    $("#rxd-chat-output").append(
      '<div class="rxd-chat-row rxd-user-row">' +
        '<span class="rxd-user-bubble">' +
        $("<div>").text(msg).html() +
        "</span>" +
        '<span class="rxd-user-avatar">' +
        userAvatar +
        "</span>" +
        "</div>"
    );
    scrollToBottom();
  }
  function formatBotReply(msg) {
    let html = msg;

    // Escape HTML to prevent XSS, except for specific markdown that will be converted
    html = $('<div>').text(html).html();

    // Convert markdown bold (**text** or __text__)
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/__(.*?)__/g, '<strong>$1</strong>');

    // Convert markdown italics (*text* or _text_)
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/_(.*?)_/g, '<em>$1</em>');

    // Convert triple backtick code blocks to <pre><code>
    html = html.replace(/```(.*?)```/gs, function(match, code) {
        return '<pre class="rxd-chatbot-code"><code>' + code.trim() + '</code></pre>';
    });

    // Convert inline code `code` to <code>
    html = html.replace(/`(.*?)`/g, '<code>$1</code>');

    // Convert unordered lists (- item or * item)
    html = html.replace(/^[\s]*[\*\-][\s]+(.*?)$/gm, '<li>$1</li>');
    if (html.includes('<li>')) {
        html = '<ul>' + html + '</ul>';
    }

    // Convert ordered lists (1. item)
    html = html.replace(/^[\s]*\d+\.[\s]+(.*?)$/gm, '<li>$1</li>');
    if (html.includes('<li>') && !html.includes('<ul>')) { // Only if not already part of an unordered list
        html = '<ol>' + html + '</ol>';
    }

    // Replace newlines with <br> for general text, but not inside <pre> or <code>
    html = html.replace(/(?<!<pre>.*?)(?<!<code>.*?)\n(?!.*?<\/pre>)(?!.*?<\/code>)/g, '<br>');

    return html;
  }
  function appendBotMessage(msg) {
    const botAvatar = openchat_engine_ajax.bot_avatar_url ? '<img src="' + openchat_engine_ajax.bot_avatar_url + '" alt="Bot Avatar" class="rxd-bot-avatar-img" width="40" height="40" style="border-radius:50%;background:#e0e0e0;" />' : '👤';
    const $row = $('<div class="rxd-chat-row"></div>');
    $row.append('<span class="rxd-bot-avatar">' + botAvatar + '</span>');
    const $bubble = $(
      '<span class="rxd-bot-bubble">' + formatBotReply(msg) + "</span>"
    );
    $row.append($bubble);
    // Add divider and theme image after bot bubble
    $row.append('<hr class="rxd-bot-preview-divider" />');

    $("#rxd-chat-output").append($row);
    // Optional: syntax highlight (if highlight.js loaded)
    if (window.hljs) {
      $("#rxd-chat-output pre code").each(function (i, block) {
        window.hljs.highlightElement(block);
      });
    }
    scrollToBottom();
  }
  function scrollToBottom() {
    // Use setTimeout to ensure DOM updates before scrolling
    setTimeout(function () {
      var $output = $("#rxd-chat-output");
      $output.scrollTop($output[0].scrollHeight);
    }, 10);
  }
  // Typing animation
  function showTyping() {
    if (!$("#rxd-chat-typing").length) {
      const botAvatar = openchat_engine_ajax.bot_avatar_url ? '<img src="' + openchat_engine_ajax.bot_avatar_url + '" alt="Bot Avatar" class="rxd-bot-avatar-img" width="40" height="40" style="border-radius:50%;background:#e0e0e0;" />' : '👤';
      $("#rxd-chat-output").append(
        '<div id="rxd-chat-typing" class="rxd-chat-row"><span class="rxd-bot-avatar">' + botAvatar + '</span><span class="rxd-bot-bubble rxd-typing-bubble"><span class="rxd-typing-dot"></span><span class="rxd-typing-dot"></span><span class="rxd-typing-dot"></span> ' + openchat_engine_ajax.typing_indicator_text + '</span></div>'
      );
      scrollToBottom();
    }
  }
  function hideTyping() {
    $("#rxd-chat-typing").remove();
  }
  // Suggested questions
  let suggestions = [
    "How to install WordPress theme?",
    "How to import demo data?",
    "Why resave permalinks?",
    "How to set header menu?",
  ];
  if (
    typeof openchat_engine_ajax !== "undefined" &&
    Array.isArray(openchat_engine_ajax.example_questions) &&
    openchat_engine_ajax.example_questions.length > 0
  ) {
    suggestions = openchat_engine_ajax.example_questions;
  }

  // Only populate suggestions if empty
  if (
    $("#rxd-chat-suggestions").length &&
    $("#rxd-chat-suggestions").children().length === 0
  ) {
    suggestions.forEach((q) => {
      $("#rxd-chat-suggestions").append(
        '<button class="rxd-chat-suggestion-btn">' + q + "</button>"
      );
    });
  }

  // Arrow scroll logic
  $("#rxd-chatbot-ui").on("click", "#rxd-chat-suggestion-left", function () {
    $("#rxd-chat-suggestions").animate({ scrollLeft: "-=120" }, 200);
  });
  $("#rxd-chatbot-ui").on("click", "#rxd-chat-suggestion-right", function () {
    $("#rxd-chat-suggestions").animate({ scrollLeft: "+=120" }, 200);
  });
  $("#rxd-chatbot-ui").on("click", ".rxd-chat-suggestion-btn", function () {
    const q = $(this).text();
    $("#rxd-chat-input").val(q);
    // Briefly highlight the selected suggestion
    $(this).addClass("selected");
    setTimeout(() => $(this).removeClass("selected"), 400);
    $("#rxd-send-chat").click();
    // Do not scroll to bottom here, so suggestions remain visible
  });

  let prompt_count = 0;
  let emailSupportActive = false;
  let collectingEmail = false;
  let collectingProblem = false;
  let clientEmail = '';

  $("#rxd-send-chat").click(function () {
    if (openchat_engine_ajax.prompt_limit_enabled && prompt_count >= openchat_engine_ajax.prompt_limit) {
        emailSupportActive = true;
        collectingEmail = true;
        appendBotMessage("You have reached your daily prompt limit. Please provide your email address for support.");
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
        return;
    }

    const userMessage = $("#rxd-chat-input").val();
    if (!userMessage) return;
    appendUserMessage(userMessage);
    $("#rxd-chat-input").val("");
    $("#rxd-send-chat").prop("disabled", true);
    showTyping();

    // For visible reCAPTCHA v2 widget
    var recaptchaToken = "";
    if (typeof grecaptcha !== "undefined" && $(".g-recaptcha").length) {
      recaptchaToken = grecaptcha.getResponse();
    }

    if (emailSupportActive) {
      if (collectingEmail) {
        // Basic email validation
        const emailRegex = /^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailRegex.test(userMessage)) {
          appendBotMessage("❌ Please enter a valid email address.");
          hideTyping();
          $("#rxd-send-chat").prop("disabled", false);
          return;
        }
        clientEmail = userMessage;
        collectingEmail = false;
        collectingProblem = true;
        appendBotMessage("What is your problem?");
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
      } else if (collectingProblem) {
        const problemDescription = userMessage;
        sendEmailSupportRequest(clientEmail, problemDescription);
        emailSupportActive = false;
        collectingProblem = false;
        clientEmail = ''; // Clear email after sending
      }
    } else if (userMessage.toLowerCase().includes("email support") || userMessage.toLowerCase().includes("contact support")) {
      emailSupportActive = true;
      collectingEmail = true;
      appendBotMessage("Please provide your email address for support.");
      hideTyping();
      $("#rxd-send-chat").prop("disabled", false);
    } else {
      sendChatAjax(userMessage, recaptchaToken);
      prompt_count++;
    }
  });

  let session_id = localStorage.getItem('openchat_engine_session_id');
  if (!session_id) {
    session_id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
    localStorage.setItem('openchat_engine_session_id', session_id);
  }

  function sendChatAjax(userMessage, recaptchaToken) {
    jQuery.ajax({
      type: "POST",
      url: openchat_engine_ajax.ajax_url,
      data: {
        action: "send_openchat_engine_message",
        message: userMessage,
        nonce: openchat_engine_ajax.nonce,
        "g-recaptcha-response": recaptchaToken,
        session_id: session_id,
      },
      success: function (res) {
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
        if (res.success) {
          const reply = res.data.reply;
          appendBotMessage(reply);
        } else {
          appendBotMessage(
            "❌ " + (res.data.message || "Something went wrong.")
          );
        }
        // Reset reCAPTCHA after each send
        if (typeof grecaptcha !== "undefined" && $(".g-recaptcha").length) {
          grecaptcha.reset();
        }
      },
      error: function (xhr) {
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
        let response = {};
        try {
          response = JSON.parse(xhr.responseText);
        } catch (e) {}
        const message =
          response?.data?.message || response?.message || "Unknown error (500)";
        appendBotMessage("❌ " + message);
        // Reset reCAPTCHA after error
        if (typeof grecaptcha !== "undefined" && $(".g-recaptcha").length) {
          grecaptcha.reset();
        }
      },
    });
  }

  function sendEmailSupportRequest(email, problem) {
    jQuery.ajax({
      type: "POST",
      url: openchat_engine_ajax.ajax_url,
      data: {
        action: "save_email_support_request",
        email: email,
        problem: problem,
        nonce: openchat_engine_ajax.nonce,
      },
      success: function (res) {
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
        if (res.success) {
          appendBotMessage("Thank you! Your support request has been submitted. We will get back to you shortly.");
        } else {
          appendBotMessage("❌ Failed to submit your support request. Please try again later.");
        }
      },
      error: function (xhr) {
        hideTyping();
        $("#rxd-send-chat").prop("disabled", false);
        appendBotMessage("❌ An error occurred while submitting your support request. Please try again later.");
      },
    });
  }

  // Allow Enter to send
  $("#rxd-chat-input").keypress(function (e) {
    if (e.which === 13) {
      $("#rxd-send-chat").click();
      return false;
    }
  });
});