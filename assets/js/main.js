(function ($) {
    "use strict";

    if (!$.wpcdExtensions)
        $.wpcdExtensions = {};
    
    function WPCDMainCore() {
        var self = this;
        self.init();
    };

    WPCDMainCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            var self = this;

            self.fileUpload($('.label-can-drag'));
            
            self.recaptchaCallback();

            self.submitListing();

            self.userLoginRegister();

            self.userChangePass();
            
            self.removeListing();

            // favorite
            self.addListingFavorite();

            self.removeListingFavorite();
            

            // compare
            self.addListingCompare();

            self.removeListingCompare();

            self.reviewInit();

            self.listingSavedSearch();

            self.select2Init();
            
            self.filterListing();
            
            // mixes
            self.mixesFn();

            self.loadExtension();
        },
        loadExtension: function() {
            var self = this;
            
            // if ($.wpcdExtensions.ajax_upload) {
            //     $.wpcdExtensions.ajax_upload.call(self);
            // }
        },
        recaptchaCallback: function() {
            if ( wp_cardealer_opts.recaptcha_enable ) {
                if (!window.grecaptcha) {
                } else {
                    setTimeout(function(){
                        var recaptchas = document.getElementsByClassName("ga-recaptcha");
                        for(var i=0; i<recaptchas.length; i++) {
                            var recaptcha = recaptchas[i];
                            var sitekey = recaptcha.dataset.sitekey;

                            grecaptcha.render(recaptcha, {
                                'sitekey' : sitekey
                            });
                        }
                    }, 500);
                }
            }
        },
        fileUpload: function($el){
            
            var isAdvancedUpload = function() {
                var div = document.createElement('div');
                return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
            }();

            if (isAdvancedUpload) {

                var droppedFiles = false;
                $el.each(function(){
                    var label_self = $(this);
                    label_self.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }).on('dragover dragenter', function() {
                        label_self.addClass('is-dragover');
                    }).on('dragleave dragend drop', function() {
                        label_self.removeClass('is-dragover');
                    }).on('drop', function(e) {
                        droppedFiles = e.originalEvent.dataTransfer.files;
                        label_self.parent().find('input[type="file"]').prop('files', droppedFiles).trigger('change');
                    });
                });
            }
            $(document).on('click', '.label-can-drag', function(){
                $(this).parent().find('input[type="file"]').trigger('click');
            });
        },
        submitListing: function() {
            var self = this;
            $('.cmb-repeatable-group').on('cmb2_add_row', function (event, newRow) {

                // Reinitialise the field we previously destroyed
                $(newRow).find('.label-can-drag').each(function () {
                    self.fileUpload($(this));
                });

            });
        },
        userLoginRegister: function() {
            var self = this;
            
            // sign in proccess
            $('body').on('submit', 'form.login-form', function(){
                var $this = $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_login' ),
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=wp_cardealer_ajax_login"
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">' + data.msg + '</div>' );
                        setTimeout(function(){
                            window.location.href = wp_cardealer_opts.after_login_page_user_url;
                            
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">' + data.msg + '</div>' );
                    }
                });
                return false; 
            } );
            $('body').on('click', '.back-link', function(e){
                e.preventDefault();
                var $con = $(this).closest('.login-form-wrapper');
                $con.find('.form-container').hide();
                $($(this).attr('href')).show(); 
                return false;
            } );

             // lost password in proccess
            $('body').on('submit', 'form.forgotpassword-form', function(){
                var $this= $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                  url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_forgotpass' ),
                  type:'POST',
                  dataType: 'json',
                  data:  $(this).serialize()+"&action=wp_cardealer_ajax_forgotpass"
                }).done(function(data) {
                     $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        setTimeout(function(){
                            window.location.reload(true);
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });
                return false; 
            } );
            $('body').on('click', '#forgot-password-form-wrapper form .btn-cancel', function(e){
                e.preventDefault();
                $('#forgot-password-form-wrapper').hide();
                $('#login-form-wrapper').show();
            } );


            // register
            var register_step = '1';
            $('body').on('submit', 'form.register-form', function(){
                var $this = $(this),
                    $parent_div = $this.closest('.register-form-wrapper');
                $('.alert', this).remove();
                if ( $this.hasClass('loading') ) {
                    return;
                }
                $this.addClass('loading');

                var action = 'wp_cardealer_ajax_register';
                if ( wp_cardealer_opts.approval_type === 'phone_approve' ) {
                    if ( register_step == '1' ) {
                        action = 'wp_cardealer_ajax_get_opt'
                    }
                }
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', action ),
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=" + action
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        if ( wp_cardealer_opts.approval_type === 'phone_approve' ) {
                            if ( register_step == '1' ) {
                                $this.hide();
                                var $otp_form = $parent_div.find('form.register-form-otp');
                                $otp_form.find('.sent-txt .no-txt').html(data.msg);
                                $otp_form.show();

                                var $resendLink     = $otp_form.find('.resend-link'),
                                    $timer          = $otp_form.find('.resend-timer'),
                                    resendTime      = parseInt( wp_cardealer_opts.resend_otp_wait_time );

                                if( resendTime > 0 ) {
                                    $resendLink.addClass('disabled');
                                    var resendTimer;
                                    clearInterval( resendTimer );

                                    resendTimer = setInterval(function(){
                                        $timer.html('('+resendTime+')');
                                        if( resendTime <= 0 ){
                                            clearInterval( resendTimer );
                                            $resendLink.removeClass('disabled');
                                            $timer.html('');
                                        }
                                        resendTime--;
                                    },1000);
                                }

                            } else if ( register_step == '2' ) {
                                $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                                if ( data.redirect ) {
                                    setTimeout(function(){
                                        window.location.href = wp_cardealer_opts.after_login_page_user_url;
                                    }, 500);
                                }
                            }
                        } else {
                            $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                            if ( data.redirect ) {
                                setTimeout(function(){
                                    window.location.href = wp_cardealer_opts.after_login_page_user_url;
                                }, 500);
                            }
                        }
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                        if ( wp_cardealer_opts.recaptcha_enable ) {
                            if (!window.grecaptcha) {
                            } else {
                                grecaptcha.reset();
                            }
                        }
                    }
                });
                return false;
            } );
            
            // verify otp
            $('body').on('submit', 'form.register-form-otp', function(){
                var $this = $(this),
                    $parent_div = $this.closest('.register-form-wrapper'),
                    $register_form = $parent_div.find('form.register-form');

                if ( $this.hasClass('loading') ) {
                    return;
                }
                $('.alert', this).remove();
                $this.addClass('loading');

                var otp = '';
                $this.find('.otp-input-cont input').each( function( index, input ){
                    otp += $(this).val();
                });

                var form_data = {
                    'otp': otp,
                    'token': $register_form.find('.register-form-token').val(),
                }

                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_verify_opt' ),
                    type:'POST',
                    dataType: 'json',
                    data: form_data
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        register_step = '2';
                        $this.hide();
                        $register_form.show().trigger('submit');
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });
                return false;
            } );

            //Switch Input
            $('.otp-input-cont input.otp-input').on('keyup', function(){
                console.log('aaa');
                if( $(this).val().length === parseInt( $(this).attr('maxlength') ) && $(this).next('input.otp-input').length !== 0 ){
                    $(this).next('input.otp-input').focus();
                }

                //Backspace is pressed
                if( $(this).val().length === 0 && event.keyCode == 8 && $(this).prev('input.otp-input').length !== 0 ){
                    $(this).prev('input.otp-input').focus().val('');
                }
            });

            // resend otp
            $('body').on('click', '.resend-link:not(.disabled)', function(){
                var $this = $(this),
                    $parent_div = $this.closest('.register-form-wrapper'),
                    $otp_form = $this.closest('form');

                if ( $otp_form.hasClass('loading') ) {
                    return;
                }
                $('.alert', this).remove();
                $otp_form.addClass('loading');

                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_resend_opt' ),
                    type:'POST',
                    dataType: 'json',
                }).done(function(data) {
                    $otp_form.removeClass('loading');
                    if ( data.status ) {

                        var $resendLink     = $otp_form.find('.resend-link'),
                            $timer          = $otp_form.find('.resend-timer'),
                            resendTime      = parseInt( wp_cardealer_opts.resend_otp_wait_time );

                        if( resendTime > 0 ) {
                            $resendLink.addClass('disabled');
                            var resendTimer;
                            clearInterval( resendTimer );

                            resendTimer = setInterval(function(){
                                $timer.html('('+resendTime+')');
                                if( resendTime <= 0 ){
                                    clearInterval( resendTimer );
                                    $resendLink.removeClass('disabled');
                                    $timer.html('');
                                }
                                resendTime--;
                            },1000);
                        }

                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });
                return false;
            } );

            $(document).on('click', 'form.register-form-otp .no-change', function(){
                var $this = $(this);
                var $parent_div = $this.closest('.register-form-wrapper');
                $this.closest('form').hide();
                register_step = '1';
                $parent_div.find('form.register-form').show();
            });

            // wp-cardealer-resend-approve-account-btn
            $(document).on('click', '.wp-cardealer-resend-approve-account-btn', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $container = $(this).parent();
                $this.addClass('loading');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_resend_approve_account' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        action: 'wp_cardealer_ajax_resend_approve_account',
                        login: $this.data('login'),
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $container.html( data.msg );
                    } else {
                        $container.html( data.msg );
                    }
                });
            });

        },
        userChangePass: function() {
            var self = this;
            $('body').on('submit', 'form.change-password-form', function(){
                var $this = $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_change_password' ),
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=wp_cardealer_ajax_change_password"
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">' + data.msg + '</div>' );
                        setTimeout(function(){
                            window.location.href = wp_cardealer_opts.login_register_url;
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">' + data.msg + '</div>' );
                    }
                });
                return false; 
            } );
        },
        removeListing: function() {
            var self = this;
            $('.listing-button-delete').on('click', function() {
                var $this = $(this);
                var r = confirm( wp_cardealer_opts.rm_item_txt );
                if ( r == true ) {
                    $this.addClass('loading');
                    var listing_id = $(this).data('listing_id');
                    var nonce = $(this).data('nonce');
                    $.ajax({
                        url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_listing' ),
                        type:'POST',
                        dataType: 'json',
                        data: {
                            'listing_id': listing_id,
                            'nonce': nonce,
                            'action': 'wp_cardealer_ajax_remove_listing',
                        }
                    }).done(function(data) {
                        $this.removeClass('loading');
                        if ( data.status ) {
                            $this.closest('.my-listings-item').remove();
                        }
                        self.showMessage(data.msg, data.status);
                    });
                }
            });
        },
        addListingFavorite: function() {
            var self = this;
            $(document).on('click', '.btn-add-listing-favorite', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_add_listing_favorite' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_add_listing_favorite',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-add-listing-favorite').addClass('btn-added-listing-favorite');
                        $this.data('nonce', data.nonce);

                        $(document).trigger( "after_add_listing_favorite", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        removeListingFavorite: function() {
            var self = this;
            $(document).on('click', '.btn-added-listing-favorite', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_listing_favorite' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_remove_listing_favorite',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-added-listing-favorite').addClass('btn-add-listing-favorite');
                        $this.data('nonce', data.nonce);

                        $(document).trigger( "after_remove_listing_favorite", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            $('.btn-remove-listing-favorite').on('click', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_listing_favorite' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_remove_listing_favorite',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.listing-favorite-wrapper').remove();

                        $(document).trigger( "after_remove_listing_favorite", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        // compare
        addListingCompare: function() {
            var self = this;
            $(document).on('click', '.btn-add-listing-compare', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_add_listing_compare' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_add_listing_compare',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-add-listing-compare').addClass('btn-added-listing-compare');
                        $this.data('nonce', data.nonce);

                        $(document).trigger( "after_add_listing_compare", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        removeListingCompare: function() {
            var self = this;
            $(document).on('click', '.btn-added-listing-compare', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_listing_compare' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_remove_listing_compare',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-added-listing-compare').addClass('btn-add-listing-compare');
                        $this.data('nonce', data.nonce);

                        $(document).trigger( "after_remove_listing_compare", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            $('.btn-remove-listing-compare').on('click', function() {
                var $this = $(this);
                $this.addClass('loading');
                var listing_id = $(this).data('listing_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_listing_compare' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'listing_id': listing_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_remove_listing_compare',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        location.reload();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        reviewInit: function() {
            var self = this;
            
            if ( $('.comment-form-rating').length > 0 ) {
                $('.comment-form-rating .rating-inner').each(function(){
                    var e_this = $(this);
                    var $star = e_this.find('.review-stars');
                    var $review = e_this.find('input.rating');
                    $star.find('li').on('mouseover',
                        function () {
                            $(this).nextAll().find('span').removeClass('active');
                            $(this).prevAll().find('span').removeClass('active').addClass('active');
                            $(this).find('span').removeClass('active').addClass('active');
                        }
                    );
                    $star.on('mouseout', function(){
                        var current = $review.val() - 1;
                        var current_e = $star.find('li').eq(current);

                        current_e.nextAll().find('span').removeClass('active');
                        current_e.prevAll().find('span').removeClass('active').addClass('active');
                        current_e.find('span').removeClass('active').addClass('active');
                    });

                    $star.find('li').on('click', function () {
                        $(this).nextAll().find('span').removeClass('active');
                        $(this).prevAll().find('span').removeClass('active').addClass('active');
                        $(this).find('span').removeClass('active').addClass('active');
                        
                        $review.val($(this).index() + 1);
                    } );

                });
            }
        },
        listingSavedSearch: function() {
            var self = this;
            $('.btn-saved-search').magnificPopup({
                mainClass: 'wp-cardealer-mfp-container',
                type:'inline',
                midClick: true
            });
            
            $(document).on('submit', 'form.saved-search-form', function() {
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return false;
                }
                
                $this.find('.alert').remove();
                
                $this.addClass('loading');
                var url_vars = self.getUrlVars();
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_add_saved_search' ),
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_cardealer_ajax_add_saved_search' + url_vars
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        setTimeout(function(){
                            $.magnificPopup.close();
                        }, 1500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            // Remove listing alert
            $(document).on('click', '.btn-remove-saved-search', function() {
                var $this = $(this);
                $this.addClass('loading');
                var saved_search_id = $(this).data('saved_search_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_remove_saved_search' ),
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'saved_search_id': saved_search_id,
                        'nonce': nonce,
                        'action': 'wp_cardealer_ajax_remove_saved_search',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.saved-search-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        getUrlVars: function() {
            var self = this;
            var vars = '';
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++) {
                vars += '&' +hashes[i];
            }
            return vars;
        },
        select2Init: function() {
            var self = this;
            if ( $.isFunction( $.fn.select2 ) && typeof wp_cardealer_select2_opts !== 'undefined' ) {
                var select2_args = wp_cardealer_select2_opts;
                select2_args['allowClear']              = false;
                select2_args['minimumResultsForSearch'] = 10;
                select2_args['width'] = 'auto';

                select2_args['language'] = {
                    noResults: function(){
                        return wp_cardealer_select2_opts.language_result;
                    }
                };

                if($('select').hasClass('orderby')){
                    select2_args.theme = 'default orderby';
                    $('select.orderby').select2( select2_args );
                }
                $('select.listing_id').select2( select2_args );
            }
        },
        filterListing: function() {
            var self = this;

            $(document).on('click', 'form .toggle-field .heading-label', function(){
                var container = $(this).closest('.form-group');
                container.find('.form-group-inner').slideToggle();
                if ( container.hasClass('hide-content') ) {
                    container.removeClass('hide-content');
                } else {
                    container.addClass('hide-content');
                }
            });
            $(document).on('click', '.toggle-filter-list', function() {
                var $this = $(this);
                var container = $(this).closest('.form-group');
                container.find('.terms-list .more-fields').each(function(){
                    if ( $(this).hasClass('active') ) {
                        $(this).removeClass('active');
                        $this.find('.text').text(wp_cardealer_opts.show_more);
                    } else {
                        $(this).addClass('active');
                        $this.find('.text').text(wp_cardealer_opts.show_less);
                    }
                });
            });

            if ( $.isFunction( $.fn.slider ) ) {
                $('.search-distance-slider').each(function(){
                    var $this = $(this);
                    var search_distance = $this.closest('.search-distance-wrapper').find('input[name^=filter-distance]');
                    var search_wrap = $this.closest('.search_distance_wrapper');
                    $(this).slider({
                        range: "min",
                        value: search_distance.val(),
                        min: 0,
                        max: 100,
                        slide: function( event, ui ) {
                            search_distance.val( ui.value );
                            $('.text-distance', search_wrap).text( ui.value );
                            $('.distance-custom-handle', $this).attr( "data-value", ui.value );
                            search_distance.trigger('change');
                        },
                        create: function() {
                            $('.distance-custom-handle', $this).attr( "data-value", $( this ).slider( "value" ) );
                        }
                    } );
                } );

                $('.main-range-slider').each(function(){
                    var $this = $(this);
                    $this.slider({
                        range: true,
                        min: $this.data('min'),
                        max: $this.data('max'),
                        values: [ $this.parent().find('.filter-from').val(), $this.parent().find('.filter-to').val() ],
                        slide: function( event, ui ) {
                             $this.closest('.form-group-inner').find('.from-text').text( ui.values[ 0 ] );
                             $this.closest('.form-group-inner').find('.filter-from').val( ui.values[ 0 ] )
                             $this.closest('.form-group-inner').find('.to-text').text( ui.values[ 1 ] );
                             $this.closest('.form-group-inner').find('.filter-to').val( ui.values[ 1 ] );

                             $this.closest('.form-group-inner').find('.filter-to').trigger('change');
                        }
                    } );
                });

                $('.price-range-slider').each(function(){
                    var $this = $(this);
                    $this.slider({
                        range: true,
                        min: $this.data('min'),
                        max: $this.data('max'),
                        values: [ $this.parent().find('.filter-from').val(), $this.parent().find('.filter-to').val() ],
                        slide: function( event, ui ) {
                            var $from_price = ui.values[ 0 ];
                            var $to_price = ui.values[ 1 ];
                            if ( wp_cardealer_opts.enable_multi_currencies === 'yes' ) {
                                $from_price = self.shortenNumber($from_price);
                                $to_price = self.shortenNumber($to_price);
                            } else {
                                $from_price = self.addCommas($from_price);
                                $to_price = self.addCommas($to_price);
                            }
                             $this.closest('.form-group-inner').find('.from-text .price-text').text( $from_price );
                             $this.closest('.form-group-inner').find('.filter-from').val( ui.values[ 0 ] )
                             $this.closest('.form-group-inner').find('.to-text .price-text').text( $to_price );
                             $this.closest('.form-group-inner').find('.filter-to').val( ui.values[ 1 ] );

                             $(document).trigger( "price_slider_after_change", [$this, ui] );

                             $this.closest('.form-group-inner').find('.filter-to').trigger('change');
                        }
                    } );
                });
            }

            $('.find-me').on('click', function() {
                $(this).addClass('loading');
                var this_e = $(this);
                var container = $(this).closest('.form-group');

                navigator.geolocation.getCurrentPosition(function (position) {
                    container.find('input[name="filter-center-latitude"]').val(position.coords.latitude);
                    container.find('input[name="filter-center-longitude"]').val(position.coords.longitude);
                    container.find('input[name="filter-center-location"]').val('Location');
                    container.find('.clear-location').removeClass('hidden');

                    var position = [position.coords.latitude, position.coords.longitude];

                    if ( typeof L.esri.Geocoding.geocodeService != 'undefined' ) {
                    
                        var geocodeService = L.esri.Geocoding.geocodeService();
                        geocodeService.reverse().latlng(position).run(function(error, result) {
                            container.find('input[name="filter-center-location"]').val(result.address.Match_addr);
                        });
                    }

                    return this_e.removeClass('loading');
                }, function (e) {
                    return this_e.removeClass('loading');
                }, {
                    enableHighAccuracy: true
                });
            });

            $('.clear-location').on('click', function() {
                var container = $(this).closest('.form-group');

                container.find('input[name="filter-center-latitude"]').val('');
                container.find('input[name="filter-center-longitude"]').val('');
                container.find('input[name="filter-center-location"]').val('');
                container.find('.clear-location').addClass('hidden');
                container.find('.leaflet-geocode-container').html('');
            });
            $('input[name="filter-center-location"]').on('keyup', function(){
                var container = $(this).closest('.form-group');
                var val = $(this).val();
                if ( $(this).val() !== '' ) {
                    container.find('.clear-location').removeClass('hidden');
                } else {
                    container.find('.clear-location').removeClass('hidden').addClass('hidden');
                }
            });
            $('input[name="filter-center-location"]').each(function(){
                var container = $(this).closest('.form-group');
                var val = $(this).val();
                if ( $(this).val() !== '' ) {
                    container.find('.clear-location').removeClass('hidden');
                } else {
                    container.find('.clear-location').removeClass('hidden').addClass('hidden');
                }
            });

            // search autocomplete location
            if ( wp_cardealer_opts.map_service == 'google-map' ) {
                if (typeof google === 'object' && typeof google.maps === 'object') {
                    function search_location_initialize() {
                        
                        $('input[name="filter-center-location"]').each(function(){
                            var $id = $(this).attr('id');
                            
                            if ( typeof $id !== 'undefined' ) {
                                var container = $('#'+$id).closest('.form-group-inner');
                                var input = document.getElementById($id);
                                var autocomplete = new google.maps.places.Autocomplete(input);
                                autocomplete.setTypes([]);

                                if ( wp_cardealer_opts.geocoder_country ) {
                                    autocomplete.setComponentRestrictions({
                                        country: [wp_cardealer_opts.geocoder_country],
                                    });
                                }

                                autocomplete.addListener( 'place_changed', function () {
                                    var place = autocomplete.getPlace();
                                    place.toString();

                                    if (!place.geometry) {
                                        window.alert("No details available for input: '" + place.name + "'");
                                        return;
                                    }

                                    container.find('input[name=filter-center-latitude]').val(place.geometry.location.lat());
                                    container.find('input[name=filter-center-longitude]').val(place.geometry.location.lng());
                                    
                                });
                            }
                        });
                    }
                    google.maps.event.addDomListener(window, 'load', search_location_initialize);
                }
            } else {
                if ( typeof L.Control.Geocoder.Nominatim != 'undefined' ) {
                    if ( wp_cardealer_opts.geocoder_country ) {
                        var geocoder = new L.Control.Geocoder.Nominatim({
                            geocodingQueryParams: {countrycodes: wp_cardealer_opts.geocoder_country}
                        });
                    } else {
                        var geocoder = new L.Control.Geocoder.Nominatim();
                    }

                    function delay(fn, ms) {
                        let timer = 0
                        return function(...args) {
                            clearTimeout(timer)
                            timer = setTimeout(fn.bind(this, ...args), ms || 0)
                        }
                    }

                    $("input[name=filter-center-location]").attr('autocomplete', 'off').after('<div class="leaflet-geocode-container"></div>');
                    $("input[name=filter-center-location]").on("keyup", delay(function (e) {
                        var s = $(this).val(), $this = $(this), container = $(this).closest('.form-group-inner');
                        if (s && s.length >= 2) {
                            
                            $this.parent().addClass('loading');
                            geocoder.geocode(s, function(results) {
                                var output_html = '';
                                for (var i = 0; i < results.length; i++) {
                                    output_html += '<li class="result-item" data-latitude="'+results[i].center.lat+'" data-longitude="'+results[i].center.lng+'" ><i class="fas fa-map-marker-alt" aria-hidden="true"></i> '+results[i].name+'</li>';
                                }
                                if ( output_html ) {
                                    output_html = '<ul>'+ output_html +'</ul>';
                                }

                                container.find('.leaflet-geocode-container').html(output_html).addClass('active');

                                var highlight_texts = s.split(' ');

                                highlight_texts.forEach(function (item) {
                                    container.find('.leaflet-geocode-container').highlight(item);
                                });

                                $this.parent().removeClass('loading');
                            });
                        } else {
                            container.find('.leaflet-geocode-container').html('').removeClass('active');
                        }
                    }, 500));
                    $('.form-group-inner').on('click', '.leaflet-geocode-container ul li', function() {
                        var container = $(this).closest('.form-group-inner');
                        container.find('input[name=filter-center-latitude]').val($(this).data('latitude'));
                        container.find('input[name=filter-center-longitude]').val($(this).data('longitude'));
                        container.find('input[name=filter-center-location]').val($(this).text());
                        container.find('.leaflet-geocode-container').removeClass('active').html('');
                    });
                }
            }

            // advance
            $('.filter-toggle-adv').on('click', function(e){
                $('.filter-advance-fields').slideToggle();
                return false;
            });
        },
        mixesFn: function() {
            var self = this;
            
            $( '.my-listings-ordering' ).on( 'change', 'select.orderby', function() {
                $( this ).closest( 'form' ).submit();
            });

            $('.contact-form-wrapper').on('submit', function(){
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return;
                }
                $this.addClass('loading');
                $this.find('.alert').remove();
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_contact_form' ),
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_cardealer_ajax_contact_form'
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            $(document).on( 'submit', 'form.delete-profile-form', function() {
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return;
                }
                $this.addClass('loading');
                $(this).find('.alert').remove();
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_delete_profile' ),
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize()
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        window.location.href = wp_cardealer_opts.home_url;
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            $('.make-an-offer-form').on('submit', function(){
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return;
                }
                $this.addClass('loading');
                $this.find('.alert').remove();
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_make_an_offer_form' ),
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize()
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            $('.schedule-test-drive-form').on('submit', function(){
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return;
                }
                $this.addClass('loading');
                $this.find('.alert').remove();
                $.ajax({
                    url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_schedule_test_drive_form' ),
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize()
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            if ( $( 'input.field-datetimepicker' ).length > 0 && $.isFunction( $.fn.datetimepicker ) ) {
                $('input.field-datetimepicker').datetimepicker({
                    timepicker: false,
                    format: 'Y-m-d'
                });
            }
            
            // Location Change
            $('body').on('change', 'select.select-field-region', function(){
                var val = $(this).val();
                var next = $(this).data('next');
                var main_select = 'select.select-field-region' + next;
                if ( $(main_select).length > 0 ) {
                    
                    var select2_args = wp_cardealer_select2_opts;
                        select2_args['allowClear'] = true;
                        select2_args['minimumResultsForSearch'] = 10;
                        select2_args['width'] = '100%';

                    select2_args['language'] = {
                        noResults: function(){
                            return wp_cardealer_select2_opts.language_result;
                        }
                    };

                    $(main_select).prop('disabled', true);
                    $(main_select).val('').trigger('change');

                    if ( val ) {
                        $(main_select).parent().addClass('loading');
                        $.ajax({
                            url: wp_cardealer_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wpcd_process_change_location' ),
                            type:'POST',
                            dataType: 'json',
                            data:{
                                'action': 'wpcd_process_change_location',
                                'parent': val,
                                'taxonomy': $(main_select).data('taxonomy'),
                                'security': wp_cardealer_opts.ajax_nonce,
                            }
                        }).done(function(data) {
                            $(main_select).parent().removeClass('loading');
                            
                            $(main_select).find('option').remove();
                            if ( data ) {
                                $.each(data, function(i, item) {
                                    var option = new Option(item.name, item.id, true, true);
                                    $(main_select).append(option);
                                });
                            }
                            $(main_select).prop("disabled", false);
                            $(main_select).val(null).select2("destroy").select2(select2_args);
                        });
                    } else {
                        $(main_select).find('option').remove();
                        $(main_select).prop("disabled", false);
                        $(main_select).val(null).select2("destroy").select2(select2_args);
                    }
                }
            });

            $('body').on('change', '.listings-currencies input', function(){
                $(this).closest('form').trigger('submit');
            });
        },
        shortenNumber: function($number) {
            var self = this;
            
            var divisors = wp_cardealer_opts.divisors;

            $.each(divisors, function( $index, $value ) {
                if ($number < ($value['divisor'] * 1000)) {
                    $number = $number / $value['divisor'];
                    return self.addCommas($number) + $value['key'];
                }
            });

            return $number;
        },
        addCommas: function(str) {
            var parts = (str + "").split("."),
                main = parts[0],
                len = main.length,
                output = "",
                first = main.charAt(0),
                i;
            
            if (first === '-') {
                main = main.slice(1);
                len = main.length;    
            } else {
                first = "";
            }
            i = len - 1;
            while(i >= 0) {
                output = main.charAt(i) + output;
                if ((len - i) % 3 === 0 && i > 0) {
                    output = wp_cardealer_opts.money_thousands_separator + output;
                }
                --i;
            }
            // put sign back
            output = first + output;
            // put decimal part back
            if (parts.length > 1) {
                output += wp_cardealer_opts.money_dec_point + parts[1];
            }
            return output;
        },
        showMessage: function(msg, status) {
            if ( msg ) {
                var classes = 'alert bg-warning';
                if ( status ) {
                    classes = 'alert bg-info';
                }
                var $html = '<div id="wp-cardealer-popup-message" class="animated fadeInRight"><div class="message-inner '+ classes +'">'+ msg +'</div></div>';
                $('body').find('#wp-cardealer-popup-message').remove();
                $('body').append($html).fadeIn(500);
                setTimeout(function() {
                    $('body').find('#wp-cardealer-popup-message').removeClass('fadeInRight').addClass('delay-2s fadeOutRight');
                }, 1500);
            }
        },
        setCookie: function(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+d.toUTCString();
            document.cookie = cname + "=" + cvalue + "; " + expires+";path=/";
        },
        getCookie: function(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
        },
    }

    $.wpcdMainCore = WPCDMainCore.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WPCDMainCore();

    });
    
})(jQuery);

