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
import DatePicker from "../../../core-ui/date-picker";

const useStyles = makeStyles((theme) => ({
  root: {
    display: "flex",
    flexWrap: "wrap",
    position: 'relative',
    marginBottom: theme.spacing(1),
    textAlign: "center",
  },
  container: {

  },
  datePickers:{
    position: 'absolute',
    display: 'flex',
    right: '0px',
    width: '350px',
    // display: 'flex',
    // width: '100%',
    justifyContent: 'flex-end'
  }
}));

export default ({ reportList, dateChange }) => {
  // export default ({ID, data, meta, onCancel, onSave}) => {
  const classes = useStyles();

  const [stateTagValue, setTagValue] = useState("");
  const [selectedDate, setSelectedDate] = useState("");

  const { reports, getReports, isRequesting, isUpdating } = useSelect(
    (select) => {
      const store = select(REPORTS_STORE_NAME);
      return {
        reports: store.getItems({
          reports: reportList,
          start: "2015-10-06",
          end: "2020-10-06",
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
  console.log('data', reports)

  return (
    <div className={classes.root}>

      <div className={classes.datePickers}>
        <DatePicker dateChange={dateChange} selectedDate={selectedDate} label={'start'} id={'start'}/>
        <DatePicker dateChange={dateChange} selectedDate={selectedDate} label={'end'} id={'end'}/>
      </div>
      <div className={classes.container}>
        {Object.keys(reports).map((reportKey, i) => {
          let title = reportKey.split("_");
          let type = reports[reportKey].chart.type;
          title.shift();
          title = title.join(" ");
          if (type === "quick_stat") {
            return <Stats title={title} id={reportKey} data={reports[reportKey]} />;
          } else {
            return <Chart title={title} id={reportKey} data={reports[reportKey]} />;
          }
        })}
      </div>
    </div>
  );

  // );
};
