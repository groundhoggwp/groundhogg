import React from 'react';
import clsx from 'clsx';
import PropTypes from 'prop-types';
import PerfectScrollbar from 'react-perfect-scrollbar';
import {
  Box,
  Card,
  CardHeader,
  CardContent,
  Divider,
  makeStyles
} from '@material-ui/core';
// import GenericMoreButton from 'src/components/GenericMoreButton';
import Chart from './Chart';

const useStyles = makeStyles(() => ({
  root: {},
  chart: {
    height: '100%'
  }
}));

export const LineChart = ({ className, title, data, loading, ...rest }) => {

  console.log('line chart', loading, data)
  if(loading || !data){
    return <div/>
  }
  if(loading || !data.chart){
    return <div/>
  }

  const classes = useStyles();
  const chartJSData = {
    data: data.chart.data.datasets,
    labels: data.chart.data.datasets[0].data.map((datum)=>{
      return datum.t
    })
  };

  return (
    <Card
      className={clsx(classes.root, className)}
      {...rest}
    >
      <CardHeader
        action={<div />}
        title={title}
      />
      <Divider />
      <CardContent>
        <PerfectScrollbar>
          <Box
            height={375}
            minWidth={500}
          >
            <Chart
              className={classes.chart}
              datasets={chartJSData.data}
              labels={chartJSData.labels}
            />
          </Box>
        </PerfectScrollbar>
      </CardContent>
    </Card>
  );
};

LineChart.propTypes = {
  className: PropTypes.string
};
