import './charts/'
import './panels/'

import { getReportPanel, getReportPanels } from 'data/reports-registry'
import { useSelect } from '@wordpress/data'
import { REPORTS_STORE_NAME } from 'data/reports'
import { isObject } from 'utils/core'
import { HashRouter, Switch, useParams, Route, Link } from 'react-router-dom'

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

  let { report } = useParams()

  const Panel = getReportPanel(report || 'overview' )

  const { reports, isRequesting } = useSelect(
    (select) => {

      const store = select(REPORTS_STORE_NAME)
      return {
        reports: store.getItems({
          reports: Panel.reports
          // start: '',
          // end: '',
        }),
        // getReports: store.getItem,
        isRequesting: store.isItemsRequesting()
        // isUpdating: store.isItemsUpdating()
      }
    }
    , [])

  return (
    <>
      {Panel.name}
      <Panel.layout
        isLoading={isRequesting || !isObject(reports)}
        reports={isObject(reports) ? reports : {}}
      />
    </>
  )
}