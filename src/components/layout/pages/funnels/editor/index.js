import { Card } from '@material-ui/core'
import Box from '@material-ui/core/Box'
import LineTo from 'react-lineto'
import BenchmarkPicker from './components/BenchmarkPicker'
import StepBlock from './components/StepBlock'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { ArcherContainer, ArcherElement } from 'react-archer'

/**
 * Breadth first search of the steps tree to build iout a row level based chart
 * for putting the steps on the page.
 *
 * @param startNodes
 * @param allNodes
 */
function buildChart (startNodes, allNodes) {

  let currentLevel = 0
  startNodes.forEach(node => node.level = currentLevel)
  let chart = [[]]
  const queue = startNodes

  while (queue.length) {
    let currentNode = queue.shift()

    // Increase the level and add an array to the chart
    if (currentNode.level > currentLevel) {
      currentLevel++
      chart.push([])
    }

    // Only if the node is not already part of the chart and is not queued up
    // for later
    if (!chart[currentLevel].find(node => node.ID === currentNode.ID) &&
      !queue.find(node => node.ID === currentNode.ID)) {
      chart[currentLevel].push(currentNode)
    }

    // Get the child nodes
    let childNodes = allNodes.filter(
      node => currentNode.data.child_steps.includes(node.ID))

    // queue up the child nodes
    childNodes.forEach((node) => {
      if (!queue.find(node => node.ID === currentNode.ID)) {
        node.level = currentLevel + 1
        queue.push(node)
      }
    })
  }

  let visited = []

  // go back thru the chart and remove duplicate nodes from higher orders
  chart = chart.reverse().map(level => {

    // Check to see if the node was visited, filter the level if it was
    level = level.filter(node => !visited.find(_node => node.ID === _node.ID))
    // Mark all the nodes of the level as visited
    level.forEach(node => visited.push(node))

    return level
  })

  chart = chart.reverse()

  return chart
}

export default (props) => {

  const { funnel } = props
  const { ID, data, steps } = funnel

  if (!steps) {
    return '...loading'
  }

  const startingSteps = steps.filter(
    step => step.data.parent_steps.length === 0)

  const chart = buildChart(startingSteps, steps)

  return (
    <>
      <ArcherContainer strokeColor={ '#e5e5e5' }>
        {
          chart[0].length === 0 && (
            <Box display={ 'flex' } justifyContent={ 'center' }>
              <Paper style={ { width: 500 } }>
                <BenchmarkPicker/>
              </Paper>
            </Box>
          )
        }
        {
          chart.map((levels, l) => {
            return (
              <Box display={ 'flex' } justifyContent={ 'space-around' }>
                {
                  levels.map((step, s) => {
                      return (
                        <>
                          <StepBlock { ...step }/>
                        </> )
                    },
                  )
                }
              </Box>
            )
          })
        }
        { chart[0].length > 0 &&
        <Box display={ 'flex' } justifyContent={ 'space-around' }>
          <ArcherElement id={ 'exit' }>
            <Card>
              { 'Exit Funnel!' }
            </Card>
          </ArcherElement>
        </Box> }
      </ArcherContainer>
    </>
  )

}