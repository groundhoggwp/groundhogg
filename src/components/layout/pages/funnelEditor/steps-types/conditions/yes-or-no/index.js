import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import {
  CONDITION,
} from '../../constants'
import { registerStepType } from 'data/step-type-registry'
import { makeStyles } from '@material-ui/core/styles'


const STEP_TYPE = 'yes_no_condition'

const useStyles = makeStyles((theme) => ( {
  edgeLabel: {
    background: '#ffffff',
    padding: theme.spacing(1),
    border: '1px solid',
    borderRadius: 3,
  },
  edgeNo: {
    background: '#F8D7DA',
    borderColor: '#f5c6cb',
    color: '#721c24',
  },
  edgeYes: {
    background: '#d4edda',
    borderColor: '#c3e6cb',
    color: '#155724',
  },
} ))

const stepAtts = {

  type: STEP_TYPE,

  group: CONDITION,

  name: 'Yes/No',

  icon: <LocalOfferIcon/>,

  view: ({ data, meta, stats }) => {
    return <></>
  },

  edit: ({ data, meta, stats }) => {
    return <></>
  },
}

registerStepType(STEP_TYPE, stepAtts)