(function ($) {
	'use strict';

	// hours operation
    $('.add-new-hour').on('click', function(e){
        e.preventDefault();
        var parent = $(this).closest('.enter-hours-content');
        var length = parent.find('.enter-hours-item-inner').length;
        var html = parent.find('.enter-hours-item-inner').eq(0).clone(true);
        

        parent.find('.enter-hours-wrapper').append(html);
    });

    $('.remove-hour').on('click', function(e) {
        e.preventDefault();
        var parent = $(this).closest('.enter-hours-content');
        var index = parent.find('.enter-hours-item-inner').last().index();
        if ( index > 0 ) {
            parent.find('.enter-hours-item-inner').eq(index).remove();
        }
    });


})(jQuery);