/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import { ListTable } from '../../../core-ui/list-table/new'
import DeleteIcon from '@material-ui/icons/Delete'
import SettingsIcon from '@material-ui/icons/Settings'
import GroupIcon from '@material-ui/icons/Group'
import TimelineIcon from '@material-ui/icons/Timeline'
import AccountCircleIcon from '@material-ui/icons/AccountCircle'
import RowActions from '../../../core-ui/row-actions'
import TextField from '@material-ui/core/TextField'
import Button from '@material-ui/core/Button'
import Box from '@material-ui/core/Box'
import makeStyles from '@material-ui/core/styles/makeStyles'
import { useKeyPress } from '../../../../utils'
import FlagIcon from '@material-ui/icons/Flag'
import Chip from '@material-ui/core/Chip'
import { Tooltip } from '@material-ui/core'
import ButtonGroup from '@material-ui/core/ButtonGroup'
import { FUNNELS_STORE_NAME } from '../../../../data/funnels'

const iconProps = {
  fontSize: 'small',
  style: {
    verticalAlign: 'middle',
  },
}

const funnelTableColumns = [
  {
    ID: 'ID',
    name: 'ID',
    orderBy: 'ID',
    align: 'right',
    cell: ({ data, ID }) => {
      return ID
    },
  },
  {
    ID: 'name',
    name: <span>{ 'Title' }</span>,
    orderBy: 'title',
    align: 'left',
    cell: ({ data }) => {
      return <>{ data.title }</>
    },
  },
  {
    ID: 'stats',
    name: <span><TimelineIcon { ...iconProps }/> { 'Stats' }</span>,
    align: 'center',
    cell: ({ stats }) => {
      return <>
        <Tooltip title={ 'Complete' }>
          <Chip
            icon={ <FlagIcon/> }
            label={ stats.complete }
          />
        </Tooltip>
        <Tooltip title={ 'Active' }>
          <Chip
            icon={ <AccountCircleIcon/> }
            label={ stats.active_now }
          />
        </Tooltip>
      </>
    },
  },
  {
    ID: 'status',
    name: <span>{ 'Status' }</span>,
    align: 'left',
    orderBy: 'status',
    cell: ({ ID, data }) => {

      const { updateItem } = useDispatch(FUNNELS_STORE_NAME)

      const handleClick = (status) => {
        updateItem(ID, {
          data: {
            status: status,
          },
        })
      }

      return <>
        <ButtonGroup size={ 'small' } color="primary"
                     aria-label={ 'funnel status' }>
          <Button
            onClick={()=>handleClick('active')}
            variant={ data.status === 'active'
              ? 'contained'
              : 'outlined' }>{ 'Active' }</Button>
          <Button
            onClick={()=>handleClick('inactive')}
            variant={ data.status !== 'active'
              ? 'contained'
              : 'outlined' }>{ 'Inactive' }</Button>
        </ButtonGroup>
      </>
    },
  },
  {
    ID: 'actions',
    name: <span><SettingsIcon { ...iconProps }/> { 'Actions' }</span>,
    align: 'right',
    cell: ({ ID, data, openQuickEdit }) => {

      const { deleteItem } = useDispatch(FUNNELS_STORE_NAME)

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

// Styling for inputs
const useStyles = makeStyles((theme) => ( {
  root: {
    '& .MuiTextField-root:not(:last-child)': {
      marginBottom: theme.spacing(2),
    },
    '& .MuiButtonBase-root:not(:last-child)': {
      marginRight: theme.spacing(2),
    },
  },
} ))

/**
 * Handle the table quick edit
 *
 * @param ID
 * @param data
 * @param exitQuickEdit
 * @returns {*}
 * @constructor
 */
const FunnelsQuickEdit = ({ ID, data, exitQuickEdit }) => {

  const classes = useStyles()
  const { updateItem } = useDispatch(FUNNELS_STORE_NAME)
  const [tempState, setTempState] = useState({
    ...data,
  })

  // Exit quick edit
  useKeyPress(27, null, () => {
    exitQuickEdit()
  })

  /**
   * Handle pressing enter in the tag name
   *
   * @param keyCode
   */
  const handleOnKeydown = ({ keyCode }) => {
    switch (keyCode) {
      case 13:
        commitChanges()
    }
  }

  /**
   * Store the changes in a temp state
   *
   * @param atts
   */
  const handleOnChange = (atts) => {
    setTempState({
      ...tempState,
      ...atts,
    })
  }

  /**
   * Commit the changes
   */
  const commitChanges = () => {
    updateItem(ID, {
      data: tempState,
    })
    exitQuickEdit()
  }

  return (
    <Box display={ 'flex' } justifyContent={ 'space-between' }
         className={ classes.root }>
      <Box flexGrow={ 2 }>
        <TextField
          autoFocus
          label={ 'Funnel Title' }
          id="funnel-title"
          fullWidth
          value={ tempState && tempState.title }
          onChange={ (e) => handleOnChange({ title: e.target.value }) }
          onKeyDown={ handleOnKeydown }
          variant="outlined"
          size="small"
        />
      </Box>
      <Box flexGrow={ 1 }>
        <Box display={ 'flex' } justifyContent={ 'flex-end' }>
          <Button variant="contained" color="primary" onClick={ commitChanges }>
            { 'Save Changes' }
          </Button>
          <Button variant="contained" onClick={ exitQuickEdit }>
            { 'Cancel' }
          </Button>
        </Box>
      </Box>
    </Box>
  )
}

const FunnelTableBulkActions = [
  {
    title: 'Delete',
    action: 'delete',
    icon: <DeleteIcon/>,
  },
]

export default () => {

  const { items, totalItems, isRequesting } = useSelect((select) => {
    const store = select(FUNNELS_STORE_NAME)

    return {
      items: store.getItems(),
      totalItems: store.getTotalItems(),
      isRequesting: store.isItemsRequesting(),
    }
  }, [])

  const { fetchItems, deleteItems } = useDispatch(FUNNELS_STORE_NAME)

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

  return (
    <Fragment>
      <ListTable
        items={ items }
        defaultOrderBy={ 'ID' }
        defaultOrder={ 'desc' }
        totalItems={ totalItems }
        fetchItems={ fetchItems }
        isRequesting={ isRequesting }
        columns={ funnelTableColumns }
        onBulkAction={ handleBulkAction }
        bulkActions={ FunnelTableBulkActions }
        QuickEdit={ FunnelsQuickEdit }
      />
    </Fragment>
  )
}