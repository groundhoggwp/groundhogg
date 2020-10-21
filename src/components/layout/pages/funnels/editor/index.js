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
function assignLevels (startNodes, allNodes) {

  startNodes.forEach(node => node.level = 0)
  const queue = startNodes

  while (queue.length) {
    let currentNode = queue.shift()

    // Get the child nodes
    let childNodes = allNodes.filter(
      node => currentNode.data.child_steps.includes(node.ID))

    // queue up the child nodes
    childNodes.forEach((node) => {
      node.level = currentNode.level + 1
      queue.push(node)
    })
  }
}

export default (props) => {

  const { funnel } = props
  const { ID, data, steps } = funnel

  if (!steps) {
    return '...loading'
  }

  const startingSteps = steps.filter(
    step => step.data.parent_steps.length === 0)

  assignLevels( startingSteps, steps );

  const levels = [ ... new Set( steps.map( step => step.level ) ) ].sort( (a, b) => {
    return a - b;
  });

  return (
    <>
      <ArcherContainer strokeColor={'#e5e5e5'}>
        {
          steps.length === 0 && (
            <Box display={'flex'} justifyContent={'center'}>
              <Paper style={{ width: 500 }}>
                <BenchmarkPicker/>
              </Paper>
            </Box>
          )
        }
        {
          levels.map((level) => {
            return (
              <Box display={'flex'} justifyContent={'space-around'}>
                {
                  steps.filter( (step) => step.level === level ).map( step => {
                    return (
                      <>
                        <StepBlock {...step}/>
                      </>)
                  } )
                }
              </Box>
            )
          })
        }
        {steps.length > 0 &&
        <Box display={'flex'} justifyContent={'space-around'}>
          <ArcherElement id={'exit'}>
            <Card>
              {'Exit Funnel!'}
            </Card>
          </ArcherElement>
        </Box>}
      </ArcherContainer>
    </>
  )

}