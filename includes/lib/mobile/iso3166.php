<?php

namespace Groundhogg\Lib\Mobile;

class Iso3166 {

	public static function get_data() {
		return self::$data;
	}

	private static $cc = [];

	/**
	 * Get all the country codes
	 *
	 * @return mixed
	 */
	public static function get_country_codes() {

		if ( empty( self::$cc ) ) {
			self::$cc = wp_list_pluck( self::$data, 'country_code' );
		}

		return self::$cc;
	}

	private static $data = array(
		0   =>
			array(
				'alpha2'               => 'US',
				'alpha3'               => 'USA',
				'country_code'         => '1',
				'country_name'         => 'United States',
				'mobile_begin_with'    =>
					array(
						0   => '201',
						1   => '202',
						2   => '203',
						3   => '205',
						4   => '206',
						5   => '207',
						6   => '208',
						7   => '209',
						8   => '210',
						9   => '212',
						10  => '213',
						11  => '214',
						12  => '215',
						13  => '216',
						14  => '217',
						15  => '218',
						16  => '219',
						17  => '224',
						18  => '225',
						19  => '227',
						20  => '228',
						21  => '229',
						22  => '231',
						23  => '234',
						24  => '239',
						25  => '240',
						26  => '248',
						27  => '251',
						28  => '252',
						29  => '253',
						30  => '254',
						31  => '256',
						32  => '260',
						33  => '262',
						34  => '267',
						35  => '269',
						36  => '270',
						37  => '272',
						38  => '274',
						39  => '276',
						40  => '278',
						41  => '281',
						42  => '283',
						43  => '301',
						44  => '302',
						45  => '303',
						46  => '304',
						47  => '305',
						48  => '307',
						49  => '308',
						50  => '309',
						51  => '310',
						52  => '312',
						53  => '313',
						54  => '314',
						55  => '315',
						56  => '316',
						57  => '317',
						58  => '318',
						59  => '319',
						60  => '320',
						61  => '321',
						62  => '323',
						63  => '325',
						64  => '327',
						65  => '330',
						66  => '331',
						67  => '334',
						68  => '336',
						69  => '337',
						70  => '339',
						71  => '341',
						72  => '346',
						73  => '347',
						74  => '351',
						75  => '352',
						76  => '360',
						77  => '361',
						78  => '364',
						79  => '369',
						80  => '380',
						81  => '385',
						82  => '386',
						83  => '401',
						84  => '402',
						85  => '404',
						86  => '405',
						87  => '406',
						88  => '407',
						89  => '408',
						90  => '409',
						91  => '410',
						92  => '412',
						93  => '413',
						94  => '414',
						95  => '415',
						96  => '417',
						97  => '419',
						98  => '423',
						99  => '424',
						100 => '425',
						101 => '430',
						102 => '432',
						103 => '434',
						104 => '435',
						105 => '440',
						106 => '442',
						107 => '443',
						108 => '445',
						109 => '447',
						110 => '458',
						111 => '464',
						112 => '469',
						113 => '470',
						114 => '475',
						115 => '478',
						116 => '479',
						117 => '480',
						118 => '484',
						119 => '501',
						120 => '502',
						121 => '503',
						122 => '504',
						123 => '505',
						124 => '507',
						125 => '508',
						126 => '509',
						127 => '510',
						128 => '512',
						129 => '513',
						130 => '515',
						131 => '516',
						132 => '517',
						133 => '518',
						134 => '520',
						135 => '530',
						136 => '531',
						137 => '534',
						138 => '539',
						139 => '540',
						140 => '541',
						141 => '551',
						142 => '557',
						143 => '559',
						144 => '561',
						145 => '562',
						146 => '563',
						147 => '564',
						148 => '567',
						149 => '570',
						150 => '571',
						151 => '573',
						152 => '574',
						153 => '575',
						154 => '580',
						155 => '582',
						156 => '585',
						157 => '586',
						158 => '601',
						159 => '602',
						160 => '603',
						161 => '605',
						162 => '606',
						163 => '607',
						164 => '608',
						165 => '609',
						166 => '610',
						167 => '612',
						168 => '614',
						169 => '615',
						170 => '616',
						171 => '617',
						172 => '618',
						173 => '619',
						174 => '620',
						175 => '623',
						176 => '626',
						177 => '627',
						178 => '628',
						179 => '630',
						180 => '631',
						181 => '636',
						182 => '641',
						183 => '646',
						184 => '650',
						185 => '651',
						186 => '657',
						187 => '659',
						188 => '660',
						189 => '661',
						190 => '662',
						191 => '667',
						192 => '669',
						193 => '678',
						194 => '679',
						195 => '681',
						196 => '682',
						197 => '689',
						198 => '701',
						199 => '702',
						200 => '703',
						201 => '704',
						202 => '706',
						203 => '707',
						204 => '708',
						205 => '712',
						206 => '713',
						207 => '714',
						208 => '715',
						209 => '716',
						210 => '717',
						211 => '718',
						212 => '719',
						213 => '720',
						214 => '724',
						215 => '725',
						216 => '727',
						217 => '730',
						218 => '731',
						219 => '732',
						220 => '734',
						221 => '737',
						222 => '740',
						223 => '747',
						224 => '752',
						225 => '754',
						226 => '757',
						227 => '760',
						228 => '762',
						229 => '763',
						230 => '764',
						231 => '765',
						232 => '769',
						233 => '770',
						234 => '772',
						235 => '773',
						236 => '774',
						237 => '775',
						238 => '779',
						239 => '781',
						240 => '785',
						241 => '786',
						242 => '801',
						243 => '802',
						244 => '803',
						245 => '804',
						246 => '805',
						247 => '806',
						248 => '808',
						249 => '810',
						250 => '812',
						251 => '813',
						252 => '814',
						253 => '815',
						254 => '816',
						255 => '817',
						256 => '818',
						257 => '828',
						258 => '830',
						259 => '831',
						260 => '832',
						261 => '835',
						262 => '843',
						263 => '845',
						264 => '847',
						265 => '848',
						266 => '850',
						267 => '856',
						268 => '857',
						269 => '858',
						270 => '859',
						271 => '860',
						272 => '862',
						273 => '863',
						274 => '864',
						275 => '865',
						276 => '870',
						277 => '872',
						278 => '878',
						279 => '901',
						280 => '903',
						281 => '904',
						282 => '906',
						283 => '907',
						284 => '908',
						285 => '909',
						286 => '910',
						287 => '912',
						288 => '913',
						289 => '914',
						290 => '915',
						291 => '916',
						292 => '917',
						293 => '918',
						294 => '919',
						295 => '920',
						296 => '925',
						297 => '927',
						298 => '928',
						299 => '929',
						300 => '931',
						301 => '935',
						302 => '936',
						303 => '937',
						304 => '938',
						305 => '940',
						306 => '941',
						307 => '947',
						308 => '949',
						309 => '951',
						310 => '952',
						311 => '954',
						312 => '956',
						313 => '957',
						314 => '959',
						315 => '970',
						316 => '971',
						317 => '972',
						318 => '973',
						319 => '975',
						320 => '978',
						321 => '979',
						322 => '980',
						323 => '984',
						324 => '985',
						325 => '989',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		1   =>
			array(
				'alpha2'               => 'AW',
				'alpha3'               => 'ABW',
				'country_code'         => '297',
				'country_name'         => 'Aruba',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '7',
						3 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		2   =>
			array(
				'alpha2'               => 'AF',
				'alpha3'               => 'AFG',
				'country_code'         => '93',
				'country_name'         => 'Afghanistan',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		3   =>
			array(
				'alpha2'               => 'AO',
				'alpha3'               => 'AGO',
				'country_code'         => '244',
				'country_name'         => 'Angola',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		4   =>
			array(
				'alpha2'               => 'AI',
				'alpha3'               => 'AIA',
				'country_code'         => '1',
				'country_name'         => 'Anguilla',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		5   =>
			array(
				'alpha2'               => 'AX',
				'alpha3'               => 'ALA',
				'country_code'         => '358',
				'country_name'         => 'Åland Islands',
				'mobile_begin_with'    =>
					array(
						0 => '18',
					),
				'phone_number_lengths' =>
					array(
						0 => 6,
						1 => 7,
						2 => 8,
					),
			),
		6   =>
			array(
				'alpha2'               => 'AL',
				'alpha3'               => 'ALB',
				'country_code'         => '355',
				'country_name'         => 'Albania',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		7   =>
			array(
				'alpha2'               => 'AD',
				'alpha3'               => 'AND',
				'country_code'         => '376',
				'country_name'         => 'Andorra',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '4',
						2 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		8   =>
			array(
				'alpha2'               => 'AE',
				'alpha3'               => 'ARE',
				'country_code'         => '971',
				'country_name'         => 'United Arab Emirates',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		9   =>
			array(
				'alpha2'               => 'AR',
				'alpha3'               => 'ARG',
				'country_code'         => '54',
				'country_name'         => 'Argentina',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 6,
						1 => 7,
						2 => 8,
						3 => 9,
						4 => 10,
					),
			),
		10  =>
			array(
				'alpha2'               => 'AM',
				'alpha3'               => 'ARM',
				'country_code'         => '374',
				'country_name'         => 'Armenia',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		11  =>
			array(
				'alpha2'               => 'AS',
				'alpha3'               => 'ASM',
				'country_code'         => '1',
				'country_name'         => 'American Samoa',
				'mobile_begin_with'    =>
					array(
						0 => '733',
						1 => '258',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		12  =>
			array(
				'alpha2'               => 'AG',
				'alpha3'               => 'ATG',
				'country_code'         => '1',
				'country_name'         => 'Antigua and Barbuda',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		13  =>
			array(
				'alpha2'               => 'AU',
				'alpha3'               => 'AUS',
				'country_code'         => '61',
				'country_name'         => 'Australia',
				'mobile_begin_with'    =>
					array(
						0 => '4',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		14  =>
			array(
				'alpha2'               => 'AT',
				'alpha3'               => 'AUT',
				'country_code'         => '43',
				'country_name'         => 'Austria',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		15  =>
			array(
				'alpha2'               => 'AZ',
				'alpha3'               => 'AZE',
				'country_code'         => '994',
				'country_name'         => 'Azerbaijan',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '5',
						2 => '6',
						3 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		16  =>
			array(
				'alpha2'               => 'BI',
				'alpha3'               => 'BDI',
				'country_code'         => '257',
				'country_name'         => 'Burundi',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '29',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		17  =>
			array(
				'alpha2'               => 'BE',
				'alpha3'               => 'BEL',
				'country_code'         => '32',
				'country_name'         => 'Belgium',
				'mobile_begin_with'    =>
					array(
						0 => '4',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		18  =>
			array(
				'alpha2'               => 'BJ',
				'alpha3'               => 'BEN',
				'country_code'         => '229',
				'country_name'         => 'Benin',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		19  =>
			array(
				'alpha2'               => 'BF',
				'alpha3'               => 'BFA',
				'country_code'         => '226',
				'country_name'         => 'Burkina Faso',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		20  =>
			array(
				'alpha2'               => 'BD',
				'alpha3'               => 'BGD',
				'country_code'         => '880',
				'country_name'         => 'Bangladesh',
				'mobile_begin_with'    =>
					array(
						0 => '1',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
						2 => 10,
					),
			),
		21  =>
			array(
				'alpha2'               => 'BG',
				'alpha3'               => 'BGR',
				'country_code'         => '359',
				'country_name'         => 'Bulgaria',
				'mobile_begin_with'    =>
					array(
						0 => '87',
						1 => '88',
						2 => '89',
						3 => '98',
						4 => '99',
						5 => '43',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
					),
			),
		22  =>
			array(
				'alpha2'               => 'BH',
				'alpha3'               => 'BHR',
				'country_code'         => '973',
				'country_name'         => 'Bahrain',
				'mobile_begin_with'    =>
					array(
						0 => '3',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		23  =>
			array(
				'alpha2'               => 'BS',
				'alpha3'               => 'BHS',
				'country_code'         => '1',
				'country_name'         => 'Bahamas',
				'mobile_begin_with'    =>
					array(
						0 => '242',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		24  =>
			array(
				'alpha2'               => 'BA',
				'alpha3'               => 'BIH',
				'country_code'         => '387',
				'country_name'         => 'Bosnia and Herzegovina',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		25  =>
			array(
				'alpha2'               => 'BY',
				'alpha3'               => 'BLR',
				'country_code'         => '375',
				'country_name'         => 'Belarus',
				'mobile_begin_with'    =>
					array(
						0 => '25',
						1 => '29',
						2 => '33',
						3 => '44',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		26  =>
			array(
				'alpha2'               => 'BZ',
				'alpha3'               => 'BLZ',
				'country_code'         => '501',
				'country_name'         => 'Belize',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		27  =>
			array(
				'alpha2'               => 'BM',
				'alpha3'               => 'BMU',
				'country_code'         => '1',
				'country_name'         => 'Bermuda',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '5',
						2 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		28  =>
			array(
				'alpha2'               => 'BO',
				'alpha3'               => 'BOL',
				'country_code'         => '591',
				'country_name'         => 'Bolivia',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		29  =>
			array(
				'alpha2'               => 'BR',
				'alpha3'               => 'BRA',
				'country_code'         => '55',
				'country_name'         => 'Brazil',
				'mobile_begin_with'    =>
					array(
						0  => '119',
						1  => '129',
						2  => '139',
						3  => '149',
						4  => '159',
						5  => '169',
						6  => '179',
						7  => '189',
						8  => '199',
						9  => '219',
						10 => '229',
						11 => '249',
						12 => '279',
						13 => '289',
						14 => '31',
						15 => '32',
						16 => '34',
						17 => '38',
						18 => '41',
						19 => '43',
						20 => '44',
						21 => '45',
						22 => '47',
						23 => '48',
						24 => '51',
						25 => '53',
						26 => '54',
						27 => '55',
						28 => '61',
						29 => '62',
						30 => '65',
						31 => '67',
						32 => '68',
						33 => '69',
						34 => '71',
						35 => '73',
						36 => '75',
						37 => '77',
						38 => '79',
						39 => '81',
						40 => '82',
						41 => '83',
						42 => '84',
						43 => '85',
						44 => '86',
						45 => '91',
						46 => '92',
						47 => '95',
						48 => '96',
						49 => '98',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
						1 => 11,
					),
			),
		30  =>
			array(
				'alpha2'               => 'BB',
				'alpha3'               => 'BRB',
				'country_code'         => '1',
				'country_name'         => 'Barbados',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		31  =>
			array(
				'alpha2'               => 'BN',
				'alpha3'               => 'BRN',
				'country_code'         => '673',
				'country_name'         => 'Brunei Darussalam',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		32  =>
			array(
				'alpha2'               => 'BT',
				'alpha3'               => 'BTN',
				'country_code'         => '975',
				'country_name'         => 'Bhutan',
				'mobile_begin_with'    =>
					array(
						0 => '17',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		33  =>
			array(
				'alpha2'               => 'BW',
				'alpha3'               => 'BWA',
				'country_code'         => '267',
				'country_name'         => 'Botswana',
				'mobile_begin_with'    =>
					array(
						0 => '71',
						1 => '72',
						2 => '73',
						3 => '74',
						4 => '75',
						5 => '76',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		34  =>
			array(
				'alpha2'               => 'CF',
				'alpha3'               => 'CAF',
				'country_code'         => '236',
				'country_name'         => 'Central African Republic',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		35  =>
			array(
				'alpha2'               => 'CA',
				'alpha3'               => 'CAN',
				'country_code'         => '1',
				'country_name'         => 'Canada',
				'mobile_begin_with'    =>
					array(
						0  => '204',
						1  => '226',
						2  => '236',
						3  => '249',
						4  => '250',
						5  => '289',
						6  => '306',
						7  => '343',
						8  => '365',
						9  => '403',
						10 => '416',
						11 => '418',
						12 => '431',
						13 => '437',
						14 => '438',
						15 => '450',
						16 => '506',
						17 => '514',
						18 => '519',
						19 => '579',
						20 => '581',
						21 => '587',
						22 => '600',
						23 => '604',
						24 => '613',
						25 => '639',
						26 => '647',
						27 => '705',
						28 => '709',
						29 => '778',
						30 => '780',
						31 => '807',
						32 => '819',
						33 => '867',
						34 => '873',
						35 => '902',
						36 => '905',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		36  =>
			array(
				'alpha2'               => 'CH',
				'alpha3'               => 'CHE',
				'country_code'         => '41',
				'country_name'         => 'Switzerland',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		37  =>
			array(
				'alpha2'               => 'CL',
				'alpha3'               => 'CHL',
				'country_code'         => '56',
				'country_name'         => 'Chile',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		38  =>
			array(
				'alpha2'               => 'CN',
				'alpha3'               => 'CHN',
				'country_code'         => '86',
				'country_name'         => 'China',
				'mobile_begin_with'    =>
					array(
						0 => '13',
						1 => '14',
						2 => '15',
						3 => '17',
						4 => '18',
					),
				'phone_number_lengths' =>
					array(
						0 => 11,
					),
			),
		39  =>
			array(
				'alpha2'               => 'CI',
				'alpha3'               => 'CIV',
				'country_code'         => '225',
				'country_name'         => 'Côte D\'Ivoire',
				'mobile_begin_with'    =>
					array(
						0 => '0',
						1 => '4',
						2 => '5',
						3 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		40  =>
			array(
				'alpha2'               => 'CM',
				'alpha3'               => 'CMR',
				'country_code'         => '237',
				'country_name'         => 'Cameroon',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		41  =>
			array(
				'alpha2'               => 'CD',
				'alpha3'               => 'COD',
				'country_code'         => '243',
				'country_name'         => 'Congo, The Democratic Republic Of The',
				'mobile_begin_with'    =>
					array(
						0 => '8',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		42  =>
			array(
				'alpha2'               => 'CG',
				'alpha3'               => 'COG',
				'country_code'         => '242',
				'country_name'         => 'Congo',
				'mobile_begin_with'    =>
					array(
						0 => '0',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		43  =>
			array(
				'alpha2'               => 'CK',
				'alpha3'               => 'COK',
				'country_code'         => '682',
				'country_name'         => 'Cook Islands',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		44  =>
			array(
				'alpha2'               => 'CO',
				'alpha3'               => 'COL',
				'country_code'         => '57',
				'country_name'         => 'Colombia',
				'mobile_begin_with'    =>
					array(
						0 => '3',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		45  =>
			array(
				'alpha2'               => 'KM',
				'alpha3'               => 'COM',
				'country_code'         => '269',
				'country_name'         => 'Comoros',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '76',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		46  =>
			array(
				'alpha2'               => 'CV',
				'alpha3'               => 'CPV',
				'country_code'         => '238',
				'country_name'         => 'Cape Verde',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		47  =>
			array(
				'alpha2'               => 'CR',
				'alpha3'               => 'CRI',
				'country_code'         => '506',
				'country_name'         => 'Costa Rica',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '7',
						3 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		48  =>
			array(
				'alpha2'               => 'CU',
				'alpha3'               => 'CUB',
				'country_code'         => '53',
				'country_name'         => 'Cuba',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		49  =>
			array(
				'alpha2'               => 'KY',
				'alpha3'               => 'CYM',
				'country_code'         => '1',
				'country_name'         => 'Cayman Islands',
				'mobile_begin_with'    =>
					array(
						0 => '345',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		50  =>
			array(
				'alpha2'               => 'CY',
				'alpha3'               => 'CYP',
				'country_code'         => '357',
				'country_name'         => 'Cyprus',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		51  =>
			array(
				'alpha2'               => 'CZ',
				'alpha3'               => 'CZE',
				'country_code'         => '420',
				'country_name'         => 'Czech Republic',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		52  =>
			array(
				'alpha2'               => 'DE',
				'alpha3'               => 'DEU',
				'country_code'         => '49',
				'country_name'         => 'Germany',
				'mobile_begin_with'    =>
					array(
						0 => '15',
						1 => '16',
						2 => '17',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
						1 => 11,
					),
			),
		53  =>
			array(
				'alpha2'               => 'DJ',
				'alpha3'               => 'DJI',
				'country_code'         => '253',
				'country_name'         => 'Djibouti',
				'mobile_begin_with'    =>
					array(
						0 => '77',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		54  =>
			array(
				'alpha2'               => 'DM',
				'alpha3'               => 'DMA',
				'country_code'         => '1',
				'country_name'         => 'Dominica',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		55  =>
			array(
				'alpha2'               => 'DK',
				'alpha3'               => 'DNK',
				'country_code'         => '45',
				'country_name'         => 'Denmark',
				'mobile_begin_with'    =>
					array(
						0  => '2',
						1  => '30',
						2  => '31',
						3  => '40',
						4  => '41',
						5  => '42',
						6  => '50',
						7  => '51',
						8  => '52',
						9  => '53',
						10 => '60',
						11 => '61',
						12 => '71',
						13 => '81',
						14 => '91',
						15 => '92',
						16 => '93',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		56  =>
			array(
				'alpha2'               => 'DO',
				'alpha3'               => 'DOM',
				'country_code'         => '1',
				'country_name'         => 'Dominican Republic',
				'mobile_begin_with'    =>
					array(
						0 => '809',
						1 => '829',
						2 => '849',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		57  =>
			array(
				'alpha2'               => 'DZ',
				'alpha3'               => 'DZA',
				'country_code'         => '213',
				'country_name'         => 'Algeria',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		58  =>
			array(
				'alpha2'               => 'EC',
				'alpha3'               => 'ECU',
				'country_code'         => '593',
				'country_name'         => 'Ecuador',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		59  =>
			array(
				'alpha2'               => 'EG',
				'alpha3'               => 'EGY',
				'country_code'         => '20',
				'country_name'         => 'Egypt',
				'mobile_begin_with'    =>
					array(
						0 => '1',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		60  =>
			array(
				'alpha2'               => 'ER',
				'alpha3'               => 'ERI',
				'country_code'         => '291',
				'country_name'         => 'Eritrea',
				'mobile_begin_with'    =>
					array(
						0 => '1',
						1 => '7',
						2 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		61  =>
			array(
				'alpha2'               => 'ES',
				'alpha3'               => 'ESP',
				'country_code'         => '34',
				'country_name'         => 'Spain',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		62  =>
			array(
				'alpha2'               => 'EE',
				'alpha3'               => 'EST',
				'country_code'         => '372',
				'country_name'         => 'Estonia',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		63  =>
			array(
				'alpha2'               => 'ET',
				'alpha3'               => 'ETH',
				'country_code'         => '251',
				'country_name'         => 'Ethiopia',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		64  =>
			array(
				'alpha2'               => 'FI',
				'alpha3'               => 'FIN',
				'country_code'         => '358',
				'country_name'         => 'Finland',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		65  =>
			array(
				'alpha2'               => 'FJ',
				'alpha3'               => 'FJI',
				'country_code'         => '679',
				'country_name'         => 'Fiji',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		66  =>
			array(
				'alpha2'               => 'FK',
				'alpha3'               => 'FLK',
				'country_code'         => '500',
				'country_name'         => 'Falkland Islands (Malvinas)',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		67  =>
			array(
				'alpha2'               => 'FR',
				'alpha3'               => 'FRA',
				'country_code'         => '33',
				'country_name'         => 'France',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		68  =>
			array(
				'alpha2'               => 'FO',
				'alpha3'               => 'FRO',
				'country_code'         => '298',
				'country_name'         => 'Faroe Islands',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		69  =>
			array(
				'alpha2'               => 'FM',
				'alpha3'               => 'FSM',
				'country_code'         => '691',
				'country_name'         => 'Micronesia, Federated States Of',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		70  =>
			array(
				'alpha2'               => 'GA',
				'alpha3'               => 'GAB',
				'country_code'         => '241',
				'country_name'         => 'Gabon',
				'mobile_begin_with'    =>
					array(
						0 => '05',
						1 => '06',
						2 => '07',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		71  =>
			array(
				'alpha2'               => 'GB',
				'alpha3'               => 'GBR',
				'country_code'         => '44',
				'country_name'         => 'United Kingdom',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		72  =>
			array(
				'alpha2'               => 'GE',
				'alpha3'               => 'GEO',
				'country_code'         => '995',
				'country_name'         => 'Georgia',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		73  =>
			array(
				'alpha2'               => 'GH',
				'alpha3'               => 'GHA',
				'country_code'         => '233',
				'country_name'         => 'Ghana',
				'mobile_begin_with'    =>
					array(
						0 => '2',
						1 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		74  =>
			array(
				'alpha2'               => 'GI',
				'alpha3'               => 'GIB',
				'country_code'         => '350',
				'country_name'         => 'Gibraltar',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		75  =>
			array(
				'alpha2'               => 'GN',
				'alpha3'               => 'GIN',
				'country_code'         => '224',
				'country_name'         => 'Guinea',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		76  =>
			array(
				'alpha2'               => 'GP',
				'alpha3'               => 'GLP',
				'country_code'         => '590',
				'country_name'         => 'Guadeloupe',
				'mobile_begin_with'    =>
					array(
						0 => '690',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		77  =>
			array(
				'alpha2'               => 'GM',
				'alpha3'               => 'GMB',
				'country_code'         => '220',
				'country_name'         => 'Gambia',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		78  =>
			array(
				'alpha2'               => 'GW',
				'alpha3'               => 'GNB',
				'country_code'         => '245',
				'country_name'         => 'Guinea-Bissau',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		79  =>
			array(
				'alpha2'               => 'GQ',
				'alpha3'               => 'GNQ',
				'country_code'         => '240',
				'country_name'         => 'Equatorial Guinea',
				'mobile_begin_with'    =>
					array(
						0 => '222',
						1 => '551',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		80  =>
			array(
				'alpha2'               => 'GR',
				'alpha3'               => 'GRC',
				'country_code'         => '30',
				'country_name'         => 'Greece',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		81  =>
			array(
				'alpha2'               => 'GD',
				'alpha3'               => 'GRD',
				'country_code'         => '1',
				'country_name'         => 'Grenada',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		82  =>
			array(
				'alpha2'               => 'GL',
				'alpha3'               => 'GRL',
				'country_code'         => '299',
				'country_name'         => 'Greenland',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		83  =>
			array(
				'alpha2'               => 'GT',
				'alpha3'               => 'GTM',
				'country_code'         => '502',
				'country_name'         => 'Guatemala',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '4',
						2 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		84  =>
			array(
				'alpha2'               => 'GF',
				'alpha3'               => 'GUF',
				'country_code'         => '594',
				'country_name'         => 'French Guiana',
				'mobile_begin_with'    =>
					array(
						0 => '694',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		85  =>
			array(
				'alpha2'               => 'GU',
				'alpha3'               => 'GUM',
				'country_code'         => '1',
				'country_name'         => 'Guam',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		86  =>
			array(
				'alpha2'               => 'GY',
				'alpha3'               => 'GUY',
				'country_code'         => '592',
				'country_name'         => 'Guyana',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		87  =>
			array(
				'alpha2'               => 'HK',
				'alpha3'               => 'HKG',
				'country_code'         => '852',
				'country_name'         => 'Hong Kong',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		88  =>
			array(
				'alpha2'               => 'HN',
				'alpha3'               => 'HND',
				'country_code'         => '504',
				'country_name'         => 'Honduras',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '7',
						2 => '8',
						3 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		89  =>
			array(
				'alpha2'               => 'HR',
				'alpha3'               => 'HRV',
				'country_code'         => '385',
				'country_name'         => 'Croatia',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
					),
			),
		90  =>
			array(
				'alpha2'               => 'HT',
				'alpha3'               => 'HTI',
				'country_code'         => '509',
				'country_name'         => 'Haiti',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '4',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		91  =>
			array(
				'alpha2'               => 'HU',
				'alpha3'               => 'HUN',
				'country_code'         => '36',
				'country_name'         => 'Hungary',
				'mobile_begin_with'    =>
					array(
						0 => '20',
						1 => '30',
						2 => '31',
						3 => '70',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		92  =>
			array(
				'alpha2'               => 'ID',
				'alpha3'               => 'IDN',
				'country_code'         => '62',
				'country_name'         => 'Indonesia',
				'mobile_begin_with'    =>
					array(
						0 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
						1 => 10,
						2 => 11,
					),
			),
		93  =>
			array(
				'alpha2'               => 'IN',
				'alpha3'               => 'IND',
				'country_code'         => '91',
				'country_name'         => 'India',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '8',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		94  =>
			array(
				'alpha2'               => 'IE',
				'alpha3'               => 'IRL',
				'country_code'         => '353',
				'country_name'         => 'Ireland',
				'mobile_begin_with'    =>
					array(
						0 => '82',
						1 => '83',
						2 => '84',
						3 => '85',
						4 => '86',
						5 => '87',
						6 => '88',
						7 => '89',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		95  =>
			array(
				'alpha2'               => 'IR',
				'alpha3'               => 'IRN',
				'country_code'         => '98',
				'country_name'         => 'Iran, Islamic Republic Of',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		96  =>
			array(
				'alpha2'               => 'IQ',
				'alpha3'               => 'IRQ',
				'country_code'         => '964',
				'country_name'         => 'Iraq',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		97  =>
			array(
				'alpha2'               => 'IS',
				'alpha3'               => 'ISL',
				'country_code'         => '354',
				'country_name'         => 'Iceland',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
						2 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		98  =>
			array(
				'alpha2'               => 'IL',
				'alpha3'               => 'ISR',
				'country_code'         => '972',
				'country_name'         => 'Israel',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		99  =>
			array(
				'alpha2'               => 'IT',
				'alpha3'               => 'ITA',
				'country_code'         => '39',
				'country_name'         => 'Italy',
				'mobile_begin_with'    =>
					array(
						0 => '3',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		100 =>
			array(
				'alpha2'               => 'JM',
				'alpha3'               => 'JAM',
				'country_code'         => '1',
				'country_name'         => 'Jamaica',
				'mobile_begin_with'    =>
					array(
						0 => '876',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		101 =>
			array(
				'alpha2'               => 'JO',
				'alpha3'               => 'JOR',
				'country_code'         => '962',
				'country_name'         => 'Jordan',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		102 =>
			array(
				'alpha2'               => 'JP',
				'alpha3'               => 'JPN',
				'country_code'         => '81',
				'country_name'         => 'Japan',
				'mobile_begin_with'    =>
					array(
						0 => '70',
						1 => '80',
						2 => '90',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		103 =>
			array(
				'alpha2'               => 'KZ',
				'alpha3'               => 'KAZ',
				'country_code'         => '7',
				'country_name'         => 'Kazakhstan',
				'mobile_begin_with'    =>
					array(
						0 => '70',
						1 => '77',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		104 =>
			array(
				'alpha2'               => 'KE',
				'alpha3'               => 'KEN',
				'country_code'         => '254',
				'country_name'         => 'Kenya',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		105 =>
			array(
				'alpha2'               => 'KG',
				'alpha3'               => 'KGZ',
				'country_code'         => '996',
				'country_name'         => 'Kyrgyzstan',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		106 =>
			array(
				'alpha2'               => 'KH',
				'alpha3'               => 'KHM',
				'country_code'         => '855',
				'country_name'         => 'Cambodia',
				'mobile_begin_with'    =>
					array(
						0 => '1',
						1 => '6',
						2 => '7',
						3 => '8',
						4 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
					),
			),
		107 =>
			array(
				'alpha2'               => 'KI',
				'alpha3'               => 'KIR',
				'country_code'         => '686',
				'country_name'         => 'Kiribati',
				'mobile_begin_with'    =>
					array(
						0 => '9',
						1 => '30',
					),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		108 =>
			array(
				'alpha2'               => 'KN',
				'alpha3'               => 'KNA',
				'country_code'         => '1',
				'country_name'         => 'Saint Kitts And Nevis',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		109 =>
			array(
				'alpha2'               => 'KR',
				'alpha3'               => 'KOR',
				'country_code'         => '82',
				'country_name'         => 'Korea, Republic of',
				'mobile_begin_with'    =>
					array(
						0 => '1',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
						1 => 10,
					),
			),
		110 =>
			array(
				'alpha2'               => 'KW',
				'alpha3'               => 'KWT',
				'country_code'         => '965',
				'country_name'         => 'Kuwait',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		111 =>
			array(
				'alpha2'               => 'LA',
				'alpha3'               => 'LAO',
				'country_code'         => '856',
				'country_name'         => 'Lao People\'s Democratic Republic',
				'mobile_begin_with'    =>
					array(
						0 => '20',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		112 =>
			array(
				'alpha2'               => 'LB',
				'alpha3'               => 'LBN',
				'country_code'         => '961',
				'country_name'         => 'Lebanon',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
						1 => 8,
					),
			),
		113 =>
			array(
				'alpha2'               => 'LR',
				'alpha3'               => 'LBR',
				'country_code'         => '231',
				'country_name'         => 'Liberia',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '5',
						2 => '6',
						3 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
						1 => 8,
					),
			),
		114 =>
			array(
				'alpha2'               => 'LY',
				'alpha3'               => 'LBY',
				'country_code'         => '218',
				'country_name'         => 'Libyan Arab Jamahiriya',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		115 =>
			array(
				'alpha2'               => 'LC',
				'alpha3'               => 'LCA',
				'country_code'         => '1',
				'country_name'         => 'Saint Lucia',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		116 =>
			array(
				'alpha2'               => 'LI',
				'alpha3'               => 'LIE',
				'country_code'         => '423',
				'country_name'         => 'Liechtenstein',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		117 =>
			array(
				'alpha2'               => 'LK',
				'alpha3'               => 'LKA',
				'country_code'         => '94',
				'country_name'         => 'Sri Lanka',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		118 =>
			array(
				'alpha2'               => 'LS',
				'alpha3'               => 'LSO',
				'country_code'         => '266',
				'country_name'         => 'Lesotho',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		119 =>
			array(
				'alpha2'               => 'LT',
				'alpha3'               => 'LTU',
				'country_code'         => '370',
				'country_name'         => 'Lithuania',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		120 =>
			array(
				'alpha2'               => 'LU',
				'alpha3'               => 'LUX',
				'country_code'         => '352',
				'country_name'         => 'Luxembourg',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		121 =>
			array(
				'alpha2'               => 'LV',
				'alpha3'               => 'LVA',
				'country_code'         => '371',
				'country_name'         => 'Latvia',
				'mobile_begin_with'    =>
					array(
						0 => '2',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		122 =>
			array(
				'alpha2'               => 'MO',
				'alpha3'               => 'MAC',
				'country_code'         => '853',
				'country_name'         => 'Macao',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		123 =>
			array(
				'alpha2'               => 'MA',
				'alpha3'               => 'MAR',
				'country_code'         => '212',
				'country_name'         => 'Morocco',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		124 =>
			array(
				'alpha2'               => 'MC',
				'alpha3'               => 'MCO',
				'country_code'         => '377',
				'country_name'         => 'Monaco',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
					),
			),
		125 =>
			array(
				'alpha2'               => 'MD',
				'alpha3'               => 'MDA',
				'country_code'         => '373',
				'country_name'         => 'Moldova, Republic of',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		126 =>
			array(
				'alpha2'               => 'MG',
				'alpha3'               => 'MDG',
				'country_code'         => '261',
				'country_name'         => 'Madagascar',
				'mobile_begin_with'    =>
					array(
						0 => '3',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		127 =>
			array(
				'alpha2'               => 'MV',
				'alpha3'               => 'MDV',
				'country_code'         => '960',
				'country_name'         => 'Maldives',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		128 =>
			array(
				'alpha2'               => 'MX',
				'alpha3'               => 'MEX',
				'country_code'         => '52',
				'country_name'         => 'Mexico',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 10,
						1 => 11,
					),
			),
		129 =>
			array(
				'alpha2'               => 'MH',
				'alpha3'               => 'MHL',
				'country_code'         => '692',
				'country_name'         => 'Marshall Islands',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		130 =>
			array(
				'alpha2'               => 'MK',
				'alpha3'               => 'MKD',
				'country_code'         => '389',
				'country_name'         => 'Macedonia, the Former Yugoslav Republic Of',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		131 =>
			array(
				'alpha2'               => 'ML',
				'alpha3'               => 'MLI',
				'country_code'         => '223',
				'country_name'         => 'Mali',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		132 =>
			array(
				'alpha2'               => 'MT',
				'alpha3'               => 'MLT',
				'country_code'         => '356',
				'country_name'         => 'Malta',
				'mobile_begin_with'    =>
					array(
						0 => '79',
						1 => '99',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		133 =>
			array(
				'alpha2'               => 'MM',
				'alpha3'               => 'MMR',
				'country_code'         => '95',
				'country_name'         => 'Myanmar',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		134 =>
			array(
				'alpha2'               => 'ME',
				'alpha3'               => 'MNE',
				'country_code'         => '382',
				'country_name'         => 'Montenegro',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		135 =>
			array(
				'alpha2'               => 'MN',
				'alpha3'               => 'MNG',
				'country_code'         => '976',
				'country_name'         => 'Mongolia',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '8',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		136 =>
			array(
				'alpha2'               => 'MP',
				'alpha3'               => 'MNP',
				'country_code'         => '1',
				'country_name'         => 'Northern Mariana Islands',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		137 =>
			array(
				'alpha2'               => 'MZ',
				'alpha3'               => 'MOZ',
				'country_code'         => '258',
				'country_name'         => 'Mozambique',
				'mobile_begin_with'    =>
					array(
						0 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		138 =>
			array(
				'alpha2'               => 'MR',
				'alpha3'               => 'MRT',
				'country_code'         => '222',
				'country_name'         => 'Mauritania',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		139 =>
			array(
				'alpha2'               => 'MS',
				'alpha3'               => 'MSR',
				'country_code'         => '1',
				'country_name'         => 'Montserrat',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		140 =>
			array(
				'alpha2'               => 'MQ',
				'alpha3'               => 'MTQ',
				'country_code'         => '596',
				'country_name'         => 'Martinique',
				'mobile_begin_with'    =>
					array(
						0 => '696',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		141 =>
			array(
				'alpha2'               => 'MU',
				'alpha3'               => 'MUS',
				'country_code'         => '230',
				'country_name'         => 'Mauritius',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		142 =>
			array(
				'alpha2'               => 'MW',
				'alpha3'               => 'MWI',
				'country_code'         => '265',
				'country_name'         => 'Malawi',
				'mobile_begin_with'    =>
					array(
						0 => '77',
						1 => '88',
						2 => '99',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		143 =>
			array(
				'alpha2'               => 'MY',
				'alpha3'               => 'MYS',
				'country_code'         => '60',
				'country_name'         => 'Malaysia',
				'mobile_begin_with'    =>
					array(
						0 => '1',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
						1 => 10,
					),
			),
		144 =>
			array(
				'alpha2'               => 'YT',
				'alpha3'               => 'MYT',
				'country_code'         => '269',
				'country_name'         => 'Mayotte',
				'mobile_begin_with'    =>
					array(
						0 => '639',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		145 =>
			array(
				'alpha2'               => 'NA',
				'alpha3'               => 'NAM',
				'country_code'         => '264',
				'country_name'         => 'Namibia',
				'mobile_begin_with'    =>
					array(
						0 => '60',
						1 => '81',
						2 => '82',
						3 => '85',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		146 =>
			array(
				'alpha2'               => 'NC',
				'alpha3'               => 'NCL',
				'country_code'         => '687',
				'country_name'         => 'New Caledonia',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		147 =>
			array(
				'alpha2'               => 'NE',
				'alpha3'               => 'NER',
				'country_code'         => '227',
				'country_name'         => 'Niger',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		148 =>
			array(
				'alpha2'               => 'NF',
				'alpha3'               => 'NFK',
				'country_code'         => '672',
				'country_name'         => 'Norfolk Island',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		149 =>
			array(
				'alpha2'               => 'NG',
				'alpha3'               => 'NGA',
				'country_code'         => '234',
				'country_name'         => 'Nigeria',
				'mobile_begin_with'    =>
					array(
						0 => '70',
						1 => '80',
						2 => '81',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		150 =>
			array(
				'alpha2'               => 'NI',
				'alpha3'               => 'NIC',
				'country_code'         => '505',
				'country_name'         => 'Nicaragua',
				'mobile_begin_with'    =>
					array(
						0 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		151 =>
			array(
				'alpha2'               => 'NU',
				'alpha3'               => 'NIU',
				'country_code'         => '683',
				'country_name'         => 'Niue',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 4,
					),
			),
		152 =>
			array(
				'alpha2'               => 'NL',
				'alpha3'               => 'NLD',
				'country_code'         => '31',
				'country_name'         => 'Netherlands',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		153 =>
			array(
				'alpha2'               => 'NO',
				'alpha3'               => 'NOR',
				'country_code'         => '47',
				'country_name'         => 'Norway',
				'mobile_begin_with'    =>
					array(
						0 => '4',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		154 =>
			array(
				'alpha2'               => 'NP',
				'alpha3'               => 'NPL',
				'country_code'         => '977',
				'country_name'         => 'Nepal',
				'mobile_begin_with'    =>
					array(
						0 => '97',
						1 => '98',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		155 =>
			array(
				'alpha2'               => 'NR',
				'alpha3'               => 'NRU',
				'country_code'         => '674',
				'country_name'         => 'Nauru',
				'mobile_begin_with'    =>
					array(
						0 => '555',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		156 =>
			array(
				'alpha2'               => 'NZ',
				'alpha3'               => 'NZL',
				'country_code'         => '64',
				'country_name'         => 'New Zealand',
				'mobile_begin_with'    =>
					array(
						0 => '2',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
						2 => 10,
					),
			),
		157 =>
			array(
				'alpha2'               => 'OM',
				'alpha3'               => 'OMN',
				'country_code'         => '968',
				'country_name'         => 'Oman',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		158 =>
			array(
				'alpha2'               => 'PK',
				'alpha3'               => 'PAK',
				'country_code'         => '92',
				'country_name'         => 'Pakistan',
				'mobile_begin_with'    =>
					array(
						0 => '3',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		159 =>
			array(
				'alpha2'               => 'PA',
				'alpha3'               => 'PAN',
				'country_code'         => '507',
				'country_name'         => 'Panama',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		160 =>
			array(
				'alpha2'               => 'PE',
				'alpha3'               => 'PER',
				'country_code'         => '51',
				'country_name'         => 'Peru',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		161 =>
			array(
				'alpha2'               => 'PH',
				'alpha3'               => 'PHL',
				'country_code'         => '63',
				'country_name'         => 'Philippines',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		162 =>
			array(
				'alpha2'               => 'PW',
				'alpha3'               => 'PLW',
				'country_code'         => '680',
				'country_name'         => 'Palau',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		163 =>
			array(
				'alpha2'               => 'PG',
				'alpha3'               => 'PNG',
				'country_code'         => '675',
				'country_name'         => 'Papua New Guinea',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		164 =>
			array(
				'alpha2'               => 'PL',
				'alpha3'               => 'POL',
				'country_code'         => '48',
				'country_name'         => 'Poland',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '6',
						2 => '7',
						3 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		165 =>
			array(
				'alpha2'               => 'PR',
				'alpha3'               => 'PRI',
				'country_code'         => '1',
				'country_name'         => 'Puerto Rico',
				'mobile_begin_with'    =>
					array(
						0 => '787',
						1 => '939',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		166 =>
			array(
				'alpha2'               => 'PT',
				'alpha3'               => 'PRT',
				'country_code'         => '351',
				'country_name'         => 'Portugal',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		167 =>
			array(
				'alpha2'               => 'PY',
				'alpha3'               => 'PRY',
				'country_code'         => '595',
				'country_name'         => 'Paraguay',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		168 =>
			array(
				'alpha2'               => 'PS',
				'alpha3'               => 'PSE',
				'country_code'         => '970',
				'country_name'         => 'Palestinian Territory, Occupied',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		169 =>
			array(
				'alpha2'               => 'PF',
				'alpha3'               => 'PYF',
				'country_code'         => '689',
				'country_name'         => 'French Polynesia',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		170 =>
			array(
				'alpha2'               => 'QA',
				'alpha3'               => 'QAT',
				'country_code'         => '974',
				'country_name'         => 'Qatar',
				'mobile_begin_with'    =>
					array(
						0 => '33',
						1 => '55',
						2 => '66',
						3 => '77',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		171 =>
			array(
				'alpha2'               => 'RE',
				'alpha3'               => 'REU',
				'country_code'         => '262',
				'country_name'         => 'Réunion',
				'mobile_begin_with'    =>
					array(
						0 => '692',
						1 => '693',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		172 =>
			array(
				'alpha2'               => 'RO',
				'alpha3'               => 'ROU',
				'country_code'         => '40',
				'country_name'         => 'Romania',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		173 =>
			array(
				'alpha2'               => 'RU',
				'alpha3'               => 'RUS',
				'country_code'         => '7',
				'country_name'         => 'Russian Federation',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		174 =>
			array(
				'alpha2'               => 'RW',
				'alpha3'               => 'RWA',
				'country_code'         => '250',
				'country_name'         => 'Rwanda',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		175 =>
			array(
				'alpha2'               => 'SA',
				'alpha3'               => 'SAU',
				'country_code'         => '966',
				'country_name'         => 'Saudi Arabia',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		176 =>
			array(
				'alpha2'               => 'SD',
				'alpha3'               => 'SDN',
				'country_code'         => '249',
				'country_name'         => 'Sudan',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		177 =>
			array(
				'alpha2'               => 'SN',
				'alpha3'               => 'SEN',
				'country_code'         => '221',
				'country_name'         => 'Senegal',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		178 =>
			array(
				'alpha2'               => 'SG',
				'alpha3'               => 'SGP',
				'country_code'         => '65',
				'country_name'         => 'Singapore',
				'mobile_begin_with'    =>
					array(
						0 => '8',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		179 =>
			array(
				'alpha2'               => 'SH',
				'alpha3'               => 'SHN',
				'country_code'         => '290',
				'country_name'         => 'Saint Helena',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 4,
					),
			),
		180 =>
			array(
				'alpha2'               => 'SJ',
				'alpha3'               => 'SJM',
				'country_code'         => '47',
				'country_name'         => 'Svalbard And Jan Mayen',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		181 =>
			array(
				'alpha2'               => 'SB',
				'alpha3'               => 'SLB',
				'country_code'         => '677',
				'country_name'         => 'Solomon Islands',
				'mobile_begin_with'    =>
					array(
						0 => '7',
						1 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		182 =>
			array(
				'alpha2'               => 'SL',
				'alpha3'               => 'SLE',
				'country_code'         => '232',
				'country_name'         => 'Sierra Leone',
				'mobile_begin_with'    =>
					array(
						0  => '21',
						1  => '25',
						2  => '30',
						3  => '33',
						4  => '34',
						5  => '40',
						6  => '44',
						7  => '50',
						8  => '55',
						9  => '76',
						10 => '77',
						11 => '78',
						12 => '79',
						13 => '88',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		183 =>
			array(
				'alpha2'               => 'SV',
				'alpha3'               => 'SLV',
				'country_code'         => '503',
				'country_name'         => 'El Salvador',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		184 =>
			array(
				'alpha2'               => 'SM',
				'alpha3'               => 'SMR',
				'country_code'         => '378',
				'country_name'         => 'San Marino',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		185 =>
			array(
				'alpha2'               => 'SO',
				'alpha3'               => 'SOM',
				'country_code'         => '252',
				'country_name'         => 'Somalia',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		186 =>
			array(
				'alpha2'               => 'PM',
				'alpha3'               => 'SPM',
				'country_code'         => '508',
				'country_name'         => 'Saint Pierre And Miquelon',
				'mobile_begin_with'    =>
					array(
						0 => '55',
					),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		187 =>
			array(
				'alpha2'               => 'RS',
				'alpha3'               => 'SRB',
				'country_code'         => '381',
				'country_name'         => 'Serbia',
				'mobile_begin_with'    =>
					array(
						0 => '6',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
						1 => 9,
					),
			),
		188 =>
			array(
				'alpha2'               => 'ST',
				'alpha3'               => 'STP',
				'country_code'         => '239',
				'country_name'         => 'Sao Tome and Principe',
				'mobile_begin_with'    =>
					array(
						0 => '98',
						1 => '99',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		189 =>
			array(
				'alpha2'               => 'SR',
				'alpha3'               => 'SUR',
				'country_code'         => '597',
				'country_name'         => 'Suriname',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
						2 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		190 =>
			array(
				'alpha2'               => 'SK',
				'alpha3'               => 'SVK',
				'country_code'         => '421',
				'country_name'         => 'Slovakia',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		191 =>
			array(
				'alpha2'               => 'SI',
				'alpha3'               => 'SVN',
				'country_code'         => '386',
				'country_name'         => 'Slovenia',
				'mobile_begin_with'    =>
					array(
						0 => '3',
						1 => '4',
						2 => '5',
						3 => '6',
						4 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		192 =>
			array(
				'alpha2'               => 'SE',
				'alpha3'               => 'SWE',
				'country_code'         => '46',
				'country_name'         => 'Sweden',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		193 =>
			array(
				'alpha2'               => 'SC',
				'alpha3'               => 'SYC',
				'country_code'         => '248',
				'country_name'         => 'Seychelles',
				'mobile_begin_with'    =>
					array(
						0 => '2',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		194 =>
			array(
				'alpha2'               => 'SY',
				'alpha3'               => 'SYR',
				'country_code'         => '963',
				'country_name'         => 'Syrian Arab Republic',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		195 =>
			array(
				'alpha2'               => 'TC',
				'alpha3'               => 'TCA',
				'country_code'         => '1',
				'country_name'         => 'Turks and Caicos Islands',
				'mobile_begin_with'    =>
					array(
						0 => '2',
						1 => '3',
						2 => '4',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		196 =>
			array(
				'alpha2'               => 'TD',
				'alpha3'               => 'TCD',
				'country_code'         => '235',
				'country_name'         => 'Chad',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
						2 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		197 =>
			array(
				'alpha2'               => 'TG',
				'alpha3'               => 'TGO',
				'country_code'         => '228',
				'country_name'         => 'Togo',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		198 =>
			array(
				'alpha2'               => 'TH',
				'alpha3'               => 'THA',
				'country_code'         => '66',
				'country_name'         => 'Thailand',
				'mobile_begin_with'    =>
					array(
						0 => '8',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		199 =>
			array(
				'alpha2'               => 'TJ',
				'alpha3'               => 'TJK',
				'country_code'         => '992',
				'country_name'         => 'Tajikistan',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		200 =>
			array(
				'alpha2'               => 'TK',
				'alpha3'               => 'TKL',
				'country_code'         => '690',
				'country_name'         => 'Tokelau',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 4,
					),
			),
		201 =>
			array(
				'alpha2'               => 'TM',
				'alpha3'               => 'TKM',
				'country_code'         => '993',
				'country_name'         => 'Turkmenistan',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		202 =>
			array(
				'alpha2'               => 'TL',
				'alpha3'               => 'TLS',
				'country_code'         => '670',
				'country_name'         => 'Timor-Leste',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		203 =>
			array(
				'alpha2'               => 'TO',
				'alpha3'               => 'TON',
				'country_code'         => '676',
				'country_name'         => 'Tonga',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		204 =>
			array(
				'alpha2'               => 'TT',
				'alpha3'               => 'TTO',
				'country_code'         => '1',
				'country_name'         => 'Trinidad and Tobago',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		205 =>
			array(
				'alpha2'               => 'TN',
				'alpha3'               => 'TUN',
				'country_code'         => '216',
				'country_name'         => 'Tunisia',
				'mobile_begin_with'    =>
					array(
						0 => '2',
						1 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		206 =>
			array(
				'alpha2'               => 'TR',
				'alpha3'               => 'TUR',
				'country_code'         => '90',
				'country_name'         => 'Turkey',
				'mobile_begin_with'    =>
					array(
						0 => '5',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		207 =>
			array(
				'alpha2'               => 'TV',
				'alpha3'               => 'TUV',
				'country_code'         => '688',
				'country_name'         => 'Tuvalu',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 5,
					),
			),
		208 =>
			array(
				'alpha2'               => 'TW',
				'alpha3'               => 'TWN',
				'country_code'         => '886',
				'country_name'         => 'Taiwan, Province Of China',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		209 =>
			array(
				'alpha2'               => 'TZ',
				'alpha3'               => 'TZA',
				'country_code'         => '255',
				'country_name'         => 'Tanzania, United Republic of',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		210 =>
			array(
				'alpha2'               => 'UG',
				'alpha3'               => 'UGA',
				'country_code'         => '256',
				'country_name'         => 'Uganda',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		211 =>
			array(
				'alpha2'               => 'UA',
				'alpha3'               => 'UKR',
				'country_code'         => '380',
				'country_name'         => 'Ukraine',
				'mobile_begin_with'    =>
					array(
						0 => '39',
						1 => '50',
						2 => '63',
						3 => '66',
						4 => '67',
						5 => '68',
						6 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		212 =>
			array(
				'alpha2'               => 'UY',
				'alpha3'               => 'URY',
				'country_code'         => '598',
				'country_name'         => 'Uruguay',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 8,
					),
			),
		213 =>
			array(
				'alpha2'               => 'UZ',
				'alpha3'               => 'UZB',
				'country_code'         => '998',
				'country_name'         => 'Uzbekistan',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		214 =>
			array(
				'alpha2'               => 'VC',
				'alpha3'               => 'VCT',
				'country_code'         => '1',
				'country_name'         => 'Saint Vincent And The Grenedines',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		215 =>
			array(
				'alpha2'               => 'VE',
				'alpha3'               => 'VEN',
				'country_code'         => '58',
				'country_name'         => 'Venezuela, Bolivarian Republic of',
				'mobile_begin_with'    =>
					array(
						0 => '4',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		216 =>
			array(
				'alpha2'               => 'VG',
				'alpha3'               => 'VGB',
				'country_code'         => '1',
				'country_name'         => 'Virgin Islands, British',
				'mobile_begin_with'    =>
					array(
						0 => '284',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		217 =>
			array(
				'alpha2'               => 'VI',
				'alpha3'               => 'VIR',
				'country_code'         => '1',
				'country_name'         => 'Virgin Islands, U.S.',
				'mobile_begin_with'    =>
					array(
						0 => '340',
					),
				'phone_number_lengths' =>
					array(
						0 => 10,
					),
			),
		218 =>
			array(
				'alpha2'               => 'VN',
				'alpha3'               => 'VNM',
				'country_code'         => '84',
				'country_name'         => 'Viet Nam',
				'mobile_begin_with'    =>
					array(
						0 => '9',
						1 => '1',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
						1 => 10,
					),
			),
		219 =>
			array(
				'alpha2'               => 'VU',
				'alpha3'               => 'VUT',
				'country_code'         => '678',
				'country_name'         => 'Vanuatu',
				'mobile_begin_with'    =>
					array(
						0 => '5',
						1 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		220 =>
			array(
				'alpha2'               => 'WF',
				'alpha3'               => 'WLF',
				'country_code'         => '681',
				'country_name'         => 'Wallis and Futuna',
				'mobile_begin_with'    =>
					array(),
				'phone_number_lengths' =>
					array(
						0 => 6,
					),
			),
		221 =>
			array(
				'alpha2'               => 'WS',
				'alpha3'               => 'WSM',
				'country_code'         => '685',
				'country_name'         => 'Samoa',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 7,
					),
			),
		222 =>
			array(
				'alpha2'               => 'YE',
				'alpha3'               => 'YEM',
				'country_code'         => '967',
				'country_name'         => 'Yemen',
				'mobile_begin_with'    =>
					array(
						0 => '7',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		223 =>
			array(
				'alpha2'               => 'ZA',
				'alpha3'               => 'ZAF',
				'country_code'         => '27',
				'country_name'         => 'South Africa',
				'mobile_begin_with'    =>
					array(
						0 => '6',
						1 => '7',
						2 => '8',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		224 =>
			array(
				'alpha2'               => 'ZM',
				'alpha3'               => 'ZMB',
				'country_code'         => '260',
				'country_name'         => 'Zambia',
				'mobile_begin_with'    =>
					array(
						0 => '9',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
		225 =>
			array(
				'alpha2'               => 'ZW',
				'alpha3'               => 'ZWE',
				'country_code'         => '263',
				'country_name'         => 'Zimbabwe',
				'mobile_begin_with'    =>
					array(
						0 => '71',
						1 => '73',
						2 => '77',
					),
				'phone_number_lengths' =>
					array(
						0 => 9,
					),
			),
	);
}