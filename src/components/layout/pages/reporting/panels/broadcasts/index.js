import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from 'components/layout/pages/reporting/charts/line-chart'
import { QuickStat } from 'components/layout/pages/reporting/charts/quick-stat'
import ContactMailIcon from '@material-ui/icons/ContactMail';

registerReportsPanel('broadcasts', {

  name: 'Broadcasts',
  reports: [
    'total_new_contacts'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      total_new_contacts
    } = reports

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={3}>
            <QuickStat
              title={'Total New Contacts'} i
              id={'total_new_contacts'}
              data={!isLoading ? total_new_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
        </Grid>
      </Box>
    )
  }
})
