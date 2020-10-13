import { Fragment } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks'
import Checkbox from '@material-ui/core/Checkbox'
import Select from '@material-ui/core/Select'
import TextareaAutosize from '@material-ui/core/TextareaAutosize'
import TextField from '@material-ui/core/TextField'
import TagPicker from '../../../core-ui/tag-picker'
import { SETTINGS_STORE_NAME } from '../../../../data'

export const SettingsSection = ( { section } ) => {

	const componentInputMap = applyFilters( 'groundhogg/settings_input_map', {
		'input' : <TextField />,
		'number' : <TextField />,
		'checkbox' : <Checkbox />,
		'tag_picker' : <TagPicker />,
		'link_picker' :  <TagPicker />, // I imagine we'll have a LinkPicker component?
		'dropdown' : <Select />,
		'dropdown_owners' : <Select />, // Investigate any difference here.
		'editor' : <TextareaAutosize />, // Need to build out TinyMCE Editor
		'textarea' :  <TextareaAutosize />
	 } )

	console.log( section );

	return (
		<Fragment>
			{ 'section' }
		</Fragment>
	);
}