import {
  useEffect,
  useState,
} from '@wordpress/element'
import { canUser } from 'utils'
import Table from '@material-ui/core/Table'
import TableContainer from '@material-ui/core/TableContainer'
import TableHead from '@material-ui/core/TableHead'
import TableRow from '@material-ui/core/TableRow'
import TableCell from '@material-ui/core/TableCell/TableCell'
import Checkbox from '@material-ui/core/Checkbox/Checkbox'
import TableSortLabel from '@material-ui/core/TableSortLabel'
import TableBody from '@material-ui/core/TableBody'
import Paper from '@material-ui/core/Paper'
import TablePagination from '@material-ui/core/TablePagination'
import Typography from '@material-ui/core/Typography'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'
import IconButton from '@material-ui/core/IconButton'
import TextField from '@material-ui/core/TextField'
import Toolbar from '@material-ui/core/Toolbar'
import { lighten, makeStyles } from '@material-ui/core/styles'
import clsx from 'clsx'

import Spinner from '../spinner'
import { CORE_STORE_NAME } from 'data';
import {isFunction} from "@material-ui/data-grid";
import { useDebounce } from 'utils/index'

const useStyles = makeStyles({
  root: {
    width: '100%',
  },
  container: {
    maxHeight: 'calc( 100vh - 250px )',
    overflow: 'auto',
  },
  sticky: {
    position: 'sticky',
    top: '0',
    zIndex: 10,
    backgroundColor: 'white',
  }})

export function ListTable ({
  defaultOrderBy,
  defaultOrder,
  columns,
  items,
  totalItems,
  fetchItems,
  isLoadingItems,
  bulkActions,
  onBulkAction,
  QuickEdit,
  onSelectItem, // used to manage Handle Select Item
  isCheckboxSelected // used to override default check of ID
}) {
  const classes = useStyles()
  const [perPage, setPerPage] = useState(10)
  const [page, setPage] = useState(0)
  const [order, setOrder] = useState(defaultOrder)
  const [orderBy, setOrderBy] = useState(defaultOrderBy)
  const [selected, setSelected] = useState([])
  const [quickEditId, setQuickEditId] = useState(null)
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search, 250)

  const __fetchItems = () => {
    return fetchItems({
      limit: perPage,
      offset: perPage * page,
      orderBy: orderBy,
      order: order,
      search: search,
    })
  }

  /**
   * When select all occurs
   */
  const handleSelectAll = () => {
    setSelected(selected.length === items.length ? [] : items)
  }

  /**
   * If an item is selected
   *
   * @param item
   * @returns {boolean}
   */
  const isSelected = (item) => {
    if (isFunction(isCheckboxSelected)) {
        return isCheckboxSelected( { item,selected }) ;
    } else {
      return selected.filter(__item => __item.ID === item.ID).length > 0
    }
  }

  /**
   * Select an item
   *
   * @param item
   */
  const handleSelectItem = (item) => {

      if (isFunction(onSelectItem)) {
          onSelectItem( { item , setSelected ,selected });
      } else {
          // default fucntion to handle is selected if there is no cusotm function
          if (isSelected(item)) {
              // Item is selected, so remove it
              setSelected(selected.filter(__item => __item.ID !== item.ID))
          } else {
              // Add it to the selected array
              setSelected([...selected, item])
          }
      }

  }

  /**
   * Handle a bulk action
   *
   * @param e
   * @param action
   */
  const handleBulkAction = (e, action) => {
    onBulkAction({
      action,
      selected,
      setSelected,
      fetchItems: __fetchItems,
    })
  }

  useEffect(() => {
    __fetchItems()
  }, [
    perPage,
    page,
    order,
    orderBy,
    totalItems,
  ])

  useEffect(() => {
      __fetchItems()
    },
    [debouncedSearch],
  )

  /**
   * Handle the search results
   *
   * @param e
   */
  const handleSearch = (e) => {
    setSearch(e.target.value)
  }

  /**
   * Handle the update of the orderBy
   *
   * @param __orderBy
   */
  const handleReOrder = (__orderBy) => {
    // If the current column used for ordering is the same one as was chosen
    // already
    if (__orderBy === orderBy) {
      setOrder(order === 'desc' ? 'asc' : 'desc')
    }
    else {
      setOrderBy(__orderBy)
    }
  }

  /**
   * Handle the changing of the number of items per page
   *
   * @param event
   */
  const handlePerPageChange = (event) => {
    const __perPage = event.target.value
    setPerPage(__perPage)

    // Handle per page being larger than available data
    if (totalItems / __perPage < page) {
      setPage(Math.floor(totalItems / __perPage))
    }
  }

  /**
   * Handle the change of the page
   *
   * @param e
   * @param __page
   */
  const handlePageChange = (e, __page) => {
    setPage(__page)
  }

  if (!items || isLoadingItems) {
    return <Spinner/>
  }

  const canUserUpdate = canUser( 'update' ); // More granularly, canUser( 'update', item.ID )

  return (
    <>
      <Paper className={ classes.root }>
        <TableToolbar
          numSelected={ selected.length }
          search={ search }
          onSearch={ handleSearch }
          onBulkAction={ handleBulkAction }
          bulkActions={ bulkActions }
        />
        <TableContainer className={ classes.container }>
          <Table stickyHeader size={ 'medium' }>
            <TableHeader
              handleReOrder={ handleReOrder }
              onSelectAll={ handleSelectAll }
              columns={ columns }
              order={ order }
              orderBy={ orderBy }
              numSelected={ selected.length }
              perPage={ perPage }
              totalItems={ totalItems }
              className={ classes.sticky }
            />
            <TableBody>
              { items &&
              items.map(item => {

                if (quickEditId === item.ID && canUserUpdate) {
                  return (
                    <TableCell colSpan={columns.length + 1 } key={item.ID}>
                      <QuickEdit
                        {...item}
                        exitQuickEdit={()=>setQuickEditId(null)}
                      />
                    </TableCell>
                  )
                }

                return (
                  <TableRow hover key={ item.ID }>
                    <TableCell padding="checkbox">
                      <Checkbox
                        checked={ isSelected(item) }
                        onChange={ () => handleSelectItem(item) }
                        inputProps={ { 'aria-label': 'select' } }
                      />
                    </TableCell>
                    { columns.map( ( col, index ) => <TableCell key={index} align={ col.align }>
                      <col.cell
                        { ...item }
                        openQuickEdit={
                          () => canUserUpdate && setQuickEditId(item.ID)
                        }
                      />
                    </TableCell>) }
                  </TableRow>
                )
              })
              }
            </TableBody>
          </Table>
          { items &&
          <TablePagination
            component="div"
            rowsPerPage={ perPage }
            rowsPerPageOptions={ [10, 25, 50, 100] }
            onChangeRowsPerPage={ handlePerPageChange }
            count={ totalItems }
            page={ page }
            onChangePage={ handlePageChange }
          />
          }
        </TableContainer>
      </Paper>
    </>
  )
}

