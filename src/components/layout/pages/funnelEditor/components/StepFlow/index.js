import { Link, Route, useParams, useRouteMatch } from 'react-router-dom'
import { makeStyles } from '@material-ui/core/styles'
import { unSlash } from 'utils/core'
import { getStepType, useStepType } from 'data/step-type-registry'

const useStyles = makeStyles((theme) => ({
  root: {
    // marginTop: 80,
    marginLeft: -390,
    width: 380,
    float: 'left'
  }
}))

/**
 * Sorts the nodes into the best order based on the state of the graph
 * @param steps
 * @param edges
 */
function processPath (steps, edges) {

  const levels = {}

  function processNode (node, prev_level, level) {
    if (levels.hasOwnProperty(node)) {
      if (levels[node] === level) {
        return
      } else if (prev_level < level && prev_level < levels[node]) {
        return
      } else if (prev_level > level && prev_level > levels[node]) {
        return
      }
    }

    levels[node] = level

    const children = edges.filter(edge => edge.from_id === node)
    const parents = edges.filter(edge => edge.to_id === node)

    children.forEach(child => processNode(child, level + 1))
    parents.forEach(parent => processNode(parent, level - 1))
  }

  processNode(steps[0], 1)

  steps.sort((a, b) => {
    return levels[a.ID] - levels[b.ID]
  })

  return steps

}

export default (props) => {

  const classes = useStyles()
  const { path } = useRouteMatch()

  return (
    <>
      <div className={classes.root}>
        <Route path={`${path}/branch/:branchId/:branchPath`}>
          <BranchPath {...props}/>
        </Route>
        <Route path={'/'}>
          <MainPath {...props}/>
        </Route>
      </div>
    </>
  )
}

const MainPath = ({steps, edges}) => {
  const stepPath = processPath(steps, edges)

  return (
    <>
      {'Main...'}
      {stepPath.map( step => {

        const { StepIcon, StepRead, StepFlow } = useStepType( step.data.step_type )

        return (
          <div className={'step-block'}>
            <StepFlow
              icon={StepIcon}
              read={<StepRead {...step}/>}
            />
          </div>
        )
      } )}
    </>
  )
}

const BranchPath = ({steps, edges}) => {

  const { branch, branchPath } = useParams()
  const stepPath = processPath(steps, edges).filter( step => {
    return step.data.path === branchPath && step.data.branch === branch
  })

  return (
    <>
      {'Branch...'}
      {stepPath.map( step => {

        const { StepIcon, StepRead, StepFlow } = useStepType( step.data.step_type )

        return (
          <div className={'step-block'}>
            <StepFlow
              icon={<StepIcon/>}
              read={<StepRead {...step}/>}
            />
          </div>
        )
      } )}
    </>
  )

}

const StepLink = (step) => {

  const { url } = useRouteMatch()

  return (
    <li><Link to={`${unSlash(url)}/${step.ID}/edit`}>{step.ID}: {step.data.step_type}</Link></li>
  )
}
