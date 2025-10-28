<?php

namespace Groundhogg;

use DateTimeZone;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Location {


	/**
	 * Get list of world countries
	 * pass $country_code to code to get the name of the country.
	 *
	 * @param string $country_code
	 *
	 * @param bool   $existing_data whether to only include countries which we have existing contacts for
	 *
	 * @return array|string
	 */
	public function get_countries_list( $country_code = '', $existing_data = false ) {
		$countries = array(
			'AF' => 'Afghanistan',
			'AX' => 'Aland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua And Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia And Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, Democratic Republic',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote D\'Ivoire',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Malvinas)',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island & Mcdonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic Of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle Of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KR' => 'Korea',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia, Federated States Of',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territory, Occupied',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts And Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin',
			'PM' => 'Saint Pierre And Miquelon',
			'VC' => 'Saint Vincent And Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome And Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia And Sandwich Isl.',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard And Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad And Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks And Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.S.',
			'WF' => 'Wallis And Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

		if ( $country_code ) {
			return key_exists( $country_code, $countries ) ? $countries[ $country_code ] : false;
		}

		if ( $existing_data ) {
			$meta          = get_db( 'contactmeta' )->query( [ 'meta_key' => 'country' ] );
			$country_codes = array_unique( wp_list_pluck( $meta, 'meta_value' ) );

			$existing = [];

			foreach ( $country_codes as $country_code ) {

				if ( ! $country_code ) {
					continue;
				}

				$existing[ $country_code ] = $this->get_countries_list( $country_code );
			}

			asort( $existing );

			return $existing;
		}

		return $countries;
	}

	/**
	 * Get list of american stats
	 *
	 * @return array
	 */
	public function get_american_states_list() {
		return array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AS' => 'American Samoa',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District Of Columbia',
			'FM' => 'Federated States Of Micronesia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'GU' => 'Guam Gu',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MH' => 'Marshall Islands',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'MP' => 'Northern Mariana Islands',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PW' => 'Palau',
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VI' => 'Virgin Islands',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
			'AE' => 'Armed Forces Africa \ Canada \ Europe \ Middle East',
			'AA' => 'Armed Forces America (Except Canada)',
			'AP' => 'Armed Forces Pacific'
		);
	}

	/**
	 * Get list of australian regions
	 *
	 * @return array
	 */
	public function get_australian_regions() {
		return array(
			"NSW" => "New South Wales",
			"VIC" => "Victoria",
			"QLD" => "Queensland",
			"TAS" => "Tasmania",
			"SA"  => "South Australia",
			"WA"  => "Western Australia",
			"NT"  => "Northern Territory",
			"ACT" => "Australian Capital Terrirory"
		);
	}

	/**
	 * Get a list of all canadian provinces
	 *
	 * @return array
	 */
	public function get_canadian_provinces_list() {
		return array(
			"BC" => "British Columbia",
			"ON" => "Ontario",
			"NF" => "Newfoundland",
			"NS" => "Nova Scotia",
			"PE" => "Prince Edward Island",
			"NB" => "New Brunswick",
			"QC" => "Quebec",
			"MB" => "Manitoba",
			"SK" => "Saskatchewan",
			"AB" => "Alberta",
			"NT" => "Northwest Territories",
			"NU" => "Nunavut",
			"YT" => "Yukon Territory"
		);
	}

	/**
	 * Get a list of all the time zones
	 *
	 * @throws \Exception
	 * @return array
	 * //     */
	public function get_time_zones() {

		static $regions = array(
			DateTimeZone::AFRICA,
			DateTimeZone::AMERICA,
			DateTimeZone::ANTARCTICA,
			DateTimeZone::ASIA,
			DateTimeZone::ATLANTIC,
			DateTimeZone::AUSTRALIA,
			DateTimeZone::EUROPE,
			DateTimeZone::INDIAN,
			DateTimeZone::PACIFIC,
		);

		static $timezones = [];

		if ( ! empty( $timezones ) ){
			return $timezones;
		}

		foreach ( $regions as $region ) {
			$timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
		}

		$timezone_offsets = array();
		foreach ( $timezones as $timezone ) {
			$tz = new DateTimeZone( $timezone );
			try {
				$timezone_offsets[ $timezone ] = $tz->getOffset( new DateTime );
			} catch ( \Exception $e ) {
				return [];
			}
		}

		// sort timezone by offset
		asort( $timezone_offsets );

		$timezone_list = array();
		foreach ( $timezone_offsets as $timezone => $offset ) {
			$offset_prefix    = $offset < 0 ? '-' : '+';
			$offset_formatted = gmdate( 'H:i', abs( $offset ) );

			$pretty_offset = "UTC{$offset_prefix}{$offset_formatted}";

			$timezone_list[ $timezone ] = "({$pretty_offset}) $timezone";
		}

		return $timezone_list;
	}

	/**
	 * Get the IP address of the current visitor
	 *
	 * @return string
	 */
	public function get_real_ip() {

		// Check for IP
		$places = [
			'REMOTE_ADDR',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
		];

		$found = '';

		foreach ( $places as $place ) {
			if ( ! empty( $_SERVER[ $place ] ) ) {
				$found = sanitize_text_field( wp_unslash( $_SERVER[ $place ] ) );
				break;
			}
		}

		$ips = array_map( 'trim', explode( ',', $found ) );

		return apply_filters( 'groundhogg/location/get_real_ip', array_pop( $ips ) );
	}


	/**
	 * Verify that the IP address is real.
	 *
	 * @param $IP string
	 *
	 * @return bool
	 */
	public function verify_ip( $IP = null ) {
		if ( ! $IP ) {
			$IP = $this->get_real_ip();
		}

		if ( ! filter_var( $IP, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		$ip_info = (object) $this->ip_info( $IP );

		return ! empty( $ip_info->time_zone ) && ! empty( $ip_info->country_code );
	}

	/**
	 * Get Geolocated information about an IP address
	 *
	 * @param null   $ip
	 * @param string $purpose
	 * @param bool   $deep_detect
	 *
	 * @return array|string|object
	 */
	public function ip_info( $ip = null, $purpose = "location", $deep_detect = true ) {

		$output = null;

		if ( $ip === null ) {
			$ip = $this->get_real_ip();
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) === false ) {
			return false;
		}

		$purpose = str_replace( [ "name", "\n", "\t", " ", "-", "_" ], '', strtolower( trim( $purpose ) ) );
		$support = [
			'raw',
			'cc',
			'country_code',
			'province',
			'country',
			'countrycode',
			'state',
			'region',
			'city',
			'location',
			'address',
			'time_zone',
			'timezone',
			'tz'
		];

		if ( in_array( $purpose, $support ) ) {

			$ip_data = wp_remote_get( "https://api.ipquery.io/" . $ip, [
				'headers' => [
					'Referer' => home_url()
				]
			] );

			if ( is_wp_error( $ip_data ) || ! $ip_data || wp_remote_retrieve_response_code( $ip_data ) !== 200 ) {
				return false;
			}

			$ip_data = wp_remote_retrieve_body( $ip_data );
			$ip_data = @json_decode( $ip_data );

			if ( ! $ip_data ) {
				return false;
			}

			if ( @strlen( trim( $ip_data->geoplugin_countryCode ) ) == 2 ) {
				switch ( $purpose ) {
					case 'raw':
						$output = $ip_data;
						break;
					case 'location':
						$output = array(
							"city"           => @$ip_data->location->city,
							"region"         => @$ip_data->location->state,
//							"region_code"    => @$ip_data->location->,
							"country"        => @$ip_data->location->country,
							"country_code"   => @$ip_data->location->country_code,
							"time_zone"      => @$ip_data->location->timezone,
						);
						break;
					case 'address':
						$address = array( $ip_data->location->country );
						if ( @strlen( $ip_data->location->state ) >= 1 ) {
							$address[] = $ip_data->location->state;
						}
						if ( @strlen( $ip_data->location->city ) >= 1 ) {
							$address[] = $ip_data->location->city;
						}
						$output = implode( ", ", array_reverse( $address ) );
						break;
					case 'city':
						$output = @$ip_data->location->city;
						break;
					case 'region':
					case 'province':
					case 'state':
						$output = @$ip_data->location->state;
						break;
					case 'country':
						$output = @$ip_data->location->country;
						break;
					case 'countrycode':
					case 'country_code':
					case 'cc':
						$output = @$ip_data->location->country_code;
						break;
					case 'time_zone':
					case 'timezone':
					case 'tz':
						$output = @$ip_data->location->timezone;
						break;
				}
			}
		}

		return $output;
	}

	/**
	 * Site country code.
	 *
	 * @return bool|mixed|string
	 */
	public function site_country_code() {
		if ( ! is_admin() ) {
			return false;
		}

		$has_saved = get_transient( 'site_country_code' );

		if ( ! empty( $has_saved ) ) {
			return $has_saved;
		}

		$c_code = $this->ip_info( $this->get_real_ip(), 'countrycode' );

		if ( ! $c_code ) {
			return 'US';
		}

		set_transient( 'site_country_code', $c_code );

		return $c_code;
	}

}
