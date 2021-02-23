/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element'

import { useSelect, useDispatch } from '@wordpress/data'

/**
 * Internal dependencies
 */
 import BlockEditor from '../../../core-ui/block-editor';
 import {
	 EMAILS_STORE_NAME
 } from '../../../../data';

export const EmailEditor = () => {

	let { ID } = window.Groundhogg.email;

	const {
		email,
		isRequesting
	} = useSelect((select) => {
		const store = select(EMAILS_STORE_NAME)

		return {
			email: store.getItem( ID ),
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

	if (Object.keys(email).length === 0) {
		return null;
	}

  email.data.editorType = 'email';

	return (
		<Fragment>
			<BlockEditor document={email} />
		</Fragment>
	)
}
