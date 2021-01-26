import './charts/'
import './panels/'

import { getReportPanel, getReportPanels } from 'data/reports-registry'
import { useSelect } from '@wordpress/data'
import { useState } from "@wordpress/element";
import { makeStyles } from "@material-ui/core/styles";
import { REPORTS_STORE_NAME } from 'data/reports'
import { isObject } from 'utils/core'
import { HashRouter, Switch, useParams, Route, Link } from 'react-router-dom'
import DatePicker from "../../../core-ui/date-picker";
import { DateTime } from 'luxon';
import {getLuxonDate} from "utils/index";

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

  console.debug( panels );

  return (
    <>
      <HashRouter>
        <div style={{ padding: 20 }}>
          {panels.map(panel =>
            <Link to={'/' + panel.id }>{panel.name}</Link>
          )}
          <Switch>
            <Route path="/" children={<ReportPanel/>}/>
            <Route path="/:report" children={<ReportPanel/>}/>
            <Route path="/:report/:subReport" children={<ReportPanel/>}/>
          </Switch>
        </div>
      </HashRouter>
    </>
  )
}

const ReportPanel = () => {
  const classes = useStyles();

  const { report } = useParams();

  const Panel = getReportPanel(report || 'overview' )


  const [startDate, setStartDate] = useState(getLuxonDate('one_year_back'));
  const [endDate, setEndDate] = useState(getLuxonDate('today'));

  const dateChange = (id, newValue)  => {
    if (id === 'start'){
      setStartDate(newValue);
    } else {
      setEndDate(newValue);
    }
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


  return (
    <>
      {Panel.name}
      <div className={classes.datePickers}>
        <DatePicker dateChange={dateChange} selectedDate={startDate} label={'start'} id={'start'}/>
        <DatePicker dateChange={dateChange} selectedDate={endDate} label={'end'} id={'end'}/>
      </div>
      <Panel.layout
        isLoading={isRequesting || !isObject(reports)}
        reports={isObject(reports) ? reports : {}}
      />
    </>
  )
}
