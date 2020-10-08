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

export const Contacts = ( props ) => {
	return (
		<Fragment>
			<ListTable
				storeName={ CONTACTS_STORE_NAME }
				columns={[
					{
						ID: 'ID',
						name: 'ID',
						orderBy: 'ID',
						align: 'left',
						cell: ({data, ID}) => {
							return data.ID
						}
					},
					{
						ID: 'name',
						name: 'Name',
						orderBy: 'first_name',
						align: 'left',
						cell: ({data}) => {
							return data.first_name || ''
						}
					}
				]}
			/>
		</Fragment>
	);
}
