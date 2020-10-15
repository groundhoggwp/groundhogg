import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import { ACTION } from '../constants'
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'apply_tag'

const stepAtts = {

  type: STEP_TYPE,

  group: ACTION,

  name: 'Apply Tag',

  icon: <LocalOfferIcon/>,

  view: ({data, meta, stats}) => {},
  edit: ({data, meta, stats}) => {},
}

registerStepType( STEP_TYPE, stepAtts );