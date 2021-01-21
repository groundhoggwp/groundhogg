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
import Label from '../Label/';

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

const StatsCard = ({ className, title, data, icon, ...rest }) => {
  const classes = useStyles();

  const { type} = data;
  const { current, compare } = data.chart.data;
  const { number } = data.chart.number;

  let { text,  percent } = data.chart.compare;
  const { direction, color } = data.chart.compare.arrow;


  percent = parseInt(percent.replace('%', ''));

  // const percentPosition = (-15*(percent.length-1)-5)+'px';
  // const arrow = direction === "up" ? <ArrowDropUpIcon style={{color}} className={classes.compareArrow}/> : <ArrowDropDownIcon style={{color}} className={classes.compareArrow}/>;

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

StatsCard.propTypes = {
  className: PropTypes.string
};

export default StatsCard;
