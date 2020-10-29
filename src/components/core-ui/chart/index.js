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


const Chart = ({id, title, data}) => {
  // console.log(type)
  const useStyles = makeStyles((theme) => ({
    root: {
      paddingTop: '25px',
      width: data.chart.type === "doughnut" ? "400px" : "100%",
      height: data.chart.type === "doughnut" ? "250px" : "400px",
    },
  }));
  const classes = useStyles();
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);


  let chartConfig = lineChartConfig;
  chartConfig.type = data.chart.type;

  if (chartConfig.type === "line") {
    chartConfig = lineChartConfig;
  } else if (chartConfig.type === "doughnut") {
    chartConfig = doughnutChartConfig;
  }

  //Capitalizes the text
  chartConfig.options.title.text =  title.replace(/(^\w{1})|(\s{1}\w{1})/g, match => match.toUpperCase());
  chartConfig.data =  data.chart.data


  console.log('chart', chartConfig.type, data.chart.type)
  useEffect(() => {
    if (chartContainer && chartContainer.current) {
      console.log(chartConfig)
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
  console.log(chartContainer.current)
  return (

    <Card  className={classes.root}>
      <canvas className={"Chart__canvas"+id} ref={chartContainer} />
    </Card>
  );
};

export default Chart;
