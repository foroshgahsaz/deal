(function ($) {
    "use strict";

    function WPCDAjaxFileUpload() {
        var self = this;
        self.init();
    };

    WPCDAjaxFileUpload.prototype = {
        init: function() {
            var self = this;

            $('.wp-cardealer-file-upload').each(function(){
                self.upload_file($(this));
            });
            $(document).on('click', '.cmb-add-group-row', function(e) {
                setTimeout(function(){
                    $('.wp-cardealer-file-upload').each(function(){
                        self.upload_file($(this));
                    });
                }, 50);
                
            });
            $(document).on('click', '.wp-cardealer-uploaded-files .wp-cardealer-remove-uploaded-file', function(e) {
                e.preventDefault();
                var $file_field = $(this).closest('.cmb-type-wp-cardealer-file').find('input.wp-cardealer-file-upload');
                if ( typeof $file_field.data( 'file_limit_left' ) !== 'undefined' ) {
                    var fileLimitLeft = parseInt( $file_field.data( 'file_limit_left' ), 10 );
                    $file_field.data( 'file_limit_left', fileLimitLeft + 1 );
                }
                $(this).closest('.wp-cardealer-uploaded-file').remove();
            });
        },
        upload_file: function($element) {
            $element.fileupload({
                dataType: 'json',
                dropZone: $(this),
                url: wp_cardealer_file_upload.ajax_url_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_upload_file' ),
                maxNumberOfFiles: 1,
                formData: {
                    script: true,
                    action: 'wp_cardealer_ajax_upload_file',
                    allow_types: $element.data('mime_types'),
                },
                add: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );
                    var $uploaded_files = $file_field.parent().find('.wp-cardealer-uploaded-files');
                    var uploadErrors    = [];
                    var fileLimitLeft    = false;
                    var fileLimit        = parseInt( $file_field.data( 'file_limit' ), 10 );

                    if ( typeof $file_field.data( 'file_limit_left' ) !== 'undefined' ) {
                        fileLimitLeft = parseInt( $file_field.data( 'file_limit_left' ), 10 );
                    } else if ( typeof fileLimit !== 'undefined' ) {
                        var currentFiles = parseInt( $uploaded_files.children( '.wp-cardealer-uploaded-file' ).length, 10);
                        fileLimitLeft = fileLimit - currentFiles;
                        $file_field.data( 'file_limit_left', fileLimitLeft );
                    }

                    if ( false !== fileLimitLeft && fileLimitLeft <= 0 ) {
                        var message = 'Exceeded upload limit';
                        if( $file_field.data( 'file_limit_message' ) ) {
                            message = $file_field.data( 'file_limit_message' );
                        } else if ( typeof wp_cardealer_file_upload !== 'undefined' ) {
                            message = wp_cardealer_file_upload.i18n_over_upload_limit;
                        }
                        message = message.replace( '%d', fileLimit );

                        uploadErrors.push( message );
                    }

                    // Validate type
                    var allowed_types = $(this).data('file_types');

                    if ( allowed_types ) {
                        var acceptFileTypes = new RegExp( '(\.|\/)(' + allowed_types + ')$', 'i' );

                        if ( data.originalFiles[0].name.length && ! acceptFileTypes.test( data.originalFiles[0].name ) ) {
                            uploadErrors.push( wp_cardealer_file_upload.i18n_invalid_file_type + ' ' + allowed_types );
                        }
                    }

                    if ( uploadErrors.length > 0 ) {
                        window.alert( uploadErrors.join( '\n' ) );
                    } else {
                        if ( false !== fileLimitLeft ) {
                            $file_field.data( 'file_limit_left', fileLimitLeft - 1 );
                        }
                        $form.find(':input[type="submit"]').attr( 'disabled', 'disabled' );
                        data.context = $('<progress value="" max="100"></progress>').appendTo( $uploaded_files );
                        data.submit();
                    }
                },
                progress: function (e, data) {
                    var progress        = parseInt(data.loaded / data.total * 100, 10);
                    data.context.val( progress );
                },
                fail: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );

                    if ( data.errorThrown ) {
                        window.alert( data.errorThrown );
                    }

                    data.context.remove();

                    $form.find(':input[type="submit"]').removeAttr( 'disabled' );
                },
                done: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );
                    var $uploaded_files = $file_field.parent().find('.wp-cardealer-uploaded-files');
                    var multiple        = $file_field.attr( 'multiple' ) ? 1 : 0;
                    var image_types     = [ 'jpg', 'gif', 'png', 'jpeg', 'jpe' ];

                    $file_field.val("");

                    data.context.remove();

                    $.each(data.result.files, function(index, file) {
                        if ( file.error ) {
                            window.alert( file.error );
                        } else {
                            var html;
                            if ( $.inArray( file.extension, image_types ) >= 0 ) {
                                html = $.parseHTML( wp_cardealer_file_upload.js_field_html_img );
                                $( html ).find('.wp-cardealer-uploaded-file-preview img').attr( 'src', file.url );
                            } else {
                                html = $.parseHTML( wp_cardealer_file_upload.js_field_html );
                                $( html ).find('.wp-cardealer-uploaded-file-name code').text( file.name );
                            }

                            $( html ).find('.input-text').val( file.url );
                            $( html ).find('.input-text').attr( 'name', 'current_' + $file_field.attr( 'name' ) );

                            if ( multiple ) {
                                $uploaded_files.append( html );
                            } else {
                                $uploaded_files.html( html );
                            }
                        }
                    });

                    $form.find(':input[type="submit"]').removeAttr( 'disabled' );
                }
            });
        }
    }

    $.wpcdAjaxFileUpload = WPCDAjaxFileUpload.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WPCDAjaxFileUpload();

    });

})(jQuery);

