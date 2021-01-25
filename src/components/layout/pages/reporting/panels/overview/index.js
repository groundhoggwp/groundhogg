import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from 'components/layout/pages/reporting/charts/line-chart'
import { QuickStat } from 'components/layout/pages/reporting/charts/quick-stat'

registerReportsPanel('overview', {

  name: 'Overview',
  reports: [
    'chart_new_contacts',
    'total_new_contacts'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      chart_new_contacts,
      total_new_contacts
    } = reports

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <LineChart
              title={'New Contacts'}
              id={'chart_new_contacts'}
              report={!isLoading ? chart_new_contacts : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={'Total New Contacts'} i
              id={'total_new_contacts'}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={'Total New Contacts'} i
              id={'total_new_contacts'}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={'Total New Contacts'}
              id={'total_new_contacts'}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
            />
          </Grid>
        </Grid>
      </Box>
    )
  }
})
