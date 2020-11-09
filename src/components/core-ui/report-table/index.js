import * as React from 'react';
import { DataGrid } from '@material-ui/data-grid';
import { makeStyles } from '@material-ui/core/styles';
import Card from "@material-ui/core/Card";

const ReportTable = ({title, data, gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd}) => {
  const useStyles = makeStyles({
    root:{
      position:'relative',
      paddingTop: '53px',
      gridColumnStart,
      gridColumnEnd,
      gridRowStart,
      gridRowEnd,
    },
    table: {
      width: '100%',
      height: '300px'
    },
    title: {
      fontSize: '18px',
      position: 'absolute',
      textTransform:'capitalize',
      top: '18px',
      left: '15px',
      fontWeight: '700'
    }
  });

  const classes = useStyles();

  if(!data.chart.data){
    return(<div/>)
  }
  if(data.chart.data.length === 0){
    return(<div/>)
  }

  const columns = data.chart.label.map((label)=>{
    if(typeof(value)=== 'object'){

      return { field: 'Conversation Rate', headerName: 'Conversation Rate', width: 200, sortable: true }
    }
    return { field: label, headerName: label, width: 200 }
  });

  const rows = data.chart.data.map((data, i)=>{
    return { id: i, [columns[0]['field']]: data.label, [columns[1]['field']]: data.data};
  })

  return (
    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <DataGrid rows={rows} columns={columns} pageSize={5} checkboxSelection={false} />
    </Card>
  );
}

export default ReportTable;
