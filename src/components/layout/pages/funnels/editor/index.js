import Box from '@material-ui/core/Box'
import { FUNNELS_STORE_NAME } from 'data'
import BenchmarkPicker from './components/Pickers/BenchmarkPicker'
import StepBlock from './components/StepBlock'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { withSelect } from '@wordpress/data'
import dagre from 'dagre'
import StepEdges from './components/StepEdges'
import StepTargets from './components/StepTargets'
import {
  ACTION,
  BENCHMARK,
  CONDITION, EXIT,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import { useLayoutEffect, useRef, useState } from '@wordpress/element'
import { numChildren } from 'components/layout/pages/funnels/editor/functions'

export const NODE_HEIGHT = 136
export const NODE_WIDTH = 250

const NODE_SEP = 100
const RANK_SEP = 150

/**
 * Breadth first search of the steps tree to build iout a row level based chart
 * for putting the steps on the page.
 *
 * @param steps
 * @param edges
 */
function buildGraph (steps, edges) {

  const graph = new dagre.graphlib.Graph()

  graph.setGraph({
    nodesep: NODE_SEP,
    ranksep: RANK_SEP,
  })

  graph.setDefaultEdgeLabel(() => { return {} })

  steps.forEach(step => {
    graph.setNode(step.ID,
      { label: step.ID, width: NODE_WIDTH, height: NODE_HEIGHT, ...step })
  })

  graph.setNode(EXIT,
    { label: EXIT, width: NODE_WIDTH, height: NODE_HEIGHT, data: {}, ID: EXIT })

  edges.forEach(edge => {
    graph.setEdge(parseInt(edge.from_id), parseInt(edge.to_id))
  })

  graph.nodes().forEach(node => {
    if (numChildren(node, graph) === 0) {
      graph.setEdge(node, EXIT)
    }
  })

  return graph
}

const FirstStepPicker = ({ funnel }) => {
  return (
    <Box display={ 'flex' } justifyContent={ 'center' }>
      <Paper style={ { width: 500 } }>
        <BenchmarkPicker funnelID={ funnel.ID }/>
      </Paper>
    </Box>
  )
}

const Editor = ({ funnel }) => {

  if (!funnel || !funnel.steps || !funnel.edges) {
    return <FirstStepPicker funnel={ funnel }/>

  }

  const [dimensions, setDimensions] = useState({ width: 0, height: 0 })
  const targetRef = useRef()
  useLayoutEffect(() => {
    if (targetRef.current) {
      const updateDimensions = () => {
        setDimensions({
          width: targetRef.current.offsetWidth,
          height: targetRef.current.offsetHeight,
        })
      }

      window.addEventListener('resize', updateDimensions)
      updateDimensions()
      return () => window.removeEventListener('resize', updateDimensions)
    }
  }, [])

  let windowMidPoint = dimensions.width / 2

  const { steps, edges } = funnel

  const graph = buildGraph(steps, edges)

  dagre.layout(graph)

  let xOffset = windowMidPoint - graph.node('exit').x - ( NODE_WIDTH / 2 )
  // let xOffset = 0

  return (
    <>
      <div ref={ targetRef } style={ {
        position: 'relative',
        height: dimensions.height,
      } }>
        {
          steps.length === 0 && (
            <FirstStepPicker funnel={ funnel }/>
          )
        }
        {
          steps.map(step => {
            return (
              <>
                <StepBlock
                  { ...step }
                  graph={ graph }
                  xOffset={ xOffset }
                />
              </>
            )
          })
        }
        {
          steps.map(step => {
            return (
              <>
                <StepTargets
                  { ...step }
                  graph={ graph }
                  xOffset={ xOffset }
                />
              </>
            )
          })
        }
        {
          steps.map(step => {
            return (
              <>
                <StepEdges
                  { ...step }
                  graph={ graph }
                  xOffset={ xOffset }
                />
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
