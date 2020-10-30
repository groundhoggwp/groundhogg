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

  const dateChange = (e)  => {
    console.log('change the data', e)
  }

  const tabs = [
    {
      label: __("Overview"),
      component: (classes) => {
        return (
          <ReportPanel
            dateChange={dateChange}
            reportList={[
              "chart_new_contacts",
              "total_new_contacts",
              "total_confirmed_contacts",
              "total_engaged_contacts",
              "total_unsubscribed_contacts",
              "total_emails_sent",
              "email_open_rate",
              "email_click_rate",
              "chart_contacts_by_optin_status",
              "table_contacts_by_lead_source",
              "table_top_converting_funnels",
              "table_top_performing_emails",
              "table_contacts_by_countries",
              "table_contacts_by_lead_source",
            ]}
          />
        );
      },
    },
    {
      label: __("Contacts"),
      component: (classes) => {
        return (
          <ReportPanel
            dateChange={dateChange}
            reportList={[
              "chart_new_contacts",
              "total_new_contacts",
              "total_confirmed_contacts",
              "total_engaged_contacts",
              "total_unsubscribed_contacts",
              "chart_contacts_by_optin_status",
              "table_contacts_by_lead_source",
              "table_contacts_by_countries",
              "chart_contacts_by_region",
              "table_contacts_by_search_engines",
              "table_contacts_by_social_media",
              "table_contacts_by_source_page",
              "table_list_engagement",
            ]}
          />
        );
      },
    },
    {
      label: __("Email"),
      component: (classes) => {
        return (
          <ReportPanel
            dateChange={dateChange}
            reportList={[
              "chart_email_activity",
              "email_open_rate",
              "email_click_rate",
            ]}
          />

        );
      },
    },
    {
      label: __("Funnels"),
      component: () => {
        return <ReportPanel
          dateChange={dateChange}
          reportList={[
            "chart_funnel_breakdown"
          ]}
        />
      },
    },
    {
      label: __("Broadcasts"),
      component: () => {
        return <ReportPanel
          dateChange={dateChange}
          reportList={[
            "table_broadcast_stats",
            "table_broadcast_link_clicked",
          ]}
        />
      },
    },
    {
      label: __("Forms"),
      component: () => {
        return <ReportPanel
          dateChange={dateChange}
          reportList={[
            "table_form_activity"
          ]}
        />
      },
    },
    {
      label: __("Pipeline"),
      component: () => {
        return <ReportPanel
          dateChange={dateChange}
          reportList={[
            "table_form_activity"
          ]}
        />
      },
    }
  ];

  return <TabPanel tabs={tabs} />;
}
