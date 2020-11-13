/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element'
import { __ } from '@wordpress/i18n';
import { useRef, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks'

/**
 * External dependencies
 */
import Checkbox from '@material-ui/core/Checkbox'
import TextareaAutosize from '@material-ui/core/TextareaAutosize'
import Typography from '@material-ui/core/Typography'
import TextField from '@material-ui/core/TextField'
import Button from '@material-ui/core/Button'
import { makeStyles } from "@material-ui/core/styles";

/**
 * Internal dependencies
 */
import TagPicker from 'components/core-ui/tag-picker'
import SelectOwners from 'components/core-ui/select-owners'
import Select from 'components/core-ui/select'
import { useSettings } from 'data'
import { addNotification } from 'utils'


export const SettingsPanel = ( { section } ) => {
	const {
		settingsError,
		isRequesting,
		isDirty,
		updateSettings,
		persistSettings,
		settings
	} = useSettings( 'gh_admin', [ 'settings' ] );

	const hasSaved = useRef( false );

	const saveChanges = () => {
		persistSettings();
	};

	const handleInputChange = ( e ) => {
		const { checked, type, value, id } = e.target;
		const nextSettings = { ...settings };

		if ( type === 'checkbox' ) {
			if ( checked ) {
				nextSettings[ id ] = [ ...nextSettings[ id ], value ];
			} else {
				nextSettings[ id ] = nextSettings[ id ].filter(
					( v ) => v !== value
				);
			}
		} else {
			nextSettings[ id ] = value;
		}

		updateSettings( 'settings', nextSettings );
	};

	useEffect( () => {
		function warnIfUnsavedChanges( event ) {
			if ( isDirty ) {
				event.returnValue = __(
					'You have unsaved changes. If you proceed, they will be lost.',
					'groundhogg'
				);
				return event.returnValue;
			}
		}
		window.addEventListener( 'beforeunload', warnIfUnsavedChanges );
		return () =>
			window.removeEventListener( 'beforeunload', warnIfUnsavedChanges );
	}, [ isDirty ] );

	useEffect( () => {
		if ( isRequesting ) {
			hasSaved.current = true;
			return;
		}
		if ( ! isRequesting && hasSaved.current ) {
			if ( ! settingsError ) {
				addNotification( {
					message : __( 'Your settings have been successfully saved.', 'groundhogg' )
				} );
			} else {
				addNotification( {
					message : __( 'There was an error saving your settings. Please try again.', 'groundhogg' ),
					type: 'error'
				} );
			}
			hasSaved.current = false;
		}
	}, [ isRequesting, settingsError ] );

	const componentInputMap = ( props, classes ) => {
		const { type, id, defaultValue, label, desc } = props;
		const { ...restProps } = props;


		const mapping = applyFilters( 'groundhogg.settings.componentInputMap', {
			'input' : { component : TextField },
			'number' : { component : TextField },
			'checkbox' : { component : Checkbox },
			'tag_picker' : { component : TagPicker },
			'link_picker' : { component : TagPicker }, // I imagine we'll have a LinkPicker component?
			'dropdown' : { component : Select },
			'dropdown_owners' : { component : SelectOwners }, // Investigate any difference here.
			// 'editor' : { component : TextareaAutosize }, // Need to build out TinyMCE Editor
			// 'textarea' : { component : TextareaAutosize },
			'editor' : { component : TextField, properties: ['multiline'] }, // Need to build out TinyMCE Editor
			'textarea' : { component : TextField },
		 } );


		 const value = settings[ id ].hasOwnProperty( 'defaultValue' ) ? defaultValue : settings[ id ];

		 // This component is a little hacky and manual, I think the entire panel needs some refactoring
		 if ( mapping.hasOwnProperty( type ) ) {
			 const mappedComponent = mapping[ type ];

			 if(['editor', 'textarea'].includes(type)){
				 restProps.rows = 4;
				 restProps.multiline = true;
			 }

			 // Some refactoring is needed on the final pass of settings, but specific styling is needed.
			 if(type === 'checkbox') {
				 return ( <>
					 <mappedComponent.component onChange={handleInputChange} value={value} {...restProps} multiline className={classes.styleCheckbox} />
 					 <Typography variant="span" component="span">{ label }</Typography>
					 <Typography className={classes.descriptionCheckbox} variant="p" component="p" dangerouslySetInnerHTML={{ __html: desc }} />
					 </>
				 );
			 } else {
				 return ( <>
					 <mappedComponent.component onChange={handleInputChange} value={value} {...restProps}  className={classes.inputStyle}   />
					 <Typography className={classes.description} variant="p" component="p" dangerouslySetInnerHTML={{ __html: desc }} />
					 </>
				 );
			 }

		 }

		 return null;
	};

  const useStyles = makeStyles((theme) => ({
      title: {
        fontSize: '28px',
        fontWeight: 700,
        '&:last-of-type':{
          marginTop: '40px'
        },
				marginBottom: '20px'
      },
      inputSection:{
        width: '100%',
        marginBottom: '10px'
        // border: '1px solid #000'
      },
      inputStyle:{
        width: '100%'
      },
			inputStyleCheckbox:{
				width: '43px',
				marginRight: '20px'
			},
      description: {
    		fontSize: "12px",
    		color: '#666',
        marginTop: '5px',
      },
      descriptionCheckbox: {
    		fontSize: "12px",
    		color: '#666',
        marginTop: '5px',
				marginLeft: '42px'
      },
  }));
	const classes = useStyles();

	return (
		<Fragment>
			{
				section.map( ( section ) => (
						<Fragment>
							<Typography variant="h4" component="h4" className={classes.title}>{ section.title }</Typography>
								{
									section.settings.map( ( setting ) => (
										<div className={classes.inputSection}>
											{ componentInputMap( setting, classes ) }
										</div>
										)
									)
								}
						</Fragment>
					)
				)
			}
			<Button variant="contained" color="primary" onClick={ saveChanges }>{ __( 'Save Settings' ) }</Button>
		</Fragment>
	);
}
