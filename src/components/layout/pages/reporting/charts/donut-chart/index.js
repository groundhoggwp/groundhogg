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
// import GenericMoreButton from 'src/components/GenericMoreButton';
// import axios from 'src/utils/axios';
import Chart from "./Chart";

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

export const DonutChart = ({
  className,
  title,
  data,
  icon,
  loading,
  ...rest
}) => {


  if(loading){
    return <div/>
  }

  const classes = useStyles();

  return (
    <Card className={clsx(classes.root, className)} {...rest}>
      <CardHeader  title={title} />
      <Divider />
      <Box p={3} position="relative" minHeight={320}>
        <Chart data={data.chart.data} />
      </Box>
      <Divider />
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
    </Card>
  );
};

export default DonutChart;
