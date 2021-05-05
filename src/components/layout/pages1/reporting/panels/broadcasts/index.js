import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from 'components/layout/pages/reporting/charts/line-chart'
import { QuickStat } from 'components/layout/pages/reporting/charts/quick-stat'
import ContactMailIcon from '@material-ui/icons/ContactMail';
import { ReportTable } from "components/layout/pages/reporting/charts/report-table";

registerReportsPanel('broadcasts', {

  name: 'Broadcasts',
  reports: [
    'table_broadcast_stats',
    'table_broadcast_link_clicked'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      // table_broadcast_stats,
      table_broadcast_link_clicked
    } = reports

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          {/*<Grid item xs={12}>*/}
          {/*  <ReportTable*/}
          {/*    title={"Broadcast Stats"}*/}
          {/*    id={"table_broadcast_stats"}*/}
          {/*    data={!isLoading ? table_broadcast_stats : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
          <Grid item xs={12}>
            <ReportTable
              title={"Broadcast Link Clicked"}
              id={"table_broadcast_link_clicked"}
              data={!isLoading ? table_broadcast_link_clicked : {}}
              loading={isLoading}
            />
          </Grid>
        </Grid>
      </Box>
    )
  }
})
