import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from "components/layout/pages/reporting/charts/line-chart";
import { QuickStat } from "components/layout/pages/reporting/charts/quick-stat";
import { DonutChart } from "components/layout/pages/reporting/charts/donut-chart";
import { ReportTable } from "components/layout/pages/reporting/charts/report-table";
import ContactMailIcon from '@material-ui/icons/ContactMail';

registerReportsPanel('funnels-single', {

  name: 'Funnel',
  reports: [
  ],
  layout: ({
    reports,
    isLoading,
  }) => {

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={3}>
            <QuickStat
                title={"Total New Contacts"}
                i
                id={"ddl_funnels"}
                data={!isLoading ? reports[0] : {}}
                loading={isLoading}
                icon={<ContactMailIcon />}
            />
          </Grid>
        </Grid>
      </Box>
    )
  }
})
