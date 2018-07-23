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

function wpfn_contact_record_text_input_field( $id, $name, $value, $classes='' )
{
	return "<input type='text' id='$id' name='$name' class='input $classes' value='$value' />";
}