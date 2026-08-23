<?php
/**
 * Price
 *
 * @package    wp-cardealer
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_CarDealer_Price {

	public static function init() {
	    add_action( 'init', array( __CLASS__, 'process_currency' ) );
	}
	/**
	 * Formats price
	 *
	 * @access public
	 * @param $price
	 * @return bool|string
	 */
	public static function format_price( $price, $show_null = false, $without_rate_exchange = false ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			if ( !$show_null ) {
				return false;
			}
			$price = 0;
		}
		$decimals = false;
		$money_decimals = wp_cardealer_get_option('money_decimals');

		if ( wp_cardealer_get_option('enable_multi_currencies') === 'yes' ) {
			$current_currency = self::get_current_currency();
			$multi_currencies = self::get_currencies_settings();

			if ( !empty($multi_currencies) && !empty($multi_currencies[$current_currency]) ) {
				$currency_args = $multi_currencies[$current_currency];
			}

			if ( !empty($currency_args) ) {
				if ( !empty($currency_args['custom_symbol']) ) {
					$symbol = $currency_args['custom_symbol'];
				} else {
					$currency = $currency_args['currency'];
					$symbol = WP_CarDealer_Price::currency_symbol($currency);
				}
				$currency_position = !empty($currency_args['currency_position']) ? $currency_args['currency_position'] : 'before';
				$rate_exchange_fee = !empty($currency_args['rate_exchange_fee']) ? $currency_args['rate_exchange_fee'] : 1;
				$decimals = true;
				$money_decimals = !empty($currency_args['money_decimals']) ? $currency_args['money_decimals'] : 1;
				if ( !$without_rate_exchange ) {
					$price = $price*$rate_exchange_fee;
				}
			} else {
				$symbol = wp_cardealer_get_option('custom_symbol', '$');
				if ( empty($symbol) ) {
					$currency = wp_cardealer_get_option('currency', 'USD');
					$symbol = WP_CarDealer_Price::currency_symbol($currency);
				}
				$currency_position = wp_cardealer_get_option('currency_position', 'before');
			}
		} else {
			$symbol = wp_cardealer_get_option('custom_symbol', '$');
			if ( empty($symbol) ) {
				$currency = wp_cardealer_get_option('currency', 'USD');
				$symbol = WP_CarDealer_Price::currency_symbol($currency);
			}
			$currency_position = wp_cardealer_get_option('currency_position', 'before');
		}

		$currency_symbol = ! empty( $symbol ) ? '<span class="suffix">'.$symbol.'</span>' : '<span class="suffix">$</span>';

		if ( wp_cardealer_get_option('enable_shorten_long_number', 'no') === 'yes' ) {
			$price = self::number_shorten( $price, $decimals, $money_decimals );
		} else {
			$price = WP_CarDealer_Mixes::format_number( $price, $decimals, $money_decimals );
		}

		if ( ! empty( $currency_symbol ) ) {
			switch ($currency_position) {
				case 'before':
					$price = $currency_symbol . '<span class="price-text">'.$price.'</span>';
					break;
				case 'after':
					$price = '<span class="price-text">'.$price.'</span>' . $currency_symbol;
					break;
				case 'before_space':
					$price = $currency_symbol . ' <span class="price-text">'.$price.'</span>';
					break;
				case 'after_space':
					$price = '<span class="price-text">'.$price.'</span> ' . $currency_symbol;
					break;
			}
		}

		return $price;
	}

	public static function convert_price_exchange( $price ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			$price = 0;
		}
		if ( wp_cardealer_get_option('enable_multi_currencies') === 'yes' ) {
			$current_currency = self::get_current_currency();
			$multi_currencies = self::get_currencies_settings();

			if ( !empty($multi_currencies) && !empty($multi_currencies[$current_currency]) ) {
				$currency_args = $multi_currencies[$current_currency];
			}

			if ( !empty($currency_args) ) {
				$rate_exchange_fee = !empty($currency_args['rate_exchange_fee']) ? $currency_args['rate_exchange_fee'] : 1;
				$price = $price*$rate_exchange_fee;
			}
		}

		return $price;
	}

	public static function convert_current_currency_to_default( $price ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			$price = 0;
		}
		if ( wp_cardealer_get_option('enable_multi_currencies') === 'yes' ) {
			$current_currency = self::get_current_currency();
			$multi_currencies = self::get_currencies_settings();

			if ( !empty($multi_currencies) && !empty($multi_currencies[$current_currency]) ) {
				$currency_args = $multi_currencies[$current_currency];
			}

			if ( !empty($currency_args) ) {
				$rate_exchange_fee = !empty($currency_args['rate_exchange_fee']) ? $currency_args['rate_exchange_fee'] : 1;

				$price = $price*(1/$rate_exchange_fee);
			}
		}

		return $price;
	}

	public static function get_current_currency() {
		if ( wp_cardealer_get_option('enable_multi_currencies') === 'yes' ) {
			$current_currency = !empty($_COOKIE['wp_cardealer_currency']) ? $_COOKIE['wp_cardealer_currency'] : wp_cardealer_get_option('currency', 'USD');
		} else {
			$current_currency = wp_cardealer_get_option('currency', 'USD');
		}
		return $current_currency;
	}
	/**
	 * Get full list of currency codes.
	 *
	 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
	 *
	 * @return array
	 */
	public static function get_currencies() {
		$currencies = array_unique(
			apply_filters(
				'wp-cardealer-currencies',
				array(
					'AED' => __( 'United Arab Emirates dirham', 'wp-cardealer' ),
					'AFN' => __( 'Afghan afghani', 'wp-cardealer' ),
					'ALL' => __( 'Albanian lek', 'wp-cardealer' ),
					'AMD' => __( 'Armenian dram', 'wp-cardealer' ),
					'ANG' => __( 'Netherlands Antillean guilder', 'wp-cardealer' ),
					'AOA' => __( 'Angolan kwanza', 'wp-cardealer' ),
					'ARS' => __( 'Argentine peso', 'wp-cardealer' ),
					'AUD' => __( 'Australian dollar', 'wp-cardealer' ),
					'AWG' => __( 'Aruban florin', 'wp-cardealer' ),
					'AZN' => __( 'Azerbaijani manat', 'wp-cardealer' ),
					'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'wp-cardealer' ),
					'BBD' => __( 'Barbadian dollar', 'wp-cardealer' ),
					'BDT' => __( 'Bangladeshi taka', 'wp-cardealer' ),
					'BGN' => __( 'Bulgarian lev', 'wp-cardealer' ),
					'BHD' => __( 'Bahraini dinar', 'wp-cardealer' ),
					'BIF' => __( 'Burundian franc', 'wp-cardealer' ),
					'BMD' => __( 'Bermudian dollar', 'wp-cardealer' ),
					'BND' => __( 'Brunei dollar', 'wp-cardealer' ),
					'BOB' => __( 'Bolivian boliviano', 'wp-cardealer' ),
					'BRL' => __( 'Brazilian real', 'wp-cardealer' ),
					'BSD' => __( 'Bahamian dollar', 'wp-cardealer' ),
					'BTC' => __( 'Bitcoin', 'wp-cardealer' ),
					'BTN' => __( 'Bhutanese ngultrum', 'wp-cardealer' ),
					'BWP' => __( 'Botswana pula', 'wp-cardealer' ),
					'BYR' => __( 'Belarusian ruble (old)', 'wp-cardealer' ),
					'BYN' => __( 'Belarusian ruble', 'wp-cardealer' ),
					'BZD' => __( 'Belize dollar', 'wp-cardealer' ),
					'CAD' => __( 'Canadian dollar', 'wp-cardealer' ),
					'CDF' => __( 'Congolese franc', 'wp-cardealer' ),
					'CHF' => __( 'Swiss franc', 'wp-cardealer' ),
					'CLP' => __( 'Chilean peso', 'wp-cardealer' ),
					'CNY' => __( 'Chinese yuan', 'wp-cardealer' ),
					'COP' => __( 'Colombian peso', 'wp-cardealer' ),
					'CRC' => __( 'Costa Rican col&oacute;n', 'wp-cardealer' ),
					'CUC' => __( 'Cuban convertible peso', 'wp-cardealer' ),
					'CUP' => __( 'Cuban peso', 'wp-cardealer' ),
					'CVE' => __( 'Cape Verdean escudo', 'wp-cardealer' ),
					'CZK' => __( 'Czech koruna', 'wp-cardealer' ),
					'DJF' => __( 'Djiboutian franc', 'wp-cardealer' ),
					'DKK' => __( 'Danish krone', 'wp-cardealer' ),
					'DOP' => __( 'Dominican peso', 'wp-cardealer' ),
					'DZD' => __( 'Algerian dinar', 'wp-cardealer' ),
					'EGP' => __( 'Egyptian pound', 'wp-cardealer' ),
					'ERN' => __( 'Eritrean nakfa', 'wp-cardealer' ),
					'ETB' => __( 'Ethiopian birr', 'wp-cardealer' ),
					'EUR' => __( 'Euro', 'wp-cardealer' ),
					'FJD' => __( 'Fijian dollar', 'wp-cardealer' ),
					'FKP' => __( 'Falkland Islands pound', 'wp-cardealer' ),
					'GBP' => __( 'Pound sterling', 'wp-cardealer' ),
					'GEL' => __( 'Georgian lari', 'wp-cardealer' ),
					'GGP' => __( 'Guernsey pound', 'wp-cardealer' ),
					'GHS' => __( 'Ghana cedi', 'wp-cardealer' ),
					'GIP' => __( 'Gibraltar pound', 'wp-cardealer' ),
					'GMD' => __( 'Gambian dalasi', 'wp-cardealer' ),
					'GNF' => __( 'Guinean franc', 'wp-cardealer' ),
					'GTQ' => __( 'Guatemalan quetzal', 'wp-cardealer' ),
					'GYD' => __( 'Guyanese dollar', 'wp-cardealer' ),
					'HKD' => __( 'Hong Kong dollar', 'wp-cardealer' ),
					'HNL' => __( 'Honduran lempira', 'wp-cardealer' ),
					'HRK' => __( 'Croatian kuna', 'wp-cardealer' ),
					'HTG' => __( 'Haitian gourde', 'wp-cardealer' ),
					'HUF' => __( 'Hungarian forint', 'wp-cardealer' ),
					'IDR' => __( 'Indonesian rupiah', 'wp-cardealer' ),
					'ILS' => __( 'Israeli new shekel', 'wp-cardealer' ),
					'IMP' => __( 'Manx pound', 'wp-cardealer' ),
					'INR' => __( 'Indian rupee', 'wp-cardealer' ),
					'IQD' => __( 'Iraqi dinar', 'wp-cardealer' ),
					'IRR' => __( 'Iranian rial', 'wp-cardealer' ),
					'IRT' => __( 'Iranian toman', 'wp-cardealer' ),
					'ISK' => __( 'Icelandic kr&oacute;na', 'wp-cardealer' ),
					'JEP' => __( 'Jersey pound', 'wp-cardealer' ),
					'JMD' => __( 'Jamaican dollar', 'wp-cardealer' ),
					'JOD' => __( 'Jordanian dinar', 'wp-cardealer' ),
					'JPY' => __( 'Japanese yen', 'wp-cardealer' ),
					'KES' => __( 'Kenyan shilling', 'wp-cardealer' ),
					'KGS' => __( 'Kyrgyzstani som', 'wp-cardealer' ),
					'KHR' => __( 'Cambodian riel', 'wp-cardealer' ),
					'KMF' => __( 'Comorian franc', 'wp-cardealer' ),
					'KPW' => __( 'North Korean won', 'wp-cardealer' ),
					'KRW' => __( 'South Korean won', 'wp-cardealer' ),
					'KWD' => __( 'Kuwaiti dinar', 'wp-cardealer' ),
					'KYD' => __( 'Cayman Islands dollar', 'wp-cardealer' ),
					'KZT' => __( 'Kazakhstani tenge', 'wp-cardealer' ),
					'LAK' => __( 'Lao kip', 'wp-cardealer' ),
					'LBP' => __( 'Lebanese pound', 'wp-cardealer' ),
					'LKR' => __( 'Sri Lankan rupee', 'wp-cardealer' ),
					'LRD' => __( 'Liberian dollar', 'wp-cardealer' ),
					'LSL' => __( 'Lesotho loti', 'wp-cardealer' ),
					'LYD' => __( 'Libyan dinar', 'wp-cardealer' ),
					'MAD' => __( 'Moroccan dirham', 'wp-cardealer' ),
					'MDL' => __( 'Moldovan leu', 'wp-cardealer' ),
					'MGA' => __( 'Malagasy ariary', 'wp-cardealer' ),
					'MKD' => __( 'Macedonian denar', 'wp-cardealer' ),
					'MMK' => __( 'Burmese kyat', 'wp-cardealer' ),
					'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'wp-cardealer' ),
					'MOP' => __( 'Macanese pataca', 'wp-cardealer' ),
					'MRU' => __( 'Mauritanian ouguiya', 'wp-cardealer' ),
					'MUR' => __( 'Mauritian rupee', 'wp-cardealer' ),
					'MVR' => __( 'Maldivian rufiyaa', 'wp-cardealer' ),
					'MWK' => __( 'Malawian kwacha', 'wp-cardealer' ),
					'MXN' => __( 'Mexican peso', 'wp-cardealer' ),
					'MYR' => __( 'Malaysian ringgit', 'wp-cardealer' ),
					'MZN' => __( 'Mozambican metical', 'wp-cardealer' ),
					'NAD' => __( 'Namibian dollar', 'wp-cardealer' ),
					'NGN' => __( 'Nigerian naira', 'wp-cardealer' ),
					'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'wp-cardealer' ),
					'NOK' => __( 'Norwegian krone', 'wp-cardealer' ),
					'NPR' => __( 'Nepalese rupee', 'wp-cardealer' ),
					'NZD' => __( 'New Zealand dollar', 'wp-cardealer' ),
					'OMR' => __( 'Omani rial', 'wp-cardealer' ),
					'PAB' => __( 'Panamanian balboa', 'wp-cardealer' ),
					'PEN' => __( 'Sol', 'wp-cardealer' ),
					'PGK' => __( 'Papua New Guinean kina', 'wp-cardealer' ),
					'PHP' => __( 'Philippine peso', 'wp-cardealer' ),
					'PKR' => __( 'Pakistani rupee', 'wp-cardealer' ),
					'PLN' => __( 'Polish z&#x142;oty', 'wp-cardealer' ),
					'PRB' => __( 'Transnistrian ruble', 'wp-cardealer' ),
					'PYG' => __( 'Paraguayan guaran&iacute;', 'wp-cardealer' ),
					'QAR' => __( 'Qatari riyal', 'wp-cardealer' ),
					'RON' => __( 'Romanian leu', 'wp-cardealer' ),
					'RSD' => __( 'Serbian dinar', 'wp-cardealer' ),
					'RUB' => __( 'Russian ruble', 'wp-cardealer' ),
					'RWF' => __( 'Rwandan franc', 'wp-cardealer' ),
					'SAR' => __( 'Saudi riyal', 'wp-cardealer' ),
					'SBD' => __( 'Solomon Islands dollar', 'wp-cardealer' ),
					'SCR' => __( 'Seychellois rupee', 'wp-cardealer' ),
					'SDG' => __( 'Sudanese pound', 'wp-cardealer' ),
					'SEK' => __( 'Swedish krona', 'wp-cardealer' ),
					'SGD' => __( 'Singapore dollar', 'wp-cardealer' ),
					'SHP' => __( 'Saint Helena pound', 'wp-cardealer' ),
					'SLL' => __( 'Sierra Leonean leone', 'wp-cardealer' ),
					'SOS' => __( 'Somali shilling', 'wp-cardealer' ),
					'SRD' => __( 'Surinamese dollar', 'wp-cardealer' ),
					'SSP' => __( 'South Sudanese pound', 'wp-cardealer' ),
					'STN' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'wp-cardealer' ),
					'SYP' => __( 'Syrian pound', 'wp-cardealer' ),
					'SZL' => __( 'Swazi lilangeni', 'wp-cardealer' ),
					'THB' => __( 'Thai baht', 'wp-cardealer' ),
					'TJS' => __( 'Tajikistani somoni', 'wp-cardealer' ),
					'TMT' => __( 'Turkmenistan manat', 'wp-cardealer' ),
					'TND' => __( 'Tunisian dinar', 'wp-cardealer' ),
					'TOP' => __( 'Tongan pa&#x2bb;anga', 'wp-cardealer' ),
					'TRY' => __( 'Turkish lira', 'wp-cardealer' ),
					'TTD' => __( 'Trinidad and Tobago dollar', 'wp-cardealer' ),
					'TWD' => __( 'New Taiwan dollar', 'wp-cardealer' ),
					'TZS' => __( 'Tanzanian shilling', 'wp-cardealer' ),
					'UAH' => __( 'Ukrainian hryvnia', 'wp-cardealer' ),
					'UGX' => __( 'Ugandan shilling', 'wp-cardealer' ),
					'USD' => __( 'United States (US) dollar', 'wp-cardealer' ),
					'UYU' => __( 'Uruguayan peso', 'wp-cardealer' ),
					'UZS' => __( 'Uzbekistani som', 'wp-cardealer' ),
					'VEF' => __( 'Venezuelan bol&iacute;var', 'wp-cardealer' ),
					'VES' => __( 'Bol&iacute;var soberano', 'wp-cardealer' ),
					'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'wp-cardealer' ),
					'VUV' => __( 'Vanuatu vatu', 'wp-cardealer' ),
					'WST' => __( 'Samoan t&#x101;l&#x101;', 'wp-cardealer' ),
					'XAF' => __( 'Central African CFA franc', 'wp-cardealer' ),
					'XCD' => __( 'East Caribbean dollar', 'wp-cardealer' ),
					'XOF' => __( 'West African CFA franc', 'wp-cardealer' ),
					'XPF' => __( 'CFP franc', 'wp-cardealer' ),
					'YER' => __( 'Yemeni rial', 'wp-cardealer' ),
					'ZAR' => __( 'South African rand', 'wp-cardealer' ),
					'ZMW' => __( 'Zambian kwacha', 'wp-cardealer' ),
				)
			)
		);

		return $currencies;
	}

	/**
	 * Get all available Currency symbols.
	 *
	 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
	 *
	 * @since 4.1.0
	 * @return array
	 */
	public static function get_currency_symbols() {

		$symbols = apply_filters(
			'wp-cardealer-currency-symbols',
			array(
				'AED' => '&#x62f;.&#x625;',
				'AFN' => '&#x60b;',
				'ALL' => 'L',
				'AMD' => 'AMD',
				'ANG' => '&fnof;',
				'AOA' => 'Kz',
				'ARS' => '&#36;',
				'AUD' => '&#36;',
				'AWG' => 'Afl.',
				'AZN' => 'AZN',
				'BAM' => 'KM',
				'BBD' => '&#36;',
				'BDT' => '&#2547;&nbsp;',
				'BGN' => '&#1083;&#1074;.',
				'BHD' => '.&#x62f;.&#x628;',
				'BIF' => 'Fr',
				'BMD' => '&#36;',
				'BND' => '&#36;',
				'BOB' => 'Bs.',
				'BRL' => '&#82;&#36;',
				'BSD' => '&#36;',
				'BTC' => '&#8383;',
				'BTN' => 'Nu.',
				'BWP' => 'P',
				'BYR' => 'Br',
				'BYN' => 'Br',
				'BZD' => '&#36;',
				'CAD' => '&#36;',
				'CDF' => 'Fr',
				'CHF' => '&#67;&#72;&#70;',
				'CLP' => '&#36;',
				'CNY' => '&yen;',
				'COP' => '&#36;',
				'CRC' => '&#x20a1;',
				'CUC' => '&#36;',
				'CUP' => '&#36;',
				'CVE' => '&#36;',
				'CZK' => '&#75;&#269;',
				'DJF' => 'Fr',
				'DKK' => 'DKK',
				'DOP' => 'RD&#36;',
				'DZD' => '&#x62f;.&#x62c;',
				'EGP' => 'EGP',
				'ERN' => 'Nfk',
				'ETB' => 'Br',
				'EUR' => '&euro;',
				'FJD' => '&#36;',
				'FKP' => '&pound;',
				'GBP' => '&pound;',
				'GEL' => '&#x20be;',
				'GGP' => '&pound;',
				'GHS' => '&#x20b5;',
				'GIP' => '&pound;',
				'GMD' => 'D',
				'GNF' => 'Fr',
				'GTQ' => 'Q',
				'GYD' => '&#36;',
				'HKD' => '&#36;',
				'HNL' => 'L',
				'HRK' => 'kn',
				'HTG' => 'G',
				'HUF' => '&#70;&#116;',
				'IDR' => 'Rp',
				'ILS' => '&#8362;',
				'IMP' => '&pound;',
				'INR' => '&#8377;',
				'IQD' => '&#x639;.&#x62f;',
				'IRR' => '&#xfdfc;',
				'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
				'ISK' => 'kr.',
				'JEP' => '&pound;',
				'JMD' => '&#36;',
				'JOD' => '&#x62f;.&#x627;',
				'JPY' => '&yen;',
				'KES' => 'KSh',
				'KGS' => '&#x441;&#x43e;&#x43c;',
				'KHR' => '&#x17db;',
				'KMF' => 'Fr',
				'KPW' => '&#x20a9;',
				'KRW' => '&#8361;',
				'KWD' => '&#x62f;.&#x643;',
				'KYD' => '&#36;',
				'KZT' => '&#8376;',
				'LAK' => '&#8365;',
				'LBP' => '&#x644;.&#x644;',
				'LKR' => '&#xdbb;&#xdd4;',
				'LRD' => '&#36;',
				'LSL' => 'L',
				'LYD' => '&#x644;.&#x62f;',
				'MAD' => '&#x62f;.&#x645;.',
				'MDL' => 'MDL',
				'MGA' => 'Ar',
				'MKD' => '&#x434;&#x435;&#x43d;',
				'MMK' => 'Ks',
				'MNT' => '&#x20ae;',
				'MOP' => 'P',
				'MRU' => 'UM',
				'MUR' => '&#x20a8;',
				'MVR' => '.&#x783;',
				'MWK' => 'MK',
				'MXN' => '&#36;',
				'MYR' => '&#82;&#77;',
				'MZN' => 'MT',
				'NAD' => 'N&#36;',
				'NGN' => '&#8358;',
				'NIO' => 'C&#36;',
				'NOK' => '&#107;&#114;',
				'NPR' => '&#8360;',
				'NZD' => '&#36;',
				'OMR' => '&#x631;.&#x639;.',
				'PAB' => 'B/.',
				'PEN' => 'S/',
				'PGK' => 'K',
				'PHP' => '&#8369;',
				'PKR' => '&#8360;',
				'PLN' => '&#122;&#322;',
				'PRB' => '&#x440;.',
				'PYG' => '&#8370;',
				'QAR' => '&#x631;.&#x642;',
				'RMB' => '&yen;',
				'RON' => 'lei',
				'RSD' => '&#1088;&#1089;&#1076;',
				'RUB' => '&#8381;',
				'RWF' => 'Fr',
				'SAR' => '&#x631;.&#x633;',
				'SBD' => '&#36;',
				'SCR' => '&#x20a8;',
				'SDG' => '&#x62c;.&#x633;.',
				'SEK' => '&#107;&#114;',
				'SGD' => '&#36;',
				'SHP' => '&pound;',
				'SLL' => 'Le',
				'SOS' => 'Sh',
				'SRD' => '&#36;',
				'SSP' => '&pound;',
				'STN' => 'Db',
				'SYP' => '&#x644;.&#x633;',
				'SZL' => 'L',
				'THB' => '&#3647;',
				'TJS' => '&#x405;&#x41c;',
				'TMT' => 'm',
				'TND' => '&#x62f;.&#x62a;',
				'TOP' => 'T&#36;',
				'TRY' => '&#8378;',
				'TTD' => '&#36;',
				'TWD' => '&#78;&#84;&#36;',
				'TZS' => 'Sh',
				'UAH' => '&#8372;',
				'UGX' => 'UGX',
				'USD' => '&#36;',
				'UYU' => '&#36;',
				'UZS' => 'UZS',
				'VEF' => 'Bs F',
				'VES' => 'Bs.S',
				'VND' => '&#8363;',
				'VUV' => 'Vt',
				'WST' => 'T',
				'XAF' => 'CFA',
				'XCD' => '&#36;',
				'XOF' => 'CFA',
				'XPF' => 'Fr',
				'YER' => '&#xfdfc;',
				'ZAR' => '&#82;',
				'ZMW' => 'ZK',
			)
		);

		return $symbols;
	}

	/**
	 * Get Currency symbol.
	 *
	 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
	 *
	 * @param string $currency Currency. (default: '').
	 * @return string
	 */
	public static function currency_symbol( $currency = '' ) {
		
		$symbols = self::get_currency_symbols();

		$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

		return apply_filters( 'wp-cardealer-currency-symbol', $currency_symbol, $currency );
	}

	public static function get_currencies_settings() {
		$currency = wp_cardealer_get_option('currency', 'USD');
		$return = array(
			$currency => array(
				'currency' => $currency,
				'currency_position' => wp_cardealer_get_option('currency_position', 'before'),
				'money_decimals' => wp_cardealer_get_option('money_decimals', ''),
				'rate_exchange_fee' => 1,
				'custom_symbol' => wp_cardealer_get_option('custom_symbol', ''),
			)
		);
		$multi_currencies = wp_cardealer_get_option('multi_currencies');
		if ( !empty($multi_currencies) ) {
			foreach ($multi_currencies as $multi_currency) {
				if ( !empty($multi_currency['currency']) && $multi_currency['currency'] != $currency) {
					$return[$multi_currency['currency']] = $multi_currency;
				}
			}
		}

		return $return;
	}

	public static function number_shorten($number, $decimals = false, $money_decimals = 0 ) {

		if ( empty( $number ) || ! is_numeric( $number ) ) {
			return 0;
		}

        $divisors = self::get_shorten_divisors();

	    // Loop through each $divisor and find the
	    // lowest amount that matches
	    foreach ($divisors as $key => $value) {
	        if (abs($number) < ($value['divisor'] * 1000)) {
	            $number = $number / $value['divisor'];
	            return WP_CarDealer_Mixes::format_number($number, $decimals, $money_decimals) . $value['key'];
	            break;
	        }
	    }

	    return WP_CarDealer_Mixes::format_number($number, $decimals, $money_decimals);
	}

	public static function get_shorten_divisors() {

        $divisors = array(
            '' => [
	            	'divisor' => pow(1000, 0),
	            	'key' => ''
	            ], // 1000^0 == 1
        );

        $shorten = wp_cardealer_get_option('shorten_thousand');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('K', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['thousand'] = [
            	'divisor' => pow(1000, 1),
            	'key' => $key
            ];
        }

        $shorten = wp_cardealer_get_option('shorten_million');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('M', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['million'] = [
            	'divisor' => pow(1000, 2),
            	'key' => $key
            ];
        }

        $shorten = wp_cardealer_get_option('shorten_billion');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('B', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['billion'] = [
            	'divisor' => pow(1000, 3),
            	'key' => $key
            ];
        }

        $shorten = wp_cardealer_get_option('shorten_trillion');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('T', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['trillion'] = [
            	'divisor' => pow(1000, 4),
            	'key' => $key
            ];
        }

        $shorten = wp_cardealer_get_option('shorten_quadrillion');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('Qa', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['quadrillion'] = [
            	'divisor' => pow(1000, 5),
            	'key' => $key
            ];
        }

        $shorten = wp_cardealer_get_option('shorten_quintillion');
        if ( !empty($shorten['enable']) && $shorten['enable'] == 'on' ) {
        	$key = __('Qi', 'wp-cardealer');
        	if ( !empty($shorten['key']) ) {
        		$key = $shorten['key'];
        	}
        	$divisors['quintillion'] = [
            	'divisor' => pow(1000, 6),
            	'key' => $key
            ];
        }

	    return apply_filters('wp_cardealer_get_shorten_divisors', $divisors);
	}

	public static function process_currency() {
		if ( !empty($_GET['currency']) && wp_cardealer_get_option('enable_multi_currencies') === 'yes' ) {
			setcookie('wp_cardealer_currency', sanitize_text_field($_GET['currency']), time() + (86400 * 30), "/" );
			$_COOKIE['wp_cardealer_currency'] = sanitize_text_field($_GET['currency']);
		}		
	}
}

WP_CarDealer_Price::init();