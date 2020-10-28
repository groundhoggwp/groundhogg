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
    position: 'relative',
    marginBottom: theme.spacing(1),
    textAlign: "center",
    // paddingTop: theme.spacing(4),
    // paddingBottom: theme.spacing(4),
  },
  container: {
    // display: "flex",
    // flexWrap: "wrap",
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
        // {
        //     "items": [
        //         "total_new_contacts",
        //         "total_confirmed_contacts",
        //         "total_engaged_contacts",
        //         "total_unsubscribed_contacts",
        //         "total_emails_sent",
        //         "email_open_rate",
        //         "email_click_rate",
        //         "chart_new_contacts",
        //         "chart_email_activity",
        //         "chart_funnel_breakdown",
        //         "chart_contacts_by_optin_status",
        //         "chart_contacts_by_region",
        //         "chart_contacts_by_country",
        //         "chart_last_broadcast",
        //         "table_contacts_by_lead_source",
        //         "table_contacts_by_search_engines",
        //         "table_contacts_by_social_media",
        //         "table_contacts_by_source_page",
        //         "table_contacts_by_countries",
        //         "table_top_performing_emails",
        //         "table_worst_performing_emails",
        //         "table_top_performing_broadcasts",
        //         "total_spam_contacts",
        //         "total_bounces_contacts",
        //         "total_complaints_contacts",
        //         "total_contacts_in_funnel",
        //         "total_funnel_conversion_rate",
        //         "total_benchmark_conversion_rate",
        //         "total_abandonment_rate",
        //         "table_broadcast_stats",
        //         "table_broadcast_link_clicked",
        //         "table_benchmark_conversion_rate",
        //         "table_top_converting_funnels",
        //         "table_form_activity",
        //         "table_email_stats",
        //         "table_email_links_clicked",
        //         "chart_donut_email_stats",
        //         "table_funnel_stats",
        //         "table_email_funnels_used_in",
        //         "table_list_engagement",
        //         "ddl_funnels",
        //         "ddl_region",
        //         "ddl_broadcasts"
        //     ],
        //     "status": "success"
        // }

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
            return <Stats title={title} data={reports[reportKey]} />;
          } else {
            return <Chart title={title} data={reports[reportKey]} />;
          }
        })}
      </div>
    </div>
  );

  // );
};