const useToolbarStyles = makeStyles((theme) => ( {
  root: {
    paddingLeft: theme.spacing(2),
    paddingRight: theme.spacing(1),
    // paddingTop: theme.spacing(1),
  },
  highlight:
    theme.palette.type === 'light'
      ? {
        color: theme.palette.secondary.main,
        backgroundColor: lighten(theme.palette.secondary.light, 0.85),
      }
      : {
        color: theme.palette.text.primary,
        backgroundColor: theme.palette.secondary.dark,
      },
  title: {
    flex: '1 1 100%',
  },
} ))

function TableToolbar (props) {

  const classes = useToolbarStyles()
  const { numSelected, tableTitle, search, onSearch, bulkActions, onBulkAction } = props

  return (
    <Toolbar
      className={ clsx(classes.root, {
        [classes.highlight]: numSelected > 0,
      }) }
    >
      { numSelected > 0 ? (
        <Typography className={ classes.title } color="inherit"
                    variant="subtitle1" component="div">
          { numSelected } selected
        </Typography>
      ) : (
        <Typography className={ classes.title } variant="h6" id="tableTitle"
                    component="div">
          { tableTitle }
        </Typography>
      ) }

      { numSelected > 0 ? bulkActions.map( ( action, index )  => (
        <Tooltip key={ index } title={ action.title }>
          <IconButton
            aria-label={ action.action }
            onClick={ (e) => onBulkAction(e, action.action) }
          >
            { action.icon }
          </IconButton>
        </Tooltip> )) : (
        <TextField id="search" label={ 'Search' } type="search"
                   variant="outlined"
                   value={ search }
                   onChange={ onSearch }
                   size={ 'small' }
        />
      ) }
    </Toolbar>
  )

}

function TableHeader (props) {

  const {
    columns,
    onSelectAll,
    order,
    orderBy,
    numSelected,
    perPage,
    totalItems,
    handleReOrder,
    className,
  } = props

  const __totalItems = Math.min(perPage, totalItems)

  return (
    <TableHead className={ className }>
      <TableRow className={ className }>
        <TableCell padding="checkbox" className={ className }>
          <Checkbox
            indeterminate={ numSelected > 0 && numSelected < __totalItems }
            checked={ __totalItems > 0 && numSelected === __totalItems }
            onChange={ onSelectAll }
            inputProps={ { 'aria-label': 'select all' } }
          />
        </TableCell>
        {
          columns.map( ( col, index ) => <HeaderTableCell
            column={ col }
            key={ index }
            currentOrderBy={ orderBy }
            order={ order }
            handleReOrder={ handleReOrder }
            className={ className }
          />)
        }
      </TableRow>
    </TableHead>
  )

}

/**
 * Assume the header type to use
 *
 * @param column
 * @param currentOrderBy
 * @param handleReOrder
 * @param order
 * @returns {*}
 * @constructor
 */
function HeaderTableCell ({ column, currentOrderBy, handleReOrder, order, className }) {
  const Component = column.orderBy ? SortableHeaderCell : NonSortableHeaderCell
  return <Component { ...column } currentOrderBy={ currentOrderBy }
                    onReOrder={ handleReOrder } className={ className } order={ order }/>
}

/**
 *
 * A head
 *
 * @param ID
 * @param name
 * @param align
 * @returns {*}
 * @constructor
 */
function NonSortableHeaderCell ({ ID, name, align, className }) {
  return (
    <TableCell
      key={ ID }
      align={ align }
      padding={ 'default' }
      className={ className }
    >
      { name }
    </TableCell>
  )
}

/**
 * A sortable table cell
 *
 * @param ID
 * @param orderBy
 * @param order
 * @param name
 * @param align
 * @param currentOrderBy
 * @param handleReOrder
 * @returns {*}
 * @constructor
 */
function SortableHeaderCell ({ ID, orderBy, order, name, align, currentOrderBy, onReOrder, className }) {
  return (
    <TableCell
      key={ ID }
      align={ align }
      padding={ 'default' }
      sortDirection={ currentOrderBy === orderBy ? order : false }
      className={ className }
    >
      <TableSortLabel
        active={ orderBy === currentOrderBy }
        direction={ orderBy === currentOrderBy ? order : 'asc' }
        onClick={ () => onReOrder(orderBy) }
      >
        { name }
      </TableSortLabel>
    </TableCell>
  )
}
