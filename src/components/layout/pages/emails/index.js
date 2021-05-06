/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data'
import DeleteIcon from '@material-ui/icons/Delete'
import Button from '@material-ui/core/Button'

import {
	useRouteMatch,
	Switch,
	Route
} from "react-router-dom";

/**
 * Internal dependencies
 */
import {
	EMAILS_STORE_NAME
} from '../../../../data';
import  ListTable  from '../../../core-ui/list-table/'
import { EmailRowPrimaryItem } from './email-row-primary-item'
import { SingleView } from './single-view'
import {getLuxonDate} from "utils/index";

const contactTableColumns = [
	{
		ID: 'title',
		name: __( 'Title' ),
		orderBy: 'title',
		align: 'left',
		cell: ({data, ID}) => {
			return <EmailRowPrimaryItem data={data} ID={ID} />
		}
	},
	{
		ID: 'subject',
		name: __( 'Subject' ),
		orderBy: 'subject',
		align: 'left',
		cell: ({data}) => {
			return data.subject || ''
		}
	},
	{
		ID: 'from_user',
		name: __( 'From User' ),
		orderBy: 'from_user',
		align: 'left',
		cell: ({data}) => {
			return data.from_user || ''
		}
	},
	{
		ID: 'author',
		name: __( 'Author' ),
		orderBy: 'author',
		align: 'left',
		cell: ({data}) => {
			return data.author || ''
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

export const Emails = ( props ) => {
	let { path } = useRouteMatch();

	const {
		items,
		totalItems,
		isRequesting,
		isCreating
	} = useSelect((select) => {
		const store = select(EMAILS_STORE_NAME)

		return {
			items: store.getItems(),
			totalItems: store.getTotalItems(),
			isRequesting: store.isItemsRequesting(),
			isCreating: store.isItemsCreating()
		}
	}, [] )

	const {
		createItem,
		fetchItems,
		deleteItems
	} = useDispatch( EMAILS_STORE_NAME );

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

	const createEmail = async () => {
		const result = await createItem({
			data: {
					content:'',
					subject:'',
					pre_header:'',
					from_user:'',
					author:'',
					last_updated:getLuxonDate('last_updated'),
					date_created:getLuxonDate('date_created'),
					status:'draft',
					is_template:0,
					title:'New Email'
			}
		});

		props.history.push(`/emails/${result.item.ID}`)
	};

	const renderListView = () => {
		return (
			<Fragment>
				<Button style={{margin: '10px'}} variant="contained" color="primary" onClick={createEmail}>
				  Create Email
				</Button>
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
					{...props}
				/>
			</Route>
		</Switch>
	)
}
