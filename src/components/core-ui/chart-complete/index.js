/**
 * External dependencies
 */
import React, { useEffect, useState, useRef } from 'react';
import { makeStyles } from '@material-ui/core/styles';
import Chartjs from 'chart.js';

const useStyles = makeStyles((theme) => ({
  root: {
    width: 500
  },
}));


const lineChartConfig = {
  // type: props.type,
  data: {
      labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
      datasets: [{
          label: '# of Votes',
          data: [12, 19, 3, 5, 2, 3],
          backgroundColor: [
              "rgba(232, 116, 59 , 0.5 )",
              "rgba(88, 153, 218 , 0.5 )",
              "rgba(25, 169, 121 , 0.5 )",
              "rgba(237, 74, 123 , 0.5 )",
              "rgba(19, 164, 180 , 0.5 )"
          ],
          borderColor: [
              "rgba(232, 116, 59 , 1)",
              "rgba(88, 153, 218 , 1)",
              "rgba(25, 169, 121 , 1)",
              "rgba(237, 74, 123 , 1)",
              "rgba(19, 164, 180 , 1)"
          ],
          borderWidth: 1
      }]
  },
  options: {
          // responsive: true,
              scales: {
                  yAxes: [{
                      ticks: {
                          beginAtZero: true
                      },
                      scaleLabel: {
              display:     true,
                          labelString: 'value'
                      }
                  }]
              }
  }
};
const doughnutChart = {
  // type: props.type,
  data: {
      labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
      datasets: [{
          label: '# of Votes',
          data: [12, 19, 3, 5, 2, 3],
          backgroundColor: [
              "rgba(232, 116, 59 , 0.5 )",
              "rgba(88, 153, 218 , 0.5 )",
              "rgba(25, 169, 121 , 0.5 )",
              "rgba(237, 74, 123 , 0.5 )",
              "rgba(19, 164, 180 , 0.5 )"
          ],
          borderColor: [
              "rgba(232, 116, 59 , 1)",
              "rgba(88, 153, 218 , 1)",
              "rgba(25, 169, 121 , 1)",
              "rgba(237, 74, 123 , 1)",
              "rgba(19, 164, 180 , 1)"
          ],
          borderWidth: 1
      }]
  },
  options: {
  }
};

const Chart = (props) => {
  const classes = useStyles();
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);
  console.log(props)

  let chartConfig = lineChartConfig
  if(props === 'line'){
    chartConfig = lineChartConfig

  } else if(props === 'doughnut'){
    chartConfig = doughnutChart
  }

  chartConfig.type = props.type

  // console.log(prop)




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
