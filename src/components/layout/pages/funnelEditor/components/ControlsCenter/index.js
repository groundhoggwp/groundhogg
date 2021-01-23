import { Card } from '@material-ui/core'
import { Route, useRouteMatch, useParams } from 'react-router-dom'
import { makeStyles } from '@material-ui/core/styles'
import StepsPath from 'components/layout/pages/funnelEditor/components/StepsPath'
import { unSlash } from 'utils/core'
import EditStep from '../EditStep'

const useStyles = makeStyles((theme) => ({
  root: {
    marginLeft: 400,
    marginTop: 80,
    marginRight: 10
    // float: 'right'
  },
  card: {
    padding: theme.spacing(4)
  }
}))

export default ({ funnel }) => {

  const classes = useStyles()

  const { steps, edges } = funnel
  const { path } = useRouteMatch()

  return (
    <>
      <div className={classes.root}>
        <StepsPath steps={steps} edges={edges}/>
        <Card className={classes.card}>
          <Route path={`${unSlash(path)}/edit/:stepId`}>
            <EditStep/>
          </Route>
        </Card>
      </div>
    </>
  )
}