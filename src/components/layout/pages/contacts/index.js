/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import { useSelect, useDispatch } from '@wordpress/data'
import DeleteIcon from '@material-ui/icons/Delete'

import {
  useRouteMatch,
  Switch,
  Route, Link,
} from 'react-router-dom'

/**
 * Internal dependencies
 */
import {
	CONTACTS_STORE_NAME,
} from '../../../../data'
import { ListTable } from '../../../core-ui/list-table/new'
import { SingleView } from './single-view'
import { makeStyles } from '@material-ui/core/styles'
import Chip from '@material-ui/core/Chip'
import QuickEdit from './quick-edit';
import RowActions from 'components/core-ui/row-actions'
import SettingsIcon from '@material-ui/icons/Settings'

const iconProps = {
	fontSize: 'small',
	style: {
		verticalAlign: 'middle',
	},
}

const useStyles = makeStyles((theme) => ( {
  contactRowImage: {
    maxWidth: '40px',
    borderRadius: '50%',
    // boxShadow: '1px 1px 4px #ccc',
  },
} ))

/**
 *
 * @param status
 * @returns {*}
 * @constructor
 */
const ContactStatus = ({ status }) => {

  const statuses = {
    1: __('Unconfirmed'),
    2: __('Confirmed'),
    3: __('Unsubscribed'),
    4: __('Weekly'),
    5: __('Monthly'),
    6: __('Hard Bounce'),
    7: __('Spam'),
    8: __('Complained'),
  }

  return (
    <Chip variant="outlined" color="primary" size="small" label={statuses[status]} />
  )
}

const contactTableColumns = [
  {
    ID: 'avatar',
    name: '',
    cell: ({ data, ID, meta }) => {

      const classes = useStyles()

      return (
        <Link to={ `/contacts/${ ID }` }>
          <img className={ classes.contactRowImage } src={ data.gravatar }/>
        </Link>
      )
    },
  },
  {
    ID: 'email',
    name: __('Email'),
    orderBy: 'email',
    align: 'left',
    cell: ({ data, ID }) => {
      return ( <Link to={ `/contacts/${ ID }` }>
        { data.email }
      </Link> )
    },
  },
	{
		ID: 'optin_status',
		name: __('Optin Status'),
		orderBy: 'optin_status',
		align: 'left',
		cell: ({ data, ID }) => {
			return <ContactStatus status={data.optin_status} />
		},
	},
  {
    ID: 'first_name',
    name: __('First Name'),
    orderBy: 'first_name',
    align: 'left',
    cell: ({ data }) => {
      return data.first_name || ''
    },
  },
  {
    ID: 'last_name',
    name: __('Last Name'),
    orderBy: 'last_name',
    align: 'left',
    cell: ({ data }) => {
      return data.last_name || ''
    },
  },
  {
    ID: 'username',
    name: __('Username'),
    orderBy: 'username',
    align: 'left',
    cell: ({ data }) => {
      return data.meta && data.meta.user_login || ''
    },
  },
  {
    ID: 'owner',
    name: __('Owner'),
    orderBy: 'owner_id',
    align: 'left',
    cell: ({ data }) => {
      return data.owner_id || '' // Need to resolve this to name/link
    },
  },
  {
    ID: 'date_created',
    name: __('Date Created'),
    orderBy: 'date_created',
    align: 'left',
    cell: ({ data }) => {
      return data.date_created || '' // Need to resolve this to proper format
    },
  },
	{
		ID: 'actions',
		name: <span><SettingsIcon { ...iconProps }/> { 'Actions' }</span>,
		align: 'right',
		cell: ({ ID, data, openQuickEdit }) => {

			const { deleteItem } = useDispatch(CONTACTS_STORE_NAME)

			const handleEdit = () => {
				openQuickEdit()
			}

			const handleDelete = (ID) => {
				deleteItem(ID)
			}

			return <>
				<RowActions
					onEdit={ openQuickEdit }
					onDelete={ () => handleDelete(ID) }
				/>
			</>
		},
	},
]

const contactTableBulkActions = [
  {
    title: 'Delete',
    action: 'delete',
    icon: <DeleteIcon/>,
  },
]

export const Contacts = (props) => {
  let { path } = useRouteMatch()

  const {
    getItem,
    items,
    totalItems,
    isRequesting,
  } = useSelect((select) => {
    const store = select(CONTACTS_STORE_NAME)

    return {
      getItem: store.getItem,
      items: store.getItems(),
      totalItems: store.getTotalItems(),
      isRequesting: store.isItemsRequesting(),
    }
  }, [])

  const {
    fetchItems,
    updateItem,
    deleteItem,
    deleteItems,
  } = useDispatch(CONTACTS_STORE_NAME)

  /**
   * Handle any bulk actions
   *
   * @param action
   * @param selected
   * @param setSelected
   * @param fetchItems
   */
  const handleBulkAction = ({ action, selected, setSelected, fetchItems }) => {
    switch (action) {
      case 'delete':
        deleteItems(selected.map(item => item.ID))
        setSelected([])
        break
    }
  }

  const renderListView = () => {
    return (
      <Fragment>
        <ListTable
          items={ items }
          defaultOrderBy={ 'date_created' }
          defaultOrder={ 'desc' }
          totalItems={ totalItems }
          fetchItems={ fetchItems }
          isRequesting={ isRequesting }
          columns={ contactTableColumns }
          onBulkAction={ handleBulkAction }
          bulkActions={ contactTableBulkActions }
          QuickEdit={QuickEdit}
        />
      </Fragment>
    )
  }

  return (
    <Switch>
      <Route exact path={ path }>
        { renderListView }
      </Route>
      <Route path={ `${ path }/:id` }>
        <SingleView
          getItem={ getItem }
          updateItem={ updateItem }
          deleteItem={ deleteItem }
          { ...props }
        />
      </Route>
    </Switch>
  )
}
