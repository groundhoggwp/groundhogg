import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions'
import DeleteIcon from '@material-ui/icons/Delete'
import EditIcon from '@material-ui/icons/Edit'
import IconButton from '@material-ui/core/IconButton'
import { useState } from '@wordpress/element'
import AddStepButton from '../AddStepButton'
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'
import { ArcherElement } from 'react-archer'
import {
  ACTION, ACTIONS,
  BENCHMARK, BENCHMARKS,
  CONDITION, CONDITIONS,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import {
  FUNNELS_STORE_NAME,
} from 'data'

const useStyles = makeStyles((theme) => ( {
  stepBlockContainer: {
    padding: theme.spacing(12),
    paddingTop: 0,
  },
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

  const { endingSteps, funnelId } = props;
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

  return (
    <>
      <Box className={ classNames.stepBlockContainer }>
        <Box className={ classNames.stepBlock }>
          { endingSteps.length > 1 &&
          <AddStepButton
            funnelID={ funnelId }
            className={ classNames.addStepButtonTop }
            parentSteps={ endingSteps }
            showGroups={ [
              BENCHMARKS,
              ACTIONS,
            ] }
            anchorEl={ anchorEl }
            open={ addingStep === 'top' }
            setAnchorEl={ setAnchorEl }
            openStepBlock={ (e) => addStepBlock('top', e) }
            closeStepBlock={ addStepBlockCancel }
          />
          }
          <Box display={ 'flex' } justifyContent={ 'center' }>
            <ArcherElement
              id={ 'exit' }
            >
              <Card style={ { width: 250 } }>
                <CardHeader
                  title={ 'Exit Funnel' }
                />
              </Card>
            </ArcherElement>
          </Box>
        </Box>
      </Box>
    </>
  )
}
