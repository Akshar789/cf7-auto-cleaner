/**
 * CF7 Auto Cleaner - Admin JavaScript
 * 
 * @package CF7_Auto_Cleaner
 * @version 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Confirm before clearing logs
        $('input[name="cf7ac_clear_logs"]').on('click', function (e) {
            if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });

        // Show/hide replace mask field based on action
        $('#cf7ac_default_action').on('change', function () {
            const action = $(this).val();
            const replaceMaskRow = $('#cf7ac_replace_mask').closest('tr');

            if (action === 'replace') {
                replaceMaskRow.show();
            } else {
                replaceMaskRow.hide();
            }
        }).trigger('change');

        // Show/hide fuzzy threshold based on fuzzy matching
        $('#cf7ac_fuzzy_matching').on('change', function () {
            const thresholdRow = $('#cf7ac_fuzzy_threshold').closest('tr');

            if ($(this).is(':checked')) {
                thresholdRow.show();
            } else {
                thresholdRow.hide();
            }
        }).trigger('change');

        // Show/hide external API fields
        $('#cf7ac_external_api_enabled').on('change', function () {
            const apiProviderRow = $('#cf7ac_external_api_provider').closest('tr');
            const apiKeyRow = $('#cf7ac_external_api_key').closest('tr');

            if ($(this).is(':checked')) {
                apiProviderRow.show();
                apiKeyRow.show();
            } else {
                apiProviderRow.hide();
                apiKeyRow.hide();
            }
        }).trigger('change');

        // Validate blacklist size
        $('#cf7ac_blacklist').on('blur', function () {
            const lines = $(this).val().split('\n').filter(line => line.trim() !== '');
            const count = lines.length;

            if (count > 2000) {
                alert('Warning: Your blacklist has ' + count + ' entries. This may impact performance. Consider enabling the "Use Fast Matcher" option.');
            }
        });

        // Auto-expand textareas
        $('textarea.large-text').each(function () {
            autoExpand(this);
        }).on('input', function () {
            autoExpand(this);
        });

        function autoExpand(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }

        // Log detail toggle
        $('.cf7ac-log-detail-toggle').on('click', function (e) {
            e.preventDefault();
            const logId = $(this).data('log-id');
            $('#cf7ac-log-detail-' + logId).slideToggle();
        });

        // Bulk actions for logs
        $('#cf7ac-bulk-action-apply').on('click', function (e) {
            e.preventDefault();

            const action = $('#cf7ac-bulk-action').val();
            const selected = $('.cf7ac-log-checkbox:checked');

            if (selected.length === 0) {
                alert('Please select at least one log entry.');
                return;
            }

            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete ' + selected.length + ' log entries?')) {
                    return;
                }
            }

            // Implement bulk action logic here
            console.log('Bulk action:', action, 'Selected:', selected.length);
        });

        // Select all logs checkbox
        $('#cf7ac-select-all-logs').on('change', function () {
            $('.cf7ac-log-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Character counter for textareas
        $('textarea[name="cf7ac_blacklist"], textarea[name="cf7ac_whitelist"]').each(function () {
            const counter = $('<div class="cf7ac-char-counter"></div>');
            $(this).after(counter);
            updateCounter($(this), counter);
        }).on('input', function () {
            const counter = $(this).next('.cf7ac-char-counter');
            updateCounter($(this), counter);
        });

        function updateCounter(textarea, counter) {
            const lines = textarea.val().split('\n').filter(line => line.trim() !== '');
            counter.text(lines.length + ' entries');
        }
    });

})(jQuery);

// Log Modal Functionality
jQuery(document).ready(function($) {
    // Handle row click to open modal
    $('.cf7ac-log-row').on('click', function() {
        var $row = $(this);
        var logId = $row.data('log-id');
        var time = $row.find('td').eq(1).text().trim();
        var form = $row.find('td').eq(2).text().trim();
        var ip = $row.find('td').eq(3).text().trim();
        var fullContent = $row.find('.cf7ac-log-full-content').text();
        
        // Populate modal
        $('#cf7ac-modal-id').text('#' + logId);
        $('#cf7ac-modal-time').text(time);
        $('#cf7ac-modal-form').text(form);
        $('#cf7ac-modal-ip').text(ip);
        $('#cf7ac-modal-full-content').text(fullContent);
        
        // Show modal
        $('#cf7ac-log-modal').fadeIn(200);
        $('body').css('overflow', 'hidden');
    });
    
    // Close modal
    $('.cf7ac-modal-close, .cf7ac-modal-overlay').on('click', function() {
        $('#cf7ac-log-modal').fadeOut(200);
        $('body').css('overflow', '');
    });
    
    // Close on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#cf7ac-log-modal').is(':visible')) {
            $('#cf7ac-log-modal').fadeOut(200);
            $('body').css('overflow', '');
        }
    });
});
