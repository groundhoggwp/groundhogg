/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element'
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks'

/**
 * External dependencies
 */
import Checkbox from '@material-ui/core/Checkbox'
import Select from '@material-ui/core/Select'
import TextareaAutosize from '@material-ui/core/TextareaAutosize'
import Typography from '@material-ui/core/Typography'
import TextField from '@material-ui/core/TextField'
import Button from '@material-ui/core/Button'
import { makeStyles } from "@material-ui/core/styles";

/**
 * Internal dependencies
 */
import TagPicker from 'components/core-ui/tag-picker'
import { SETTINGS_STORE_NAME } from 'data'

const useStyles = makeStyles((theme) => ({
    description: {
		fontSize: ".9em",
		color: '#666'
    },
}));

export const SettingsSection = ( { section } ) => {
	const classes = useStyles();

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

	console.log(section);
	return (
		<Fragment>
			{
				section.map( ( section ) => (
						<Fragment>
							<Typography variant="h4" component="h4">{ section.title }</Typography>
								{
									section.settings.map( ( setting ) => (
										<>
											<Typography variant="p" component="p">{ setting.label }</Typography>
											<Typography className={classes.description} variant="p" component="p">{ setting.desc }</Typography>
										</>
										)
									)
								}
						</Fragment>
					)
				)
			}
			<Button variant="contained" color="primary">{ __( 'Save Settings' ) }</Button>
		</Fragment>
	);
}