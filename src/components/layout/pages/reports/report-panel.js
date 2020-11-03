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
import Spinner from "../../../core-ui/spinner";
// import TabPanel from '../../../core-ui/tab-panel';
import { REPORTS_STORE_NAME } from "../../../../data/reports";
import Chart from "../../../core-ui/chart";
import Stats from "../../../core-ui/stats";
import ReportTable from "../../../core-ui/report-table";
import DatePicker from "../../../core-ui/date-picker";

const useStyles = makeStyles((theme) => ({
  root: {
    position: 'relative',
    marginBottom: theme.spacing(1),
    textAlign: "center",
  },
  container: {
    display: "grid",
    width: '100%',
    gridTemplateColumns: "repeat(10, 25%)",
    gridTemplateRows: "repeat(10, 150px)"
  },
  datePickers:{
    float: 'right',
    display: 'flex',
    right: '0px',
    width: '350px',
    justifyContent: 'flex-end',
    zIndex: '5'
  }
}));

export default ({ reportList, dateChange, startDate, endDate }) => {
  const classes = useStyles();

  const { reports, getReports, isRequesting, isUpdating } = useSelect(
    (select) => {
      const reportNames = Object.values(reportList).map((report)=>{ return report.name
        // console.log(report)
        return report.name
      });

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
    <div className={classes.root}>

      <div className={classes.datePickers}>
        <DatePicker dateChange={dateChange} selectedDate={startDate} label={'start'} id={'start'}/>
        <DatePicker dateChange={dateChange} selectedDate={endDate} label={'end'} id={'end'}/>
      </div>
      <div className={classes.container}>
        {Object.keys(reports).map((reportKey, i) => {
          let title = reportKey.split("_");
          let type = reports[reportKey].chart.type;
          title.shift();
          title = title.join(" ");

          const { gridColumnStart, gridColumnEnd, gridRowStart, gridRowEnd } = reportList[i];

          if (type === "quick_stat") {
            return <Stats title={title} id={reportKey} data={reports[reportKey]}  gridColumnStart={gridColumnStart} gridColumnEnd={gridColumnEnd} gridRowStart={gridRowStart} gridRowEnd={gridRowEnd} />;
          } else if (type === "table") {
            return <ReportTable title={title} id={reportKey} data={reports[reportKey]}  gridColumnStart={gridColumnStart} gridColumnEnd={gridColumnEnd} gridRowStart={gridRowStart} gridRowEnd={gridRowEnd} />;
          } else if(type === "doughnut" || type === "line") {
            return <Chart title={title} id={reportKey} data={reports[reportKey]} gridColumnStart={gridColumnStart} gridColumnEnd={gridColumnEnd} gridRowStart={gridRowStart} gridRowEnd={gridRowEnd} />;
          } else {
            return <Card>{title} No data?</Card>
          }
        })}
      </div>
    </div>
  );
};
