/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element'
import {
	useParams
} from "react-router-dom";

import { useSelect, useDispatch } from '@wordpress/data'

/**
 * Internal dependencies
 */
 import BlockEditor from '../../../core-ui/block-editor';
 import {
	 EMAILS_STORE_NAME
 } from '../../../../data';

export const SingleView = ( props ) => {

	let { id } = useParams();

	const {
		email,
		isRequesting
	} = useSelect((select) => {
		const store = select(EMAILS_STORE_NAME)

		return {
			email: store.getItem( id ),
			isRequesting: store.isItemsRequesting(),
		}
	}, [] )

	const {
		fetchItems,
		deleteItems
	} = useDispatch( EMAILS_STORE_NAME );


	if ( isRequesting || ! email ) {
		return null;
	}

	return (
		<Fragment>
			<BlockEditor email={email} />
		</Fragment>
	)
}
