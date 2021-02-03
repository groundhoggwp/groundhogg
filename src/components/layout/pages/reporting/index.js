import './charts/'
import './panels/'

import { __ } from "@wordpress/i18n";
import { getReportPanel, getReportPanels } from 'data/reports-registry'
import { useSelect } from '@wordpress/data'
import { useState } from "@wordpress/element";
import { makeStyles } from "@material-ui/core/styles";
import { REPORTS_STORE_NAME } from 'data/reports'
import { isObject } from 'utils/core'
import { HashRouter, Switch, useLocation, useHistory, Route, Link } from 'react-router-dom'
import DatePicker from "../../../core-ui/date-picker";
import { DateTime } from 'luxon';
import {getLuxonDate} from "utils/index";
import TabPanel from "../../../core-ui/tab-panel";
import Breadcrumb from "../../../core-ui/bread-crumb";

const useStyles = makeStyles((theme) => ({
  datePickers:{
    float: 'right',
    display: 'flex',
    right: '0px',
    width: '350px',
    justifyContent: 'flex-end',
    zIndex: '5'
  }
}));

export const ReportsPage = () => {
  const panels = getReportPanels()

  console.log(panels)
  return (
    <>
      <HashRouter>
        <div style={{ padding: 20 }}>
          {/*panels.map(panel =>
            <Link to={'/' + panel.id }>{panel.name}</Link>
          )*/}
          <Switch>
            <Route path="/" children={<ReportPanel/>}/>
            <Route path="/:report" children={<ReportPanel />}/>
            <Route path="/:report/:subReport" children={<ReportPanel/>}/>
          </Switch>
        </div>
      </HashRouter>
    </>
  )
}

const ReportPanel = (props) => {
  const classes = useStyles();

  console.log('report panel', props, useLocation(), useHistory())

  const [report, setReport] = useState(useLocation().pathname.replace('/',''));
  const [startDate, setStartDate] = useState(getLuxonDate('one_year_back'));
  const [endDate, setEndDate] = useState(getLuxonDate('today'));

  const Panel = getReportPanel(report || 'overview' )

  const dateChange = (id, newValue)  => {
    if (id === 'start'){
      setStartDate(newValue);
    } else {
      setEndDate(newValue);
    }
  }
  const tabsHandleChange = (value)  => {
    console.log('change', value)

    const {setIsRequestingItems} = useDispatch(REPORTS_STORE_NAME)
    setIsRequestingItems(true);
    setReport(value)
  }



  const { reports, isRequesting } = useSelect(
    (select) => {

      const store = select(REPORTS_STORE_NAME);

      return {
        reports: store.getItems({
          reports: Panel.reports,
          start: startDate,
          end: endDate,
        }),
        isRequesting: store.isItemsRequesting()
      }
    }
    , [])

    console.log(report, isRequesting, startDate, endDate)

     const panel = <>
       <Breadcrumb path={['Reporting', Panel.name]}/>
       <div className={classes.datePickers}>
         <DatePicker dateChange={dateChange} selectedDate={startDate} label={'start'} id={'start'}/>
         <DatePicker dateChange={dateChange} selectedDate={endDate} label={'end'} id={'end'}/>
       </div>
       <Panel.layout
         isLoading={isRequesting || !isObject(reports)}
         reports={isObject(reports) ? reports : {}}
       />
   </>
    const tabs = [
      {
        label: __("Overview"),
        route: __("overview"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Contacts"),
        route: __("contacts"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Emails"),
        route: __("emails"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Funnels"),
        route: __("funnels"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Broadcasts"),
        route: __("broadcasts"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Forms"),
        route: __("forms"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      },
      {
        label: __("Pipeline"),
        route: __("pipeline"),
        component: (classes) => {
          return <div>{panel}</div>
        }
      }
    ]


  return (
    <>
      <TabPanel tabs={tabs} handleChangeHook={tabsHandleChange} />
    </>
  )
}
