const doughnutChartConfig = {
  type: 'doughnut',
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
    responsive: false,
    title: {
      display: true,
      text: '',
      fontFamily:  'Roboto, Helvetica, Arial, sans-serif',
      // fontSize: '18px',
      fontWeight: '100'

    },
    legend: {
      position: 'right'
    },
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

export default doughnutChartConfig
