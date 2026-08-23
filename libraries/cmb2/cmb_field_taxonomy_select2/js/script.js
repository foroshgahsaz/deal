(function ($) {
	'use strict';

	$.fn.filterSelect2 = function( class_a = ''){
	  	if( $(this).is('select') ){
	  		var allowclear = true;
			var width = $(this).data('width') ? $(this).data('width') : '100%';
	  		if ( $(this).hasClass('select2-hidden-accessible') ) {
		    	$(this).select2('destroy');
		    }
	    	$(this).empty();
	    	$(this).append( $('option.condition--all', $clonedSelect[$(this).attr('name')]).clone() );
	    	$(this).append( $('option.' + class_a, $clonedSelect[$(this).attr('name')]).clone() );
	    	$(this).select2({
	    		allowClear: allowclear,
				width: width,
	    	});
	  	}
	  	return $(this);
	}
	
	var $clonedSelect = {};

	$('.pw_taxonomy_select').each(function () {
		var allowclear = true;
		var width = $(this).data('width') ? $(this).data('width') : '100%';
		$(this).select2({
			allowClear: allowclear,
			width: width
		});
	});
	
	$('.pw_taxonomy_select[data-condition^="listing_"]').each(function () {
		$clonedSelect[$(this).attr('name')] = $(this).clone();
	});

	$('.pw_taxonomy_select[name="_listing_category"]').on('change', function(){
		var value = $(this).val();
		if ( value ) {
			$('.pw_taxonomy_select[data-condition="listing_category"]').each(function () {
				$(this).filterSelect2('condition-' + value);
			});
		}
	});

	if ( $('.pw_taxonomy_select[name="_listing_category"]').length ) {
		var value = $('.pw_taxonomy_select[name="_listing_category"]').val();
		if ( value ) {
			$('.pw_taxonomy_select[data-condition="listing_category"]').each(function () {
				$(this).filterSelect2('condition-' + value);
			});
		}
	}
	$('.pw_taxonomy_select[name="_listing_make"]').on('change', function(){
		var value = $(this).val();
		if ( value ) {
			$('.pw_taxonomy_select[data-condition="listing_make"]').each(function () {
				$(this).filterSelect2('condition-' + value);
			});
		}
	});
	
	if ( $('.pw_taxonomy_select[name="_listing_make"]').length ) {
		var value = $('.pw_taxonomy_select[name="_listing_make"]').val();
		if ( value ) {
			$('.pw_taxonomy_select[data-condition="listing_make"]').each(function () {
				$(this).filterSelect2('condition-' + value);
			});
		}
	}

	

	$.fn.extend({
		select2_sortable: function () {
			var select = $(this);
			var allowclear = true;
			var width = $(this).data('width') ? $(this).data('width') : '100%';

			$(select).select2({
				allowClear: allowclear,
				width: width
			});

			var ul = $(select).next('.select2-container').first('ul.select2-selection__rendered');
			ul.sortable({
				containment: 'parent',
				items      : 'li:not(.select2-search--inline)',
				tolerance  : 'pointer',
				stop       : function () {
					$($(ul).find('.select2-selection__choice').get().reverse()).each(function () {
						var id = $(this).data('data').id;
						var option = select.find('option[value="' + id + '"]')[0];
						$(select).prepend(option);
					});
				}
			});
		}
	});

	$('.pw_taxonomy_multiselect').each(function () {
		$(this).select2_sortable();
	});
	
})(jQuery);