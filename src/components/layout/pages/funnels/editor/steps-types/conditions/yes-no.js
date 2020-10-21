import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import { CONDITION } from '../constants'
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'yes_no_condition'

const stepAtts = {

  type: STEP_TYPE,

  group: CONDITION,

  name: 'Yes/No',

  icon: <LocalOfferIcon/>,

  view: ({data, meta, stats}) => {},
  edit: ({data, meta, stats}) => {},
}

registerStepType( STEP_TYPE, stepAtts );