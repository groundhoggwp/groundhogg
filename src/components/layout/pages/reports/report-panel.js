/**
 * External dependencies
 */
import { Fragment, useState } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import { makeStyles } from "@material-ui/core/styles";
import { __ } from "@wordpress/i18n";
import {Card, Container, Grid} from "@material-ui/core";
import Typography from "@material-ui/core/Typography";
import AttachMoneyIcon from '@material-ui/icons/AttachMoney'

/**
 * Internal dependencies
 */
import Spinner from "../../../core-ui/spinner";
import { REPORTS_STORE_NAME } from "../../../../data/reports";
import Chart from "../../../core-ui/chart";
// import StatsCard from "../../../core-ui/stats-card";
import ReportTable from "../../../core-ui/report-table";
import DatePicker from "../../../core-ui/date-picker";


const useStyles = makeStyles((theme) => ({
  datePickers:{
    float: 'right',
    display: 'flex',
    right: '0px',
    width: '350px',
    justifyContent: 'flex-end',
    zIndex: '5'
  }
}));



export default ({ key, reportList, dateChange, startDate, endDate }) => {
  const classes = useStyles();
  const reportNames = Object.values(reportList).map((report)=>{ return report.name
    return report.name
  });
  const { reports, getReports, isRequesting, isUpdating } = useSelect(
    (select) => {

      const store = select(REPORTS_STORE_NAME);
      return {
        reports: store.getItems({
          reports: reportNames,
          start: startDate,
          end: endDate,
        }),
        getReports: store.getItem,
        isRequesting: store.isItemsRequesting(),
        isUpdating: store.isItemsUpdating(),
      };
    }
  );

  // This needs to be re-factored, its an issue in the data store
  // console.log('before manual fix', reports.length)
  Object.keys(reports).forEach((reportName)=>{
    // console.log(reportName, reportList)
    // console.log(reportName, reportNames.includes(reportName))
    if(!reportNames.includes(reportName)){
      delete reports[reportName]
    }
  })

  // console.log('result', reports.length)

  if (typeof reports === "undefined") {
    return null;
  }

  if (reports.length === 0) {
    return null;
  }

  if (isRequesting || isUpdating) {
    return <Spinner />;
  }

  return (
    <>
        {/*<Header />*/}
        <div className={classes.datePickers}>
          <DatePicker dateChange={dateChange} selectedDate={startDate} label={'start'} id={'start'}/>
          <DatePicker dateChange={dateChange} selectedDate={endDate} label={'end'} id={'end'}/>
        </div>
        <Grid
          container
          spacing={3}
        >


        {Object.keys(reports).map((reportKey, i) => {
          let title = reportKey.split("_");
          let type = reports[reportKey].chart.type;
          title.shift();
          title = title.join(" ");

          if(reportList[i]){
            const { gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd, fullWidth } = reportList[i];

            if (type === "quick_stat") {
              // return <StatsCard title={title} id={reportKey} data={reports[reportKey]} icon={<AttachMoneyIcon />} />;
            } else if (type === "table") {
              return <ReportTable title={title} id={reportKey} data={reports[reportKey]} fullWidth={fullWidth}/>;
            } else if(type === "doughnut" || type === "line" || type === "bar" ) {
              return <Chart title={title} id={reportKey} data={reports[reportKey]} />;
            } else {

            }
          }

          return   <Grid
              item
              lg={3}
              sm={6}
              xs={12}
            >
              <Card>{title} No data?</Card>
            </Grid>
        })}

      </Grid>
      </>


  );
};
