/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	CONTACTS_STORE_NAME
} from '../../../../data';
import { ListTable } from '../../../core-ui/list-table/new'
import { ContactRowPrimaryItem } from './contact-row-primary-item'

export const Contacts = ( props ) => {
	return (
		<Fragment>
			<ListTable
				storeName={ CONTACTS_STORE_NAME }
				columns={[
					{
						ID: 'email',
						name: 'Email',
						orderBy: 'email',
						align: 'left',
						cell: ({data, ID}) => {
							return <ContactRowPrimaryItem data={data} />
						}
					},
					{
						ID: 'first_name',
						name: 'First Name',
						orderBy: 'first_name',
						align: 'left',
						cell: ({data}) => {
							return data.first_name || ''
						}
					},
					{
						ID: 'last_name',
						name: 'Last Name',
						orderBy: 'last_name',
						align: 'left',
						cell: ({data}) => {
							return data.last_name || ''
						}
					},
					{
						ID: 'username',
						name: 'Username',
						orderBy: 'username',
						align: 'left',
						cell: ({data}) => {
							return data.meta && data.meta.user_login || ''
						}
					},
					{
						ID: 'owner',
						name: 'Owner',
						orderBy: 'owner_id',
						align: 'left',
						cell: ({data}) => {
							return data.owner_id || '' // Need to resolve this to name/link
						}
					},
					{
						ID: 'date_created',
						name: 'Date Created',
						orderBy: 'date_created',
						align: 'left',
						cell: ({data}) => {
							return data.date_created || '' // Need to resolve this to proper format
						}
					},
				]}
			/>
		</Fragment>
	);
}
