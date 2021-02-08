import { Fragment, useState } from '@wordpress/element'
import { useSelect, useDispatch } from '@wordpress/data'
import { ListTable } from 'components/core-ui/list-table/new'
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

import ListItem from './list-item.js'
import Toolbar from './toolbar.js'

import {
  FUNNELS_STORE_NAME
} from 'data'

const iconProps = {
  fontSize: 'small',
  style: {
    verticalAlign: 'middle',
  },
}

const funnelTableColumns = [
  {
    ID: 'ID',
    name: 'ID',
    orderBy: 'ID',
    align: 'right',
    cell: ({ data, ID }) => {
      return ID
    },
  },
  {
    ID: 'name',
    name: <span>{ 'Title' }</span>,
    orderBy: 'title',
    align: 'left',
    cell: ({ data, ID }) => {
      return <>
      { canUser( 'update', ID )
        ? <Link to={ `/funnels/${ID}` }>{ data.title }</Link>
        : <Fragment>{ data.title }</Fragment>
      }
    </>
    }
  },
  {
    ID: 'stats',
    name: <span><TimelineIcon { ...iconProps }/> { 'Stats' }</span>,
    align: 'center',
    cell: ({ stats }) => {
      return <>
        <Tooltip title={ 'Complete' }>
          <Chip
            icon={ <FlagIcon/> }
            label={ stats.complete }
          />
        </Tooltip>
        <Tooltip title={ 'Active' }>
          <Chip
            icon={ <AccountCircleIcon/> }
            label={ stats.active_now }
          />
        </Tooltip>
      </>
    },
  },
  {
    ID: 'status',
    name: <span>{ 'Status' }</span>,
    align: 'left',
    orderBy: 'status',
    cell: ({ ID, data }) => {

      const { updateItem } = useDispatch(FUNNELS_STORE_NAME)

      const handleClick = (status) => {
        updateItem(ID, {
          data: {
            status: status,
          },
        })
      }

      return <>
        <ButtonGroup size={ 'small' } color="primary"
                     aria-label={ 'funnel status' }>
          <Button
            onClick={()=>handleClick('active')}
            variant={ data.status === 'active'
              ? 'contained'
              : 'outlined' }>{ 'Active' }</Button>
          <Button
            onClick={()=>handleClick('inactive')}
            variant={ data.status !== 'active'
              ? 'contained'
              : 'outlined' }>{ 'Inactive' }</Button>
        </ButtonGroup>
      </>
    },
  },
  {
    ID: 'actions',
    name: <span><SettingsIcon { ...iconProps }/> { 'Actions' }</span>,
    align: 'right',
    // cell: ({ ID, data, openQuickEdit }) => {

      // const { deleteItem } = useDispatch(FUNNELS_STORE_NAME)
      //
      // const handleEdit = () => {
      //   openQuickEdit()
      // }
      //
      // const handleDelete = (ID) => {
      //   deleteItem(ID)
      // }
      //
      // return <>
      //   <RowActions
      //     onEdit={ openQuickEdit }
      //     onDelete={ () => canUser( 'delete', ID ) && handleDelete(ID) }
      //   />
      // </>
    // },
  },
]

registerReportsPanel('funnels-list', {

  name: 'Funnels List',
  reports: [
    // 'chart_funnel_breakdown',
    // 'total_new_contacts',
    // 'total_funnel_conversion_rate',
    // 'total_abandonment_rate',
    // 'table_top_performing_emails',
    // 'table_worst_performing_emails',
    // 'table_benchmark_conversion_rate',
    'table_form_activity'
  ],
  layout: ({
    reports,
    isLoading
  }) => {

    const {
      // chart_funnel_breakdown,
      // total_new_contacts,
      // total_funnel_conversion_rate,
      // total_abandonment_rate,
      // table_top_performing_emails,
      // table_worst_performing_emails,
      // table_benchmark_conversion_rate,
      table_form_activity,
    } = reports

    const { funnels, totalItems, isRequesting } = useSelect((select) => {
      const store = select(FUNNELS_STORE_NAME)

      return {
        funnels: store.getItems(),
        totalItems: store.getTotalItems(),
        isRequesting: store.isItemsRequesting(),
      }
    }, [])

    const { fetchItems, deleteItems } = useDispatch(FUNNELS_STORE_NAME)
    console.log('funnels', funnels)
    // onBulkAction={ ()=>{} }
    // bulkActions={ FunnelTableBulkActions }
    // QuickEdit={ FunnelsQuickEdit }
    return (
      <Fragment>
      <Toolbar
        onDeselectAll={()=>{'de-selected'}}
        onSelectAll={()=>{'de-selected'}}
        selectedMails={5}
        mails={totalItems}
      />
      <Divider />
        { funnels.map(funnel => {
          const {title, last_updated} = funnel.data
          console.log(title, last_updated)
          return <ListItem title={title} date={last_updated}/>
        }) }
      </Fragment>
    )
    // return (
    //   <Box flexGrow={1}>
    //     <Grid container spacing={3}>
    //       <Grid item xs={12}>
    //         <LineChart
    //           title={"Funnel Breakdown"}
    //           id={"chart_funnel_breakdown"}
    //           data={!isLoading ? chart_funnel_breakdown : {}}
    //           loading={isLoading}
    //         />
    //       </Grid>
    //       <Grid item xs={4}>
    //         <QuickStat
    //           title={"New Contacts"}
    //           i
    //           id={"total_new_contacts"}
    //           data={!isLoading ? total_new_contacts : {}}
    //           loading={isLoading}
    //           icon={<ContactMailIcon />}
    //         />
    //       </Grid>
    //       <Grid item xs={4}>
    //         <QuickStat
    //           title={"Abandonment Rate"}
    //           i
    //           id={"total_abandonment_rate"}
    //           data={!isLoading ? total_funnel_conversion_rate : {}}
    //           loading={isLoading}
    //           icon={<ContactMailIcon />}
    //         />
    //       </Grid>
    //       <Grid item xs={4}>
    //         <QuickStat
    //           title={"Abandonment Rate"}
    //           i
    //           id={"total_abandonment_rate"}
    //           data={!isLoading ? total_abandonment_rate : {}}
    //           loading={isLoading}
    //           icon={<ContactMailIcon />}
    //         />
    //       </Grid>
    //       <Grid item xs={12}>
    //         <ReportTable
    //           title={"Top Performing Emails"}
    //           id={"table_top_performing_emails"}
    //           data={!isLoading ? table_top_performing_emails : {}}
    //           loading={isLoading}
    //         />
    //       </Grid>
    //       <Grid item xs={12}>
    //         <ReportTable
    //           title={"Worst Performing Emails"}
    //           id={"table_worst_performing_emails"}
    //           data={!isLoading ? table_worst_performing_emails : {}}
    //           loading={isLoading}
    //         />
    //       </Grid>
    //       <Grid item xs={12}>
    //         <ReportTable
    //           title={"Benchmark Converstion Rate"}
    //           id={"table_benchmark_conversion_rate"}
    //           data={!isLoading ? table_benchmark_conversion_rate : {}}
    //           loading={isLoading}
    //         />
    //       </Grid>
    //       <Grid item xs={12}>
    //         <ReportTable
    //           title={"Top Performing Emails"}
    //           id={"table_top_performing_emails"}
    //           data={!isLoading ? table_top_performing_emails : {}}
    //           loading={isLoading}
    //         />
    //       </Grid>
    //     </Grid>
    //   </Box>
    // )
  }
})
