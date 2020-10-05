/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import { TAGS_STORE_NAME } from '../../../../data';

const singleEntityExample = ( ID, callback ) => {
	let tag = callback( ID );
	console.log( tag );
}

export const Dashboard = ( props ) => {
	const [ stateTagValue, setTagValue ] = useState( '' );

	const { updateTags } = useDispatch( TAGS_STORE_NAME );

	const { tags, getTag, isRequesting, isUpdating } = useSelect( ( select ) => {
		const store = select( TAGS_STORE_NAME );
		return {
			tags : store.getTags(),
			getTag : store.getTag,
			isRequesting : store.isTagsRequesting(),
			isUpdating: store.isTagsUpdating()
		}
	} );

	if ( isRequesting || isUpdating ) {
		return <Spinner />;
	}

	return (
			<Fragment>
				<h2>Dashboard</h2>
				<ol>
					{ tags.map( tag => <li data-id={tag.ID} onClick={ () => { singleEntityExample( tag.ID, getTag ) } }>{ tag.tag_name }</li> ) }
				</ol>
				<TextField id="outlined-basic" label="Add Tags" variant="outlined" value={ stateTagValue } onChange={ ( event ) => setTagValue( event.target.value ) } />
				<p onClick={ () => { updateTags( { tags : stateTagValue } ) } }>Add</p>
				{ ( isUpdating ) && ( <Spinner /> ) }
			</Fragment>
	);
}