/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import Spinner from '../../../core-ui/spinner';
import Listable from '../../../core-ui/list-table';

/**
 * Internal dependencies
 */
import { EMAILS_STORE_NAME } from '../../../../data';

export const Emails = ( props ) => {
	const { emails, getEmail, isRequesting } = useSelect( ( select ) => {
		const store = select( EMAILS_STORE_NAME );
		return {
			emails : store.getItems(),
			getEmail : store.getItem,
			isRequesting : store.isItemsRequesting()
		}
	} );

	if (isRequesting || ! emails ) {
		return <Spinner />;
	}

	return (
			<Fragment>
				<h2>Dashboard</h2>
				<Listable data={emails}/>
				<ol>
					{ emails.map( email => <li data-id={email.ID}>{ email.data.subject_line }</li> ) }
				</ol>
			</Fragment>
	);
}
