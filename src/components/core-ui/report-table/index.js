import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import Table from '@material-ui/core/Table';
import TableBody from '@material-ui/core/TableBody';
import TableCell from '@material-ui/core/TableCell';
import TableContainer from '@material-ui/core/TableContainer';
import TableHead from '@material-ui/core/TableHead';
import TableRow from '@material-ui/core/TableRow';
import Paper from '@material-ui/core/Paper';
import Card from "@material-ui/core/Card";

const useStyles = makeStyles({
  root:{
    position:'relative',
    paddingTop: '53px',
    width: 'calc(50% - 20px)',
    display: 'inline-block',
    margin: '10px'
  },
  table: {
    // minWidth: 650,
    width: '100%'
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

export default function ReportTable({title, data}) {
  const classes = useStyles();


  if(!data.chart.data){
    return(<div/>)
  }
  if(data.chart.data.length === 0){
    return(<div/>)
  }

  const headers = data.chart.label.map((label)=>{
    console.log(label)
    if(typeof(value)=== 'object'){
      'Conversation Rate'
    }
    return label
  });

  const rows = data.chart.data.slice(0,5)

  // console.log(headers)

  return (
    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <TableContainer component={Paper}>
        <Table className={classes.table} aria-label="simple table">
          <TableHead>
            <TableRow>
              {headers.map((header) => (
                <TableCell>{header}</TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
          {
            rows.map(row => {
              return (<TableRow>
                  {Object.values(row).map((value)=> {
                    if(typeof(value)=== 'object'){
                      // console.log(value)
                      return <TableCell>{value.data.title}</TableCell>
                    } else {
                      return <TableCell>{value}</TableCell>
                    }
                  })}
                </TableRow>)
            })
          }
          </TableBody>
        </Table>
      </TableContainer>
    </Card>
  );
}
