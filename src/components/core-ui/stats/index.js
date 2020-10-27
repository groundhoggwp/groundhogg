/**
 * External dependencies
 */
import { Fragment, useState } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import { makeStyles } from "@material-ui/core/styles";
import { __ } from "@wordpress/i18n";
import Card from "@material-ui/core/Card";
import Typography from "@material-ui/core/Typography";
import ArrowDropUpIcon from '@material-ui/icons/ArrowDropUp';
import ArrowDropDownIcon from '@material-ui/icons/ArrowDropDown';

/**
 * Internal dependencies
 */

const useStyles = makeStyles((theme) => ({
  root: {
    position: 'relative',
    width: "300px",
    height: "150px",
    margin: "10px",
    // height: type === "doughnut" ? "700px" : "400px",
  },
  title: {
    display: 'block',
    fontSize: "18px",
    textTransform: "capitalize",
    padding: "10px 5px 5px 10px",
    color: '#ffffff',
    background: '#DB741A',
    marginBottom: '10px'
  },
  current: {
    fontSize: "50px",
    fontWeight: 900
  },
  compareArrow: {
    position: 'absolute',
    left: '-7px',
    bottom: '-15px',
    fontSize: '50px'
  },
  compare: {
    position: 'absolute',
    left: '35px',
    bottom: '0',
    fontSize: "12px",
    // fontWeight: 900
  },
}));

const Stats = ({ title, data }) => {
  const classes = useStyles();

  const { type} = data;
  const { current, compare } = data.chart.data;
  const { number } = data.chart.number;

  const { text,  percent } = data.chart.compare;
  const { direction, color } = data.chart.compare.arrow;

  let arrow = direction === "up" ? <ArrowDropUpIcon style={{color}} className={classes.compareArrow}/> : <ArrowDropDownIcon style={{color}} className={classes.compareArrow}/>;

  return (
    <Card className={classes.root}>
      <div
        className={classes.title}
      >
        {title}
      </div>
      <div
        className={classes.current}
      >
        {current}
      </div>

      <div
        className={classes.currentMetric}
      >
        {number}
      </div>

        {arrow}
      <div className={classes.compare}>
        {percent}{text}
      </div>
    </Card>
  );
  // return (
  //   <Card className="groundhogg-state-card">
  //       <div className={"groundhogg-stat-card-body"}>
  //           <div className="groundhogg-quick-stat">
  //               <div className="groundhogg-quick-stat-title">{report.data.title} </div>
  //               <div className="groundhogg-quick-stat-number">{report.data.chart.number}</div>
  //               <div className={arrow_color}>
  //                   <span className={arrow}/>
  //                   <span
  //                       className="groundhogg-quick-stat-prev-percent">{report.data.chart.compare.percent}</span>
  //               </div>
  //               <div className="groundhogg-quick-stat-compare">{report.data.chart.compare.text}</div>
  //           </div>
  //       </div>
  //   </Card>
  // );
};

export default Stats;
