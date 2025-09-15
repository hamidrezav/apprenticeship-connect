/**
 * Apprenticeship Connect Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Manual sync functionality
    $('#aprcn-manual-sync').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Disable button and show loading
        $button.prop('disabled', true).text(aprcnAjax.strings.syncing);
        
        // Make AJAX call
        $.ajax({
            url: aprcnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aprcn_manual_sync',
                nonce: aprcnAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data, 'success');
                    // Reload page to update status
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(response.data, 'error');
                }
            },
            error: function() {
                showNotice(aprcnAjax.strings.error, 'error');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Test API functionality
    $('#aprcn-test-api').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Disable button and show loading
        $button.prop('disabled', true).text(aprcnAjax.strings.testing);
        
        // Make AJAX call
        $.ajax({
            url: aprcnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aprcn_test_api',
                nonce: aprcnAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data, 'success');
                } else {
                    showNotice(response.data, 'error');
                }
            },
            error: function() {
                showNotice(aprcnAjax.strings.error, 'error');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Test & Sync API functionality
    $(document).on('click', '#aprcn-test-and-sync', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Create or select a result container under the API section
        var $apiFieldRow = $('#api_ukprn').closest('tr');
        var $result = $('#aprcn-test-sync-result');
        if ($result.length === 0) {
            $result = $('<div id="aprcn-test-sync-result" style="margin-top: 10px;"></div>');
            $apiFieldRow.after($('<tr><td colspan="2"></td></tr>').find('td').append($result).end());
        }
        
        // Collect current API form values so saving isn't required
        var apiBaseUrl = $('#api_base_url').val();
        var apiKey = $('#api_subscription_key').val();
        var ukprn = $('#api_ukprn').val();
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Testing & Syncing...');
        $result.html('<p style="color: #0073aa;">Testing API connection and syncing vacancies...</p>');
        
        // Make AJAX call to test & sync using current values
        $.ajax({
            url: aprcnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aprcn_test_and_sync',
                nonce: aprcnAjax.nonce,
                api_base_url: apiBaseUrl,
                api_subscription_key: apiKey,
                api_ukprn: ukprn
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<p style="color: #46b450; font-weight: bold;">' + response.data + '</p>');
                    // Persist the successful API settings via AJAX save
                    $.ajax({
                        url: aprcnAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aprcn_save_api_settings',
                            nonce: aprcnAjax.nonce,
                            api_base_url: apiBaseUrl,
                            api_subscription_key: apiKey,
                            api_ukprn: ukprn
                        }
                    });
                } else {
                    $result.html('<p style="color: #dc3232; font-weight: bold;">Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                $result.html('<p style="color: #dc3232; font-weight: bold;">Error: ' + aprcnAjax.strings.error + '</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Show notice function
    function showNotice(message, type) {
        var noticeClass = 'aprcn-notice aprcn-notice-' + type;
        var $notice = $('<div class="' + noticeClass + '">' + message + '</div>');
        
        // Remove existing notices
        $('.aprcn-notice').remove();
        
        // Add new notice at the top of the page
        $('.wrap h1').after($notice);
        
        // Do not auto-remove; keep persistent per user request
    }
    
    // Setup wizard functionality
    if ($('.aprcn-setup-progress').length) {
        // Auto-save form data on input change
        $('.aprcn-setup-step input, .aprcn-setup-step select, .aprcn-setup-step textarea').on('change', function() {
            var $form = $(this).closest('form');
            var formData = $form.serialize();
            
            // Save to localStorage
            localStorage.setItem('aprcn_setup_form_data', formData);
        });
        
        // Restore form data on page load
        var savedData = localStorage.getItem('aprcn_setup_form_data');
        if (savedData) {
            var $form = $('.aprcn-setup-step form');
            var params = new URLSearchParams(savedData);
            
            params.forEach(function(value, key) {
                var $field = $form.find('[name="' + key + '"]');
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', value === '1');
                    } else {
                        $field.val(value);
                    }
                }
            });
        }
        
        // Clear saved data when setup is complete
        if (window.location.search.includes('step=5')) {
            localStorage.removeItem('aprcn_setup_form_data');
        }
    }
    
    // Form validation
    $('form').on('submit', function(e) {
        var $form = $(this);
        var $requiredFields = $form.find('[required]');
        var isValid = true;
        
        $requiredFields.each(function() {
            var $field = $(this);
            var value = $field.val();
            
            if (!value || value.trim() === '') {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotice('Please fill in all required fields.', 'error');
        }
    });
    
    // Remove error class on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('error');
    });
    
    // Copy shortcode to clipboard
    $('.ac-shortcode-box code').on('click', function() {
        var text = $(this).text();
        
        // Create temporary textarea
        var $textarea = $('<textarea>').val(text).appendTo('body');
        $textarea.select();
        document.execCommand('copy');
        $textarea.remove();
        
        // Show feedback
        var $code = $(this);
        var originalText = $code.text();
        $code.text('Copied!').addClass('copied');
        
        setTimeout(function() {
            $code.text(originalText).removeClass('copied');
        }, 2000);
    });
    
    // Add copied style
    $('<style>')
        .text('.ac-shortcode-box code.copied { background: #46b450; color: #fff; }')
        .appendTo('head');
    
    // Auto-refresh status every 30 seconds
    if ($('#aprcn-manual-sync').length) {
        setInterval(function() {
            // Update last sync time if it exists
            var $lastSync = $('.ac-status-box p:contains("Last Sync")');
            if ($lastSync.length) {
                // This would require an AJAX call to get updated status
                // For now, just update the display
            }
        }, 30000);
    }
    
    // Confirm before leaving setup wizard
    if ($('.aprcn-setup-progress').length) {
        window.onbeforeunload = function() {
            return 'Are you sure you want to leave? Your progress will be saved.';
        };
        
        // Remove warning when submitting form
        $('form').on('submit', function() {
            window.onbeforeunload = null;
        });
    }
    
    // Tooltip functionality
    $('[data-tooltip]').on('mouseenter', function() {
        var tooltip = $(this).data('tooltip');
        var $tooltip = $('<div class="ac-tooltip">' + tooltip + '</div>');
        
        $('body').append($tooltip);
        
        var $element = $(this);
        var offset = $element.offset();
        
        $tooltip.css({
            position: 'absolute',
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
            zIndex: 9999
        });
    }).on('mouseleave', function() {
        $('.ac-tooltip').remove();
    });
    
    // Add tooltip styles
    $('<style>')
        .text(`
            .aprcn-tooltip {
                background: #333;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                max-width: 200px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            .aprcn-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border: 5px solid transparent;
                border-top-color: #333;
            }
        `)
        .appendTo('head');
    
    // Responsive admin sidebar
    function handleResponsiveSidebar() {
        if ($(window).width() <= 768) {
            $('.aprcn-admin-sidebar').insertAfter('.aprcn-admin-main');
        } else {
            $('.aprcn-admin-sidebar').insertBefore('.aprcn-admin-main');
        }
    }
    
    $(window).on('resize', handleResponsiveSidebar);
    handleResponsiveSidebar();
    
    // Collapsible sections
    $('.aprcn-shortcode-box h3, .aprcn-status-box h3').on('click', function() {
        var $section = $(this).siblings();
        $section.slideToggle();
        $(this).toggleClass('collapsed');
    });
    
    // Add collapse indicator
    $('.aprcn-shortcode-box h3, .aprcn-status-box h3').append('<span class="dashicons dashicons-arrow-down" style="float: right; cursor: pointer;"></span>');
    
    // Update collapse icon
    $('.aprcn-shortcode-box h3, .aprcn-status-box h3').on('click', function() {
        var $icon = $(this).find('.dashicons');
        if ($(this).hasClass('collapsed')) {
            $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        } else {
            $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }
    });

    // Setup wizard: toggle page fields disabled state based on checkbox
    $(document).on('change', '#create_page', function() {
        var checked = $(this).is(':checked');
        $('#page_title, #page_slug').prop('disabled', !checked);
    });
    // Initialize on load if present
    if ($('#create_page').length) {
        var checked = $('#create_page').is(':checked');
        $('#page_title, #page_slug').prop('disabled', !checked);
    }
}); 