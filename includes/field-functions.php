<?php
/**
 * Field Functions
 *
 * @package     groundhogg
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
function wpgh_contact_record_input_field( $type, $id, $name, $value, $classes='' )
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
function wpgh_admin_text_input_field( $id, $name, $value, $classes='' )
{
	return wpgh_contact_record_input_field( 'text', $id, $name, $value, $classes );
}

/**
 * @param $id string Field ID
 * @param $name string Field Name
 * @param $value string Field Value
 * @param string $classes Custom Classes
 *
 * @return string
 */
function wpgh_admin_email_input_field( $id, $name, $value, $classes='' )
{
	return wpgh_contact_record_input_field( 'email', $id, $name, $value, $classes );
}

function wpgh_font_select( $id, $name )
{
	?>
	<select name="<?php echo $name?>" id="<?php echo $id?>">
		<option value="Arial, sans-serif">Arial</option>
		<option value="Arial Black, Arial, sans-serif">Arial Black</option>
		<option value="Century Gothic, Times, serif">Century Gothic</option>
		<option value="Courier, monospace">Courier</option>
		<option value="Courier New, monospace">Courier New</option>
		<option value="Geneva, Tahoma, Verdana, sans-serif">Geneva</option>
		<option value="Georgia, Times, Times New Roman, serif">Georgia</option>
		<option value="Helvetica, Arial, sans-serif">Helvetica</option>
		<option value="Lucida, Geneva, Verdana, sans-serif">Lucida</option>
		<option value="Tahoma, Verdana, sans-serif">Tahoma</option>
		<option value="Times, Times New Roman, Baskerville, Georgia, serif">Times</option>
		<option value="Times New Roman, Times, Georgia, serif">Times New Roman</option>
		<option value="Verdana, Geneva, sans-serif">Verdana</option>
	</select>
	<?php
}

function wpgh_color_select( $id, $name, $default='' )
{
	?>
	<input type="text" id="<?php echo $id; ?>" name="<?php echo $name; ?>" class="wpgh-color" data-default-color="<?php echo $default;?>" />
	<?php
}