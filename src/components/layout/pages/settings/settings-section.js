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

	const componentInputMap = ( props ) => {
		const { type } = props;

		const mapping = applyFilters( 'groundhogg.settings.componentInputMap', {
			'input' : { component : TextField },
			'number' : { component : TextField },
			'checkbox' : { component : Checkbox },
			'tag_picker' : { component : TagPicker },
			'link_picker' : { component : TagPicker }, // I imagine we'll have a LinkPicker component?
			'dropdown' : { component : Select },
			'dropdown_owners' : { component : Select }, // Investigate any difference here.
			'editor' : { component : TextareaAutosize }, // Need to build out TinyMCE Editor
			'textarea' : { component : TextareaAutosize },
		 } );

		 if ( mapping.hasOwnProperty( type ) ) {
			 const mappedComponent = mapping[ type ];
			 return ( <mappedComponent.component {...restProps} /> );
		 }

		 return null;
	};

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
											{ componentInputMap( setting ) }
											<Typography className={classes.description} variant="p" component="p" dangerouslySetInnerHTML={{ __html: setting.desc }} />
										</>
										)
									)
								}
						</Fragment>
					)
				)
			}
			<Button variant="contained" color="primary" onClick={ onClick() }>{ __( 'Save Settings' ) }</Button>
		</Fragment>
	);
}