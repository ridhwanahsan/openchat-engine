jQuery(document).ready(function($) {
    // Clear Analytics Data functionality
    $('.analytics-card').on('click', '#clear-analytics-data, #clear-email-support-data', function(e) {
        e.preventDefault();
        var button = $(this);
        var clearType = button.data('clear-type');
        var confirmMessage = '';
        var ajaxAction = '';

        if (clearType === 'all') {
            confirmMessage = 'Are you sure you want to clear ALL chatbot analytics data? This action cannot be undone.';
            ajaxAction = 'clear_openchat_engine_analytics';
        } else if (clearType === 'email_support') {
            confirmMessage = 'Are you sure you want to clear ALL email support requests? This action cannot be undone.';
            ajaxAction = 'clear_openchat_engine_email_support_data';
        }

        if (confirm(confirmMessage)) {
            button.prop('disabled', true).text('Clearing...');

            jQuery.ajax({
                url: openchat_engine_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    nonce: openchat_engine_analytics_ajax.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Reload the page to show updated data
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error.'));
                        button.prop('disabled', false).text(clearType === 'all' ? 'Clear All Analytics Data' : 'Clear Email Support Data');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error: ' + error);
                    button.prop('disabled', false).text(clearType === 'all' ? 'Clear All Analytics Data' : 'Clear Email Support Data');
                }
            });
        }
    });
});