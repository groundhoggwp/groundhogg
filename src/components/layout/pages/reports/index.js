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
              "total_new_contacts",
              "total_confirmed_contacts",
              "total_engaged_contacts",
              "total_unsubscribed_contacts",
              "total_emails_sent",
              "email_open_rate",
              "email_click_rate",
              "chart_new_contacts",
              "chart_email_activity",
              "chart_funnel_breakdown",
              "chart_contacts_by_optin_status",
              "chart_contacts_by_region",
              "chart_contacts_by_country",
              "chart_last_broadcast",
            ]}
          />
        );
      },
    },
    {
      label: __("Contacts"),
      component: (classes) => {
        return (
          <Card className={classes.container}>
            <Chart type="doughnut" />
          </Card>
        );
      },
    },
    {
      label: __("Email"),
      component: (classes) => {
        return (
          <Card className={classes.container}>
            <Chart type="doughnut" />
          </Card>
        );
      },
    },
    {
      label: __("Funnels"),
      component: () => {
        return "Item Four";
      },
    },
    {
      label: __("Broadcasts"),
      component: () => {
        return "Item Five";
      },
    },
    {
      label: __("Forms"),
      component: () => {
        return "Item Six";
      },
    },
    {
      label: __("Pipeline"),
      component: () => {
        return "Item Seven";
      },
    },
  ];

  return <TabPanel tabs={tabs} />;
}
