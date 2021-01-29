import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'

import { useState } from '@wordpress/element'
import AddStepButton from '../AddStepButton'

import makeStyles from '@material-ui/core/styles/makeStyles'

import {
  ACTION, ACTIONS,
  BENCHMARK, BENCHMARKS, CARD_WIDTH, CONDITION, CONDITIONS,

} from 'components/layout/pages/funnels/editor/steps-types/constants'

const useStyles = makeStyles((theme) => ( {
  stepBlockContainer: {},
  stepBlock: {
    position: 'relative',
  },
  addStepButtonTop: {
    position: 'absolute',
    margin: 'auto',
    top: -theme.spacing(8),
    left: 0,
    bottom: 'auto',
    right: 0,
  },
  addStepButtonRight: {
    position: 'absolute',
    margin: 'auto',
    right: -theme.spacing(8),
    top: 0,
    left: 'auto',
    bottom: 0,
  },
  addStepButtonBottom: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(8),
    top: 'auto',
    left: 0,
    right: 0,
  },
} ))

export default (props) => {

  const { endingSteps, funnelId, graph, xOffset } = props
  const [anchorEl, setAnchorEl] = useState(null)
  const [addingStep, setAddingStep] = useState(null)

  const classNames = useStyles()

  const addStepBlock = (where, e) => {
    setAddingStep(where)
    setAnchorEl(e.currentTarget)
  }

  const addStepBlockCancel = () => {
    setAnchorEl(null)
    setAddingStep(null)
  }

  const position = {
    position: 'absolute',
    top: graph.node('exit').y,
    left: graph.node('exit').x + xOffset,
  }

  return (
    <>
      <Box className={ classNames.stepBlockContainer } style={ position }>
        <Box className={ classNames.stepBlock }>
          { ( endingSteps.length > 1 || endingSteps[0].data.step_group ===
            CONDITION ) &&
          <AddStepButton
            id={ 'add-step-top-exit' }
            funnelID={ funnelId }
            position={ 'topMiddle' }
            parentSteps={ endingSteps.map(step => step.ID) }
            showGroups={ [
              BENCHMARKS,
              ACTIONS,
              CONDITIONS,
            ] }
            anchorEl={ anchorEl }
            open={ addingStep === 'top' }
            setAnchorEl={ setAnchorEl }
            openStepBlock={ (e) => addStepBlock('top', e) }
            closeStepBlock={ addStepBlockCancel }
          />
          }
          <Box display={ 'flex' } justifyContent={ 'center' }>
            <Card style={ { width: CARD_WIDTH } } id={ 'step-exit' }>
              <CardHeader
                title={ 'Exit Funnel' }
              />
            </Card>
          </Box>
        </Box>
      </Box>
    </>
  )
}
