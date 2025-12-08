<?php
/**
 * Simplified Logs page view template.
 *
 * @package CF7_Auto_Cleaner
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('CF7 Auto Cleaner Logs', 'cf7-auto-cleaner'); ?></h1>

    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-auto-cleaner')); ?>" class="nav-tab">
            <?php esc_html_e('Settings', 'cf7-auto-cleaner'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cf7-auto-cleaner-logs')); ?>"
            class="nav-tab nav-tab-active">
            <?php esc_html_e('Logs', 'cf7-auto-cleaner'); ?>
        </a>
    </nav>

    <?php settings_errors('cf7ac_messages'); ?>

    <!-- Quick Date Filters -->
    <div style="margin: 20px 0; display: flex; gap: 10px;">
        <a href="<?php echo esc_url(add_query_arg(array('page' => 'cf7-auto-cleaner-logs', 'quick_filter' => 'today'), admin_url('admin.php'))); ?>" 
           class="button <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'today') ? 'button-primary' : ''; ?>">
            <?php esc_html_e('Today', 'cf7-auto-cleaner'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('page' => 'cf7-auto-cleaner-logs', 'quick_filter' => 'last7'), admin_url('admin.php'))); ?>" 
           class="button <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'last7') ? 'button-primary' : ''; ?>">
            <?php esc_html_e('Last 7 Days', 'cf7-auto-cleaner'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('page' => 'cf7-auto-cleaner-logs', 'quick_filter' => 'last30'), admin_url('admin.php'))); ?>" 
           class="button <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'last30') ? 'button-primary' : ''; ?>">
            <?php esc_html_e('Last 30 Days', 'cf7-auto-cleaner'); ?>
        </a>
    </div>

    <!-- Filters -->
    <div class="cf7ac-logs-filters">
        <form method="get" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; width: 100%;">
            <input type="hidden" name="page" value="cf7-auto-cleaner-logs">

            <div class="cf7ac-filter-group">
                <label for="form_id"><?php esc_html_e('Form:', 'cf7-auto-cleaner'); ?></label>
                <select name="form_id" id="form_id">
                    <option value=""><?php esc_html_e('All Forms', 'cf7-auto-cleaner'); ?></option>
                    <?php foreach ($cf7_forms as $form): ?>
                        <option value="<?php echo esc_attr($form->ID); ?>" <?php selected($args['form_id'], $form->ID); ?>>
                            <?php echo esc_html($form->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cf7ac-filter-group">
                <label for="date_from"><?php esc_html_e('From:', 'cf7-auto-cleaner'); ?></label>
                <input type="date" name="date_from" id="cf7ac_date_from"
                    value="<?php echo esc_attr($args['date_from']); ?>">
            </div>

            <div class="cf7ac-filter-group">
                <label><?php esc_html_e('To:', 'cf7-auto-cleaner'); ?></label>
                <input type="date" name="date_to" id="cf7ac_date_to"
                    value="<?php echo esc_attr($args['date_to']); ?>">
            </div>

            <div class="cf7ac-filter-group">
                <label for="s"><?php esc_html_e('Search:', 'cf7-auto-cleaner'); ?></label>
                <input type="text" name="s" id="s" value="<?php echo esc_attr($args['search']); ?>"
                    placeholder="<?php esc_attr_e('Search logs...', 'cf7-auto-cleaner'); ?>">
            </div>

            <div class="cf7ac-filter-group" style="flex-direction: row; align-self: flex-end; gap: 5px;">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Filter', 'cf7-auto-cleaner'); ?>
                </button>
                <a href="?page=cf7-auto-cleaner-logs" class="button">
                    <?php esc_html_e('Reset', 'cf7-auto-cleaner'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Actions Bar -->
    <div style="margin-bottom: 20px; display: flex; justify-content: flex-end; gap: 10px;">
        <form method="post" action="" style="display: inline;">
            <?php wp_nonce_field('cf7ac_export_logs', 'cf7ac_export_nonce'); ?>
            <input type="hidden" name="form_id" value="<?php echo esc_attr($args['form_id']); ?>">
            <input type="hidden" name="date_from" value="<?php echo esc_attr($args['date_from']); ?>">
            <input type="hidden" name="date_to" value="<?php echo esc_attr($args['date_to']); ?>">
            <button type="submit" name="cf7ac_export_logs" class="button">
                <span class="dashicons dashicons-download" style="vertical-align: text-bottom;"></span>
                <?php esc_html_e('Export CSV', 'cf7-auto-cleaner'); ?>
            </button>
        </form>

        <form method="post" action="" style="display: inline;"
            onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete all logs? This cannot be undone.', 'cf7-auto-cleaner'); ?>');">
            <?php wp_nonce_field('cf7ac_clear_logs', 'cf7ac_clear_nonce'); ?>
            <button type="submit" name="cf7ac_clear_logs" class="button button-secondary">
                <span class="dashicons dashicons-trash" style="vertical-align: text-bottom;"></span>
                <?php esc_html_e('Clear All', 'cf7-auto-cleaner'); ?>
            </button>
        </form>
    </div>

    <!-- Logs Table -->
    <?php if (empty($logs)): ?>
        <div class="cf7ac-empty-state">
            <span class="dashicons dashicons-saved"></span>
            <p style="font-size: 16px; margin: 0; color: #3c434a; font-weight: 600;">
                <?php esc_html_e('No logs found', 'cf7-auto-cleaner'); ?>
            </p>
            <p style="margin-top: 5px;">
                <?php esc_html_e('Logs will appear here when the plugin detects and removes banned content.', 'cf7-auto-cleaner'); ?>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php esc_html_e('ID', 'cf7-auto-cleaner'); ?></th>
                    <th style="width: 160px;"><?php esc_html_e('Time', 'cf7-auto-cleaner'); ?></th>
                    <th style="width: 180px;"><?php esc_html_e('Form', 'cf7-auto-cleaner'); ?></th>
                    <th style="width: 140px;"><?php esc_html_e('IP Address', 'cf7-auto-cleaner'); ?></th>
                    <th><?php esc_html_e('Content', 'cf7-auto-cleaner'); ?></th>
                    <th style="width: 80px; text-align: right;"><?php esc_html_e('Actions', 'cf7-auto-cleaner'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr class="cf7ac-log-row" data-log-id="<?php echo esc_attr($log['id']); ?>" style="cursor: pointer;">
                        <td class="cf7ac-log-id">#<?php echo esc_html($log['id']); ?></td>
                        <td>
                            <?php echo esc_html(date('M j, Y', strtotime($log['time']))); ?><br>
                            <span style="color: #646970; font-size: 12px;"><?php echo esc_html(date('g:i A', strtotime($log['time']))); ?></span>
                        </td>
                        <td>
                            <?php
                            $form_title = get_the_title($log['form_id']);
                            echo esc_html($form_title ? $form_title : 'Form #' . $log['form_id']);
                            ?>
                        </td>
                        <td>
                            <code class="cf7ac-log-ip"><?php echo esc_html($log['ip']); ?></code>
                        </td>
                        <td>
                            <div class="cf7ac-log-content">
                                <?php echo esc_html($log['raw_posted_excerpt']); ?>
                            </div>
                            <div class="cf7ac-log-full-content" style="display: none;">
                                <?php echo esc_html($log['raw_posted_data']); ?>
                            </div>
                        </td>
                        <td style="text-align: right;" onclick="event.stopPropagation();">
                            <form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e('Delete this log entry?', 'cf7-auto-cleaner'); ?>');">
                                <?php wp_nonce_field('cf7ac_delete_log', 'cf7ac_delete_nonce'); ?>
                                <input type="hidden" name="log_id" value="<?php echo esc_attr($log['id']); ?>">
                                <button type="submit" name="cf7ac_delete_log" class="button-link-delete" title="<?php esc_attr_e('Delete', 'cf7-auto-cleaner'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $base_url = add_query_arg(
                        array(
                            'page' => 'cf7-auto-cleaner-logs',
                            'form_id' => $args['form_id'],
                            'date_from' => $args['date_from'],
                            'date_to' => $args['date_to'],
                            's' => $args['search'],
                        ),
                        admin_url('admin.php')
                    );

                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'current' => $current_page,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal for viewing full log content -->
<div id="cf7ac-log-modal" class="cf7ac-modal" style="display: none;">
    <div class="cf7ac-modal-overlay"></div>
    <div class="cf7ac-modal-content">
        <div class="cf7ac-modal-header">
            <h2><?php esc_html_e('Log Details', 'cf7-auto-cleaner'); ?></h2>
            <button class="cf7ac-modal-close" type="button">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="cf7ac-modal-body">
            <div class="cf7ac-modal-info">
                <div class="cf7ac-modal-info-item">
                    <strong><?php esc_html_e('ID:', 'cf7-auto-cleaner'); ?></strong>
                    <span id="cf7ac-modal-id"></span>
                </div>
                <div class="cf7ac-modal-info-item">
                    <strong><?php esc_html_e('Time:', 'cf7-auto-cleaner'); ?></strong>
                    <span id="cf7ac-modal-time"></span>
                </div>
                <div class="cf7ac-modal-info-item">
                    <strong><?php esc_html_e('Form:', 'cf7-auto-cleaner'); ?></strong>
                    <span id="cf7ac-modal-form"></span>
                </div>
                <div class="cf7ac-modal-info-item">
                    <strong><?php esc_html_e('IP Address:', 'cf7-auto-cleaner'); ?></strong>
                    <span id="cf7ac-modal-ip"></span>
                </div>
            </div>
            <div class="cf7ac-modal-content-section">
                <h3><?php esc_html_e('Message Content:', 'cf7-auto-cleaner'); ?></h3>
                <div id="cf7ac-modal-full-content" style="padding: 15px 0; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; line-height: 1.6;"></div>
            </div>
        </div>
    </div>
</div>

