/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import Spinner from '../../../core-ui/spinner';
import Table from '@material-ui/core/Table'
/**
 * Internal dependencies
 */
import { TAGS_STORE_NAME } from '../../../../data';

const singleEntityExample = ( ID, callback ) => {
	let tag = callback( ID );
}

export const Tags = ( props ) => {
	const [ stateTagValue, setTagValue ] = useState( '' );

	const { updateTags } = useDispatch( TAGS_STORE_NAME );

	const { tags, getTag, isRequesting, isUpdating } = useSelect( ( select ) => {
		const store = select( TAGS_STORE_NAME );
		return {
			tags : store.getItems() ? store.getItems().map( tag => tag.data ) : [],
			getTag : store.getItem,
			isRequesting : store.isItemsRequesting(),
			isUpdating: store.isItemsUpdating()
		}
	} );
	
	if ( isRequesting || isUpdating || ! tags ) {
		return <Spinner />;
	}

	return (
			<Fragment>
				<Table


				/>
			</Fragment>
	);
}
