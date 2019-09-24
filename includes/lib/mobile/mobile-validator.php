<?php

namespace Groundhogg\Lib\Mobile;

require_once dirname( __FILE__ ) . '/iso3116.php';

class Mobile_Validator {
    private function get_iso3166_entry( $country_name ) {
        switch ( strlen( $country_name ) ) {
            case 0:
                $usa_entry = Iso3166::get_data()[0];
                return $usa_entry;

            case 2:
                $country_name_upper = strtoupper( $country_name );

                foreach ( Iso3166::get_data() as $iso3166_entry ){
                    if ( $country_name_upper == $iso3166_entry["alpha2"] ) {
                        return $iso3166_entry;
                    }
                }

            case 3:
                $country_name_upper = strtoupper( $country_name );

                foreach ( Iso3166::get_data() as $iso3166_entry ) {
                    if ( $country_name_upper == $iso3166_entry["alpha3"] ) {
                        return $iso3166_entry;
                    }
                }

            default:
                $country_name_upper = strtoupper( $country_name );

                foreach ( Iso3166::get_data() as $iso3166_entry ) {
                    if ( $country_name_upper == strtoupper( $iso3166_entry["country_name"] ) ) {
                        return $iso3166_entry;
                    }
                }
        }

        return array();
    }

    private function get_iso3166_by_phone( $phone_number ) {
        foreach ( Iso3166::get_data() as $iso3166_entry ) {
            foreach ( $iso3166_entry["phone_number_lengths"] as $number_length ) {
                $country_code = $iso3166_entry["country_code"];

                if ( 0 === strpos( $phone_number, $country_code ) &&
                    strlen( $phone_number ) == strlen( $country_code ) + $number_length ) {

                    // comment originated from node-phone:
                    // if the country doesn't have mobile prefixes (e.g. about 20 countries, like
                    // Argentina), then return the first match, as we can do no better
                    if ( empty( $iso3166_entry["mobile_begin_with"] ) ) {
                        return $iso3166_entry;
                    }

                    // comment originated from node-phone:
                    // it match.. but may have more than one result.
                    // e.g. USA and Canada. need to check mobile_begin_with
                    foreach ( $iso3166_entry["mobile_begin_with"] as $mobile_prefix ) {
                        if ( 0 === strpos( $phone_number, "$country_code$mobile_prefix" ) ) {
                            return $iso3166_entry;
                        }
                    }
                }
            }
        }

        return array();
    }

    private function validate_phone_iso3166( $phone_number, $iso3166_entry ) {
        if ( empty( $iso3166_entry ) ) {
            return false;
        }

        $country_code = $iso3166_entry["country_code"];
        $unprefix_number = preg_replace( "/^$country_code/", "" , $phone_number );

        foreach ( $iso3166_entry["phone_number_lengths"] as $number_length ) {
            if ( strlen( $unprefix_number ) == $number_length ) {
                if ( empty( $iso3166_entry["mobile_begin_with"] ) ) {
                    return true;
                }

                foreach ( $iso3166_entry["mobile_begin_with"] as $mobile_prefix ) {
                    if ( 0 === strpos( $unprefix_number, $mobile_prefix ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function normalize( $phone_number, $country_name = null ) {
        if ( empty( $phone_number ) || !is_string( $phone_number ) ) {
            return array();
        }
        if ( empty( $country_name ) || !is_string ( $country_name ) ) {
            $country_name = "";
        }
        $phone_number = trim( $phone_number );
        $country_name = trim( $country_name );

        $is_plus_prefixed = preg_match( "/^\+/", $phone_number );

        // comment originated from node-phone:
        // remove any non-digit character, included the +
        $phone_number = preg_replace( "/\D/", "", $phone_number );
        $iso3166_entry = $this->get_iso3166_entry( $country_name );

        if( empty( $iso3166_entry ) ) {
            return array();
        }

        if( $country_name ) {
            $alpha3 = $iso3166_entry["alpha3"];

            // comment originated from node-phone:
            // remove leading 0s for all countries except 'GAB', 'CIV', 'COG'
            if( !in_array( $alpha3, array( "GAB", "CIV", "COG" ) ) ) {
                $phone_number = preg_replace( "/^0+/", "", $phone_number );
            }

            // comment originated from node-phone:
            // if input 89234567890, RUS, remove the 8
            if ( "RUS" == $alpha3 && 11 == strlen( $phone_number ) && preg_match( "/^89/", $phone_number ) ) {
                $phone_number = preg_replace("/^8+/", "", $phone_number );
            }

            if ( $is_plus_prefixed ) {
                // comment originated from node-phone:
                // D is here.
            } else {
                // comment originated from node-phone:
                // C: have country, no plus sign --->
                //	case 1
                //		check phone_number_length == phone.length
                //		add back the country code
                //	case 2
                //		phone_number_length+phone_country_code.length == phone.length
                //		then go to D
                if( in_array( strlen( $phone_number ), $iso3166_entry["phone_number_lengths"] ) ) {
                    $phone_number = $iso3166_entry["country_code"] . $phone_number;
                }
            }
        } else {
            if ( $is_plus_prefixed ) {
                // comment originated from node-phone:
                // A: no country, have plus sign --> lookup country_code, length, and get the iso3166 directly
                // also validation is done here. so, the iso3166 is the matched result.
                $iso3166_entry = $this->get_iso3166_by_phone( $phone_number );
            } else {
                // comment originated from node-phone:
                // B: no country, no plus sign --> treat it as USA
                // 1. check length if == 11, or 10, if 10, add +1, then go go D
                // no plus sign, no country is given. then it must be USA
                if ( in_array( strlen( $phone_number ), $iso3166_entry["phone_number_lengths"] ) ) {
                    $phone_number = "1" . $phone_number;
                }
            }
        }

        if ( $this->validate_phone_iso3166( $phone_number, $iso3166_entry ) ) {
            return array( "+" . $phone_number, $iso3166_entry["alpha3"] );
        } else {
            return array();
        }
    }
}
