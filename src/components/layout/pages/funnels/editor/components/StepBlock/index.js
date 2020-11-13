import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions'
import { select, useDispatch, useSelect } from '@wordpress/data'
import { getStepType, STEP_TYPES_STORE_NAME } from 'data/step-type-registry'
import DeleteIcon from '@material-ui/icons/Delete'
import EditIcon from '@material-ui/icons/Edit'
import IconButton from '@material-ui/core/IconButton'
import { useState } from '@wordpress/element'
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'
import {
  FUNNELS_STORE_NAME
} from 'data'
import { CARD_WIDTH } from 'components/layout/pages/funnels/editor/steps-types/constants'

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

export default (props) => {

  const [editing, setEditing] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const classNames = useStyles()

  const { ID, data, meta, funnelID, level, index, graph, xOffset } = props
  const { step_title, step_type, step_group, funnel_id } = data

  const StepType = getStepType( step_type );

  const { deleteStep, updateStep } = useDispatch(FUNNELS_STORE_NAME)

  const classes = [
    step_type,
    step_group,
    ID
  ]

  const handleEdit = () => {
    // openStepBlock();
  }

  const handleDelete = () => {
    setDeleting(true)
    deleteStep(ID, funnel_id)
  }

  let thisNode =  graph.node(ID);

  const positioning = {
    top: thisNode && thisNode.y,
    left: thisNode && thisNode.x + xOffset
  }

  return (
    <>
      <Box className={classNames.stepBlockContainer} style={positioning}>
        <Box className={classNames.stepBlock}>
          <Card className={classes.join(' ') + ' ' + classNames.stepCard}
                style={{ width: CARD_WIDTH }}
                id={'step-card-' + ID}>
            <CardHeader
              avatar={StepType.icon}
              title={ID}
              subheader={StepType.name}
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
