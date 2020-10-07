/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import {
	CONTACTS_STORE_NAME
} from '../../../../data';
import Listable from '../../../core-ui/list-table';

export const Contacts = ( props ) => {
	const {
		store,
		contacts,
		getContact,
		isRequesting,
		isUpdating
	} = useSelect( ( select ) => {
		const store = select( CONTACTS_STORE_NAME );
		return {
			store : store,
			contacts : store.getItems(),
			getContact : store.getItem,
			isRequesting : store.isItemsRequesting(),
			isUpdating: store.isItemsUpdating()
		}
	} );

	if ( isRequesting || isUpdating || ! contacts ) {
		return <Spinner />;
	}

	return (
			<Fragment>
				<h2>Contacts</h2>
				<Listable data={contacts} />
			</Fragment>
	);
}