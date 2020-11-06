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
import lineChartConfig from './chart-config/line-chart-config.js'
import doughnutChartConfig from './chart-config/doughnut-chart-config.js'


const Chart = ({id, title, data, gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd}) => {
  // console.log(type)
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
      fontSize: '28px',
      position: 'absolute',
      textTransform:'capitalize',
      top: '-50px',
      left: '37px',
      fontWeight: '700'

    }
  }));
  const classes = useStyles();
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);


  let chartConfig;
  if (data.chart.type === "line") {
    chartConfig = lineChartConfig;
  } else if (data.chart.type === "doughnut") {
    chartConfig = doughnutChartConfig;
  }

  //Capitalizes the text
  // chartConfig.options.title.text =  title.replace(/(^\w{1})|(\s{1}\w{1})/g, match => match.toUpperCase());
  chartConfig.data =  data.chart.data


  // console.log('chart', chartConfig.type, data.chart.type)
  useEffect(() => {
    if (chartContainer && chartContainer.current) {
      console.log('config fire', data.chart.type, chartConfig.type, newChartInstance, chartInstance)
      const newChartInstance = new Chartjs(chartContainer.current, chartConfig);
      setChartInstance(newChartInstance);

      // Vertical line blurb
      // //Vertical line
      // $(document).ready(function(){
      //     $("#myChart").on("mousemove", function(evt) {
      //         var element = $("#cursor"),
      // 				offsetLeft = element.offset().left,
      // 				domElement = element.get(0),
      // 				clientX = parseInt(evt.clientX - offsetLeft),
      // 				ctx = element.get(0).getContext('2d');
      //
      //         ctx.clearRect(0, 0, domElement.width, domElement.height),
      //             ctx.beginPath(),
      //             ctx.moveTo(clientX, 0),
      //             ctx.lineTo(clientX, domElement.height),
      //             ctx.setLineDash([10, 10]),
      //             ctx.strokeStyle = "#333",
      //             ctx.stroke()
      //     });
      // });
    }
  }, [chartContainer]);
  // console.log(chartContainer.current)
  return (

    <Card className={classes.root}>
      <div className={classes.title}>{title}</div>
      <canvas className={"Chart__canvas"+id} ref={chartContainer} />
    </Card>
  );
};

export default Chart;
