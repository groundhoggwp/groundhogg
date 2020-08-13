import React from 'react'
import { Table } from 'react-bootstrap'
import range from 'lodash/range'
import InfiniteScroll from 'react-infinite-scroll-component'

import './style.scss'

export const ListTable = ({
  tableProps,
  isLoading,
  items, // the actual table items
  totalItems,
  fetchData,
  columns, // the columns themselves, structured with name and render information
  noItems
}) => {

  return (
    <>
      <InfiniteScroll
        style={ { overflow: 'visible' } }
        next={ fetchData }
        hasMore={ totalItems !== items.length }
        loader={ <></> }
        dataLength={ items.length }
      >
        <Table { ...tableProps } className={ 'list-table' }>
          <thead>
          <tr>
            { columns.map((column,i) => <ListTableTH key={i} { ...column } />) }
          </tr>
          </thead>
          <tbody>
          {

            items.length > 0 ?
              items.map(
                (item,i) => <ListTableItemRow key={i} item={ item } columns={ columns }/>)
              : ! isLoading ? <ListTableRowEmpty
                colSpan={columns.length}
                noItems={noItems}
              /> : <></>
          }
          { isLoading &&
          range(10).map(i => <ListTableRowLoading key={i} columns={ columns }/>) }
          </tbody>
          <tfoot>
          <tr>
            { columns.map((column,i) => <ListTableTH key={i} { ...column } />) }
          </tr>
          </tfoot>
        </Table>
      </InfiniteScroll>
    </>

  )

}

const ListTableTH = ({ id, name, sortable }) => {
  return (
    <th id={ id }>
      { sortable ? <button
        className={ 'list-table-order-button' }>{ name }</button> : name }
    </th>
  )
}

const ListTableRowLoading = ({ columns }) => {
  return (
    <tr className={ 'list-table-item-row' }>
      { columns.map((column,i) => <td key={i} className={ 'loading-animation' }>
        <div></div>
      </td>) }
    </tr>
  )
}

const ListTableItemRow = ({ item, columns }) => {
  return (
    <tr className={ 'list-table-item-row' }>
      { columns.map((column,i) => <td key={i} >
        <column.render item={ item }/>
      </td>) }
    </tr>
  )
}

const ListTableRowEmpty = ({ colSpan, noItems }) => {
  return (
    <tr className={ 'list-table-item-row' }>
      <td
        colSpan={colSpan}
      >
        {noItems}
      </td>
    </tr>
  )
}