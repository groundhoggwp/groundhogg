import React from 'react';
import clsx from 'clsx';
import PropTypes from 'prop-types';
import {
  Avatar,
  Box,
  Card,
  Typography,
  makeStyles
} from '@material-ui/core';
import Label from '../../../../../core-ui/label/';

const useStyles = makeStyles((theme) => ({
  root: {
    padding: theme.spacing(3),
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between'
  },
  label: {
    marginLeft: theme.spacing(1)
  },
  avatar: {
    backgroundColor: theme.palette.secondary.main,
    color: theme.palette.secondary.contrastText,
    height: 48,
    width: 48
  }
}));

const dummyData = {
  chart : {
    data : [

    ],
    number : 1,
    compare: {
      percent: '99%'
    }
  }
}

export const QuickStat = ({ className,  title, data, loading, ...rest }) => {
  // export const QuickStat = ({ className, title, data, icon, ...rest }) => { 

  const icon = <div/>;

  const chartData = loading ? dummyData : data

  const classes = useStyles();

  const { current, compare } = chartData.chart.data;
  const { number } = chartData.chart.number;

  let { percent } = chartData.chart.compare;
  percent = parseInt(percent.replace('%', ''));

  return (
    <Card
      className={clsx(classes.root, className)}
      {...rest}
    >
      <Box flexGrow={1}>
        <Typography
          component="h3"
          gutterBottom
          variant="overline"
          color="textSecondary"
        >
          {title}
        </Typography>
        <Box
          display="flex"
          alignItems="center"
          flexWrap="wrap"
        >
          <Typography
            variant="h3"
            color="textPrimary"
          >
            {data.currency}
            {current}
          </Typography>
          <Label
            className={classes.label}
            color={percent > 0 ? 'success' : 'error'}
          >
            {percent > 0 ? '+' : '-'}
            {percent}
            %
          </Label>
        </Box>
      </Box>
      <Avatar className={classes.avatar}>
        {icon}
      </Avatar>
    </Card>
  );
};

QuickStat.propTypes = {
  className: PropTypes.string,
  title: PropTypes.string,
  data: PropTypes.object,
  loading: PropTypes.Boolean,
  icon: PropTypes.object
};

export default QuickStat;
