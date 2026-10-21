// Country names for the address field. A contact's country is stored as its two letter code, and the names come
// from the browser (Intl.DisplayNames), so they follow the person's language without shipping a translated list.

export const COUNTRY_CODES = (
  'AF AX AL DZ AS AD AO AI AQ AG AR AM AW AU AT AZ BS BH BD BB BY BE BZ BJ BM BT BO BQ BA BW BV BR IO BN BG BF BI ' +
  'CV KH CM CA KY CF TD CL CN CX CC CO KM CG CD CK CR CI HR CU CW CY CZ DK DJ DM DO EC EG SV GQ ER EE SZ ET FK FO FJ ' +
  'FI FR GF PF TF GA GM GE DE GH GI GR GL GD GP GU GT GG GN GW GY HT HM VA HN HK HU IS IN ID IR IQ IE IM IL IT JM JP ' +
  'JE JO KZ KE KI KP KR KW KG LA LV LB LS LR LY LI LT LU MO MG MW MY MV ML MT MH MQ MR MU YT MX FM MD MC MN ME MS MA ' +
  'MZ MM NA NR NP NL NC NZ NI NE NG NU NF MK MP NO OM PK PW PS PA PG PY PE PH PN PL PT PR QA RE RO RU RW BL SH KN LC ' +
  'MF PM VC WS SM ST SA SN RS SC SL SG SX SK SI SB SO ZA GS SS ES LK SD SR SJ SE CH SY TW TJ TZ TH TL TG TK TO TT TN ' +
  'TR TM TC TV UG UA AE GB US UM UY UZ VU VE VN VG VI WF EH YE ZM ZW'
).split( ' ' )

/**
 * @param {string} code two letter country code
 * @param {string} locale
 * @returns {string} the country's name, or the code itself when the browser doesn't know it
 */
export function countryName ( code, locale = 'en' ) {
  code = String( code || '' ).trim().toUpperCase()

  if ( ! code ) {
    return ''
  }

  try {
    return new Intl.DisplayNames( [ locale ], { type: 'region' } ).of( code ) || code
  } catch ( e ) {
    return code
  }
}

/**
 * Options for a country select, sorted by name. A code the list doesn't have (stored by an import, say) is kept
 * as an option, so opening a contact and saving never silently drops their country.
 *
 * @param {string} locale
 * @param {string} [current] the contact's current country code
 * @returns {{value: string, label: string}[]}
 */
export function countryOptions ( locale = 'en', current = '' ) {
  const codes = COUNTRY_CODES.includes( String( current ).toUpperCase() ) || ! current ? COUNTRY_CODES : [ ...COUNTRY_CODES, current ]

  return codes
    .map( code => ( { value: code, label: countryName( code, locale ) } ) )
    .sort( ( a, b ) => a.label.localeCompare( b.label, locale ) )
}
