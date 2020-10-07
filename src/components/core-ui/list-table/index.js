/**
 * External dependencies
 */
 import * as React from 'react';
 import { DataGrid } from '@material-ui/data-grid';
 import { find, forEach, isNull, get, includes, _ } from 'lodash';
/**
 * Internal dependencies
 */

export default function Listable(props) {
  let rows = []
  let columns = []
  if(props.data.length > 0){
    rows = props.data.map(row=> ({ ...row, id: _.uniqueId() }))
    columns = Object.keys(props.data[0]).map((email)=>{
        return  { field: email, headerName: email, width: 70 };
    })
  }

  return (
    <div style={{ height: 400, width: '100%' }}>
      <DataGrid rows={rows} columns={columns} pageSize={5} checkboxSelection />
    </div>
  );
}
