jQuery(document).ready(function($) {
    console.log('openchat_engine_analytics_ajax:', typeof openchat_engine_analytics_ajax !== 'undefined' ? openchat_engine_analytics_ajax : 'undefined');
    console.log('openchat_engine_analytics_ajax.current_page:', typeof openchat_engine_analytics_ajax !== 'undefined' ? openchat_engine_analytics_ajax.current_page : 'undefined');
    // Check if we are on the correct analytics page
    if (typeof openchat_engine_analytics_ajax === 'undefined' || openchat_engine_analytics_ajax.current_page !== 'openchat_page_openchat-engine-analytics') {
        return;
    }

    console.log('Analytics script loaded and running on the correct page.');
    // Clear Analytics Data functionality
    $('.analytics-card').on('click', '#clear-analytics-data, #clear-email-support-data', function(e) {
        e.preventDefault();
        console.log('Clear button clicked.');
        var button = $(this);
        var clearType = button.data('clear-type');
        var confirmMessage = '';
        var ajaxAction = '';

        console.log('clearType:', clearType);

        if (clearType === 'all') {
            confirmMessage = 'Are you sure you want to clear ALL chatbot analytics data? This action cannot be undone.';
            ajaxAction = 'clear_openchat_engine_analytics';
        } else if (clearType === 'email_support') {
            confirmMessage = 'Are you sure you want to clear ALL email support requests? This action cannot be undone.';
            ajaxAction = 'clear_openchat_engine_email_support_data';
        }

        console.log('ajaxAction:', ajaxAction);

        if (confirm(confirmMessage)) {
            button.prop('disabled', true).text('Clearing...');
            console.log('Initiating AJAX call...');

            console.log('AJAX Data:', {
                action: ajaxAction,
                nonce: openchat_engine_analytics_ajax.nonce
            });
            jQuery.ajax({
                url: openchat_engine_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    nonce: openchat_engine_analytics_ajax.nonce,
                    clear_type: clearType
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Reload the page to show updated data
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error.'));
                        button.prop('disabled', false).text(clearType === 'all' ? 'Clear All Analytics Data' : 'Clear Email Support Data');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr, status, error);
                    alert('AJAX Error: ' + error);
                    button.prop('disabled', false).text(clearType === 'all' ? 'Clear All Analytics Data' : 'Clear Email Support Data');
                }
            });
        }
    });
});