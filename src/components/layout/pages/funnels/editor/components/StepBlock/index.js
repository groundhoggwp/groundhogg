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
  CONDITION, CONDITIONS
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import {
  FUNNELS_STORE_NAME
} from 'data'

const useStyles = makeStyles((theme) => ({
  stepBlockContainer: {
    padding: theme.spacing(12),
    paddingTop: 0,
    position: 'absolute',
    '& .MuiFab-root': {
      zIndex: 99
    }
  },
  stepBlock: {
    position: 'relative'
  },
  stepCard: {
    '&.benchmark': {
      '& .MuiCardHeader-root': {
        backgroundColor: '#DB741A'
      }
    },
    '&.action': {
      '& .MuiCardHeader-root': {
        backgroundColor: '#58AB7E'
      }
    },
    '&.condition': {
      '& .MuiCardHeader-root': {
        backgroundColor: '#48639C'
      }
    }
  }
}))

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [anchorEl, setAnchorEl] = useState(null)
  const [addingStep, setAddingStep] = useState(null)

  const classNames = useStyles()

  const { ID, data, meta, funnelID, level, index, graph } = props
  const { step_title, step_type, step_group, parent_steps, child_steps, funnel_id } = data
  const { yes_steps, no_steps } = meta

  let numParents = Object.values(parent_steps).length
  let numChildren = Object.values(child_steps).length

  const stepType = select(STEP_TYPES_STORE_NAME).getType(step_type)

  const { deleteStep, updateStep } = useDispatch(FUNNELS_STORE_NAME)

  // output ghost node...
  if (!stepType) {
    return <div>boo!</div>
  }

  const classes = [
    step_type,
    step_group,
    ID
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
    top: graph.node(ID).y,
    left: graph.node(ID).x
  }

  return (
    <>
      <Box className={classNames.stepBlockContainer} style={positioning}>
        <Box className={classNames.stepBlock}>
          {numParents > 1 &&
          <AddStepButton
            id={'add-step-top-' + ID}
            funnelID={funnel_id}
            position={'topMiddle'}
            parentSteps={parent_steps}
            childSteps={[ID]}
            showGroups={[
              BENCHMARKS,
              ACTIONS
            ]}
            anchorEl={anchorEl}
            open={addingStep === 'top'}
            setAnchorEl={setAnchorEl}
            openStepBlock={(e) => addStepBlock('top', e)}
            closeStepBlock={addStepBlockCancel}
          />
          }
          {step_group === BENCHMARK && numParents < 2 &&
          <AddStepButton
            id={'add-step-right-' + ID}
            funnelID={funnel_id}
            position={'rightMiddle'}
            childSteps={child_steps}
            parentSteps={parent_steps}
            showGroups={[
              BENCHMARKS
            ]}
            anchorEl={anchorEl}
            open={addingStep === 'right'}
            setAnchorEl={setAnchorEl}
            openStepBlock={(e) => addStepBlock('right', e)}
            closeStepBlock={addStepBlockCancel}
          />}
          {step_group !== CONDITION &&
          <AddStepButton
            id={'add-step-bottom-' + ID}
            funnelID={funnel_id}
            position={'bottomMiddle'}
            parentSteps={[ID]}
            childSteps={child_steps}
            showGroups={[
              step_group === ACTION ? BENCHMARKS : false,
              ACTIONS,
              CONDITIONS
            ].filter(item => item !== false)}
            open={addingStep === 'bottom'}
            anchorEl={anchorEl}
            setAnchorEl={setAnchorEl}
            openStepBlock={(e) => addStepBlock('bottom', e)}
            closeStepBlock={addStepBlockCancel}
          />
          }
          {step_group === CONDITION && (
            <>
              <AddStepButton
                toolTipTitle={'No'}
                id={'add-step-no-' + ID}
                funnelID={funnel_id}
                position={graph.node(child_steps.no || 'exit').x >= graph.node(child_steps.yes || 'exit').x ? 'bottomRight' : 'bottomLeft'}
                parentSteps={[ID]}
                childSteps={[child_steps.no]}
                showGroups={[
                  ACTIONS,
                  CONDITIONS
                ].filter(item => item !== false)}
                open={addingStep === 'no'}
                anchorEl={anchorEl}
                setAnchorEl={setAnchorEl}
                openStepBlock={(e) => addStepBlock('no', e)}
                closeStepBlock={addStepBlockCancel}
                conditionPath={'no'}
              />
              <AddStepButton
                toolTipTitle={'Yes'}
                id={'add-step-yes-' + ID}
                funnelID={funnel_id}
                position={graph.node(child_steps.yes || 'exit').x <= graph.node(child_steps.no || 'exit').x ? 'bottomLeft' : 'bottomRight'}
                parentSteps={[ID]}
                childSteps={[child_steps.yes]}
                showGroups={[
                  ACTIONS,
                  CONDITIONS
                ].filter(item => item !== false)}
                open={addingStep === 'yes'}
                anchorEl={anchorEl}
                setAnchorEl={setAnchorEl}
                openStepBlock={(e) => addStepBlock('yes', e)}
                closeStepBlock={addStepBlockCancel}
                conditionPath={'yes'}
              />
            </>)
          }
          <Card className={classes.join(' ') + ' ' + classNames.stepCard}
                style={{ width: 250 }}
                id={'step-card-' + ID}>
            <CardHeader
              avatar={stepType.icon}
              title={ID}
              subheader={stepType.name}
            />
            <CardActions>
              <Tooltip title={'Edit'}>
                <IconButton
                  color={'primary'}
                  onClick={handleEdit}
                >
                  <EditIcon/>
                </IconButton>
              </Tooltip>
              <Tooltip title={'Delete'}>
                <IconButton
                  color={'secondary'}
                  onClick={handleDelete}
                >
                  <DeleteIcon/>
                </IconButton>
              </Tooltip>
            </CardActions>
          </Card>
        </Box>
      </Box>
    </>
  )
}
