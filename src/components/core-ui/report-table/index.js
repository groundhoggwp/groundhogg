import * as React from 'react';
import { DataGrid } from '@material-ui/data-grid';
import { makeStyles } from '@material-ui/core/styles';
import Card from "@material-ui/core/Card";

const ReportTable = ({title, data, gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd, fullWidth}) => {
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
      height: '100%'
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
    return (
      <Card className={classes.root}>
        <div className={classes.title}>{title}</div>
        <p>{data.no_data}</p>
      </Card>
    );
  }

  // Manual width calc is required because each container is dynamic and data grid can't use % then
  let columnWidth = ((window.innerWidth/2)/(data.chart.label.length*2));
  if(fullWidth){
    columnWidth = ((window.innerWidth)/(data.chart.label.length*2));
  }
  let columns = data.chart.label.map((label)=>{
    if(typeof(value)=== 'object'){
      label = 'Conversation Rate';
    }

    return { field: label, headerName: label, width: columnWidth, align: 'left', sortable: true }
  });

  if(data.chart.data[0].percentage && !data.chart.label.includes('Percentage')){
    columns.push({ field: 'Percentage', headerName: 'Percentage', width: columnWidth, align: 'left', sortable: true })
  }


  const rows = data.chart.data.map((data, i)=>{
    let row = { id: i, [columns[0]['field']]: data.label, [columns[1]['field']]: data.data};
    if(data.percentage){
      row['Percentage'] = data.percentage;
    }
    return row
  })

  return (
    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <DataGrid rows={rows} columns={columns} pageSize={6} checkboxSelection={false} />
    </Card>
  );
}

export default ReportTable;
