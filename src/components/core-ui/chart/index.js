import React, { useState, useEffect, useRef } from 'react';
import Chartjs from 'chart.js';

let chartConfig = {
  // type: 'bar',
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
    // ...
  }
};

const Chart = (props) => {
  const chartContainer = useRef(null);
  const [chartInstance, setChartInstance] = useState(null);
  console.log(props)
  useEffect(() => {
    chartConfig.type = props.type
    if (chartContainer && chartContainer.current) {
      const newChartInstance = new Chartjs(chartContainer.current, chartConfig);
      console.log(chartConfig)
      setChartInstance(newChartInstance);
    }
  }, [chartContainer]);

  return (
    <div>
      <canvas ref={chartContainer} />
    </div>
  );
};

export default Chart;
