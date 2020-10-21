import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions'
import { select, useDispatch, useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from 'data/step-type-registry'
import { STEPS_STORE_NAME } from 'data/steps'
import CardContent from '@material-ui/core/CardContent'
import DeleteIcon from '@material-ui/icons/Delete'
import EditIcon from '@material-ui/icons/Edit'
import IconButton from '@material-ui/core/IconButton'
import { useState } from '@wordpress/element'
import StepEditor from '../StepEditor'
import AddStepButton from '../AddStepButton'
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'
import { ArcherElement } from 'react-archer'
import {
  ACTION, ACTIONS,
  BENCHMARK, BENCHMARKS,
  CONDITION, CONDITIONS
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import {
	FUNNELS_STORE_NAME
} from 'data';

const useStyles = makeStyles((theme) => ({
  stepBlockContainer: {
    padding: theme.spacing(12),
    paddingTop: 0
  },
  stepBlock: {
    position: 'relative',
  },
  addStepButtonTop: {
    position: 'absolute',
    margin: 'auto',
    top: - theme.spacing(8),
    left: 0,
    bottom: 'auto',
    right: 0,
  },
  addStepButtonRight: {
    position: 'absolute',
    margin: 'auto',
    right: - theme.spacing(8),
    top: 0,
    left: 'auto',
    bottom: 0
  },
  addStepButtonBottom: {
    position: 'absolute',
    margin: 'auto',
    bottom: - theme.spacing(8),
    top: 'auto',
    left: 0,
    right: 0,
  }
}))

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const classNames = useStyles()

  const { ID, data, meta, funnelID } = props
  const { step_title, step_type, step_group, parent_steps, child_steps, funnel_id } = data
  const stepType = select(STEP_TYPES_STORE_NAME).getType(step_type)

  const { deleteStep, updateStep } = useDispatch(FUNNELS_STORE_NAME)

  if (!stepType) {
    return 'loading...'
  }

  const classes = [
    step_type,
    step_group,
    ID
  ]

  const handleEdit = () => {
    setEditing(true)
  }

  const handleCancel = () => {
    setEditing(false)
  }

  const handleDelete = () => {    
    setDeleting(true)
    deleteStep(ID, funnel_id)
  }

  let childRelations = child_steps.length > 0 ? child_steps.map(stepId => {
    return {
      targetId: 'archer-' + stepId,
      targetAnchor: 'top',
      sourceAnchor: 'bottom'
    }
  }) : [{
    targetId: 'exit',
    targetAnchor: 'top',
    sourceAnchor: 'bottom'
  }]

  return (
    <>
      <Box className={classNames.stepBlockContainer}>
        <Box className={classNames.stepBlock}>
          {parent_steps.length > 1 &&
          <AddStepButton
            funnelID={funnel_id}
            className={classNames.addStepButtonTop}
            parentSteps={parent_steps}
            childSteps={[ID]}
            showGroups={[
              BENCHMARKS,
              ACTIONS
            ]}
          />
          }
          {step_group === BENCHMARK &&
          <AddStepButton
            funnelID={funnel_id}
            className={classNames.addStepButtonRight}
            parentSteps={parent_steps}
            childSteps={child_steps}
            showGroups={[
              BENCHMARKS
            ]}
          />}
          {step_group !== CONDITION &&
          <AddStepButton
            funnelID={funnel_id}
            className={classNames.addStepButtonBottom}
            parentSteps={[ID]}
            childSteps={child_steps}
            showGroups={[
              step_group === ACTION ? BENCHMARKS : false,
              ACTIONS,
              CONDITIONS
            ].filter(item => item !== false)}
          />
          }
          <Box display={'flex'} justifyContent={'center'}>
            <ArcherElement
              id={'archer-' + ID}
              relations={childRelations}
            >
              <Card className={classes.join(' ')} style={{ width: 250 }}>
                <CardHeader
                  avatar={stepType.icon}
                  title={ID}
                  subheader={stepType.name}
                />
                <CardActions>
                  <Tooltip title={'Edit'}>
                    <IconButton
                      color={'primary'}
                      onClick={() => handleEdit()}
                    >
                      <EditIcon/>
                    </IconButton>
                  </Tooltip>
                  <Tooltip title={'Delete'}>
                    <IconButton
                      color={'secondary'}
                      onClick={() => handleDelete()}
                    >
                      <DeleteIcon/>
                    </IconButton>
                  </Tooltip>
                </CardActions>
              </Card>
            </ArcherElement>
          </Box>
        </Box>
      </Box>
    </>
  )
}
