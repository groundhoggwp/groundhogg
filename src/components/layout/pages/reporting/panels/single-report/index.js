import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from 'components/layout/pages/reporting/charts/line-chart'
import { QuickStat } from 'components/layout/pages/reporting/charts/quick-stat'
import ContactMailIcon from '@material-ui/icons/ContactMail';

registerReportsPanel('single-report', {

  name: 'Pipeline',
  reports: [
    // 'total_new_contacts' this gets passed in
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      // total_new_contacts props hopefully
    } = reports

    // <QuickStat
    //   title={'Total New Contacts'} i
    //   id={'total_new_contacts'}
    //   data={!isLoading ? props : {}}
    //   loading={isLoading}
    //   icon={<ContactMailIcon/>}
    // />

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={12}>

          </Grid>
        </Grid>
      </Box>
    )
  }
})
