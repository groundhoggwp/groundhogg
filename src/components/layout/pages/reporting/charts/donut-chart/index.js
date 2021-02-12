// import {registerChartType} from 'data/reports-registry'

import React, { useState, useEffect, useCallback } from "react";
import clsx from "clsx";
import {
  Box,
  Card,
  CardHeader,
  Divider,
  Typography,
  makeStyles,
} from "@material-ui/core";
import Chart from "./Chart";
import PerfectScrollbar from "react-perfect-scrollbar";
import { LoadingReport } from "../loading-report";
import {useDispatch} from "@wordpress/data";
import {REPORTS_STORE_NAME} from "../../../../../../data";

const useStyles = makeStyles((theme) => ({
  root: {},
  item: {
    textAlign: "center",
    flexGrow: 1,
    display: "flex",
    flexDirection: "column",
    justifyContent: "center",
    padding: theme.spacing(3, 2),
    "&:not(:last-of-type)": {
      borderRight: `1px solid ${theme.palette.divider}`,
    },
  },
}));


export const DropDown = ( {data , selectionChange} )=>{

  if (!data || !data.hasOwnProperty('chart') ) {
    return  <div/>;
  }

 if(!data.chart) {
   console.log(data.chart);
return  <div/>
 }


  return (
      <select onChange={selectionChange} >
        {Object.entries(data.chart).map((obj , i)=>{
          return  <option value={obj[0]}> {obj[1]}</option>
        })}
      </select>
  )

}



export const DonutChart =(props) => {
  const {
    className,
    title,
    icon,
    loading,
    dropdown,
      id,
    startDate,
    endDate,
    ...rest
  } = props;

  if (loading || !props.hasOwnProperty('data') || !props.data ||!props.data.hasOwnProperty("chart")) {
    return <LoadingReport className={className} title={title} />;
  }

  const classes = useStyles();

  const [data ,setData ] = useState(props.data);
  const { fetchItems } = useDispatch(REPORTS_STORE_NAME);

  const handleSelectionChange = (e) =>{
    // make get request
    console.log(e.target.value)

    // make get request and set data
    fetchItems({
      // reports: [],
      reports:[id],
      start: startDate,
      end: endDate,
    }).then((results) => {

        setData(results.items[id]);

    });


  }



  return (
    <Card className={clsx(classes.root, className)} {...rest}>
      <CardHeader title={title} />
      <Divider />

      {dropdown? <DropDown data={dropdown} selectionChange={handleSelectionChange} /> : <div />}

      <Box p={3} position="relative" minHeight={320}>
        <Chart data={data.chart.data} />
      </Box>
      <Divider />

      <PerfectScrollbar>
        <Box display="flex">
          {data.chart.data.labels.map((label, i) => (
            <div key={label} className={classes.item}>
              <Typography variant="h4" color="textPrimary">
                {data.chart.data.datasets[0].data[i]}
              </Typography>
              <Typography variant="overline" color="textSecondary">
                {label}
              </Typography>
            </div>
          ))}
        </Box>
      </PerfectScrollbar>
    </Card>
  );
}

export default DonutChart;
