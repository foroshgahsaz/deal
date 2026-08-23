(function ($) {
	'use strict';

	var __cache = [];
	$('.pw_taxonomy_select_search').each(function () {
		select_init($(this));
	});

	function select_init($element) {
		var allowclear = true;
		var width = $element.data('width') ? $element.data('width') : '100%';
		var $taxonomy = $element.data('taxonomy');

		$element.select2({
			allowClear: allowclear,
			width: width,
            width: '100%',
            dir: wp_cardealer_select2_opts['dir'],
            language: {
                noResults: function (params) {
                    return wp_cardealer_select2_opts['language_result'];
                },
                inputTooShort: function () {
                    return wp_cardealer_select2_opts['formatInputTooShort_text'];
                }
            },
			ajax: {
				url: wp_cardealer_tax_search_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wpcd_search_terms' ),
				dataType: 'json',
				delay: 250,
				data: function (params) {
					var query = {
						search: params.term,
						page: params.page || 1,
						taxonomy: $taxonomy,
					}

					// Query parameters will be ?search=[term]&type=public
					return query;
				},
				processResults: function (data, params) {
			      	params.page = params.page || 1;

			      	return {
				        results: $.map(data.results, function (item) {
		                    return {
		                        text: item.name,
		                        id: item.id
		                    }
		                }),
				        pagination: {
				          	more: params.page < data.pages
				        }
			      	};
			    },
			    transport: function(params, success, failure) {
					//retrieve the cached key or default to _ALL_
					var __cachekey = params.data.search + '-' + params.data.taxonomy + '-' + params.data.page;

					if ('undefined' !== typeof __cache[__cachekey]) {
						//display the cached results
						success(__cache[__cachekey]);
						return; /* noop */
					}
					var $request = $.ajax(params);
					$request.then(function(data) {
						//store data in cache
						__cache[__cachekey] = data;
						//display the results
						success(__cache[__cachekey]);
					});
					$request.fail(failure);
					return $request;
				},
			    cache: true
			},
			placeholder: 'Search for a repository',
  			minimumInputLength: 2
		});
	}
	
	$.fn.extend({
		select2_sortable: function () {
			var select = $(this);
			
			select_init($(this));
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

	$('.pw_taxonomy_multiselect_search').each(function () {
		$(this).select2_sortable();
	});

	// Before a new group row is added, destroy Select2. We'll reinitialise after the row is added
	$('.cmb-repeatable-group').on('cmb2_add_group_row_start', function (event, instance) {
		var $table = $(document.getElementById($(instance).data('selector')));
		var $oldRow = $table.find('.cmb-repeatable-grouping').last();

		$oldRow.find('.pw_taxonomy_select2_search').each(function () {
			$(this).select2('destroy');
		});
	});

	// When a new group row is added, clear selection and initialise Select2
	$('.cmb-repeatable-group').on('cmb2_add_row', function (event, newRow) {
		$(newRow).find('.pw_taxonomy_select_search').each(function () {
			$('option:selected', this).removeAttr("selected");
			select_init($(this));
		});

		$(newRow).find('.pw_taxonomy_multiselect_search').each(function () {
			$('option:selected', this).removeAttr("selected");
			$(this).select2_sortable();
		});

		// Reinitialise the field we previously destroyed
		$(newRow).prev().find('.pw_taxonomy_select_search').each(function () {
			select_init($(this));
		});

		// Reinitialise the field we previously destroyed
		$(newRow).prev().find('.pw_taxonomy_multiselect_search').each(function () {
			$(this).select2_sortable();
		});
	});

	// Before a group row is shifted, destroy Select2. We'll reinitialise after the row shift
	$('.cmb-repeatable-group').on('cmb2_shift_rows_start', function (event, instance) {
		var groupWrap = $(instance).closest('.cmb-repeatable-group');
		groupWrap.find('.pw_taxonomy_select2_search').each(function () {
			$(this).select2('destroy');
		});

	});

	// When a group row is shifted, reinitialise Select2
	$('.cmb-repeatable-group').on('cmb2_shift_rows_complete', function (event, instance) {
		var groupWrap = $(instance).closest('.cmb-repeatable-group');
		groupWrap.find('.pw_taxonomy_select_search').each(function () {
			select_init($(this));
		});

		groupWrap.find('.pw_taxonomy_multiselect_search').each(function () {
			$(this).select2_sortable();
		});
	});

	// Before a new repeatable field row is added, destroy Select2. We'll reinitialise after the row is added
	$('.cmb-add-row-button').on('click', function (event) {
		var $table = $(document.getElementById($(event.target).data('selector')));
		var $oldRow = $table.find('.cmb-row').last();

		$oldRow.find('.pw_taxonomy_select2_search').each(function () {
			$(this).select2('destroy');
		});
	});

	// When a new repeatable field row is added, clear selection and initialise Select2
	$('.cmb-repeat-table').on('cmb2_add_row', function (event, newRow) {

		// Reinitialise the field we previously destroyed
		$(newRow).prev().find('.pw_taxonomy_select_search').each(function () {
			$('option:selected', this).removeAttr("selected");
			select_init($(this));
		});

		// Reinitialise the field we previously destroyed
		$(newRow).prev().find('.pw_taxonomy_multiselect_search').each(function () {
			$('option:selected', this).removeAttr("selected");
			$(this).select2_sortable();
		});
	});
})(jQuery);