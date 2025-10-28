/**
 * GateSpark Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Test Connection Button
         */
        $(document).on('click', '.gatespark-test-connection', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $button.siblings('.gatespark-test-result');
            var mode = $button.data('mode');
            var apiKeyField = mode === 'sandbox' 
                ? '#woocommerce_gatespark_revolut_sandbox_api_key'
                : '#woocommerce_gatespark_revolut_live_api_key';
            var apiKey = $(apiKeyField).val();
            
            // Validate API key exists
            if (!apiKey || apiKey.length < 10) {
                $result.removeClass('testing success').addClass('error');
                $result.html('✗ ' + gatesparkAdmin.strings.error + ': ' + 'Please enter an API key first');
                return;
            }
            
            // Reset result
            $result.removeClass('success error').addClass('testing');
            $result.html('<span class="gatespark-spinner"></span> ' + gatesparkAdmin.strings.testing);
            
            // Disable button
            $button.prop('disabled', true);
            
            // AJAX request with nonce
            $.ajax({
                url: gatesparkAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gatespark_test_connection',
                    nonce: gatesparkAdmin.nonce,
                    mode: mode,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('testing error').addClass('success');
                        $result.html('✓ ' + response.data.message);
                        
                        // Show success animation
                        setTimeout(function() {
                            $result.fadeOut(300, function() {
                                $result.html('').show();
                            });
                        }, 5000);
                    } else {
                        $result.removeClass('testing success').addClass('error');
                        $result.html('✗ ' + (response.data.message || gatesparkAdmin.strings.error));
                    }
                },
                error: function(xhr, status, error) {
                    $result.removeClass('testing success').addClass('error');
                    $result.html('✗ ' + gatesparkAdmin.strings.error + ': ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
        
        /**
         * Copy Webhook URL
         */
        $(document).on('click', '#woocommerce_gatespark_revolut_webhook_url', function() {
            this.select();
            document.execCommand('copy');
            
            // Show tooltip
            var $this = $(this);
            var originalBorder = $this.css('border-color');
            $this.css('border-color', '#46b450');
            
            setTimeout(function() {
                $this.css('border-color', originalBorder);
            }, 1000);
        });
        
        /**
         * Auto-save indicator
         */
        var $saveButton = $('.woocommerce-save-button');
        if ($saveButton.length) {
            var originalText = $saveButton.text();
            
            $('form').on('submit', function() {
                $saveButton.text('Saving...');
            });
            
            $(window).on('load', function() {
                if (window.location.search.indexOf('settings-updated=true') > -1) {
                    $saveButton.text('Saved!');
                    setTimeout(function() {
                        $saveButton.text(originalText);
                    }, 2000);
                }
            });
        }
        
        /**
         * Enhanced field descriptions
         */
        $('.gatespark-info-box a').attr('target', '_blank');
        
        /**
         * Confirmation for potentially destructive actions
         */
        $(document).on('click', '.gatespark-danger-action', function(e) {
            if (!confirm('Are you sure you want to proceed?')) {
                e.preventDefault();
                return false;
            }
        });
        
    });

})(jQuery);
