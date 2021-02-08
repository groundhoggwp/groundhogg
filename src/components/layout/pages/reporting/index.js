import './charts/'
import './panels/'

import { __ } from "@wordpress/i18n";
import { getReportPanel, getReportPanels, getReportPanelList } from 'data/reports-registry'
import { useSelect, useDispatch } from '@wordpress/data'
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
    marginTop: '-38px',
    zIndex: '5'
  }
}));

export const ReportsPage = () => {
  const panels = getReportPanels()

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

  const history = useHistory();
  const location = useLocation();
  const defaultReport = location.pathname.split('/')[1]
  const subRoute = location.pathname.split('/')[2]



  const [report, setReport] = useState(defaultReport);
  const [startDate, setStartDate] = useState(getLuxonDate('one_year_back'));
  const [endDate, setEndDate] = useState(getLuxonDate('today'));

  let Panel = getReportPanel(report || 'overview' )
  if(subRoute){
    Panel = getReportPanel(report+'-list' )
  }

  console.log(defaultReport, Panel)


  const { reports, isRequesting } = useSelect(
    (select) => {

      const store = select(REPORTS_STORE_NAME);

      return {
        reports: store.getItems({
          // reports: [],
          reports: Panel.reports,
          start: startDate,
          end: endDate,
        }),
        isRequesting: store.isItemsRequesting()
      }
    }
    , [])

    let datePickers = <div/>;
    if(subRoute){
      datePickers = <>
        <DatePicker dateChange={dateChange} selectedDate={startDate} label={'start'} id={'start'}/>
        <DatePicker dateChange={dateChange} selectedDate={endDate} label={'end'} id={'end'}/>
      </>
    }

     const panel = <>
       <Breadcrumb path={['Reporting', Panel.name]}/>
       <div className={classes.datePickers}>
        {datePickers}
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

    const dateChange = (id, newValue)  => {
      if (id === 'start'){
        setStartDate(newValue);
      } else {
        setEndDate(newValue);
      }
    }

    const {setIsRequestingItems} = useDispatch(REPORTS_STORE_NAME)

    const tabsHandleChange = (value)  => {

      let newCurrentTab = 0;
      tabs.forEach((tab,i)=>{
        if(tab.route === value){
          newCurrentTab = i
          history.push(tab.route)
        }
      });

      setIsRequestingItems(true);
      setReport(value);
      // setIsRequestingItems(false);
    }

    console.log('report', reports)

  return (
    <>
      <TabPanel tabs={tabs} tabRoute={report} handleChangeHook={tabsHandleChange}  />
    </>
  )
}
