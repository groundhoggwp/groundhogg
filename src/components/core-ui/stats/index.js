/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
import Card from '@material-ui/core/Card';
import Typography from '@material-ui/core/Typography';

/**
 * Internal dependencies
 */

 const useStyles = makeStyles((theme) => ({
   root: {
     width: "100%",
     // height: props.type === "doughnut" ? "700px" : "400px",
   },
   kpiTitle:{
     fontSize: '24px'
   },
   kpiMetric:{
     fontSize: '24px'
   }
 }));

const Stats = (props) => {

  const classes = useStyles();
  // const chartContainer = useRef(null);
  // const [chartInstance, setChartInstance] = useState(null);
  // console.log(props);

  // <Card className={classes.container}><Chart type='line'/></Card>
  // <Card className={classes.container}>
  //   <Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
  //   <Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
  // </Card>
  return (
    <Fragment>
asdasd
    </Fragment>
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
