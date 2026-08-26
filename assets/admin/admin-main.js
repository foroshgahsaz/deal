(function ($) {
    "use strict";

    if (!$.wpcdAdminExtensions)
        $.wpcdAdminExtensions = {};
    
    function WPCDAdminMainCore() {
        var self = this;
        self.init();
    };

    WPCDAdminMainCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            var self = this;

            self.taxInit();

            self.emailSettings();

            self.mixes();

            $('.upload_image_action .user-remove-image').on( 'click', function(event) {
                self.remove_file( $(this).parent().parent() );
            });

            $('.upload_image_action .user-add-image').on('click', function(event) {

                self.add_file(event, $(this).parent().parent());
            });
        },
        taxInit: function() {
            $('.tax_color_input').wpColorPicker();
        },
        emailSettings: function() {
            var show_hiden_action = function(key, checked) {
                if ( checked ) {
                    $('.cmb2-id-' + key + '-subject').show();
                    $('.cmb2-id-' + key + '-content').show();
                } else {
                    $('.cmb2-id-' + key + '-subject').hide();
                    $('.cmb2-id-' + key + '-content').hide();
                }
            }
            $('#admin_notice_add_new_listing').on('change', function(){
                var key = 'admin-notice-add-new-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#admin_notice_add_new_listing').is(":checked");
            var key = 'admin-notice-add-new-listing';
            show_hiden_action(key, checked);

            // updated
            $('#admin_notice_updated_listing').on('change', function(){
                var key = 'admin-notice-updated-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#admin_notice_updated_listing').is(":checked");
            var key = 'admin-notice-updated-listing';
            show_hiden_action(key, checked);

            // admin expiring
            $('#admin_notice_expiring_listing').on('change', function(){
                var key = 'admin-notice-expiring-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
                if ( checked ) {
                    $('.cmb2-id-admin-notice-expiring-listing-days').show();
                } else {
                    $('.cmb2-id-admin-notice-expiring-listing-days').hide();
                }
            });
            var checked = $('#admin_notice_expiring_listing').is(":checked");
            var key = 'admin-notice-expiring-listing';
            show_hiden_action(key, checked);
            if ( checked ) {
                $('.cmb2-id-admin-notice-expiring-listing-days').show();
            } else {
                $('.cmb2-id-admin-notice-expiring-listing-days').hide();
            }

            // employer expiring
            $('#user_notice_expiring_listing').on('change', function(){
                var key = 'user-notice-expiring-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);

                if ( checked ) {
                    $('.cmb2-id-user-notice-expiring-listing-days').show();
                } else {
                    $('.cmb2-id-user-notice-expiring-listing-days').hide();
                }
            });
            var checked = $('#user_notice_expiring_listing').is(":checked");
            var key = 'user-notice-expiring-listing';
            show_hiden_action(key, checked);
            if ( checked ) {
                $('.cmb2-id-user-notice-expiring-listing-days').show();
            } else {
                $('.cmb2-id-user-notice-expiring-listing-days').hide();
            }
        },
        mixes: function() {
            var self = this;
            var map_service = $('.cmb2-id-map-service select').val();
            if ( map_service == 'mapbox' ) {
                $('.cmb2-id-google-map-api-keys').hide();
                $('.cmb2-id-google-map-style').hide();
                $('.cmb2-id-googlemap-type').hide();
                $('.cmb2-id-here-map-api-key').hide();
                $('.cmb2-id-here-map-style').hide();
                $('.cmb2-id-mapbox-token').show();
                $('.cmb2-id-mapbox-style').show();
            } else if ( map_service == 'here' ) {
                $('.cmb2-id-google-map-api-keys').hide();
                $('.cmb2-id-google-map-style').hide();
                $('.cmb2-id-googlemap-type').hide();
                $('.cmb2-id-mapbox-token').hide();
                $('.cmb2-id-mapbox-style').hide();

                $('.cmb2-id-here-map-api-key').show();
                $('.cmb2-id-here-map-style').show();
            } else if ( map_service == 'openstreetmap' ) {
                $('.cmb2-id-google-map-api-keys').hide();
                $('.cmb2-id-google-map-style').hide();
                $('.cmb2-id-googlemap-type').hide();
                $('.cmb2-id-mapbox-token').hide();
                $('.cmb2-id-mapbox-style').hide();

                $('.cmb2-id-here-map-api-key').hide();
                $('.cmb2-id-here-map-style').hide();
            } else {
                $('.cmb2-id-google-map-api-keys').show();
                $('.cmb2-id-google-map-style').show();
                $('.cmb2-id-googlemap-type').show();
                $('.cmb2-id-mapbox-token').hide();
                $('.cmb2-id-mapbox-style').hide();
                $('.cmb2-id-here-map-api-key').hide();
                $('.cmb2-id-here-map-style').hide();
            }

            $('.cmb2-id-map-service select').on('change', function() {
                var map_service = $(this).val();
                if ( map_service == 'mapbox' ) {
                    $('.cmb2-id-google-map-api-keys').hide();
                    $('.cmb2-id-google-map-style').hide();
                    $('.cmb2-id-googlemap-type').hide();
                    $('.cmb2-id-here-map-api-key').hide();
                    $('.cmb2-id-here-map-style').hide();
                    $('.cmb2-id-mapbox-token').show();
                    $('.cmb2-id-mapbox-style').show();
                } else if ( map_service == 'here' ) {
                    $('.cmb2-id-google-map-api-keys').hide();
                    $('.cmb2-id-google-map-style').hide();
                    $('.cmb2-id-googlemap-type').hide();
                    $('.cmb2-id-mapbox-token').hide();
                    $('.cmb2-id-mapbox-style').hide();

                    $('.cmb2-id-here-map-api-key').show();
                    $('.cmb2-id-here-map-style').show();
                } else if ( map_service == 'openstreetmap' ) {
                    $('.cmb2-id-google-map-api-keys').hide();
                    $('.cmb2-id-google-map-style').hide();
                    $('.cmb2-id-googlemap-type').hide();
                    $('.cmb2-id-mapbox-token').hide();
                    $('.cmb2-id-mapbox-style').hide();

                    $('.cmb2-id-here-map-api-key').hide();
                    $('.cmb2-id-here-map-style').hide();
                } else {
                    $('.cmb2-id-google-map-api-keys').show();
                    $('.cmb2-id-google-map-style').show();
                    $('.cmb2-id-googlemap-type').show();
                    $('.cmb2-id-mapbox-token').hide();
                    $('.cmb2-id-mapbox-style').hide();
                    $('.cmb2-id-here-map-api-key').hide();
                    $('.cmb2-id-here-map-style').hide();
                }
            });

            //
            var location_type = $('.cmb2-id-location-multiple-fields select').val();
            if ( location_type == 'yes' ) {
                $('.cmb2-id-location-nb-fields').show();
                $('.cmb2-id-location-1-field-label').show();
                $('.cmb2-id-location-2-field-label').show();
                $('.cmb2-id-location-3-field-label').show();
                $('.cmb2-id-location-4-field-label').show();
            } else {
                $('.cmb2-id-location-nb-fields').hide();
                $('.cmb2-id-location-1-field-label').hide();
                $('.cmb2-id-location-2-field-label').hide();
                $('.cmb2-id-location-3-field-label').hide();
                $('.cmb2-id-location-4-field-label').hide();
            }

            $('.cmb2-id-location-multiple-fields select').on('change', function() {
                var location_type = $(this).val();
                if ( location_type == 'yes' ) {
                    $('.cmb2-id-location-nb-fields').show();
                    $('.cmb2-id-location-1-field-label').show();
                    $('.cmb2-id-location-2-field-label').show();
                    $('.cmb2-id-location-3-field-label').show();
                    $('.cmb2-id-location-4-field-label').show();
                } else {
                    $('.cmb2-id-location-nb-fields').hide();
                    $('.cmb2-id-location-1-field-label').hide();
                    $('.cmb2-id-location-2-field-label').hide();
                    $('.cmb2-id-location-3-field-label').hide();
                    $('.cmb2-id-location-4-field-label').hide();
                }
            });

            var enable_mutil_currencies = $('#enable_multi_currencies').val();
            if ( enable_mutil_currencies == 'yes' ) {
                $('.cmb2-id-multi-currencies').show();
                $('.cmb2-id-exchangerate-api-key').show();
            } else {
                $('.cmb2-id-multi-currencies').hide();
                $('.cmb2-id-exchangerate-api-key').hide();
            }

            $('#enable_multi_currencies').on('change', function() {
                var enable_mutil_currencies = $(this).val();
                if ( enable_mutil_currencies == 'yes' ) {
                    $('.cmb2-id-multi-currencies').show();
                    $('.cmb2-id-exchangerate-api-key').show();
                } else {
                    $('.cmb2-id-multi-currencies').hide();
                    $('.cmb2-id-exchangerate-api-key').hide();
                }
            });

            var enable_shorten_long_number = $('#enable_shorten_long_number').val();
            if ( enable_shorten_long_number == 'yes' ) {
                $('.cmb2-id-shorten-precision').show();
                $('.cmb2-id-shorten-thousand').show();
                $('.cmb2-id-shorten-million').show();
                $('.cmb2-id-shorten-billion').show();
                $('.cmb2-id-shorten-trillion').show();
                $('.cmb2-id-shorten-quadrillion').show();
                $('.cmb2-id-shorten-quintillion').show();
            } else {
                $('.cmb2-id-shorten-precision').hide();
                $('.cmb2-id-shorten-thousand').hide();
                $('.cmb2-id-shorten-million').hide();
                $('.cmb2-id-shorten-billion').hide();
                $('.cmb2-id-shorten-trillion').hide();
                $('.cmb2-id-shorten-quadrillion').hide();
                $('.cmb2-id-shorten-quintillion').hide();
            }

            $('#enable_shorten_long_number').on('change', function() {
                var enable_shorten_long_number = $(this).val();
                if ( enable_shorten_long_number == 'yes' ) {
                    $('.cmb2-id-shorten-precision').show();
                    $('.cmb2-id-shorten-thousand').show();
                    $('.cmb2-id-shorten-million').show();
                    $('.cmb2-id-shorten-billion').show();
                    $('.cmb2-id-shorten-trillion').show();
                    $('.cmb2-id-shorten-quadrillion').show();
                    $('.cmb2-id-shorten-quintillion').show();
                } else {
                    $('.cmb2-id-shorten-precision').hide();
                    $('.cmb2-id-shorten-thousand').hide();
                    $('.cmb2-id-shorten-million').hide();
                    $('.cmb2-id-shorten-billion').hide();
                    $('.cmb2-id-shorten-trillion').hide();
                    $('.cmb2-id-shorten-quadrillion').hide();
                    $('.cmb2-id-shorten-quintillion').hide();
                }
            });

            //
            $('#users_requires_approval').on('change', function() {
                var users_requires_approval = $(this).val();
                self.show_hide_phone_fields(users_requires_approval);
            });

            var users_requires_approval = $('#users_requires_approval').val();
            self.show_hide_phone_fields(users_requires_approval);

            self.navasanTest();
        },
        navasanTest: function() {
            var $button = $('#wp-cardealer-navasan-test');
            if ( ! $button.length ) {
                return;
            }

            $button.on('click', function(event) {
                event.preventDefault();
                var $result = $('.wp-cardealer-navasan-test-result');
                var apiKey = $('#navasan_api_key').val() || '';
                var item = $('#navasan_usd_item').val() || 'USD';

                $button.prop('disabled', true);
                $result.removeClass('notice-success notice-error').text( wp_cardealer_opts.navasan_testing || 'Testing...' );

                $.post(wp_cardealer_opts.ajaxurl, {
                    action: 'wp_cardealer_navasan_test_token',
                    nonce: wp_cardealer_opts.navasan_nonce,
                    api_key: apiKey,
                    item: item
                }).done(function(response) {
                    if ( response && response.success && response.data ) {
                        $result.css('color', '#008a20').text(response.data.message);
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : 'Request failed.';
                        $result.css('color', '#d63638').text(message);
                    }
                }).fail(function() {
                    $result.css('color', '#d63638').text('Request failed.');
                }).always(function() {
                    $button.prop('disabled', false);
                });
            });
        },
        show_hide_phone_fields: function(val) {
            if ( val == 'phone_approve' ) {
                $('.cmb2-id-wp-cardealer-title-general-settings-phone-approve').show();
                $('.cmb2-id-phone-approve-operator').show();
                $('.cmb2-id-wp-cardealer-title-general-settings-phone-register-settings').show();
                $('.cmb2-id-phone-approve-default-country-code').show();
                $('.cmb2-id-phone-approve-default-country-code-custom').show();
                $('.cmb2-id-phone-approve-sms-text').show();
                $('.cmb2-id-wp-cardealer-title-general-settings-otp-settings').show();
                $('.cmb2-id-phone-approve-otp-digits').show();
                $('.cmb2-id-phone-approve-incorrect-otp-limit').show();
                $('.cmb2-id-phone-approve-otp-expiry').show();
                $('.cmb2-id-phone-approve-resend-otp-limit').show();
                $('.cmb2-id-phone-approve-ban-time').show();
                $('.cmb2-id-phone-approve-resend-otp-wait-time').show();


                $('.cmb2-id-wp-cardealer-title-general-settings-amazon-settings').show();
                $('.cmb2-id-phone-approve-aws-access-key').show();
                $('.cmb2-id-phone-approve-aws-secret-access-key').show();
                $('.cmb2-id-wp-cardealer-title-general-settings-twilio-settings').show();
                $('.cmb2-id-phone-approve-twilio-account-sid').show();
                $('.cmb2-id-phone-approve-twilio-auth-token').show();
                $('.cmb2-id-phone-approve-twilio-sender-number').show();


            } else {
                $('.cmb2-id-wp-cardealer-title-general-settings-phone-approve').hide();
                $('.cmb2-id-phone-approve-operator').hide();
                $('.cmb2-id-wp-cardealer-title-general-settings-phone-register-settings').hide();
                $('.cmb2-id-phone-approve-default-country-code').hide();
                $('.cmb2-id-phone-approve-default-country-code-custom').hide();
                $('.cmb2-id-phone-approve-sms-text').hide();
                $('.cmb2-id-wp-cardealer-title-general-settings-otp-settings').hide();
                $('.cmb2-id-phone-approve-otp-digits').hide();
                $('.cmb2-id-phone-approve-incorrect-otp-limit').hide();
                $('.cmb2-id-phone-approve-otp-expiry').hide();
                $('.cmb2-id-phone-approve-resend-otp-limit').hide();
                $('.cmb2-id-phone-approve-ban-time').hide();
                $('.cmb2-id-phone-approve-resend-otp-wait-time').hide();

                $('.cmb2-id-wp-cardealer-title-general-settings-amazon-settings').hide();
                $('.cmb2-id-phone-approve-aws-access-key').hide();
                $('.cmb2-id-phone-approve-aws-secret-access-key').hide();
                $('.cmb2-id-wp-cardealer-title-general-settings-twilio-settings').hide();
                $('.cmb2-id-phone-approve-twilio-account-sid').hide();
                $('.cmb2-id-phone-approve-twilio-auth-token').hide();
                $('.cmb2-id-phone-approve-twilio-sender-number').hide();
            }
        },
        add_file: function(event, selector) {

            var upload = $(".uploaded-file"), frame;
            var cardealer_selector = selector, cardealer_upload;

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( cardealer_upload ) {
                cardealer_upload.open();
            } else {
                // Create the media frame.
                cardealer_upload = wp.media.frames.cardealer_upload =  wp.media({
                    // Set the title of the modal.
                    title: "Select Image",

                    // Customize the submit button.
                    button: {
                        // Set the text of the button.
                        text: "Selected",
                        // Tell the button not to close the modal, since we're
                        // going to refresh the page when the image is selected.
                        close: false
                    }
                });

                // When an image is selected, run a callback.
                cardealer_upload.on( 'select', function() {
                    // Grab the selected attachment.
                    var attachment = cardealer_upload.state().get('selection').first();
                    cardealer_upload.close();
                    cardealer_selector.find('.upload_image').val(attachment.attributes.id).change();
                    if ( attachment.attributes.type == 'image' ) {
                        cardealer_selector.find('.screenshot-user').empty().hide().prepend('<img src="' + attachment.attributes.url + '">').slideDown('fast');
                    }
                });

            }
            // Finally, open the modal.
            cardealer_upload.open();
        },
        remove_file: function(selector) {
            selector.find('.screenshot-user').slideUp('fast').next().val('').trigger('change');
        }
        
    }

    $.wpcdAdminMainCore = WPCDAdminMainCore.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WPCDAdminMainCore();
    });
    
})(jQuery);

