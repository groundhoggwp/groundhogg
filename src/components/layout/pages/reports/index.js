/**
 * External dependencies
 */
import { Fragment, useState } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import { makeStyles } from "@material-ui/core/styles";
import { __ } from "@wordpress/i18n";
import Card from "@material-ui/core/Card";
import Typography from "@material-ui/core/Typography";
import { DateTime } from 'luxon';

/**
 * Internal dependencies
 */
import Spinner from "../../../core-ui/spinner";
import TabPanel from "../../../core-ui/tab-panel";
import { REPORTS_STORE_NAME } from "../../../../data/reports";
import Chart from "../../../core-ui/chart";
import Stats from "../../../core-ui/stats";
import DatePicker from "../../../core-ui/date-picker";
import ReportPanel from "./report-panel.js";

const useStyles = makeStyles((theme) => ({
  container: {
    marginBottom: theme.spacing(1),
    textAlign: "center",
  },
}));


export function Reports(props) {
  const classes = useStyles();

  const [startDate, setStartDate] = useState(DateTime.local().minus({ years: 1 }).startOf('day').toISODate());
  const [endDate, setEndDate] = useState(DateTime.local().startOf('day').toISODate());

  const dateChange = (id, e)  => {
    if (id === 'start'){
      setStartDate(e.target.value);
    } else {
      setEndDate(e.target.value);
    }
  }

  const tabs = [
    {
      label: __("Overview"),
      component: (classes) => {
        return (
          <ReportPanel
            startDate={startDate}
            endDate={endDate}
            dateChange={dateChange}
            reportList={[
              {
               name:"chart_new_contacts",
               gridColumnStart: 1,
               gridColumnEnd: 4,
               gridRowStart: 1,
               gridRowEnd: 4,
              },
              {
               name:"total_new_contacts",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 1,
               gridRowEnd: 1
              },
              {
               name:"total_confirmed_contacts",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 2,
               gridRowEnd: 2
              },
              {
               name:"total_engaged_contacts",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 3,
               gridRowEnd: 3,
              },
              {
               name:"total_unsubscribed_contacts",
               gridColumnStart: 1,
               gridColumnEnd: 1,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"total_emails_sent",
               gridColumnStart: 2,
               gridColumnEnd: 2,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"email_open_rate",
               gridColumnStart: 3,
               gridColumnEnd: 3,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"email_click_rate",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"table_top_converting_funnels",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 5,
               gridRowEnd: 8,
              },
              {
               name:"table_contacts_by_countries",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 5,
               gridRowEnd: 8,
              },
              {
               name:"table_contacts_by_lead_source",
               gridColumnStart: 1,
               gridColumnEnd: 5,
               gridRowStart: 8,
               gridRowEnd: 12,
              },
            ]}
          />
        );
      },
    },
    // {
    //   label: __("Contacts"),
    //   component: (classes) => {
    //     return (
    //       <ReportPanel
    //         startDate={startDate}
    //         endDate={endDate}
    //         dateChange={dateChange}
    //         reportList={[
    //           // "chart_new_contacts",
    //           // "total_new_contacts",
    //           // "total_confirmed_contacts",
    //           // "total_engaged_contacts",
    //           // "total_unsubscribed_contacts",
    //           // "chart_contacts_by_optin_status",
    //           // "table_contacts_by_lead_source",
    //           // "table_contacts_by_countries",
    //           // "chart_contacts_by_region",
    //           // "table_contacts_by_search_engines",
    //           // "table_contacts_by_social_media",
    //           // "table_contacts_by_source_page",
    //           // "table_list_engagement",
    //         ]}
    //       />
    //     );
    //   },
    // },
    // {
    //   label: __("Email"),
    //   component: (classes) => {
    //     return (
    //       <ReportPanel
    //         startDate={startDate}
    //         endDate={endDate}
    //         dateChange={dateChange}
    //         reportList={[
    //           // "chart_email_activity",
    //           // "total_emails_sent",
    //           // "email_open_rate",
    //           // "email_click_rate",
    //           // "total_unsubscribed_contacts",
    //           // "total_spam_contacts",
    //           // "total_bounces_contacts",
    //           // "total_complaints_contacts",
    //           // "table_top_performing_broadcasts",
    //           // "chart_last_broadcast",
    //           // "table_top_performing_emails",
    //           // "table_worst_performing_emails",
    //         ]}
    //       />
    //
    //     );
    //   },
    // },
    // {
    //   label: __("Funnels"),
    //   component: () => {
    //     return <ReportPanel
    //       startDate={startDate}
    //       endDate={endDate}
    //       dateChange={dateChange}
    //       reportList={[
    //         // "chart_funnel_breakdown",
    //         // "total_contacts_in_funnel",
    //         // "total_funnel_conversion_rate",
    //         // "total_abandonment_rate",
    //         // "table_top_performing_emails",
    //         // "table_worst_performing_emails",
    //         // "total_benchmark_conversion_rate",
    //         // "table_form_activity"
    //       ]}
    //     />
    //   },
    // },
    // {
    //   label: __("Broadcasts"),
    //   component: () => {
    //     return <ReportPanel
    //       startDate={startDate}
    //       endDate={endDate}
    //       dateChange={dateChange}
    //       reportList={[
    //         // "table_broadcast_stats",
    //         // "table_broadcast_link_clicked",
    //       ]}
    //     />
    //   },
    // },
    // {
    //   label: __("Forms"),
    //   component: () => {
    //     return <ReportPanel
    //       startDate={startDate}
    //       endDate={endDate}
    //       dateChange={dateChange}
    //       reportList={[
    //         "table_form_activity"
    //       ]}
    //     />
    //   },
    // },
    // {
    //   label: __("Pipeline"),
    //   component: () => {
    //     return <ReportPanel
    //       startDate={startDate}
    //       endDate={endDate}
    //       dateChange={dateChange}
    //       reportList={[
    //         "table_form_activity"
    //       ]}
    //     />
    //   },
    // }
  ];

  return <TabPanel tabs={tabs} />;
}
