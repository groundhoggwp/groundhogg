import * as React from 'react';
import { DataGrid } from '@material-ui/data-grid';
import { makeStyles } from '@material-ui/core/styles';
import Typography from '@material-ui/core/Typography'
import Card from "@material-ui/core/Card";
import _ from 'lodash';

// Bring this into a
function titleCase(str) {
   var splitStr = str.toLowerCase().split(' ');
   for (var i = 0; i < splitStr.length; i++) {
       // You do not need to check if i is larger than splitStr length, as your for does that for you
       // Assign it back to the array
       splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);
   }
   // Directly return the joined string
   return splitStr.join(' ');
}

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

  // Stop Renders while data is loading
  if(!data.chart.data){
    return(<div/>)
  }

  // Report has no Data
  if(data.chart.data.length === 0){
    return (
      <Card className={classes.root}>
        <div className={classes.title}>{title}</div>
        <Typography className={classes.descriptionCheckbox} variant="p" component="p" dangerouslySetInnerHTML={{ __html: data.no_data }}  />
      </Card>
    );
  }
  const tableLabels = data.chart.label;
  const tableData = data.chart.data.map((row)=>{
    Object.keys(row).map((key)=>{
      if(typeof row[key] === 'object') {
        delete row[key];
      }
    });
    return row;
  })
  const tableFields = Object.keys(tableData[0]);


  // Manual width calc is required because each container is dynamic and data grid can't use % then
  let columnWidth = (((window.innerWidth-300)/2)/(tableFields.length));
  if(fullWidth){
    columnWidth = ((window.innerWidth)/(tableFields.length*2));
  }

  // console.log(tableFields.length, window.innerWidth, columnWidth)

  const columns = Object.keys(data.chart.data[0]).map((label, i)=>{
    // The server data model isn't ideal for this component, sometimes labels are valid and exist
    // Sometimes they need to be inferred from row data.
    if(tableLabels[i]){
      label = tableLabels[i]
    }

    return { field: tableFields[i], headerName: _.capitalize(label), width: columnWidth, headerAlign:'center', sortable: true }
  });


  // Remove funnels and other garbage from here
  const rows = data.chart.data.map((data, i)=>{
    let row = { id: i};
    Object.keys(data).forEach((field, ii)=>{
      row[columns[ii]['field']] = data[field]
    })
    return row
  })

  return (
    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <DataGrid rows={rows} columns={columns} pageSize={5} alignLeft checkboxSelection={false} />
    </Card>
  );
}

export default ReportTable;
