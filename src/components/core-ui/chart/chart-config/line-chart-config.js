const lineChartConfig = {
    type: 'line',
    data: {
      labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
      datasets: [{
          label: '# of Votes',
          data: [12, 19, 3, 5, 2, 3],
          backgroundColor: [
              'rgba(255, 99, 132, 0.2)',
              'rgba(54, 162, 235, 0.2)',
              'rgba(255, 206, 86, 0.2)',
              'rgba(75, 192, 192, 0.2)',
              'rgba(153, 102, 255, 0.2)',
              'rgba(255, 159, 64, 0.2)'
          ],
          borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(54, 162, 235, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)',
              'rgba(255, 159, 64, 1)'
          ],
          borderWidth: 1
      }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    title: {
      display: true,
      text: '',
      fontFamily:  'Roboto, Helvetica, Arial, sans-serif',
      // fontSize: '18px',
      fontStyle: 'normal',
      fontWeight: '100'
    },
    legend: {
      position: 'bottom',
      fontFamily:  'Roboto, Helvetica, Arial, sans-serif'
    },
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
      // mode: "nearest",
      axis: 'x',
      intersect: true,
    },
    scales : {
      xAxes: [{
          type: "time",
          time: {
              parser: "YYY-MM-DD HH:mm:ss",
              tooltipFormat: "l HH:mm"
          },
          scaleLabel: {
              display: true,
              labelString: "Date"
          },
          gridLines: {display: false},
      }],
      yAxes: [{
          scaleLabel: {
              display: true,
              labelString: "Numbers"
          }
      }]
    },
    // scales: {
    //   x: {
    //     display: true,
    //     scaleLabel: {
    //       display: true,
    //       labelString: "Month",
    //     },
    //   },
    //   y: {
    //     display: true,
    //     scaleLabel: {
    //       display: true,
    //       labelString: "Value",
    //     },
    //     min: 0,
    //     max: 100,
    //     ticks: {
    //       // forces step size to be 5 units
    //       stepSize: 5,
    //     },
    //   },
    // },
  }
};

export default lineChartConfig;
