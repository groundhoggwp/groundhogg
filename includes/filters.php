<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-22
 * Time: 9:38 AM
 */


add_filter( 'groundhogg/admin/emails/sanitize_email_content', 'Groundhogg\safe_css_filter_rgb_to_hex', 10 );
add_filter( 'groundhogg/admin/emails/sanitize_email_content', 'Groundhogg\add_safe_style_attributes_to_email', 10 );
add_filter( 'groundhogg/admin/emails/sanitize_email_content', 'wp_kses_post', 11 );

/**
 * Add some filters....
 *
 * @param $content
 * @return mixed
 */
function add_safe_style_attributes_to_email( $content )
{
    add_filter( 'safe_style_css', 'Groundhogg\_safe_display_css' );

    return $content;
}

/**
 * Add display to list of allowed attributes
 *
 * @param $attributes
 * @return array
 */
function _safe_display_css( $attributes )
{
    $attributes[] = 'display';
    return $attributes;
}

/**
 * Convert all RGB to HEX in content.
 *
 * @param $content
 * @return mixed
 */
function safe_css_filter_rgb_to_hex( $content )
{
    $content = preg_replace_callback( '/rgb\((\d{1,3}), ?(\d{1,3}), ?(\d{1,3})\)/', 'Groundhogg\_safe_css_filter_rgb_to_hex_callback', $content );
    return $content;
}

/**
 * @param $matches
 * @return string
 */
function _safe_css_filter_rgb_to_hex_callback( $matches )
{
    return rgb2hex( $matches[1], $matches[2], $matches[3] );
}

/**
 * Convert RGB to HEX.
 *
 * @param $r
 * @param int $g
 * @param int $b
 * @return string
 */
function rgb2hex($r, $g=-1, $b=-1)
{
    if (is_array($r) && sizeof($r) == 3)
        list($r, $g, $b) = $r;

    $r = intval($r); $g = intval($g);
    $b = intval($b);

    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}


add_filter( 'tiny_mce_before_init', '\Groundhogg\tiny_mce_before_init' );

// Add listener for on lick event
function tiny_mce_before_init( $initArray )
{
    $initArray['setup'] = <<<JS
[function(ed) {
    ed.on( 'click', function(ed, e) {
        //your function goes here
        $(document).trigger( 'to_mce' );
        // console.log( {trigger:'to_mce'} );
    });

}][0]
JS;
    return $initArray;
}