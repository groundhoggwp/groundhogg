<?php
/**
 * Field Functions
 *
 * @package     wp-funnels
 * @subpackage  Includes
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * @param $type string Field Type
 * @param $id string Field ID
 * @param $name string Field Name
 * @param $value string Field Value
 * @param string $classes Custom Classes
 *
 * @return string
 */
function wpfn_contact_record_input_field( $type, $id, $name, $value, $classes='' )
{
	return "<input type='$type' id='$id' name='$name' class='regular-text $classes' value='$value' />";
}


/**
 * @param $id string Field ID
 * @param $name string Field Name
 * @param $value string Field Value
 * @param string $classes Custom Classes
 *
 * @return string
 */
function wpfn_admin_text_input_field( $id, $name, $value, $classes='' )
{
	return wpfn_contact_record_input_field( 'text', $id, $name, $value, $classes );
}

/**
 * @param $id string Field ID
 * @param $name string Field Name
 * @param $value string Field Value
 * @param string $classes Custom Classes
 *
 * @return string
 */
function wpfn_admin_email_input_field( $id, $name, $value, $classes='' )
{
	return wpfn_contact_record_input_field( 'email', $id, $name, $value, $classes );
}