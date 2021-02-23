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
    dropdown,
  ...rest
}) => {
  if (loading || !data || !data.hasOwnProperty("chart")) {
    return <LoadingReport className={className} title={title} />;
  }

  const classes = useStyles();

  return (
    <Card className={clsx(classes.root, className)} {...rest}>
      <CardHeader title={title} />
      <Divider />
      {dropdown ? dropdown : '' }
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
};

export default DonutChart;
