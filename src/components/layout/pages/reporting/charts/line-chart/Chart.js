import React from 'react';
import clsx from 'clsx';
import PropTypes from 'prop-types';
import { Line } from 'react-chartjs-2';
import {
  fade,
  makeStyles,
  useTheme
} from '@material-ui/core';


import { createTheme } from '../../../../../../theme';
const theme = createTheme({});


const useStyles = makeStyles(() => ({
  root: {
    position: 'relative'
  }
}));

const Chart = ({
  className,
  datasets,
  labels,
  ...rest
}) => {
  const classes = useStyles();

  const data = (canvas) => {


    datasets = datasets.map((dataset, i)=>{
      const ctx = canvas.getContext('2d');
      const gradient = ctx.createLinearGradient(0, 0, 0, 400);


      let lineColor = i === 0 ? theme.palette.primary.main : theme.palette.secondary.main;
      gradient.addColorStop(0, fade(lineColor, 0.2));
      gradient.addColorStop(0.9, 'rgba(255,255,255,0)');
      gradient.addColorStop(1, 'rgba(255,255,255,0)');

      const data = dataset.data
      return   {
          data,
          backgroundColor: gradient,
          borderColor: lineColor,
          pointBorderColor: lineColor,
          pointBorderWidth: 3,
          pointRadius: 6,
          pointBackgroundColor: lineColor
        }
    })
    return {
      datasets,
      labels
    };
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    legend: {
      display: false
    },
    layout: {
      padding: 0
    },
    scales: {
      xAxes: [
        // {
        //     type: 'time',
        //     time: {
        //       displayFormats: {
        //           day: 'MMM D'
        //       }
        //     }
        // },
        {
          gridLines: {
            display: false,
            drawBorder: false
          },
          ticks: {
            padding: 20,
            fontColor: theme.palette.text.secondary,
            callback: (value) => {
                return new Date(value).toLocaleDateString('de-DE', {month:'short', year:'numeric'});
            },
          }
        }
      ],
      yAxes: [
        {
          gridLines: {
            borderDash: [2],
            borderDashOffset: [2],
            color: theme.palette.divider,
            drawBorder: false,
            zeroLineBorderDash: [2],
            zeroLineBorderDashOffset: [2],
            zeroLineColor: theme.palette.divider
          },
          ticks: {
            padding: 20,
            fontColor: theme.palette.text.secondary,
            beginAtZero: true,
            min: 0,
            maxTicksLimit: 7,
            // callback: (value) => (value > 0 ? `${value}K` : value)
          }
        }
      ]
    },
    tooltips: {
      enabled: true,
      mode: 'index',
      intersect: false,
      caretSize: 10,
      yPadding: 20,
      xPadding: 20,
      borderWidth: 1,
      borderColor: theme.palette.divider,
      backgroundColor: theme.palette.background.default,
      titleFontColor: theme.palette.text.primary,
      bodyFontColor: theme.palette.text.secondary,
      footerFontColor: theme.palette.text.secondary,
      callbacks: {
        title: () => {},
        label: (tooltipItem) => {
          return `Contacts: ${tooltipItem.yLabel} ${new Date(tooltipItem.xLabel).toLocaleDateString('de-DE', {month:'short', year:'numeric'})}`;
        }
      }
    }
  };

  return (
    <div
      className={clsx(classes.root, className)}
      {...rest}
    >
      <Line
        data={data}
        options={options}
      />
    </div>
  );
};

Chart.propTypes = {
  className: PropTypes.string,
  data: PropTypes.array.isRequired,
  labels: PropTypes.array.isRequired
};

export default Chart;
