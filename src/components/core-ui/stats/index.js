/**
 * External dependencies
 */
import { Fragment, useState } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import { makeStyles } from "@material-ui/core/styles";
import { __ } from "@wordpress/i18n";
import Card from "@material-ui/core/Card";
import Typography from "@material-ui/core/Typography";

/**
 * Internal dependencies
 */

const useStyles = makeStyles((theme) => ({
  root: {
    width: "300px",
    height: "150px",
    margin: "10px",
    // height: type === "doughnut" ? "700px" : "400px",
  },
  title: {
    fontSize: "24px",
    fontStyle: "capitalize",
  },
  current: {
    fontSize: "24px",
  },
}));

const Stats = ({ title, data }) => {
  const classes = useStyles();

  // const {title} = props;
  const { type, chart } = data;
  const { current, compare } = data.chart.data;
  const { number } = data.chart.number;
  const { text } = data.chart.compare.text;
  const { direction, color } = data.chart.compare.arrow;
  // console.log('stats', data, title, type)
  console.log(title, data, direction, color);
  let arrow = direction === "up" ? "^" : "___";

  return (
    <Card className={classes.root}>
      <Typography
        className={classes.title}
        component="h1"
        color="textSecondary"
      >
        {title}
      </Typography>
      <Typography
        className={classes.current}
        component="div"
        color="textSecondary"
      >
        {current}
      </Typography>
      <Typography
        className={classes.currentMetric}
        component="div"
        color="textSecondary"
      >
        {compare}
      </Typography>
      <Typography
        className={classes.currentMetric}
        component="div"
        color="textSecondary"
      >
        {number}
      </Typography>
      <Typography
        className={classes.currentMetric}
        component="div"
        color="textSecondary"
      >
        {text}
      </Typography>
      <div>{arrow}</div>
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
