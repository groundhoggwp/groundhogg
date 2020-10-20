import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions';
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

const useStyles = makeStyles((theme) => ({
  addStepButton: {
    padding: theme.spacing(3)
  }
}))

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const classNames = useStyles()

  const { ID, data, meta } = props
  const { step_title, step_type, step_group, parent_steps, child_steps } = data
  const stepType = select( STEP_TYPES_STORE_NAME ).getType( step_type );

  const { deleteItem, updateItem } = useDispatch(STEPS_STORE_NAME)

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
    deleteItem(ID)
  }

  return (
    <>
      <Box>
        {parent_steps.length > 1 &&
        <Box display={'flex'} justifyContent={'center'} className={classNames.addStepButton}>
          <AddStepButton
            parentSteps={parent_steps}
            childSteps={[ID]}
          />
        </Box>}
        <Box>
          <ArcherElement
            id={'archer-' + ID}
            relations={data.child_steps.map( stepId => {
              return {
                targetId: 'archer-' + stepId,
                targetAnchor: 'top',
                sourceAnchor: 'bottom',
              }
            })}
          >
            <Card className={classes.join(' ')} style={{ width: 250 }}>
              <CardHeader
                avatar={stepType.icon}
                title={step_title}
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
        { step_group !== 'condition' &&
        <Box display={'flex'} justifyContent={'center'} className={classNames.addStepButton}>
          <AddStepButton
            parentSteps={[ID]}
            childSteps={child_steps}
          />
        </Box>}
      </Box>
    </>
  )
}