import { getChartType, registerReportsPanel } from "data/reports-registry";
import Grid from "@material-ui/core/Grid";
import { Box } from "@material-ui/core";
import { LineChart } from "components/layout/pages/reporting/charts/line-chart";
import { QuickStat } from "components/layout/pages/reporting/charts/quick-stat";
import { DonutChart } from "components/layout/pages/reporting/charts/donut-chart";
import { ReportTable } from "components/layout/pages/reporting/charts/report-table";
import ContactMailIcon from "@material-ui/icons/ContactMail";

registerReportsPanel("overview", {
  name: "Overview",
  reports: [
    "total_new_contacts",
    "total_confirmed_contacts",
    "total_engaged_contacts",
    "total_unsubscribed_contacts",
    "total_emails_sent",
    "email_open_rate",
    "email_click_rate",
    "chart_new_contacts",
    "chart_contacts_by_optin_status",
    "table_top_performing_emails",
    // "table_top_converting_funnels",
    "table_contacts_by_countries",
    "table_contacts_by_lead_source",
  ],
  layout: ({ reports, isLoading }) => {
    const {
      total_new_contacts,
      total_confirmed_contacts,
      total_engaged_contacts,
      total_unsubscribed_contacts,
      total_emails_sent,
      email_open_rate,
      email_click_rate,
      chart_new_contacts,
      chart_contacts_by_optin_status,
      table_top_performing_emails,
      // table_top_converting_funnels, //todo does not work with new funnel
      table_contacts_by_countries,
      table_contacts_by_lead_source,
    } = reports;

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              i
              id={"total_new_contacts"}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              i
              id={"total_confirmed_contacts"}
              data={!isLoading ? total_confirmed_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              id={"total_engaged_contacts"}
              data={!isLoading ? total_engaged_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={"Total New Contacts"}
              id={"total_unsubscribed_contacts"}
              data={!isLoading ? total_unsubscribed_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>

          <Grid item xs={4}>
            <QuickStat
              title={"Email Sent"}
              id={"total_emails_sent"}
              data={!isLoading ? total_emails_sent : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={"Open Rate"}
              id={"email_open_rate"}
              data={!isLoading ? email_open_rate : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={"Click Thru Rate"}
              id={"email_click_rate"}
              data={!isLoading ? email_click_rate : {}}
              loading={isLoading}
              icon={<ContactMailIcon />}
            />
          </Grid>

          <Grid item xs={12}>
            <LineChart
              title={"New Contacts"}
              id={"chart_new_contacts"}
              data={!isLoading ? chart_new_contacts : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={12}>
            <DonutChart
              title={"Donut Chart"}
              id={"chart_contacts_by_optin_status"}
              data={!isLoading ? chart_contacts_by_optin_status : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={12}>
            <ReportTable
              title={"Top Performing Emails"}
              id={"table_top_performing_emails"}
              data={!isLoading ? table_top_performing_emails : {}}
              loading={isLoading}
            />
          </Grid>

          <Grid item xs={6}>
            <ReportTable
              title={"Top Countries"}
              id={"table_contacts_by_countries"}
              data={!isLoading ? table_contacts_by_countries : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={6}>
            <ReportTable
              title={"Top lead Sources"}
              id={"table_contacts_by_lead_source"}
              data={!isLoading ? table_contacts_by_lead_source : {}}
              loading={isLoading}
            />
          </Grid>
        </Grid>
      </Box>
    );
  },
});