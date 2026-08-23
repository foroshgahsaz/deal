(function ($) {
    "use strict";

    $('body').on('click', '.wp-cardealer-rate-exchange-btn', function(e) {
    	var $this = $(this);
		if ( $this.hasClass('loading') ) {
			return false;
		}
        $this.addClass('loading');

        var $currency_val = $('#currency').val();
        var $current_currency_val = $this.closest('.cmb-field-list').find('.multi-currency-select select').val();

    	$.ajax({
            url: wp_job_board_rate_exchange_opts.ajaxurl_endpoint.toString().replace( '%%endpoint%%', 'wp_cardealer_ajax_rate_exchange' ),
            type:'POST',
            dataType: 'json',
            data: {
                default_currency: $currency_val,
            	current_currency: $current_currency_val,
            }
        }).done(function(data) {
            $this.removeClass('loading');
            if ( data.status ) {
    			$this.closest('.rate-exchange-wrapper').find('.wp-cardealer-rate-exchange-input').val(data.rate);
            }
        });
        return false;
    });

})(jQuery);
