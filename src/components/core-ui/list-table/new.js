import { useState } from '@wordpress/element'
import Table from '@material-ui/core/Table'
import TableContainer from '@material-ui/core/TableContainer'
import TableHead from '@material-ui/core/TableHead'
import TableRow from '@material-ui/core/TableRow'
import TableCell from '@material-ui/core/TableCell/TableCell'
import Checkbox from '@material-ui/core/Checkbox/Checkbox'
import TableSortLabel from '@material-ui/core/TableSortLabel'
import React from 'react'
import TableBody from '@material-ui/core/TableBody'
import { useDispatch, useSelect } from '@wordpress/data'
import Paper from '@material-ui/core/Paper'
import TablePagination from '@material-ui/core/TablePagination'
import Spinner from '../spinner'

export function ListTable ({ columns, items, isRequesting, fetchItems }) {

  const [perPage, setPerPage] = useState(25)
  const [page, setPage] = useState(1)
  const [order, setOrder] = useState('asc')
  const [orderBy, setOrderBy] = useState('ID')
  const [selected, setSelected] = useState([])

  if ( ! items || isRequesting ){
    return <Spinner/>
  }

  const __fetchItems = () => {
    fetchItems({
      limit: perPage,
      offset: perPage * ( page - 1 ),
      orderby: orderBy,
      order: order,
    })
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
      setOrder(order === 'desc' ? 'asc' : 'desc', __fetchItems)
    }
    else {
      setOrderBy(__orderBy, __fetchItems)
    }
  }

  /**
   * Handle the changing of the number of items per page
   *
   * @param value
   */
  const handlePerPageChange = ({ value }) => {
    setPerPage(value, __fetchItems )
  }

  /**
   * Handle the change of the page
   *
   * @param e
   * @param __page
   */
  const handlePageChange = (e, __page) => {
    setPage(__page, __fetchItems)
  }

  return (
    <>
      <TableContainer component={ Paper }>
        <Table size={ 'medium' }>
          <AdvancedTableHeader
            handleReOrder={ handleReOrder }
            columns={ columns }
            order={ order }
            orderBy={ orderBy }
            numSelected={ selected.length }
          />
          <TableBody>
            { items &&
            items.map(item => {

              return (
                <TableRow key={ item.ID }>
                  <TableCell padding="checkbox">
                    <Checkbox

                    />
                  </TableCell>
                  { columns.map(col => <TableCell align={ col.align }>
                    <col.cell { ...item }/>
                  </TableCell>) }
                </TableRow>
              )
            })
            }
          </TableBody>
        </Table>
        <TablePagination
          component="div"
          rowsPerPage={ perPage }
          rowsPerPageOptions={ [10, 25, 50, 100] }
          onChangeRowsPerPage={ handlePerPageChange }
          count={ items.length }
          page={ page }
          onChangePage={ handlePageChange }

        />
      </TableContainer>
    </>
  )
}

function AdvancedTableHeader (props) {

  const {
    columns,
    onSelectAllClick,
    order,
    orderBy,
    numSelected,
    rowCount,
    handleReOrder,
  } = props

  return (
    <TableHead>
      <TableRow>
        <TableCell padding="checkbox">
          <Checkbox
            indeterminate={ numSelected > 0 && numSelected < rowCount }
            checked={ rowCount > 0 && numSelected === rowCount }
            onChange={ onSelectAllClick }
            inputProps={ { 'aria-label': 'select all' } }
          />
        </TableCell>
        {
          columns.map(col => <HeaderTableCell
            column={ col }
            currentOrderBy={ orderBy }
            order={ order }
            handleReOrder={ handleReOrder }
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
function HeaderTableCell ({ column, currentOrderBy, handleReOrder, order }) {
  const Component = column.orderBy ? SortableHeaderCell : NonSortableHeaderCell
  return <Component { ...column } currentOrderBy={ currentOrderBy }
                    onReOrder={ handleReOrder } order={ order }/>
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
function NonSortableHeaderCell ({ ID, name, align }) {
  return (
    <TableCell
      variant={ 'head' }
      key={ ID }
      align={ align }
      padding={ 'default' }
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
function SortableHeaderCell ({ ID, orderBy, order, name, align, currentOrderBy, onReOrder }) {
  return (
    <TableCell
      key={ ID }
      variant={ 'head' }
      align={ align }
      padding={ 'default' }
      sortDirection={ currentOrderBy === orderBy ? order : false }
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