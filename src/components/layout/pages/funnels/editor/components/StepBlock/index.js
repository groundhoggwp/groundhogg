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
import theme from 'components/layout/theme'
import { NODE_HEIGHT, NODE_WIDTH } from 'components/layout/pages/funnels/editor'

const useStyles = makeStyles((theme) => ({
  stepBlockContainer: {
    paddingTop: 0,
    zIndex: 1,
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

const CONDITION_ADD_STEP_OFFSET = 45
export const CARD_WIDTH = 250;

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [anchorEl, setAnchorEl] = useState(null)
  const [addingStep, setAddingStep] = useState(null)

  const classNames = useStyles()

  const { ID, data, meta, funnelID, level, index, graph, xOffset } = props
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

  let gNode =  graph.node(ID);

  const positioning = {
    top: gNode && gNode.y,
    left: gNode && gNode.x + xOffset
  }

  let yesPosY, yesPosX, noPosY, noPosX

  if ( step_group === CONDITION ){

    let yesNode = graph.node( child_steps.yes || 'exit' );
    let noNode = graph.node( child_steps.no || 'exit' );

    yesPosY = noPosY = graph.node(ID).y + (NODE_HEIGHT / 2) + CONDITION_ADD_STEP_OFFSET;

    if ( yesNode.x === noNode.x ){
      // case 1: yes/no are the same node
      yesPosX = gNode.x - CONDITION_ADD_STEP_OFFSET;
      noPosX = gNode.x + NODE_WIDTH - CONDITION_ADD_STEP_OFFSET;

    } else if ( yesNode.x === gNode.x && noNode.x !== gNode.x ){
      // case 2: yes is 2 levels down, no is 1 level down
      noPosX = noNode.x + (NODE_WIDTH / 2) - CONDITION_ADD_STEP_OFFSET;
      yesPosX = gNode.x - CONDITION_ADD_STEP_OFFSET;

    } else if ( noNode.x === gNode.x && yesNode.x !== gNode.x ){
      // case 3: no is 2 levels down, yes is 1 level down
      yesPosX = yesNode.x + (NODE_WIDTH / 2) - CONDITION_ADD_STEP_OFFSET;
      noPosX = gNode.x - CONDITION_ADD_STEP_OFFSET;

    } else {
      // cas3 4: yes, no are different and are both down 1 level
      noPosX = noNode.x + (NODE_WIDTH / 2) - CONDITION_ADD_STEP_OFFSET;
      yesPosX = yesNode.x + (NODE_WIDTH / 2) - CONDITION_ADD_STEP_OFFSET;

    }

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
          <Card className={classes.join(' ') + ' ' + classNames.stepCard}
                style={{ width: CARD_WIDTH }}
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
      {step_group === CONDITION && (
        <>
          <AddStepButton
            toolTipTitle={'No'}
            id={'add-step-no-' + ID}
            funnelID={funnel_id}
            position={{
              position: 'absolute',
              top: noPosY,
              left: noPosX + xOffset
            }}
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
            position={{
              position: 'absolute',
              top: yesPosY,
              left: yesPosX + xOffset
            }}
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
    </>
  )
}
