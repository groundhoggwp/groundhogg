<?php
namespace Groundhogg\Form\Fields;

use function Groundhogg\get_array_var;
use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

/**
 *
 * Class File
 * @package Groundhogg\Form\Fields
 */
class Birthday extends Input
{

    public function get_default_args()
    {
        return [
            'label'         => _x( 'Birthday *', 'form_default', 'groundhogg' ),
            'class'         => 'birthday',
            'required'      => false,
        ];
    }

    public function get_name()
    {
        return 'birthday';
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'birthday';
    }

    /**
     * Return the value that will be the final value.
     *
     * @param $input string|array
     * @param $config array
     * @return string
     */
    public static function validate( $input, $config )
    {
        $input = array_map( 'absint', $input );

        $parts = [
            'year',
            'month',
            'day',
        ];

        $birthday = [];

        foreach ( $parts as $key ){
            $date = get_array_var( $input, $key );
            $birthday[] = $date;
        }

        if ( ! checkdate( $birthday[1], $birthday[2], $birthday[0] ) ){
            return new \WP_Error( 'invalid_date', 'Please provide a valid date!' );
        }

        return apply_filters( 'groundhogg/form/fields/birthday/validate' , array_map( 'absint', $input ) );
    }

    /**
     * Render
     *
     * @return string
     */
    public function render()
    {

        $years = array_reverse( range( date( 'Y' ) - 100, date( 'Y' ) ) );
        $years = array_combine( $years, $years );
        $days = range( 1, 31 );
        $days = array_combine( $days, $days );
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $timestamp = mktime(0, 0, 0, $i, 1, date( 'Y' ) );
            $months[ $i ] = date_i18n("F", $timestamp);
        }

        $html = html()->e( 'div', [ 'class'=> $this->get_classes(), 'id' => $this->get_id() ], [
            html()->e( 'label', [ 'class' => 'gh-input-label' ], $this->get_label() ),
            html()->e( 'div', [ 'class' => 'gh-form-row clearfix' ], [
                // Year
                html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], [
                    html()->dropdown( [
                        'name'              => sprintf( "%s[%s]", $this->get_name(), 'year' ),
                        'id'                => 'birthday_year',
                        'options'           => $years,
                        'multiple'          => false,
                        'option_none'       => false,
                        'required'          => $this->is_required(),
                        'class' => 'gh-input'
                    ] ),
                ] ),
                // Month
                html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ],  [
                    html()->dropdown( [
                        'name'              => sprintf( "%s[%s]", $this->get_name(), 'month' ),
                        'id'                => 'birthday_month',
                        'options'           => $months,
                        'multiple'          => false,
                        'option_none'       => false,
                        'required'          => $this->is_required(),
                        'class' => 'gh-input'
                    ] ),
                ] ),
                // Day
                html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ],  [
                    html()->dropdown( [
                        'name'              => sprintf( "%s[%s]", $this->get_name(), 'day' ),
                        'id'                => 'birthday_day',
                        'options'           => $days,
                        'multiple'          => false,
                        'option_none'       => false,
                        'required'          => $this->is_required(),
                        'class' => 'gh-input'
                    ] ),
                ] ),
            ] )
        ] );

        return $html;
    }
}