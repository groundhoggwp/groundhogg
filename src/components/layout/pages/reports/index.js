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


export function Reports({history, match}) {
  const classes = useStyles();


  const [startDate, setStartDate] = useState(DateTime.local().minus({ years: 1 }).startOf('day').toISODate());
  const [endDate, setEndDate] = useState(DateTime.local().startOf('day').toISODate());

  const dateChange = (id, newValue)  => {
    if (id === 'start'){
      setStartDate(newValue);
    } else {
      setEndDate(newValue);
    }
  }

  const tabs = [
    {
      label: __("Overview"),
      route: __("overview"),
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
               name:"chart_contacts_by_optin_status",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 5,
               gridRowEnd: 7,
              },
              // Busted
              // {
              //  name:"table_top_converting_funnels",
              //  gridColumnStart: 1,
              //  gridColumnEnd: 3,
              //  gridRowStart: 7,
              //  gridRowEnd: 10,
              //  fullWidth: false
              // },
              {
               name:"table_contacts_by_countries",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 5,
               gridRowEnd: 7,
               fullWidth: false
              },
              {
               name:"table_contacts_by_lead_source",
               gridColumnStart: 1,
               gridColumnEnd: 5,
               gridRowStart: 7,
               gridRowEnd: 10,
               fullWidth: true
              },
            ]}
          />
        );
      },
    },
    {
      label: __("Contacts"),
      route: __("contacts"),
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
               gridColumnEnd: 5,
               gridRowStart: 1,
               gridRowEnd: 4,
              },
              {
               name:"total_new_contacts",
               gridColumnStart: 1,
               gridColumnEnd: 1,
               gridRowStart: 4,
               gridRowEnd: 5
              },
              {
               name:"total_confirmed_contacts",
               gridColumnStart: 2,
               gridColumnEnd: 2,
               gridRowStart: 4,
               gridRowEnd: 5
              },
              {
               name:"total_engaged_contacts",
               gridColumnStart: 3,
               gridColumnEnd: 3,
               gridRowStart: 4,
               gridRowEnd: 5
              },
              {
               name:"total_unsubscribed_contacts",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 4,
               gridRowEnd: 5
              },
              {
               name:"chart_contacts_by_optin_status",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 5,
               gridRowEnd: 7,
              },
              {
               name:"table_contacts_by_lead_source",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 5,
               gridRowEnd: 7,
              },
              {
               name:"chart_contacts_by_country",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 7,
               gridRowEnd: 9,
              },
              {
               name:"chart_contacts_by_region",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 7,
               gridRowEnd: 9,
              },
              {
               name:"table_contacts_by_search_engines",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 9,
               gridRowEnd: 12,
              },
              {
               name:"table_contacts_by_social_media",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 9,
               gridRowEnd: 12,
              },
              {
               name:"table_contacts_by_source_page",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 12,
               gridRowEnd: 15,
              },
              // Undefined server side
              {
               name:"table_contacts_by_lead_source",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 12,
               gridRowEnd: 15,
              },
              {
               name:"table_list_engagement",
               gridColumnStart: 1,
               gridColumnEnd: 5,
               gridRowStart: 15,
               gridRowEnd: 18,
              },
            ]}
          />
        );
      },
    },
    {
      label: __("Email"),
      route: __("email"),
      component: (classes) => {
        return (
          <ReportPanel
            startDate={startDate}
            endDate={endDate}
            dateChange={dateChange}
            reportList={[
              {
               name:"chart_email_activity",
               gridColumnStart: 1,
               gridColumnEnd: 4,
               gridRowStart: 1,
               gridRowEnd: 4,
              },
              {
               name:"total_emails_sent",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 1,
               gridRowEnd: 1,
              },
              {
               name:"email_open_rate",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 2,
               gridRowEnd: 2,
              },
              {
               name:"email_click_rate",
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
               name:"total_spam_contacts",
               gridColumnStart: 2,
               gridColumnEnd: 2,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"total_bounces_contacts",
               gridColumnStart: 3,
               gridColumnEnd: 3,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"total_complaints_contacts",
               gridColumnStart: 4,
               gridColumnEnd: 4,
               gridRowStart: 4,
               gridRowEnd: 4,
              },
              {
               name:"table_top_performing_broadcasts",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 5,
               gridRowEnd: 8,
              },
              {
               name:"chart_last_broadcast",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 5,
               gridRowEnd: 8,
              },
              {
               name:"table_top_performing_emails",
               gridColumnStart: 1,
               gridColumnEnd: 3,
               gridRowStart: 8,
               gridRowEnd: 11,
              },
              {
               name:"table_worst_performing_emails",
               gridColumnStart: 3,
               gridColumnEnd: 5,
               gridRowStart: 8,
               gridRowEnd: 11,
              },
            ]}
          />

        );
      },
    },
    {
      label: __("Funnels"),
      route: __("funnels"),
      component: () => {
        return <ReportPanel
          startDate={startDate}
          endDate={endDate}
          dateChange={dateChange}
          reportList={[
            {
             name:"chart_funnel_breakdown",
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
             name:"total_funnel_conversion_rate",
             gridColumnStart: 4,
             gridColumnEnd: 4,
             gridRowStart: 2,
             gridRowEnd: 2
            },
            {
             name:"total_abandonment_rate",
             gridColumnStart: 4,
             gridColumnEnd: 4,
             gridRowStart: 3,
             gridRowEnd: 3,
            },
            {
             name:"table_top_performing_emails",
             gridColumnStart: 1,
             gridColumnEnd: 3,
             gridRowStart: 4,
             gridRowEnd: 7,
            },
            {
             name:"table_worst_performing_emails",
             gridColumnStart: 3,
             gridColumnEnd: 5,
             gridRowStart: 4,
             gridRowEnd: 7,
             fullWidth: false
            },
            {
             name:"table_benchmark_conversion_rate",
             gridColumnStart: 1,
             gridColumnEnd: 5,
             gridRowStart: 7,
             gridRowEnd: 10,
             fullWidth: false
            },
            {
             name:"table_form_activity",
             gridColumnStart: 1,
             gridColumnEnd: 5,
             gridRowStart: 10,
             gridRowEnd: 13,
             fullWidth: true
            },
          ]}
        />
      },
    },
    {
      label: __("Broadcasts"),
      route: __("broadcasts"),
      component: () => {
        return <ReportPanel
          startDate={startDate}
          endDate={endDate}
          dateChange={dateChange}
          reportList={[
            {
             name:"table_broadcast_stats",
             gridColumnStart: 1,
             gridColumnEnd: 3,
             gridRowStart: 1,
             gridRowEnd: 4,
            },
            {
             name:"table_broadcast_link_clicked",
             gridColumnStart: 3,
             gridColumnEnd: 5,
             gridRowStart: 1,
             gridRowEnd: 4,
            },
            {
             name:"table_broadcast_link_clicked",
             gridColumnStart: 1,
             gridColumnEnd: 4,
             gridRowStart: 4,
             gridRowEnd: 7,
            },
          ]}
        />
      },
    },
    {
      label: __("Forms"),
      route: __("forms"),
      component: () => {
        return <ReportPanel
          startDate={startDate}
          endDate={endDate}
          dateChange={dateChange}
          reportList={[
            {
             name:"table_form_activity",
             gridColumnStart: 1,
             gridColumnEnd: 4,
             gridRowStart: 1,
             gridRowEnd: 4,
            },
          ]}
        />
      },
    },
    {
      label: __("Pipeline"),
      route: __("pipeline"),
      component: () => {
        return <ReportPanel
          startDate={startDate}
          endDate={endDate}
          dateChange={dateChange}
          reportList={[
            {
             name:"table_form_activity",
             gridColumnStart: 1,
             gridColumnEnd: 4,
             gridRowStart: 1,
             gridRowEnd: 4,
            },
          ]}
        />
      },
    }
  ];


  return <TabPanel tabs={tabs} enableRouting={true} history={history} match={match} />;
}
