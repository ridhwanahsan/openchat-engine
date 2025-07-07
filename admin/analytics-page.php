<?php
function openchat_engine_analytics_page() {
    $license_key = get_option('openchat_license_key');
    $is_premium = false;

    if ($license_key) {
        $api_url  = 'https://license-api-ocm3.vercel.app/api/check-key?key=' . urlencode($license_key);
        $response = wp_remote_get($api_url);
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response));
            if (!empty($data->valid)) {
                $is_premium = true;
            }
        }
    }

    if (!$is_premium) {
        echo '<div class="wrap"><h1>OpenChat Engine Analytics</h1><div class="notice notice-warning"><p>Please go to the developer and buy a license key to access the Analytics tab. A valid license unlocks premium features including advanced usage analytics, priority support, and upcoming integrations with AI model insights and user behavior tracking.</p></div></div>';
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'openchat_engine_analytics';

    // Total interactions
    $total_interactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Unique sessions
    $unique_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name");

    // Average interactions per session
    $avg_interactions_per_session = $unique_sessions > 0 ? round($total_interactions / $unique_sessions, 2) : 0;

    // Interactions today
    $today = current_time('mysql', 1);
    $interactions_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = DATE(%s)", $today));

    // Interactions this week (last 7 days)
    $interactions_this_week = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

    // First and last interaction
    $first_interaction = $wpdb->get_var("SELECT MIN(timestamp) FROM $table_name");
    $last_interaction = $wpdb->get_var("SELECT MAX(timestamp) FROM $table_name");

    // Total email support requests
    $email_support_table_name = $wpdb->prefix . 'openchat_engine_email_support';
    $total_email_support_requests = $wpdb->get_var("SELECT COUNT(*) FROM $email_support_table_name");

    // Popular queries
    $popular_queries = $wpdb->get_results("SELECT user_message, COUNT(*) as count FROM $table_name GROUP BY user_message ORDER BY count DESC LIMIT 10");

    // Prepare data for Chart.js
    $chart_labels = [];
    $chart_data = [];
    foreach ($popular_queries as $query) {
        $chart_labels[] = esc_js($query->user_message);
        $chart_data[] = (int)$query->count;
    }

    // Recent interactions
    $recent_interactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 20");

    ?>
    <div class="wrap">
        <h1>OpenChat Engine Analytics</h1>

        <h2 class="nav-tab-wrapper">
            <a href="#overview" class="nav-tab nav-tab-active" id="overview-tab">Overview</a>
            <a href="#popular-queries" class="nav-tab" id="popular-queries-tab">Popular Queries</a>
            <a href="#recent-interactions" class="nav-tab" id="recent-interactions-tab">Recent Interactions</a>
            <a href="#email-support" class="nav-tab" id="email-support-tab">Email Support</a>
        </h2>

        <div class="tab-content" id="overview">
            <div class="analytics-card">
                <h2>Overview <button id="clear-analytics-data" class="button button-secondary" data-clear-type="all">Clear All Analytics Data</button></h2>
                <div class="overview-grid">
                    <div class="overview-item">
                        <strong>Total Interactions</strong>
                        <span><?php echo esc_html($total_interactions); ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Unique Sessions</strong>
                        <span><?php echo esc_html($unique_sessions); ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Avg. Interactions/Session</strong>
                        <span><?php echo esc_html($avg_interactions_per_session); ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Interactions Today</strong>
                        <span><?php echo esc_html($interactions_today); ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Interactions This Week</strong>
                        <span><?php echo esc_html($interactions_this_week); ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>First Interaction</strong>
                        <span><?php echo $first_interaction ? esc_html(date_i18n(get_option('date_format'), strtotime($first_interaction))) : 'N/A'; ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Last Interaction</strong>
                        <span><?php echo $last_interaction ? esc_html(date_i18n(get_option('date_format'), strtotime($last_interaction))) : 'N/A'; ?></span>
                    </div>
                    <div class="overview-item">
                        <strong>Total Email Support Requests</strong>
                        <span><?php echo esc_html($total_email_support_requests); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="popular-queries" style="display:none;">
            <div class="analytics-card">
                <h2>Popular Queries</h2>
                <?php if ($popular_queries) : ?>
                    <div class="chart-container" style="position: relative; height:40vh; width:80vw; max-width: 600px;">
                        <canvas id="popularQueriesChart"></canvas>
                    </div>
                    <ol>
                        <?php foreach ($popular_queries as $query) : ?>
                            <li>"<?php echo esc_html($query->user_message); ?>" (<?php echo esc_html($query->count); ?> times)</li>
                        <?php endforeach; ?>
                    </ol>
                <?php else : ?>
                    <p>No popular queries yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="recent-interactions" style="display:none;">
            <div class="analytics-card">
                <h2>Recent Interactions</h2>
                <?php if ($recent_interactions) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>User Message</th>
                                <th>Bot Response</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_interactions as $interaction) : ?>
                                <tr>
                                    <td><?php echo esc_html($interaction->session_id); ?></td>
                                    <td><?php echo esc_html($interaction->user_message); ?></td>
                                <td><?php
                                    $full_bot_response = preg_replace('/◁think▷.*?◁\/think▷/', '', $interaction->bot_response);
                                    $words = explode(' ', $full_bot_response);
                                    $truncated_words = array_slice($words, 0, 40);
                                    $truncated_bot_response = implode(' ', $truncated_words);

                                    $needs_truncation = count($words) > 40;

                                    if ($needs_truncation) {
                                        // Display 40 words initially
                                        echo '<span class="bot-response-content">' . esc_html($truncated_bot_response) . '</span>';
                                        // Hidden full text and visible toggle
                                        echo ' <a href="#" class="read-more-toggle" data-full-text="' . esc_attr($full_bot_response) . '" data-truncated-text="' . esc_attr($truncated_bot_response) . '">...Read More</a>';
                                    } else {
                                        // Display full text if no truncation needed
                                        echo esc_html($full_bot_response);
                                    }
                                ?></td>
                                    <td><?php echo esc_html($interaction->timestamp); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No recent interactions yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="email-support" style="display:none;">
            <div class="analytics-card">
                <h2>Email Support Requests <button id="clear-email-support-data" class="button button-secondary" data-clear-type="email_support">Clear Email Support Data</button></h2>
                <?php
                $email_support_table_name = $wpdb->prefix . 'openchat_engine_email_support';
                $email_support_requests = $wpdb->get_results("SELECT * FROM $email_support_table_name ORDER BY submission_time DESC LIMIT 50");

                if ($email_support_requests) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Client Email</th>
                                <th>Problem Description</th>
                                <th>Submission Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($email_support_requests as $request) : ?>
                                <tr>
                                    <td><?php echo esc_html($request->client_email); ?></td>
                                    <td><?php echo esc_html($request->problem_description); ?></td>
                                    <td><?php echo esc_html($request->submission_time); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No email support requests yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

                // Re-render chart if popular queries tab is active
                if (target === '#popular-queries') {
                    renderChart();
                }
            });

            // Chart.js initialization
            function renderChart() {
                var ctx = document.getElementById('popularQueriesChart').getContext('2d');
                // Destroy existing chart instance if it exists to prevent duplicates
                if (window.popularQueriesChartInstance) {
                    window.popularQueriesChartInstance.destroy();
                }
                window.popularQueriesChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Number of Queries',
                            data: <?php echo json_encode($chart_data); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Allow chart to resize based on container
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            // Initial chart render if popular queries tab is default active
            if ($('#popular-queries-tab').hasClass('nav-tab-active')) {
                renderChart();
            }

            // Initial tab display
            $('.tab-content').hide();
            $('#overview').show();

            // Read More/Less functionality
            $('.wp-list-table').on('click', '.read-more-toggle', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $contentSpan = $this.siblings('.bot-response-content');
                var fullText = $this.data('full-text');
                var truncatedText = $this.data('truncated-text');

                if ($this.text() === '...Read More') {
                    $contentSpan.text(fullText);
                    $this.text(' Read Less');
                } else {
                    $contentSpan.text(truncatedText);
                    $this.text('...Read More');
                }
            });
        });
    </script>
    <style>
        .analytics-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .analytics-card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.5em;
            color: #333;
        }
        .analytics-card p {
            font-size: 1.1em;
            line-height: 1.6;
        }
        .analytics-card ol {
            margin-left: 20px;
        }
        .analytics-card table {
            margin-top: 15px;
        }
        /* Overview specific styles */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .overview-item {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .overview-item strong {
            display: block;
            font-size: 1.2em;
            color: #555;
            margin-bottom: 5px;
        }
        .overview-item span {
            font-size: 1.8em;
            font-weight: bold;
            color: #0073aa;
        }
        /* Tab styles */
        .nav-tab-wrapper {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 0;
        }
        .nav-tab {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 5px 0 0;
            background: #f0f0f0;
            border: 1px solid #ccc;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            text-decoration: none;
            color: #555;
        }
        .nav-tab-active {
            background: #fff;
            border-bottom: 1px solid #fff;
            color: #000;
        }
        .tab-content {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .blurred-container {
            filter: blur(5px);
            pointer-events: none;
        }
        .premium-notice {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            width: 80%;
            text-align: center;
        }
    </style>
    <?php
    echo '</div>'; // close container
    echo '</div>'; // close wrap
}
?>