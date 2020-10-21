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
  CONDITION, CONDITIONS,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import { FUNNELS_STORE_NAME } from 'data/funnels'

const useStyles = makeStyles((theme) => ( {
  addStepButton: {
    padding: theme.spacing(3),
  },
} ))

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const classNames = useStyles()

  const { ID, data, meta, funnelID } = props
  const { step_title, step_type, step_group, parent_steps, child_steps } = data
  const stepType = select(STEP_TYPES_STORE_NAME).getType(step_type)

  const { deleteStep, updateStep } = useDispatch(FUNNELS_STORE_NAME)

  if (!stepType) {
    return 'loading...'
  }

  const classes = [
    step_type,
    step_group,
    ID,
  ]

  const handleEdit = () => {
    setEditing(true)
  }

  const handleCancel = () => {
    setEditing(false)
  }

  const handleDelete = () => {
    setDeleting(true)
    deleteStep( ID )
  }

  let childRelations = child_steps.length > 0 ? child_steps.map(stepId => {
    return {
      targetId: 'archer-' + stepId,
      targetAnchor: 'top',
      sourceAnchor: 'bottom',
    }
  } ) : [{
    targetId: 'exit',
    targetAnchor: 'top',
    sourceAnchor: 'bottom',
  } ];

  return (
    <>
      <Box>
        { parent_steps.length > 1 &&
        <Box display={ 'flex' } justifyContent={ 'center' }
             className={ classNames.addStepButton }>
          <AddStepButton
           funnelID={funnelID}
            parentSteps={ parent_steps }
            childSteps={ [ID] }
            showGroups={[
              BENCHMARKS,
              ACTIONS
            ]}
          />
        </Box> }
        <Box display={ 'flex' } justifyContent={ 'center' }>
          <ArcherElement
            id={ 'archer-' + ID }
            relations={childRelations}
          >
            <Card className={ classes.join(' ') } style={ { width: 250 } }>
              <CardHeader
                avatar={ stepType.icon }
                title={ step_title }
                subheader={ stepType.name }
              />
              <CardActions>
                <Tooltip title={ 'Edit' }>
                  <IconButton
                    color={ 'primary' }
                    onClick={ () => handleEdit() }
                  >
                    <EditIcon/>
                  </IconButton>
                </Tooltip>
                <Tooltip title={ 'Delete' }>
                  <IconButton
                    color={ 'secondary' }
                    onClick={ () => handleDelete() }
                  >
                    <DeleteIcon/>
                  </IconButton>
                </Tooltip>
              </CardActions>
            </Card>
          </ArcherElement>
          { step_group === BENCHMARK &&
          <Box display={'flex'} alignItems={'center'} className={ classNames.addStepButton }>
            <AddStepButton
              funnelID={funnelID}
              parentSteps={ parent_steps }
              childSteps={ child_steps }
              showGroups={[
                BENCHMARKS
              ]}
            />
          </Box> }
        </Box>
        { step_group !== CONDITION &&
        <Box display={ 'flex' } justifyContent={ 'center' }
             className={ classNames.addStepButton }>
          <AddStepButton
            funnelID={funnelID}
            parentSteps={ [ID] }
            childSteps={ child_steps }
            showGroups={[
              step_group === ACTION ? BENCHMARKS : false,
              ACTIONS,
              CONDITIONS,
            ].filter( item => item !== false )}
          />
        </Box> }
      </Box>
    </>
  )
}
