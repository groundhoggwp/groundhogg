import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions'
import { select, useDispatch, useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from 'data/step-type-registry'
import DeleteIcon from '@material-ui/icons/Delete'
import EditIcon from '@material-ui/icons/Edit'
import IconButton from '@material-ui/core/IconButton'
import { useState } from '@wordpress/element'
import AddStepButton from '../AddStepButton'
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'
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
    position: 'absolute',
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
  addStepButtonConditionRight: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(8),
    top: 'auto',
    left: 0,
  },
  addStepButtonConditionLeft: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(8),
    top: 'auto',
    right: 0,
  },
} ))

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [anchorEl, setAnchorEl] = useState(null)
  const [addingStep, setAddingStep] = useState(null)

  const classNames = useStyles()

  const { ID, data, meta, funnelID, level, index, xPos, yPos } = props
  const { step_title, step_type, step_group, parent_steps, child_steps, funnel_id } = data
  const { yes_children, no_children } = meta;
  const stepType = select(STEP_TYPES_STORE_NAME).getType(step_type)

  const { deleteStep, updateStep } = useDispatch(FUNNELS_STORE_NAME)

  // output ghost node...
  if (!stepType) {
    return <div>boo!</div>
  }

  const classes = [
    step_type,
    step_group,
    ID,
  ]

  const handleEdit = () => {
    // openStepBlock();
  }

  const handleCancelAdd = () => {
    // closeStepBlock();
  }

  const handleDelete = () => {
    setDeleting(true)
    deleteStep(ID, funnel_id)
  }

  const addStepBlock = (where, e) => {
    setAddingStep(where)
    setAnchorEl(e.currentTarget)
  }

  const addStepBlockCancel = () => {
    setAnchorEl(null)
    setAddingStep(null)
  }

  const positioning = {
    top: yPos,
    left: xPos,
  }

  return (
    <>
      <Box className={ classNames.stepBlockContainer } style={ positioning }>
        <Box className={ classNames.stepBlock }>
          { parent_steps.length > 1 &&
          <AddStepButton
            funnelID={ funnel_id }
            className={ classNames.addStepButtonTop }
            parentSteps={ parent_steps }
            childSteps={ [ID] }
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
          { step_group === BENCHMARK && parent_steps.length < 2 &&
          <AddStepButton
            funnelID={ funnel_id }
            className={ classNames.addStepButtonRight }
            childSteps={ child_steps }
            parentSteps={ parent_steps }
            showGroups={ [
              BENCHMARKS,
            ] }
            anchorEl={ anchorEl }
            open={ addingStep === 'right' }
            setAnchorEl={ setAnchorEl }
            openStepBlock={ (e) => addStepBlock('right', e) }
            closeStepBlock={ addStepBlockCancel }
          /> }
          { step_group !== CONDITION &&
          <AddStepButton
            funnelID={ funnel_id }
            className={ classNames.addStepButtonBottom }
            parentSteps={ [ID] }
            childSteps={ child_steps }
            showGroups={ [
              step_group === ACTION ? BENCHMARKS : false,
              ACTIONS,
              CONDITIONS,
            ].filter(item => item !== false) }
            open={ addingStep === 'bottom' }
            anchorEl={ anchorEl }
            setAnchorEl={ setAnchorEl }
            openStepBlock={ (e) => addStepBlock('bottom', e) }
            closeStepBlock={ addStepBlockCancel }
          />
          }
          { step_group === CONDITION && (
            <>
              <AddStepButton
                funnelID={ funnel_id }
                className={ classNames.addStepButtonConditionRight }
                parentSteps={ [ID] }
                childSteps={ no_children || child_steps }
                showGroups={ [
                  ACTIONS,
                  CONDITIONS,
                ].filter(item => item !== false) }
                open={ addingStep === 'bottom' }
                anchorEl={ anchorEl }
                setAnchorEl={ setAnchorEl }
                openStepBlock={ (e) => addStepBlock('bottom', e) }
                closeStepBlock={ addStepBlockCancel }
                conditionPath={'yes'}
              />
              <AddStepButton
                funnelID={ funnel_id }
                className={ classNames.addStepButtonConditionLeft }
                parentSteps={ [ID] }
                childSteps={ yes_children || child_steps }
                showGroups={ [
                  ACTIONS,
                  CONDITIONS,
                ].filter(item => item !== false) }
                open={ addingStep === 'bottom' }
                anchorEl={ anchorEl }
                setAnchorEl={ setAnchorEl }
                openStepBlock={ (e) => addStepBlock('bottom', e) }
                closeStepBlock={ addStepBlockCancel }
                conditionPath={'no'}
              />
            </> )
          }
          <Box display={ 'flex' } justifyContent={ 'center' }>
            <Card className={ classes.join(' ') } style={ { width: 250 } }
                  id={ 'step-' + ID }>
              <CardHeader
                avatar={ stepType.icon }
                title={ ID }
                subheader={ stepType.name }
              />
              <CardActions>
                <Tooltip title={ 'Edit' }>
                  <IconButton
                    color={ 'primary' }
                    onClick={ handleEdit }
                  >
                    <EditIcon/>
                  </IconButton>
                </Tooltip>
                <Tooltip title={ 'Delete' }>
                  <IconButton
                    color={ 'secondary' }
                    onClick={ handleDelete }
                  >
                    <DeleteIcon/>
                  </IconButton>
                </Tooltip>
              </CardActions>
            </Card>
          </Box>
        </Box>
      </Box>
    </>
  )
}
