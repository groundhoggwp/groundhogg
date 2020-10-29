import Box from '@material-ui/core/Box'
import { FUNNELS_STORE_NAME } from 'data'
import BenchmarkPicker from './components/Pickers/BenchmarkPicker'
import StepBlock from './components/StepBlock'
import ExitFunnel from './components/ExitFunnel'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { withSelect } from '@wordpress/data'
import dagre from 'dagre'
import Xarrow from 'react-xarrows'

/**
 * Breadth first search of the steps tree to build iout a row level based chart
 * for putting the steps on the page.
 *
 * @param startNodes
 * @param allNodes
 */
function assignLevels (startNodes, allNodes) {

  startNodes.forEach((node, i) => {
    node.level = 0
    node.xPos = i
  })

  const queue = startNodes

  while (queue.length) {
    let currentNode = queue.shift()

    // Get the child nodes
    let childNodes = allNodes.filter(
      node => currentNode.data.child_steps.includes(node.ID))

    // queue up the child nodes
    childNodes.forEach((node, i) => {

      node.level = currentNode.level + 1
      node.xPos = i + currentNode.xPos

      queue.push(node)
    })
  }// let parentNodes = allNodes.filter(
  //   node => currentNode.data.parent_steps.includes(node.ID))

  console.log(allNodes)
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
    step => step.data.child_steps.length === 0)

  const graph = new dagre.graphlib.Graph()

  graph.setGraph({
    // ranker: 'tight-tree',
    // align: 'UL'
    nodesep: 100
  })

  graph.setDefaultEdgeLabel(() => { return {} })

  graph.setNode('exit', { label: 'exit', width: 300, height: 250 })

  steps.forEach((step) => {
    const { child_steps } = step.data

    graph.setNode(step.ID, { label: step.ID, width: 300, height: 250 })

    child_steps.forEach((child) => {
      graph.setEdge(step.ID, child)
    })

    if (!child_steps.length) {
      graph.setEdge(step.ID, 'exit')
    }
  })

  dagre.layout(graph)

  let exitYPos, exitXPos = 0
  let graphHeight = 0

  graph.nodes().forEach((ID) => {

    if (!ID) {
      return
    }

    const { x, y } = graph.node(ID) || {}

    graphHeight = Math.max(graphHeight, y)

    if (ID === 'exit') {
      exitXPos = x
      exitYPos = y
      return
    }

    steps.forEach((step) => {
      if (step.ID == ID) {
        // step.yPos = step.data.parent_steps.length ? y : 125
        step.yPos = y
        step.xPos = x
      }
    })
  })

  return (
    <>
      <div style={ { position: 'relative', height: exitYPos + 100 } }>
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
                <StepBlock { ...step }/>
              </>
            )
          })
        }
        { steps.length > 0 && <ExitFunnel
          xPos={ exitXPos }
          yPos={ exitYPos }
          funnelId={ funnel.ID }
          endingSteps={ endingSteps.map(step => step.ID) }/>
        }
        {
          steps.map((step, i) => {

            const { child_steps } = step.data;

            if ( ! child_steps.length ){
              child_steps.push( 'exit' )
            }

            return (
              <>
                {
                  child_steps.map(child => <Xarrow
                    key={ i }
                    start={ 'step-' + step.ID }
                    end={ 'step-' + child }
                    startAnchor={ ['bottom', 'middle'] }
                    endAnchor={ ['top', 'middle'] }
                    curveness={1}
                    headSize={5}
                    strokeWidth={2}
                    path={'smooth'}
                    color={'#cbcbcb'}
                  />)
                }
              </>
            )
          })
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
