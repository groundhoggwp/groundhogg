import { Fragment, useState } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import  ListTable  from 'components/core-ui/list-table/'
import { getChartType, registerReportsPanel } from 'data/reports-registry'
import Grid from '@material-ui/core/Grid'
import { Box, Divider } from '@material-ui/core'
import { LineChart } from "components/layout/pages/reporting/charts/line-chart";
import { QuickStat } from "components/layout/pages/reporting/charts/quick-stat";
import { DonutChart } from "components/layout/pages/reporting/charts/donut-chart";
import { ReportTable } from "components/layout/pages/reporting/charts/report-table";
import ContactMailIcon from '@material-ui/icons/ContactMail';
import DeleteIcon from '@material-ui/icons/Delete'
import SettingsIcon from '@material-ui/icons/Settings'
import TimelineIcon from '@material-ui/icons/Timeline'
import AccountCircleIcon from '@material-ui/icons/AccountCircle'
import _ from 'lodash';

import ReportList from '../../components/report-list'


registerReportsPanel('funnels', {

  name: 'Funnels',
  reports: [
    // 'chart_funnel_breakdown',
    // 'total_contacts_in_funnel',
    // 'total_funnel_conversion_rate',
    // 'table_top_converting_funnels',
    // 'table_funnel_stats',
    'table_email_funnels_used_in'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      // chart_funnel_breakdown,
      // total_contacts_in_funnel,
      // total_funnel_conversion_rate,
      // table_top_converting_funnels,
      // table_funnel_stats,
      table_email_funnels_used_in
    } = reports


    return (
      <ReportList reports={reports} />
    )
  }
})
