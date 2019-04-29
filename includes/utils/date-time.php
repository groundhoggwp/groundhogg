<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 12:04 PM
 */

class Date_Time
{

    public function get_wp_offset( $in_seconds = true )
    {
        $offset = intval( get_option( 'gmt_offset' ) );

        if ( $in_seconds ){
            $offset = $offset * HOUR_IN_SECONDS;
        }

        return $offset;
    }


    /**
     * Round time to the nearest hour.
     *
     * @param $time int
     * @return int
     */
    function round_to_hour( $time ){

        $minutes = $time % HOUR_IN_SECONDS; # pulls the remainder of the hour.

        $time -= $minutes; # just start off rounded down.

        if ($minutes >= ( HOUR_IN_SECONDS / 2 ) ) $time += HOUR_IN_SECONDS; # add one hour if 30 mins or higher.

        return $time;
    }

    /**
     * Round time to the nearest day.
     *
     * @param $time int
     * @return int
     */
    function round_to_day( $time ){

        $hours = $time % DAY_IN_SECONDS; # pulls the remainder of the hour.

        $time -= $hours; # just start off rounded down.

        if ($hours >= ( DAY_IN_SECONDS / 2 ) ) $time += DAY_IN_SECONDS; # add one day if 12 hours or higher.

        return $time;
    }

    /**
     * Convert a unix timestamp to UTC-0 time
     *
     * @param $time
     * @return int
     */
    public function convert_to_utc_0( $time )
    {
        if ( is_string( $time ) ){
            $time = strtotime( $time );
        }

        return $time - $this->get_wp_offset();

    }

    /**
     * Get a timezone offset.
     *
     * @param string $timeZone
     * @return int
     * @throws \Exception
     */
    public function get_timezone_offset( $timeZone = '' )
    {
        if ( ! $timeZone ){
            return 0;
        }

        try{
            $timeZone = new \DateTimeZone( $timeZone );
        } catch ( \Exception $e) {
            return 0;
        }

        try{
            $dateTime = new \DateTime( 'now', $timeZone );
        } catch ( \Exception $e ){
            return 0;
        }

        return $timeZone->getOffset( $dateTime );
    }

    /**
     * Convert a unix timestamp to local time
     *
     * @param $time
     * @return int
     */
    public function convert_to_local_time($time )
    {
        if ( is_string( $time ) ){
            $time = strtotime( $time );
        }

        return $time + $this->get_wp_offset();
    }


    /**
     * Converts the given time into the timeZone
     *
     * @param $time int UTC-0 Timestamp
     * @param string $timeZone the timezone to change to
     * @return int UTC-0 TImestamp that reflects the given timezone
     * @throws \Exception
     */
    public function convert_to_foreign_time( $time, $timeZone = '' )
    {

        if ( ! $timeZone ){
            return $time;
        }

        $time += $this->get_timezone_offset( $timeZone );

        return $time;
    }

}