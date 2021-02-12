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
    // 'chart_funnel_breakdown',
    // 'total_new_contacts',
    // 'total_funnel_conversion_rate',
    // 'total_abandonment_rate',
    // 'table_top_performing_emails',
    // 'table_worst_performing_emails',
    // 'table_benchmark_conversion_rate',
    // 'table_form_activity',
    'total_new_contacts'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      chart_funnel_breakdown,
      // total_new_contacts,
      // total_funnel_conversion_rate,
      // total_abandonment_rate,
      table_top_performing_emails,
      table_worst_performing_emails,
      table_benchmark_conversion_rate,
      table_form_activity,
      total_new_contacts
    } = reports

    console.log('funnels' ,reports);


    return (
      <Box flexGrow={1}>
        <Grid container spacing={3}>

          {/*<Grid item xs={12}>*/}
          {/*  <LineChart*/}
          {/*    title={"Funnel Breakdown"}*/}
          {/*    id={"chart_funnel_breakdown"}*/}
          {/*    data={!isLoading ? chart_funnel_breakdown : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
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
          {/*<Grid item xs={4}>*/}
          {/*  <QuickStat*/}
          {/*    title={"Abandonment Rate"}*/}
          {/*    i*/}
          {/*    id={"total_abandonment_rate"}*/}
          {/*    data={!isLoading ? total_funnel_conversion_rate : {}}*/}
          {/*    loading={isLoading}*/}
          {/*    icon={<ContactMailIcon />}*/}
          {/*  />*/}
          {/*</Grid>*/}
          {/*<Grid item xs={4}>*/}
          {/*  <QuickStat*/}
          {/*    title={"Abandonment Rate"}*/}
          {/*    i*/}
          {/*    id={"total_abandonment_rate"}*/}
          {/*    data={!isLoading ? total_abandonment_rate : {}}*/}
          {/*    loading={isLoading}*/}
          {/*    icon={<ContactMailIcon />}*/}
          {/*  />*/}
          {/*</Grid>*/}
          {/*<Grid item xs={12}>*/}
          {/*  <ReportTable*/}
          {/*    title={"Top Performing Emails"}*/}
          {/*    id={"table_top_performing_emails"}*/}
          {/*    data={!isLoading ? table_top_performing_emails : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
          {/*<Grid item xs={12}>*/}
          {/*  <ReportTable*/}
          {/*    title={"Worst Performing Emails"}*/}
          {/*    id={"table_worst_performing_emails"}*/}
          {/*    data={!isLoading ? table_worst_performing_emails : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
          {/*<Grid item xs={12}>*/}
          {/*  <ReportTable*/}
          {/*    title={"Benchmark Converstion Rate"}*/}
          {/*    id={"table_benchmark_conversion_rate"}*/}
          {/*    data={!isLoading ? table_benchmark_conversion_rate : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
          {/*<Grid item xs={12}>*/}
          {/*  <ReportTable*/}
          {/*    title={"Top Performing Emails"}*/}
          {/*    id={"table_top_performing_emails"}*/}
          {/*    data={!isLoading ? table_top_performing_emails : {}}*/}
          {/*    loading={isLoading}*/}
          {/*  />*/}
          {/*</Grid>*/}
        </Grid>
      </Box>
    )
  }
})
