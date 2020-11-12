/**
 * External dependencies
 */
import React, { useEffect, useState, useRef } from "react";
import { makeStyles } from "@material-ui/core/styles";
import Card from "@material-ui/core/Card";
/**
 * Internal dependencies
 */
import Chartjs from "chart.js";
import barChartConfig from './chart-config/bar-chart-config.js'
import lineChartConfig from './chart-config/line-chart-config.js'
import doughnutChartConfig from './chart-config/doughnut-chart-config.js'


const Chart = ({id, title, data, gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd}) => {
  const useStyles = makeStyles((theme) => ({
    root: {
      position: 'relative',
      overflow: 'visible',
      gridColumnStart,
      gridColumnEnd,
      gridRowStart,
      gridRowEnd,
    },
    title: {
      fontSize: gridRowStart === 1 ? '28px' : '18px',
      position: 'absolute',
      textTransform:'capitalize',
      top: gridRowStart === 1 ? '-50px' : '10px',
      left: gridRowStart === 1 ? '37px' : '25px',
      fontWeight: '700'
    }
  }));
  const classes = useStyles();
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);

  let chartConfig;
  let canvasHeight;
  if (data.chart.type === "line") {
    chartConfig = lineChartConfig;
  } else if (data.chart.type === "bar") {
    chartConfig = barChartConfig;
  } else if (data.chart.type === "doughnut") {
    chartConfig = doughnutChartConfig;
    canvasHeight = 145
  }

  // Remove and just set the data in the future, my local shows about 213 labels for this
  if (data.chart.type === "doughnut") {
    let labels = data.chart.data.labels.splice(0,10);
    let backgroundColor = data.chart.data.datasets[0].backgroundColor.splice(0,10);
    let dataNameSpace = data.chart.data.datasets[0].data.splice(0,10);
    chartConfig.data = {
      labels,
      datasets: [{"backgroundColor":backgroundColor, "data":dataNameSpace}]
    }
  } else {
    chartConfig.data =  data.chart.data;
  }

  useEffect(() => {
    if (chartContainer && chartContainer.current) {
      const newChartInstance = new Chartjs(chartContainer.current, chartConfig);
    }
  }, [chartContainer]);

  return (
    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <canvas height={canvasHeight} className={`Chart__canvas ${id}`} ref={chartContainer} />
    </Card>
  );
};

export default Chart;
