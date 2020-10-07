import { useState } from '@wordpress/element'
import Table from '@material-ui/core/Table'
import TableContainer from '@material-ui/core/TableContainer'
import TableHead from '@material-ui/core/TableHead'
import TableRow from '@material-ui/core/TableRow'

export function ListTable ({ items, columns, store }) {

  const [perPage, setPerPage] = useState(25)
  const [page, setPage] = useState(1)
  const [order, setOrder] = useState('DESC')
  const [selected, setSelected] = useState([])

  return (
    <TableContainer>
      <Table size={'medium'}>
        <TableHead>
          <TableRow>
            {
              columns.map( col => <col.header/> )
            }
          </TableRow>
        </TableHead>
        <TableBody>
          {
            items.map( item => <TableRow key={item.ID}>
              { columns.map( col => <col.cell item={item}/> ) }
            </TableRow> )
          }
        </TableBody>
      </Table>
    </TableContainer>
  )
}