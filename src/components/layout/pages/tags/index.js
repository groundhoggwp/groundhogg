/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import { ListTable } from '../../../core-ui/list-table/new'
import { TAGS_STORE_NAME } from '../../../../data/tags'
import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import DeleteIcon from '@material-ui/icons/Delete'
import SettingsIcon from '@material-ui/icons/Settings';
import GroupIcon from '@material-ui/icons/Group'
import ButtonWithDropdown from '../../../core-ui/buttonWithDropdown'

const iconProps = {
	fontSize: 'small',
	style: {
		verticalAlign: 'middle'
	}
}

const tagTableColumns =  [
	{
		ID: 'tag_id',
		name: 'ID',
		orderBy: 'tag_id',
		align: 'right',
		cell: ({ data, ID }) => {
			return data.tag_id
		},
	},
	{
		ID: 'name',
		name: <span><LocalOfferIcon {...iconProps}/> { 'Name' }</span>,
		orderBy: 'tag_name',
		align: 'left',
		cell: ({ data }) => {
			return <>{ data.tag_name }</>
		},
	},
	{
		ID: 'contacts',
		name: <span><GroupIcon {...iconProps}/> { 'Contacts' }</span>,
		orderBy: 'contact_count',
		align: 'right',
		cell: ({ data }) => {
			return <>{ data.contact_count }</>
		},
	},
	{
		ID: 'contacts',
		name: <span><SettingsIcon {...iconProps}/> { 'Actions' }</span>,
		align: 'right',
		cell: ({ data }) => {

			return <>
				<ButtonWithDropdown
					button={'Edit'}
					menuOptions={[
						{
							key: 'delete',
							render: 'Delete'
						},
					]}
				/>
			</>
		},
	},
];

const tagTableBulkActions = [
	{
		title: 'Delete',
		action: 'delete',
		icon: <DeleteIcon/>
	}
]

export const Tags = () => {

	const { items, totalItems, isRequesting } = useSelect( (select) => {
		const store = select(TAGS_STORE_NAME);

		return {
			items: store.getItems(),
			totalItems: store.getTotalItems(),
			isRequesting: store.isItemsRequesting()
		}
	}, [] );

	const { fetchItems, deleteItems } = useDispatch( TAGS_STORE_NAME );

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

	return (
		<Fragment>
			<ListTable
				items={items}
				defaultOrderBy={'tag_id'}
				defaultOrder={'desc'}
				totalItems={totalItems}
				fetchItems={fetchItems}
				isRequesting={isRequesting}
				columns={tagTableColumns}
				onBulkAction={handleBulkAction}
				bulkActions={tagTableBulkActions}
			/>
		</Fragment>
	)
}