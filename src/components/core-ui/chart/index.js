/**
 * External dependencies
 */
import React, { useEffect, useState, useRef } from "react";
import { makeStyles } from "@material-ui/core/styles";
import Chartjs from "chart.js";

const lineChartConfig = {
  data: {
    labels: ["Red", "Blue", "Yellow", "Green", "Purple", "Orange"],
    datasets: [
      {
        label: "# of Votes",
        data: [12, 19, 3, 5, 2, 3],
        backgroundColor: [
          "rgba(232, 116, 59 , 0.5)",
          "rgba(88, 153, 218 , 0.5)",
          "rgba(25, 169, 121 , 0.5)",
          "rgba(237, 74, 123 , 0.5)",
          "rgba(19, 164, 180 , 0.5)",
        ],
        borderColor: [
          "rgba(232, 116, 59 , 1)",
          "rgba(88, 153, 218 , 1)",
          "rgba(25, 169, 121 , 1)",
          "rgba(237, 74, 123 , 1)",
          "rgba(19, 164, 180 , 1)",
        ],
        borderWidth: 1,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    // title: {
    // display: true,
    // text: 'Chart.js Line Chart'
    // },
    tooltips: {
      mode: "index",
      intersect: false,
      backgroundColor: "#FFF",
      bodyFontColor: "#000",
      borderColor: "#727272",
      borderWidth: 2,
      titleFontColor: "#000",
    },
    hover: {
      mode: "nearest",
      intersect: true,
    },
    scales: {
      x: {
        display: true,
        scaleLabel: {
          display: true,
          labelString: "Month",
        },
      },
      y: {
        display: true,
        scaleLabel: {
          display: true,
          labelString: "Value",
        },
        min: 0,
        max: 100,
        ticks: {
          // forces step size to be 5 units
          stepSize: 5,
        },
      },
    },
  },
};

const doughnutChart = {
  data: {
    labels: ["Red", "Blue", "Yellow", "Green", "Purple"],
    datasets: [
      {
        label: "# of Votes",
        data: [12, 19, 3, 5, 2],
        backgroundColor: [
          "rgba(232, 116, 59 , 0.5 )",
          "rgba(88, 153, 218 , 0.5 )",
          "rgba(25, 169, 121 , 0.5 )",
          "rgba(237, 74, 123 , 0.5 )",
          "rgba(19, 164, 180 , 0.5 )",
        ],
        borderColor: [
          "rgba(232, 116, 59 , 1)",
          "rgba(88, 153, 218 , 1)",
          "rgba(25, 169, 121 , 1)",
          "rgba(237, 74, 123 , 1)",
          "rgba(19, 164, 180 , 1)",
        ],
        borderWidth: 1,
      },
    ],
  },
  options: {
    grid: {
      clickable: true,
      hoverable: true,
    },
    tooltips: {
      // mode: 'index',
      // intersect: false,
      backgroundColor: "#FFF",
      bodyFontColor: "#000",
      borderColor: "#727272",
      borderWidth: 2,
      titleFontColor: "#000",
    },
    series: {
      pie: {
        show: true,
        label: {
          show: true,
          radius: 7 / 8,
          formatter: function (label, series) {
            return (
              "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" +
              label +
              " (" +
              Math.round(series.percent) +
              "%)</div>"
            );
          },
          background: {
            // opacity: 0.5,
            // color: '#000'
          },
        },
      },
    },
  },
};

const Chart = (props) => {
  const useStyles = makeStyles((theme) => ({
    root: {
      width: "100%",
      height: props.type === "doughnut" ? "700px" : "400px",
    },
  }));
  const classes = useStyles();
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);
  console.log(props);
  console.log(props.data.chart.type);

  let chartConfig = lineChartConfig;
  let chartType = props.data.chart.type;
  let data = props.data.chart.data;

  if (chartType === "line") {
    chartConfig = lineChartConfig;
  } else if (chartType === "doughnut") {
    chartConfig = doughnutChart;
  }
  chartConfig.type =  chartType
  chartConfig.data =  data



  useEffect(() => {
    if (chartContainer && chartContainer.current) {
      const newChartInstance = new Chartjs(chartContainer.current, chartConfig);
      setChartInstance(newChartInstance);
    }
  }, [chartContainer]);

  return (
    <div className={classes.root}>
      <canvas className="Chart__canvas" ref={chartContainer} />
    </div>
  );
};

export default Chart;
