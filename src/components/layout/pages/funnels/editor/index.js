import { Card } from '@material-ui/core'
import Box from '@material-ui/core/Box'
import { FUNNELS_STORE_NAME } from 'data';
import BenchmarkPicker from './components/Pickers/BenchmarkPicker'
import StepBlock from './components/StepBlock'
import ExitFunnel from './components/ExitFunnel'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { ArcherContainer, ArcherElement } from 'react-archer'
import { withSelect } from '@wordpress/data'

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
  let index = 0;

  while (queue.length) {
    let currentNode = queue.shift()

    if ( ! currentNode.index ){
      currentNode.index = index;
    }

    // Get the child nodes
    let childNodes = allNodes.filter(
      node => currentNode.data.child_steps.includes(node.ID))

    // queue up the child nodes
    childNodes.forEach((node) => {
      node.level = currentNode.level + 1
      queue.push(node)
    })

    index++;
  }
}

const Editor = ({ funnel }) => {

  if ( ! funnel ) {
    return null;
  }

  if ( ! funnel.steps ) {
    return null;
  }

  const steps = funnel.steps;

  const startingSteps = steps.filter(
    step => step.data.parent_steps.length === 0)
  const endingSteps = steps.filter(
    step => step.data.child_steps.length === 0)

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
                <BenchmarkPicker funnelID={funnel.ID}/>
              </Paper>
            </Box>
          )
        }
        {
          levels.map((level, l) => {

            const lSteps = steps.filter( (step) => step.level === level ).sort( (a,b) => {
              return a.index - b.index;
            });

            // Check to see if the steps

            return (
              <Box display={'flex'} justifyContent={'space-around'}>
                {
                  lSteps.map( step => {
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
          <ExitFunnel
            funnelId={funnel.ID}
            endingSteps={endingSteps.map( step => step.ID )}
          />
        }
      </ArcherContainer>
    </>
  )
}

export default withSelect( ( select, ownProps ) => {

  const store = select( FUNNELS_STORE_NAME )

  return {
    // ...ownProps,
    funnel: store.getItem( ownProps.id ),
    // isCreating: store.isCreatingStep(),
    // isDeleting: store.isDeletingStep(),
    // isUpdating: store.isUpdatingStep(),
    // isRequesting: store.isItemsRequesting()
  }
} )( Editor );