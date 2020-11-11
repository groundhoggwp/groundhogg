import Box from '@material-ui/core/Box'
import { FUNNELS_STORE_NAME } from 'data'
import BenchmarkPicker from './components/Pickers/BenchmarkPicker'
import StepBlock from './components/StepBlock'
import ExitFunnel from './components/ExitFunnel'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { withSelect } from '@wordpress/data'
import dagre from 'dagre'
import StepEdges from './components/StepEdges'
import { CONDITION } from 'components/layout/pages/funnels/editor/steps-types/constants'

/**
 * Breadth first search of the steps tree to build iout a row level based chart
 * for putting the steps on the page.
 *
 * @param steps
 * @param graph
 */
function buildGraph (steps, graph) {

  const queue = steps.filter(
    step => Object.values(step.data.parent_steps).length === 0)

  while (queue.length) {
    let currentNode = queue.shift()
    const { ID, data } = currentNode
    const { child_steps, step_group } = data
    let children = Object.values(child_steps)

    if (graph.node(ID)) {
      continue
    }

    graph.setNode(ID, { label: ID, width: 300, height: 250 })

    // Get the child nodes
    let childNodes = steps.filter(
      node => children.includes(node.ID))

    if (step_group === CONDITION) {
      graph.setEdge(ID, child_steps.no || 'exit')
      graph.setEdge(ID, child_steps.yes || 'exit')

      child_steps.yes && queue.push( childNodes.find( node => node.ID === child_steps.yes  ) )
      child_steps.no && queue.push( childNodes.find( node => node.ID === child_steps.no  ) )

    } else {

      if (!childNodes.length) {
        // set to exit
        graph.setEdge(ID, 'exit')
        continue
      }

      // queue up the child nodes
      childNodes.forEach((node, i) => {
        graph.setEdge(ID, node.ID)
        queue.push(node)
      })
    }
  }
}

const Editor = ({ funnel }) => {

  if (!funnel) {
    return null
  }

  if (!funnel.steps) {
    return null
  }

  const steps = funnel.steps

  const endingSteps = steps.filter(
    step => Object.values(step.data.child_steps).length === 0)

  const graph = new dagre.graphlib.Graph()

  graph.setGraph({
    // ranker: 'tight-tree',
    // align: 'DL',
    // rankdir: 'LR',
    nodesep: 100,
  })

  graph.setDefaultEdgeLabel(() => { return {} })

  graph.setNode('exit', { label: 'exit', width: 300, height: 250 })

  buildGraph(steps, graph)

  dagre.layout(graph)

  return (
    <>
      <div style={ { position: 'relative', height: graph.node( 'exit' ).y + 100 } }>
        {
          steps.length === 0 && (
            <Box display={ 'flex' } justifyContent={ 'center' }>
              <Paper style={ { width: 500 } }>
                <BenchmarkPicker funnelID={ funnel.ID }/>
              </Paper>
            </Box>
          )
        }
        {
          steps.map(step => {
            return (
              <>
                <StepBlock { ...step } graph={graph}/>
              </>
            )
          })
        }
        { steps.length > 0 && <ExitFunnel
          xPos={ graph.node( 'exit' ).x }
          yPos={ graph.node( 'exit' ).y }
          funnelId={ funnel.ID }
          endingSteps={ endingSteps.map(step => step.ID) }/>
        }
        {
          steps.map((step, i) => <StepEdges { ...step } />)
        }
      </div>
    </>
  )
}

export default withSelect((select, ownProps) => {

  const store = select(FUNNELS_STORE_NAME)

  return {
    // ...ownProps,
    funnel: store.getItem(ownProps.id),
    // isCreating: store.isCreatingStep(),
    // isDeleting: store.isDeletingStep(),
    // isUpdating: store.isUpdatingStep(),
    // isRequesting: store.isItemsRequesting()
  }
})(Editor)
