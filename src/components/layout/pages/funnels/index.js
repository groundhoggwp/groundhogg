/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { castArray } from 'lodash';
import TextField from '@material-ui/core/TextField';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import { FUNNELS_STORE_NAME } from '../../../../data';

export const Funnels = ( props ) => {
	// const [ sts , setTagValue ] = useState( '' );

	// const { updateTags } = useDispatch( TAGS_STORE_NAME );

	const { funnels , isRequesting, isUpdating 	} = useSelect( ( select ) => {
		const store = select( FUNNELS_STORE_NAME );
		return {
			funnels : castArray( store.getFunnels() ),
			isRequesting : store.isFunnelsRequesting(),
			isUpdating: store.isFunnelsUpdating()
		}
	} );

	if ( isRequesting || isUpdating ) {
		return <Spinner />;
	}
	console.log(funnels);

	return (
			<Fragment>
				<h2>Funnels</h2>
				<ol>
					{ funnels.map( funnel => <li>{

						console.log(funnel)

					    // funnel.data.title

					}</li> ) }
				</ol>
				{/*<TextField id="outlined-basic" label="Add Tags" variant="outlined" value={ stateTagValue } onChange={ ( event ) => setTagValue( event.target.value ) } />*/}
				{/*<p onClick={ () => { updateTags( { tags : stateTagValue } ) } }>Add</p>*/}
				{/*{ ( isUpdating ) && ( <Spinner /> ) }*/}
			</Fragment>
	);
}