/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import { ListTable } from '../../../core-ui/list-table/new'
import { TAGS_STORE_NAME } from '../../../../data/tags'
import LocalOfferIcon from '@material-ui/icons/LocalOffer'
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

export const Tags = () => {

	const { items, totalItems, isRequesting } = useSelect( (select) => {
		const store = select(TAGS_STORE_NAME);

		return {
			items: store.getItems(),
			totalItems: store.getTotalItems(),
			isRequesting: store.isItemsRequesting()
		}
	}, [] );

	const { fetchItems } = useDispatch( TAGS_STORE_NAME );

	return (
		<Fragment>
			<ListTable
				items={items}
				totalItems={totalItems}
				fetchItems={fetchItems}
				isRequesting={isRequesting}
				columns={tagTableColumns}
			/>
		</Fragment>
	)
}