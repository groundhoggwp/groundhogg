import { getChartType, registerReportsPanel } from "data/reports-registry";
import Grid from "@material-ui/core/Grid";
import { Box } from "@material-ui/core";
import { LineChart } from "components/layout/pages/reporting/charts/line-chart";
import { QuickStat } from "components/layout/pages/reporting/charts/quick-stat";
import { DonutChart } from "components/layout/pages/reporting/charts/donut-chart";
import ContactMailIcon from "@material-ui/icons/ContactMail";

registerReportsPanel("overview", {
  name: "Overview",
  reports: [
    'total_new_contacts',
    'total_confirmed_contacts',
    'total_engaged_contacts',
    'total_unsubscribed_contacts',
    'chart_new_contacts',
    "chart_contacts_by_optin_status",
  ],
  layout: ({ reports, isLoading }) => {
    const {
      total_new_contacts,
      total_confirmed_contacts,
      total_engaged_contacts,
      total_unsubscribed_contacts,
      chart_new_contacts,
      chart_contacts_by_optin_status,
    } = reports;


    let chart_contacts_by_optin_status_dummy = {
      "type": "chart",
          "title": "TODO IF REQUIRED PLEASE LET ME KNOW",
          "chart": {
        "type": "doughnut",
            "data": {
          "labels": ["Unconfirmed", "Unconfirmed", "Unconfirmed", "Unconfirmed", "Unconfirmed"],
              "datasets": [{
            "data": [366, 1888, 86, 19, 1],
            "backgroundColor": ["#F18F01", "#006E90", "#99C24D", "#F46036", "#41BBD9"]
          }]
        },
        "options": {
          "maintainAspectRatio": false,
              "legend": {
            "display": false
          },
          "responsive": false,
              "tooltips": {
            "backgroundColor": "#FFF",
                "bodyFontColor": "#000",
                "borderColor": "#727272",
                "borderWidth": 2,
                "titleFontColor": "#000"
          }
        },
        "no_data": "No information available."
      }
    } ;

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
          {/*<Grid item xs={12}>*/}
          {/*  <LineChart*/}
          {/*    title={'New Contacts'}*/}
          {/*    id={'chart_new_contacts'}*/}
          {/*    data={!isLoading ? chart_new_contacts : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}

          <Grid item xs={12}>
            <DonutChart
              title={"Donut Chart"}
              id={"chart_contacts_by_optin_status"}
              data={chart_contacts_by_optin_status_dummy}
              loading={isLoading}
            />
          </Grid>
        </Grid>
      </Box>
    );
  },
});
