(function ($) {
	'use strict';

	var $clonedSelect = {};

	$('.pw_taxonomy_select_parent').each(function () {
		var allowclear = true;
		var width = $(this).data('width') ? $(this).data('width') : '100%';
		$(this).select2({
			allowClear: allowclear,
			width: width
		});
	});
	

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

	$('.pw_taxonomy_multiselect_parent').each(function () {
		$(this).select2_sortable();
	});
	
})(jQuery);