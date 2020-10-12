/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data'
import DeleteIcon from '@material-ui/icons/Delete'

import {
	useRouteMatch,
	Switch,
	Route
} from "react-router-dom";

/**
 * Internal dependencies
 */
import {
	CONTACTS_STORE_NAME
} from '../../../../data';
import { ListTable } from '../../../core-ui/list-table/new'
import { ContactRowPrimaryItem } from './contact-row-primary-item'
import { SingleView } from './single-view'

const contactTableColumns = [
	{
		ID: 'email',
		name: __( 'Email' ),
		orderBy: 'email',
		align: 'left',
		cell: ({data, ID}) => {
			return <ContactRowPrimaryItem data={data} />
		}
	},
	{
		ID: 'first_name',
		name: __( 'First Name' ),
		orderBy: 'first_name',
		align: 'left',
		cell: ({data}) => {
			return data.first_name || ''
		}
	},
	{
		ID: 'last_name',
		name: __( 'Last Name' ),
		orderBy: 'last_name',
		align: 'left',
		cell: ({data}) => {
			return data.last_name || ''
		}
	},
	{
		ID: 'username',
		name: __( 'Username' ),
		orderBy: 'username',
		align: 'left',
		cell: ({data}) => {
			return data.meta && data.meta.user_login || ''
		}
	},
	{
		ID: 'owner',
		name: __( 'Owner' ),
		orderBy: 'owner_id',
		align: 'left',
		cell: ({data}) => {
			return data.owner_id || '' // Need to resolve this to name/link
		}
	},
	{
		ID: 'date_created',
		name: __( 'Date Created' ),
		orderBy: 'date_created',
		align: 'left',
		cell: ({data}) => {
			return data.date_created || '' // Need to resolve this to proper format
		}
	},
];

const contactTableBulkActions = [
	{
		title: 'Delete',
		action: 'delete',
		icon: <DeleteIcon/>
	}
];

export const Contacts = ( props ) => {
	let { path } = useRouteMatch();

	const {
		getItem,
		items,
		totalItems,
		isRequesting
	} = useSelect((select) => {
		const store = select(CONTACTS_STORE_NAME)

		return {
			getItem: store.getItem,
			items: store.getItems(),
			totalItems: store.getTotalItems(),
			isRequesting: store.isItemsRequesting(),
		}
	}, [] )

	const {
		fetchItems,
		updateItem,
		deleteItem,
		deleteItems
	} = useDispatch( CONTACTS_STORE_NAME );

	/**
	 * Handle any bulk actions
	 *
	 * @param action
	 * @param selected
	 * @param setSelected
	 * @param fetchItems
	 */
	const handleBulkAction = ( { action, selected, setSelected, fetchItems } ) => {
		switch (action) {
			case 'delete':
				deleteItems( selected.map( item => item.ID ) );
				setSelected([])
				break;
		}
	}

	const renderListView = () => {
		return (
			<Fragment>
				<ListTable
					items={items}
					defaultOrderBy={'date_created'}
					defaultOrder={'desc'}
					totalItems={totalItems}
					fetchItems={fetchItems}
					isRequesting={isRequesting}
					columns={contactTableColumns}
					onBulkAction={handleBulkAction}
					bulkActions={contactTableBulkActions}
				/>
			</Fragment>
		)
	}

	return (
		<Switch>
			<Route exact path={path}>
				{ renderListView }
			</Route>
			<Route path={`${path}/:id`}>
				<SingleView
					getItem={ getItem }
					updateItem={ updateItem }
					deleteItem={ deleteItem }
					{...props}
				/>
			</Route>
		</Switch>
	)
}
