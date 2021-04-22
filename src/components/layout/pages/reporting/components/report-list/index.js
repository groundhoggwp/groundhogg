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

import ListItem from './components/list-item.js'
import Toolbar from './components/toolbar.js'

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

export default ({ reports }) => {

    const [searchTerm, setSearchTerm] = useState('');

    const searchListHandler = (e) =>{
      setSearchTerm(e.target.value)
    }

    const reportsBySearch = (report) =>{
      return report.toLowerCase().indexOf(searchTerm) !== -1
    }

    return (
      <Fragment>
      <Toolbar
        onDeselectAll={()=>{'de-selected'}}
        onSelectAll={()=>{'de-selected'}}
        selectedMails={5}
        mails={Object.keys(reports).length}
        searchTerm={searchTerm}
        searchListHandler={searchListHandler}
      />
      <Divider />
        { Object.keys(reports).filter(reportsBySearch).map(report => {
          const title = _.startCase(_.toLower(report.split('_').join(' ')))
          return <ListItem title={title} link={`funnels/${report}`}/>
        }) }
      </Fragment>
    )
  }
