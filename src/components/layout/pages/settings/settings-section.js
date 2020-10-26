import { Fragment } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks'
import Checkbox from '@material-ui/core/Checkbox'
import Select from '@material-ui/core/Select'
import TextareaAutosize from '@material-ui/core/TextareaAutosize'
import Typography from '@material-ui/core/Typography'
import TextField from '@material-ui/core/TextField'
import TagPicker from '../../../core-ui/tag-picker'
import { SETTINGS_STORE_NAME } from '../../../../data'


export const SettingsSection = ( { section } ) => {
	const componentInputMap = applyFilters( 'groundhogg.settings.componentInputMap', {
		'input' : ( props ) => ( <TextField {...props} /> ),
		'number' : ( props ) => ( <TextField {...props} /> ),
		'checkbox' : ( props ) => ( <Checkbox {...props} /> ),
		'tag_picker' : ( props ) => ( <TagPicker {...props} /> ),
		'link_picker' : ( props ) => ( <TagPicker {...props} /> ), // I imagine we'll have a LinkPicker component?
		'dropdown' : ( props ) => ( <Select {...props} /> ),
		'dropdown_owners' : ( props ) => ( <Select {...props} /> ), // Investigate any difference here.
		'editor' : ( props ) => ( <TextareaAutosize {...props} /> ), // Need to build out TinyMCE Editor
		'textarea' :  ( props ) => ( <TextareaAutosize {...props} /> ),
	 } );

	return (
		<Fragment>
			{
				section.map( ( section ) => (
						<Fragment>
							<Typography variant="h3" component="h3">{ section.title }</Typography>
								{
									section.settings.map( ( setting ) => (
											<Typography variant="h4" component="h4">{ setting.label }</Typography>
										)
									)
								}
						</Fragment>
					)
				)
			}
		</Fragment>
	);
}