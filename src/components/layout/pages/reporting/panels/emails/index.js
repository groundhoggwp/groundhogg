import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box } from '@material-ui/core'
import { LineChart } from 'components/layout/pages/reporting/charts/line-chart'
import { QuickStat } from 'components/layout/pages/reporting/charts/quick-stat'
import ContactMailIcon from '@material-ui/icons/ContactMail';

registerReportsPanel('emails', {

  name: 'Emails',
  reports: [
    'chart_email_activity',
    'total_emails_sent',
    'email_open_rate',
    'email_click_rate',
    'total_unsubscribed_contacts',
    'total_spam_contacts',
    'total_bounces_contacts',
    'total_complaints_contacts'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      chart_email_activity,
      total_emails_sent,
      email_open_rate,
      email_click_rate,
      total_unsubscribed_contacts,
      total_spam_contacts,
      total_bounces_contacts,
      total_complaints_contacts
    } = reports

    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>
          <Grid item xs={4}>
            <QuickStat
              title={'Emails Sent'} i
              id={'total_emails_sent'}
              data={!isLoading ? total_emails_sent : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={'Open Rate'} i
              id={'email_open_rate'}
              data={!isLoading ? email_open_rate : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={4}>
            <QuickStat
              title={'Click Thru Rate'} i
              id={'email_click_rate'}
              data={!isLoading ? email_click_rate : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={12}>
            <LineChart
              title={'Email Activity'}
              id={'chart_email_activity'}
              data={!isLoading ? chart_email_activity : {}}
              loading={isLoading}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={'Unsubscribes'} i
              id={'total_unsubscribed_contacts'}
              data={!isLoading ? total_unsubscribed_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={'Spam'} i
              id={'total_spam_contacts'}
              data={!isLoading ? total_spam_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={'Bounces'} i
              id={'total_bounces_contacts'}
              data={!isLoading ? total_bounces_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
          <Grid item xs={3}>
            <QuickStat
              title={'Complaints'} i
              id={'total_complaints_contacts'}
              data={!isLoading ? total_complaints_contacts : {}}
              loading={isLoading}
              icon={<ContactMailIcon/>}
            />
          </Grid>
        </Grid>
      </Box>
    )
  }
})
