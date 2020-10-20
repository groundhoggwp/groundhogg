import { Card } from '@material-ui/core'
import Box from '@material-ui/core/Box'
import LineTo from 'react-lineto'
import { useDispatch, useSelect } from '@wordpress/data'
import { STEPS_STORE_NAME } from 'data/steps'
import BenchmarkPicker from './components/BenchmarkPicker'
import AddStepButton from './components/AddStepButton'
import StepBlock from './components/StepBlock'
import Paper from '@material-ui/core/Paper'
import './steps-types'
import { ArcherContainer } from 'react-archer'

const ChartLine = ({ from, to }) => {
  return (
    <LineTo
      from={ from + '-out' }
      to={ to + '-in' }
    />
  )
}

/**
 * Breadth first search of the steps tree to build iout a row level based chart
 * for putting the steps on the page.
 *
 * @param startNodes
 * @param allNodes
 */
function buildChart( startNodes, allNodes ){

  let currentLevel = 0;
  startNodes.forEach( node => node.level = currentLevel );
  const chart = [[]];
  const queue = startNodes;

  console.log( queue );

  while ( queue.length ){
    let currentNode = queue.shift();

    // Increase the level and add an array to the chart
    if ( currentNode.level > currentLevel ){
      currentLevel++;
      chart.push([])
    }

    // Only if the node is not already part of the chart and is not queued up for later
    if ( ! chart[currentLevel].find( node => node.ID === currentNode.ID ) && ! queue.find( node => node.ID === currentNode.ID ) ){
      chart[currentLevel].push( currentNode );
    }

    // Get the child nodes
    let childNodes = allNodes.filter( node => currentNode.data.child_steps.includes( node.ID ) );

    // queue up the child nodes
    childNodes.forEach( ( node ) => {
      node.level = currentLevel + 1;
      queue.push( node )
    })
  }

  return chart;
}

export default (props) => {

  const { funnel } = props
  const { ID, data } = funnel

  const stepQuery = {
    where: {
      funnel_id: ID,
    },
    limit: 999
  };

  const { fetchItems } = useDispatch( STEPS_STORE_NAME )
  const { steps } = useSelect((select) => {

    const store = select(STEPS_STORE_NAME)

    return {
      steps: store.getItems(stepQuery),
    }

  }, [])

  if ( ! steps ){
    return '...loading';
  }

  const startingSteps = steps.filter( step => step.data.parent_steps.length === 0 );

  const chart = buildChart( startingSteps, steps );

  console.log( chart );

  return (
    <>
      <ArcherContainer strokeColor={'#e5e5e5'}>
        {
          chart[0].length === 0 && (
            <Box display={ 'flex' } justifyContent={ 'center' }>
              <Paper style={{width:500}}>
                <BenchmarkPicker/>
              </Paper>
            </Box>
          )
        }
        {
          chart.map(__steps => {
            return (
              <Box display={ 'flex' } justifyContent={ 'space-around' }>
                {
                  __steps.map(_step => <StepBlock { ..._step }/>)
                }
              </Box>
            )
          })
        }
      </ArcherContainer>
    </>
  )

}