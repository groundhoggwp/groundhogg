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
  return <div/>;
  if (loading) {
    return <div />;
  }

  const classes = useStyles();

  return (
    <Card className={clsx(classes.root, className)} {...rest}>

    </Card>
  );
};

export default DonutChart;
